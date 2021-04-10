<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\controller\Hm;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Goods extends Backend {

    protected $searchFields = 'goods.name';

    /**
     * Goods模型对象
     * @var \app\admin\model\Goods
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\Goods;

        $where = [
            'type' => 'goods',
        ];
        $category = db::name('category')->where($where)->select();
        $attach = db::name('attach')->select();
        $this->assign([
            'category' => $category,
            'attach' => $attach
        ]);

    }

    public function import() {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 回收站
     */
    public function recyclebin() {

        $this->searchFields = 'name';
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model->onlyTrashed()->where($where)->order($sort, $order)->paginate($limit);

            $result = ["total" => $list->total(), "rows" => $list->items()];

            return json($result);
        }
        return $this->view->fetch();
    }

	public function stock_add() {
		$id = $this->request->param('ids');
		$goods_info = db::name('goods')->where(['id' => $id])->find();
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
//                    print_r($params);die;
					$kami = $params['kami'];
					if($goods_info['goods_type'] == 3){
					    $kami = explode(',', $kami);
                    }else{
                        $kami = explode("\r\n", $kami);
                    }

					$kami = array_filter($kami); //去除空元素
					$insert = [];
					$timestamp = time();
					foreach($kami as $val){
						$insert[] = [
							'type' => $goods_info['goods_type'],
							'goods_id' => $id,
							'cdk' => $val,
							'createtime' => $timestamp
						];
					}
					db::name('cdkey')->insertAll($insert);
					$kami_num = count($kami);
					if($kami_num > 0){
						db::name('goods')->where(['id' => $id])->setInc('stock', $kami_num);
					}
					$result = true;
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
		$this->assign([
			'goods_info' => $goods_info
		]);
        return $this->view->fetch();
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

					if($params["deliver"] == 0){ //自动发货，默认库存
						unset($params["stock"]);
					}

					$result = $this->model->allowField(true)->save($params);
                    db::name("options")->where(["option_name" => "goods_total"])->setInc("option_content");
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

					$ids = $this->request->param('ids');


					if(isset($params["deliver"]) && $params["deliver"] == 0){ //自动发货 重构库存
						$stock = db::name('cdkey')->where(['goods_id' => $ids])->count();
						$params['stock'] = $stock;
					}
//					echo $row->goods_type;die;
//					print_r($row->toArray());die;
                    if(isset($params['goods_type']) && $row->goods_type != $params['goods_type'] && $stock > 0){ //自营商品类型
                        throw new Exception('商品存在库存时无法修改商品类型');
                    }

                    if($row->type == "jiuwu"){
                        $dock_data = json_decode($row->dock_data, true);
                        $dock_data["increase_id"] = $params["increase_id"];
                        unset($params["increase_id"]);
                        if($params['num'] > $dock_data['max_buy_num']){
                            $this->error("默认数量不能大于{$dock_data['max_buy_num']}");
                        }
                        if($params['num'] < $dock_data['min_buy_num']){
                            $this->error("默认数量不能小于{$dock_data['min_buy_num']}");
                        }
                        $dock_data['max_int'] = intval($dock_data["max_buy_num"] / $params["num"]);
                        $dock_data['num'] = $params["num"];
                        unset($params["num"]);
                        $params["dock_data"] = json_encode($dock_data);
//                        print_r($dock_data);
                    }

//                    print_r($params);die;

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
//
        $row = Hm::handle_goods($row->toArray());
        $row['images'] = implode(',', $row['images']);
//        echo '<pre>'; print_r($row);die;
        if($row['type'] != 'own'){
            //获取加价模板列表
            $increase = db::name('docking_increase')->select();
            $this->assign([
                'increase' => $increase
            ]);
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

	public function stock_del(){
		$ids = $this->request->param('ids');
		Db::startTrans();
		try{
			$ids_arr = explode(',', $ids);
			$ids_num = count($ids_arr);
			$goods_id = false;
			if($ids_num > 0){ //减去商品库存
				foreach($ids_arr as $val){
					$goods_id = db::name('cdkey')->where(['id' => $val])->value('goods_id');
					if($goods_id){
						break;
					}
				}
				if($goods_id){
					db::name('goods')->where(['id' => $goods_id])->setDec('stock', $ids_num);
					db::name('cdkey')->whereIn('id', $ids)->delete();
				}
			}
			db::commit();
		} catch (\Exception $e) {
			db::rollback();
			$this->error('删除失败');
		}

		$this->success('删除成功');
	}

	/**
	 * 查看库存
	 */
	public function stock(){

		$ids = $this->request->param('ids');

		//设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            $post = $this->request->param();
			$list = db::name('cdkey')->where(['goods_id' => $ids])->limit($post['offset'], $post['limit'])->select();
			$total = db::name('cdkey')->where(['goods_id' => $ids])->count();

            $result = ["total" => $total, "rows" => $list];
            return json($result);
        }

		$this->assign([
			'id' => $ids
		]);
        return $this->view->fetch();
	}


    /**
     * 查看
     */
    public function index() {
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model->with('category')->where($where)->order($sort, $order)->paginate($limit);
            $rows = $list->items();

            $result = ["total" => $list->total(), "rows" => $rows];
            return json($result);
        }


        return $this->view->fetch();
    }


	//上架商品
	public function upGoods(){
		$post = $this->request->param();
		db::name('goods')->where(['id' => $post['id']])->update(['shelf' => $post['shelf']]);
		return json(['data' => '', 'msg' => '操作成功', 'code' => 200]);
	}

	//下架商品
	public function downGoods(){
		$post = $this->request->param();
		db::name('goods')->where(['id' => $post['id']])->update(['shelf' => $post['shelf']]);
		return json(['data' => '', 'msg' => '操作成功', 'code' => 200]);
	}


}
