<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Pay extends Backend {

    /**
     * Pay模型对象
     * @var \app\admin\model\Pay
     */
    protected $model = null;

    public function _initialize() {
        parent::_initialize();
        $this->model = new \app\admin\model\Pay;
    }



    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


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

//				print_r($params);die;

                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }

                    /*//支付宝
					if($params['type'] == 'alipay'){
						if(empty($params['sm']) && empty($params['wap']) && empty($params['pc'])){
							$this->error('您至少要选择一个支付类型！');
						}
					}

					//码支付
					if($params['type'] == 'codepay'){
						if(empty($params['wxpay']) && empty($params['alipay']) && empty($params['qqpay'])){
							$this->error('您至少要选择一个支付类型！');
						}
					}

					//易支付
                    if($params['type'] == 'epay'){
                        if(empty($params['wxpay']) && empty($params['alipay']) && empty($params['qqpay'])){
                            $this->error('您至少要选择一个支付类型！');
                        }
                    }

                    //v免签
                    if($params['type'] == 'vpay'){
                        if(empty($params['wxpay']) && empty($params['alipay'])){
                            $this->error('您至少要选择一个支付类型！');
                        }
                    }*/



					unset($params['type']);

					$update = [];
                    $update['value'] = json_encode($params);
                    $result = $row->allowField(true)->save($update);
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
                    // echo 1;die;
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row = $row->toArray();
        $info = json_decode($row['value'], true);
        $this->assign([
            'row' => $row,
            'info' => $info
        ]);
        return $this->view->fetch();
    }


	//启用支付
	public function openStatus(){
		$post = $this->request->param();

		$info = db::name('pay')->where(['id' => $post['id']])->find();
		if(empty($info["value"])){
			return json(['data' => '', 'msg' => '未填写配置，无法开启', 'code' => 400]);
		}


		db::name('pay')->where(['id' => $post['id']])->update(['status' => $post['status']]);
		return json(['data' => '', 'msg' => '操作成功', 'code' => 200]);
	}

	//关闭支付
	public function closeStatus(){
		$post = $this->request->param();
		db::name('pay')->where(['id' => $post['id']])->update(['status' => $post['status']]);
		return json(['data' => '', 'msg' => '操作成功', 'code' => 200]);
	}

}
