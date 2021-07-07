<?php
namespace app\common\model;
use think\Db;
use think\Cache;
use app\common\util\Pinyin;

class Banner extends Base {
    // 设置数据表（不含前缀）
    protected $name = 'banner';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    protected $autoWriteTimestamp = true;

    // 自动完成
    protected $auto       = [];
    protected $insert     = [];
    protected $update     = [];

    public function countData($where)
    {
        $total = $this->where($where)->count();
        return $total;
    }

    public function listData($where,$order,$page=1,$limit=20,$start=0,$field='*',$totalshow=1)
    {
        if(!is_array($where)){
            $where = json_decode($where,true);
        }
        $limit_str = ($limit * ($page-1) + $start) .",".$limit;
        if($totalshow==1) {
            $total = $this->where($where)->count();
        }
        $tmp = Db::name('Banner')->where($where)->order($order)->limit($limit_str)->select();

        $list = [];
        foreach($tmp as $k=>$v){
            $list[$v['banner_id']] = $v;
        }

        return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>ceil($total/$limit),'limit'=>$limit,'total'=>$total,'list'=>$list];
    }

    public function listData2($where,$order,$type)
    {
        if(!is_array($where)){
            $where = json_decode($where,true);
        }


        if($type == 'all'){
            $tmp = Db::name('Banner')->where($where)->order($order)->select();
        }else{
            $catid = Db::name('BannerCat')->where('cat_code="'.$type.'"')->find();
            //if(!empty($catid))
            $where['banner_cat'] = ['eq',$catid['cat_id']];

            $tmp = Db::name('Banner')->where($where)->order($order)->select();
        }

        $list = [];
        foreach($tmp as $k=>$v){
            $list[$v['banner_id']] = $v;
        }

        return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>ceil($total/$limit),'limit'=>$limit,'total'=>$total,'list'=>$list];
    }

    public function infoData($where,$field='*',$cache=0)
    {
        if(empty($where) || !is_array($where)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }
        $key = 'banner_detail_'.$where['banner_id'][1].'_'.$where['banner_en'][1];
        $info = Cache::get($key);

        if($GLOBALS['config']['app']['cache_core']==0 || $cache==0 || empty($info['banner_id']) ) {
            $info = $this->field($field)->where($where)->find();
            if (empty($info)) {
                return ['code' => 1002, 'msg' => '获取数据失败'];
            }
            $info = $info->toArray();

            if($GLOBALS['config']['app']['cache_core']==1) {
                Cache::set($key, $info);
            }
        }
        return ['code'=>1,'msg'=>'获取成功','info'=>$info];
    }

    public function saveData($data)
    {
        $validate = \think\Loader::validate('Banner');
        if(!$validate->check($data)){
            return ['code'=>1001,'msg'=>'参数错误：'.$validate->getError() ];
        }
        $key = 'banner_detail_'.$data['banner_id'];
        Cache::rm($key);

        if(!empty($data['banner_stime'])){
            $data['banner_stime'] = strtotime($data['banner_stime']);
        }
        else{
            $data['banner_stime'] = strtotime(date('Y-m-d'));
        }

        if(!empty($data['banner_etime'])){
            $data['banner_etime'] = strtotime($data['banner_etime']);
        }
        else{
            $data['banner_etime'] = strtotime(date('Y-m-d').' 23:59:59');
        }
        $data['banner_order'] = (int)$data['banner_order'];

        if(!empty($data['banner_id'])){
            $where=[];
            $where['banner_id'] = ['eq',$data['banner_id']];
            $res = $this->allowField(true)->where($where)->update($data);
        }
        else{
            $res = $this->allowField(true)->insert($data);
        }
        if(false === $res){
            return ['code'=>1002,'msg'=>'保存失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'保存成功'];
    }

    public function listCacheData($lp)
    {
        if (!is_array($lp)) {
            $lp = json_decode($lp, true);
        }

        $order = $lp['order'];
        $by = $lp['by'];
        $type = $lp['type'];
        $start = intval(abs($lp['start']));
        $num = intval(abs($lp['num']));
        $cachetime = $lp['cachetime'];

        $page = 1;
        $where = [];

        if(empty($num)){
            $num = 20;
        }
        if($start>1){
            $start--;
        }
        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }
        if (!in_array($by, ['id', 'order'])) {
            $by = 'id';
        }
        $by = 'banner_'.$by;
        $order = $by . ' ' . $order;
        $where['banner_status'] = 1;
        $times = time();
        $where['banner_stime'] = ['ELT',$times];
        $where['banner_etime'] = ['EGT',$times];

        $cach_name = $GLOBALS['config']['app']['cache_flag']. '_' . md5('banner_listcache_'.join('&',$where).'_'.$order.'_'.$page.'_'.$num.'_'.$start);
        $res = Cache::get($cach_name);
        if($GLOBALS['config']['app']['cache_core']==0 || empty($res)) {
            $res = $this->listData2($where, $order, $type);
            $cache_time = $GLOBALS['config']['app']['cache_time'];
            if(intval($cachetime)>0){
                $cache_time = $cachetime;
            }
            if($GLOBALS['config']['app']['cache_core']==1) {
                Cache::set($cach_name, $res, $cache_time);
            }
        }
        return $res;
    }

    public function createTableIfNotExists() {
        if ($this->lockTableUpdate(1) === true) {
            return true;
        }
        if (!Db::execute("SHOW TABLES LIKE '". config('database.prefix') . $this->name ."'")) {
            $sql = "CREATE TABLE `". config('database.prefix') . $this->name ."` (
                `banner_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `banner_title` varchar(100) DEFAULT NULL,
                `banner_link` varchar(200) DEFAULT NULL,
                `banner_cat` int(11) NOT NULL DEFAULT '0',
                `banner_pic` varchar(200) DEFAULT NULL,
                `banner_stime` bigint DEFAULT NULL,
                `banner_etime` bigint DEFAULT NULL,
                `banner_type` int(11) NOT NULL DEFAULT '0',
                `banner_status` int(11) NOT NULL DEFAULT '0',
                `banner_order` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`banner_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='广告表';";
            Db::execute($sql);
        }
    }
}