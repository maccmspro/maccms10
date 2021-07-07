<?php
namespace app\common\validate;
use think\Validate;

class Banner extends Validate
{
    protected $rule =   [
        'banner_title' => 'require|max:30',
        'banner_link'  => 'require|max:200',
        'banner_pic'   => 'max:200',
    ];

    protected $message  =   [
        'banner_title.require' => '名称必须',
        'banner_title.max'     => '名称最多不能超过30个字符'
    ];

    protected $scene = [
        'add'=> ['banner_title','banner_link','banner_cat','banner_pic','banner_stime','banner_etime','banner_type','banner_status','banner_order'],
        'edit'=> ['banner_title','banner_link','banner_cat','banner_pic','banner_stime','banner_etime','banner_type','banner_status','banner_order'],
    ];
}