<?php

namespace app\shop\controller;
use app\common\controller\Hm;

use think\Db;

class Order extends Base {

    public function index() {

        $search_type = $this->request->get('search_type');
        $user = Hm::getUser();

        $post = $this->request->post();
        $this->assign([
            'title' => '订单列表',
            'search_type' => $search_type, //订单状态
            'user' => $user, //用户信息
            'post' => $post,
        ]);
        return view($this->template_path . "order.html");
    }

    //查看订单内容
    public function orderContent(){
        $user = Hm::getUser();
        $order_id = $this->request->param('order_id');
        $where = [
            'uid' => $user['id'],
            'id' => $order_id
        ];
        $order = db::name('order')->where($where)->find();

        $kami = empty($order['kami']) ? '' : $order['kami'];

        $this->assign([
            'title' => '订单详情    ',
            'order' => $order,
            'kami' => $kami,
        ]);
        return view($this->template_path . "orderContent.html");
    }

    //确认收货
    public function shouhuo(){
        $order_id = $this->request->param('order_id');
        $update = [
            'status' => 'success'
        ];
        $user = Hm::getUser(); //获取当前用户信息
        $where = [
            "id" => $order_id,
            'uid' => $user['id'],
        ];
        db::name('order')->where($where)->update($update);
        return json(['msg' => '已收货', 'code' => 200]);
    }

    //删除订单
    public function del(){
        $order_id = $this->request->param('order_id');
        $user = Hm::getUser();
        $where = [
            "id" => $order_id,
            'uid' => $user['id']
        ];
        db::name('order')->where($where)->delete();
        return json(['msg' => '已删除', 'code' => 200]);
    }

    /**
     * 获取订单的支付状态
    */
    public function getorderstatus(){
        $order_no = $this->request->param('out_trade_no');
        $table = $this->request->param('table');
        if($table == 'order'){
            $order = db::name($table)->where(['order_no' => $order_no])->find();

			if(time() - $order['createtime'] >= 600){
				return json(['msg' => '订单已过期', 'code' => 400]);
			}
			if($order['status'] != 'weizhifu' && $order['status'] != 'yiguoqi'){
                return json(['code' => 200, 'msg' => '已支付', 'data' => 1]);
			}else{
                return json(['code' => 200, 'msg' => '未支付', 'data' => -1]);
			}

        }else if($table == 'money_bill'){
            $pay = db::name($table)->where(['order_no' => $order_no])->value('status');
            $pay = $pay && $pay != 0 ? true : false;
        }

        if(!$pay){
            return json(['code' => 200, 'msg' => '未支付', 'data' => -1]);
        }else{
            return json(['code' => 200, 'msg' => '已支付', 'data' => 1]);
        }
    }



}
