<?php
namespace app\common\validate;
use think\Validate;

class BannerCat extends Validate
{
    protected $rule =   [
        'cat_title'  => 'require|max:30',
        'cat_code' => 'require|max:20',

    ];

    protected $message  =   [
        'cat_title.require' => '名称必须',
        'cat_title.max'     => '名称最多不能超过30个字符',
        'cat_code.require'   => '英文编码必须'
    ];

    protected $scene = [
        'add'=> ['cat_title','cat_code','cat_type'],
        'edit'=> ['cat_title','cat_code','cat_type'],
    ];
}