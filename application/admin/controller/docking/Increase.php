<?php

namespace app\admin\controller\docking;

use app\common\controller\Backend;

/**
 * 对接 加价模板
 *
 * @icon fa fa-circle-o
 */
class Increase extends Backend
{

    /**
     * Increase模型对象
     * @var \app\admin\model\docking\Increase
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\docking\Increase;

        $type = [
            [
                'value' => 'follow',
                'name' => '跟随对接站',
            ],
            [
                'value' => 'fixed',
                'name' => '固定金额',
            ],
            [
                'value' => 'percent',
                'name' => '百分比',
            ],
        ];

        $effect = [
            [
                'value' => 1,
                'name' => '对接站价格高于本站销售价格时'
            ],
            [
                'value' => 2,
                'name' => '对接站价格出现变动时',
            ]
        ];

        $this->assign([
            'type' => $type,
            'effect' => $effect
        ]);

    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


}
