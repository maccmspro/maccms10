<?php
namespace app\common\model;
use think\Db;
use think\Cache;
use app\common\util\Pinyin;

class BannerCat extends Base {
    // 设置数据表（不含前缀）
    protected $name = 'banner_cat';

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
        $tmp = Db::name('BannerCat')->where($where)->order($order)->limit($limit_str)->select();

        $list = [];
        foreach($tmp as $k=>$v){
            $list[$v['cat_id']] = $v;
        }

        return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>ceil($total/$limit),'limit'=>$limit,'total'=>$total,'list'=>$list];
    }

    public function infoData($where,$field='*',$cache=0)
    {
        if(empty($where) || !is_array($where)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }
        $key = 'cat_detail_'.$where['cat_id'][1].'_'.$where['cat_en'][1];
        $info = Cache::get($key);

        if($GLOBALS['config']['app']['cache_core']==0 || $cache==0 || empty($info['cat_id']) ) {
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
        $validate = \think\Loader::validate('BannerCat');
        if(!$validate->check($data)){
            return ['code'=>1001,'msg'=>'参数错误：'.$validate->getError() ];
        }
        $key = 'cat_detail_'.$data['cat_id'];
        Cache::rm($key);

        if(!empty($data['cat_id'])){
            $where=[];
            $where['cat_id'] = ['eq',$data['cat_id']];
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

    public function createTableIfNotExists() {
        if ($this->lockTableUpdate(0) === true) {
            return true;
        }
        if (!Db::execute("SHOW TABLES LIKE '". config('database.prefix') . $this->name ."'")) {
            $sql = "CREATE TABLE `". config('database.prefix') . $this->name ."` (
                `cat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `cat_title` varchar(50) NOT NULL,
                `cat_code` varchar(20) NOT NULL,
                `cat_type` int(11) NOT NULL DEFAULT '0',
                PRIMARY KEY (`cat_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 COMMENT='广告分类表';";
            Db::execute($sql);
        }
    }
}
