<?php

namespace app\admin\controller\docking;

use app\common\controller\Backend;
use think\Cache;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 对接站点管理
 *
 * @icon fa fa-circle-o
 */
class DockingSite extends Backend {

    /**
     * DockingSite模型对象
     * @var \app\admin\model\docking\DockingSite
     */
    protected $model = null;

    public function _initialize() {
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

    public function import(){
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
    public function sync(){
        $site_id = $this->request->param('site_id'); //对接站id
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

                    if($params['price'] < $params['buy_price']){
                        throw new Exception('价格不能低于进货价');
                    }

                    $where = [
                        'site_id' => $params['site_id'],
                        'remote_id' => $params['remote_id']
                    ];
                    $goods = db::name('goods')->where($where)->find();
                    if($goods){
                        throw new Exception('您不能重复对接该商品');
                    }
                    $params['stock'] = -1; //该库存代表对接站没有库存字段，则显示正常字样
                    $params['createtime'] = time();

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

        $list = $this->get_goods_list($site_id); //获取对接站所有商品

        //根据对接站商品id获取商品详情
        $key = array_search($goods_id, array_column($list, 'id'));
        $goods = $list[$key];

        //获取加价模板列表
        $increase = db::name('docking_increase')->select();

        //对接站信息
        $docking_site = db::name('docking_site')->where(['id' => $site_id])->find();

        $this->assign([
            'goods' => $goods,
            'increase' => $increase,
            'docking_site' => $docking_site,

        ]);

//        echo '<pre>'; print_r($goods);die;

        return view();
    }

    /**
     * 通过对接站id获取商品列表
    */
    public function get_goods_list($id){
        $site = db::name('docking_site')->where(['id' => $id])->find();
        $info = json_decode($site['info'], true);

        $domain = $site['domain'];

        if(Cache::has('goods_list_' . $domain)){
            $list = Cache::get('goods_list_' . $domain);
        }else{
            if($site['type'] == 'jiuwu'){
                $url = $domain . "index.php?m=home&c=api&a=get_goods_lists";
                $account = $info['account'];
                $password = md5($info['password']);
                $url = $domain . 'index.php?m=home&c=api&a=user_get_goods_lists_details&Api_UserName=' . $account . '&Api_UserMd5Pass=' . $password;
                $result = file_get_contents($url);
                $result = json_decode($result, true);
                $list = $result['user_goods_lists_details'];
                $list = $this->handle_list_wujiu($list);
                Cache::set('goods_list_' . $domain, $list);
            }
        }
        return $list;
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
            $list = $this->get_goods_list($ids);


            $total = count($list);

            $result = ["total" => $total, "rows" => $list];
            return json($result);
        }

        $this->assign([
            'id' => $ids
        ]);
        return $this->view->fetch();
    }

    public function handle_list_wujiu($list){
        foreach($list as &$val){
            $price = $val['goods_unitprice'];
            $price_info = $this->calc_price($price, 1, $val['minbuynum_0']);
            $val['num'] = $price_info['num'];
            $val['price'] = upDecimal($price_info['price']);
            $look_num = $this->look_num($val['num']);
            $val['look_price'] = $look_num . $val['unit'] . '=' . $val['price'] .'元';
        }

        return $list;
    }

    public function look_num($num){
        if($num == 1000){
            return '1千';
        }else if($num == 10000){
            return '1万';
        }else if($num == 100000){
            return '10万';
        }else{
            return $num;
        }
    }

    /**
     * 计算点数价格
    */
    public function calc_price($price, $num = 1, $min){
        $num *= 10;
        $price *= 10;

        if($price < 0.1 || $num <= $min){
            return $this->calc_price($price, $num, $min);
        }else{
            $num /= 10;
            $price /= 10;
        }
        return [
            'num' => $num,
            'price' => $price
        ];

    }



    /**
     * 添加
     */
    public function add() {
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
    public function edit($ids = null) {
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
        if($row->type == 'jiuwu'){
            $row->account = empty($info['account']) ? '' : $info['account'];
            $row->password = empty($info['password']) ? '' : $info['password'];
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function handle_params($params){
        if($params['type'] == 'jiuwu'){ //玖伍社区
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
