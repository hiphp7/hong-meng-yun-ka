<?php

namespace app\admin\controller\docking;

use app\common\controller\Backend;
use fast\Http;
use think\Cache;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use app\common\controller\Dock;
/**
 * 对接站点管理
 *
 * @icon fa fa-circle-o
 */
class DockingSite extends Backend
{

    /**
     * DockingSite模型对象
     * @var \app\admin\model\docking\DockingSite
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\docking\DockingSite;

        $type = [
            [
                'name' => '玖伍社区',
                'value' => 'jiuwu',
            ]

        ];

        $where = [
            'type' => 'goods',
        ];
        $category = db::name('category')->where($where)->select();
        $attach = db::name('attach')->select();
        $this->assign([
            'category' => $category,
            'attach' => $attach
        ]);

        $this->assign('type', $type);

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


    /**
     * 同步商品
     */
    public function sync()
    {
        $site_id = $this->request->param('site_id'); //对接站id
        $site = db::name('docking_site')->where(['id' => $site_id])->find();
        $goods_id = $this->request->param('ids'); //对接站商品id
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $params['site_id'] = $site_id; //对接站点id
                    $params['remote_id'] = $goods_id; //对接站商品id
                    $params['type'] = $site['type'];


                    if($params['num'] < $params['min_buy_num']){
                        throw new Exception("默认数量不能小于最小下单量");
                    }
                    if($params['num'] > $params['max_buy_num']){
                        throw new Exception("默认数量不能大于最大下单量");
                    }

                    if ($params['price'] < $params['buy_price']) {
                        throw new Exception('售价不能低于进货价');
                    }

                    if($params['default_num'] / $params['buy_price'] < $params['num'] / $params['price']){
                        throw new Exception('售价不能低于进货价');
                    }

                    $order_params = unserialize(base64_decode($params['order_params']));

                    $dock_data = [ //对接订单所需数据
                        'order_params' => $order_params, //订单所需参数信息
                        'num' => $params['num'], //默认一单购买数量
                        'goods_type' => $params['goods_type'],
                        'max_int' => intval($params['max_buy_num'] / $params['num']), //一次最多可购买单数
                        'increase_id' => $params['increase_id'], //加价模板
                        'min_buy_num' => $params['min_buy_num'], //商品最小购买数量
                        'max_buy_num' => $params['max_buy_num'],
                    ];
                    $params['dock_data'] = json_encode($dock_data);
                    $params['stock'] = -1; //该库存代表对接站没有库存字段，则显示正常字样
                    $params['createtime'] = time();

                    unset($params['max_buy_num']);
                    unset($params['min_buy_num']);
                    unset($params['goods_type']);
                    unset($params['order_params']);
                    unset($params['num']);
                    unset($params['default_num']);
                    unset($params['increase_id']);

//                    print_r($params);die;

                    $result = db::name('goods')->insert($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $list = Dock::get_goods_list($site_id); //获取对接站所有商品

        //根据对接站商品id获取商品详情
        $key = array_search($goods_id, array_column($list, 'id'));
        $goods = $list[$key];


        $url = "{$site['domain']}index.php?m=home&c=goods&a=detail&id={$goods['id']}&goods_type={$goods['goods_type']}";

        $order_params = Dock::getParams("jiuwu", $url, $site);

        //获取加价模板列表
        $increase = db::name('docking_increase')->select();

        //对接站信息
        $docking_site = db::name('docking_site')->where(['id' => $site_id])->find();



        $this->assign([
            'goods' => $goods,
            'increase' => $increase,
            'docking_site' => $docking_site,
            'order_params' => base64_encode(serialize($order_params)),
        ]);

//        echo '<pre>'; print_r($order_params);die;

        return view();
    }





    /**
     * 商品列表
     */
    public function goods_list(){

        $ids = $this->request->param('ids');

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            $post = $this->request->param();
//            print_r($post);die;
            $list = Dock::get_goods_list($ids);


            $total = count($list);

            $result = ["total" => $total, "rows" => $list];
            return json($result);
        }

        $this->assign([
            'id' => $ids
        ]);
        return $this->view->fetch();
    }








    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $params = $this->handle_params($params);
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $params = $this->handle_params($params);
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $info = json_decode($row->info, true);
        if ($row->type == 'jiuwu') {
            $row->account = empty($info['account']) ? '' : $info['account'];
            $row->password = empty($info['password']) ? '' : $info['password'];
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function handle_params($params)
    {
        if ($params['type'] == 'jiuwu') { //玖伍社区
            $info = [
                'account' => $params['account'],
                'password' => $params['password']
            ];
            unset($params['account']);
            unset($params['password']);
            $params['info'] = json_encode($info);
        }
        return $params;
    }


}
