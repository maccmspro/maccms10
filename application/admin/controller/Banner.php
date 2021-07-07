<?php
namespace app\admin\controller;
use think\Db;

class Banner extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $param = input();
        $param['page'] = intval($param['page']) <1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) <1 ? $this->_pagesize : $param['limit'];

        $where=[];
        if(in_array($param['status'],['0','1'],true)){
            $where['banner_status'] = ['eq',$param['status']];
        }
        if(!empty($param['wd'])){
            $param['wd'] = urldecode($param['wd']);
            $where['banner_title'] = ['like','%'.$param['wd'].'%'];
        }
        if(!empty($param['cat'])){
            $where['banner_cat'] = ['eq',$param['cat']];
        }

        $order='banner_order asc';
        $res = model('banner')->listData($where,$order,$param['page'],$param['limit']);
        $catlist = model('Banner_Cat')->listData(null,null);
        $cat = [];

        foreach($catlist['list'] as $k=>&$v){
            $cat[$v['cat_id']] = $v;
        }

        $this->assign('list',$res['list']);
        $this->assign('total',$res['total']);
        $this->assign('page',$res['page']);
        $this->assign('limit',$res['limit']);

        $this->assign('cat',$cat);

        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param',$param);
        $this->assign('title','广告管理');
        return $this->fetch('admin@banner/index');
    }

    public function info()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $res = model('Banner')->saveData($param);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }


        $id = input('id');
        $where=[];
        $where['banner_id'] = ['eq',$id];
        $res = model('Banner')->infoData($where);

        $cat = model('Banner_Cat')->listData(null,null);
        $this->assign('cat',$cat['list']);

        $info = $res['info'] ?: [];
        if (empty($info)) {
            // 添加时的初始值
            $info['banner_stime'] = time();
            $info['banner_etime'] = time() + 86400 * 365;
            $info['banner_order'] = 0;
        }
        $this->assign('info',$info);

        $config = config('maccms.site');
        $this->assign('install_dir',$config['install_dir']);

        return $this->fetch('admin@banner/info');
    }

    public function infocat()
    {
        if (Request()->isPost()) {
            $param = input('post.');
            $param['banner_content'] = str_replace( $GLOBALS['config']['upload']['protocol'].':','mac:',$param['banner_content']);
            $res = model('BannerCat')->saveData($param);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }


        $id = input('id');
        $where=[];
        $where['cat_id'] = ['eq',$id];
        $res = model('BannerCat')->where($where)->find();

        $this->assign('info',$res['info']);

        $config = config('maccms.site');
        $this->assign('install_dir',$config['install_dir']);

        return $this->fetch('admin@banner/infocat');
    }

    public function del()
    {
        $param = input();
        $ids = $param['ids'];

        if(!empty($ids)){
            $where=[];
            $where['banner_id'] = ['in',$ids];
            $res = model('banner')->delData($where);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }

    public function field()
    {
        $param = input();
        $ids = $param['ids'];
        $col = $param['col'];
        $val = $param['val'];

        if(!empty($ids) && in_array($col,['banner_status','banner_level']) ){
            $where=[];
            $where['banner_id'] = ['in',$ids];

            $res = model('banner')->fieldData($where,$col,$val);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }

}
