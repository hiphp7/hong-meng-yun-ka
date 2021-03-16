<?php

namespace app\admin\model;

use think\Model;
use traits\model\SoftDelete;

class Goods extends Model
{

    use SoftDelete;



    // 表名

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [

    ];

    public function category(){
        return $this->belongsTo('category', 'category_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    /**
     * goods关联order一对多
     */
    public function order(){
        return $this->hasMany('Order', 'goods_id', 'id')->field('money');
    }








}
