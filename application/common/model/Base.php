<?php
namespace app\common\model;
use think\Model;
use think\Db;
use think\Cache;

class Base extends Model {

    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();
        // 表创建或修改
        if (method_exists($this, 'createTableIfNotExists')) {
            $this->createTableIfNotExists();
        }
    }


    public function delData($where)
    {
        $res = $this->where($where)->delete();
        if($res===false){
            return ['code'=>1001,'msg'=>lang('del_err').'：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>lang('del_ok')];
    }

    /**
     * 锁定表更新（通过缓存）
     * 
     * @param  int $version 
     */
    protected function lockTableUpdate($version) {
        $cache_key = 'tbl:lock:' . config('database.prefix') . $this->name . ':v1:' . $version;
        if (Cache::get($cache_key)) {
            return true;
        }
        Cache::set($cache_key, 'locked', 50 * 365 * 86400);
        return false;
    }

    /**
     * 检查更新字段，确认后执行更新
     */
    protected function checkAndAlterTableField($field, $alter, $is_add = true) {
        $has_field = !empty(Db::execute("DESCRIBE " . config('database.prefix') . $this->name . " `{$field}`"));
        if (($is_add && !$has_field) || (!$is_add && $has_field)) {
            $sql = "ALTER TABLE " . config('database.prefix') . $this->name . " {$alter}";
            Db::execute($sql);
        }
    }
}
