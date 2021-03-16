<?php

namespace app\api\controller;

use think\Db;

class Order extends Base {

    /**
     * 获取订单列表
    */
    public function list() {

        $offset = input("offset");
        $limit = input("limit");
        $uid = session::has("uid") ? session::get("uid") : session::get("tourist");

        $where = [
            'uid' => $uid,
        ];

        $list = db::name('order')->where($where)->order('id desc')->limit($offset, $limit)->select();

        $timestamp = time();

        foreach($list as &$val){

            if($val['status'] == -1){
                $val['s'] = '订单已失效';
            }elseif($val['pay'] == 0 && $timestamp - $val['createtime'] >= 600){
                $val['s'] = '订单已失效';
                db::name('order')->where(['id' => $val['id']])->update(['status' => -1]);
                $val['status'] = -1;
            }elseif($val['pay'] == 0){
                $val['s'] = '待付款';
            }elseif($val['pay'] == 1 && $val['status'] == 1){
                $val['s'] = '待发货';
            }elseif($val['pay'] == 1 && $val['status'] == 2){
                $val['s'] = '待收货';
            }elseif($val['pay'] == 1 && $val['status'] == 9){
                $val['s'] = '交易完成';
                $val['s_color'] = '#52c41a';
            }else{
                $val['s'] = '订单状态错误';
                $val['s_color'] = '#d20707';
            }
            $val['timestamp'] = date('Y-m-d H:i:s', $val['createtime']);
        }
        return json_encode(['data' => $list, 'info' => 'ok', 'status' => 0]);

    }

    //查看订单内容
    public function orderContent(){

        $order_id = $this->request->param('order_id');
        $where = [
            'uid' => $this->uid == null ? $this->tourist : $this->uid,
            'id' => $order_id
        ];
        $order = db::name('order')->where($where)->find();

        $kami = $order['kami'];

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
            'status' => 9
        ];
        $where = [
            "id" => $order_id,
            'uid' => $this->uid == null ? $this->tourist : $this->uid,
        ];
        db::name('order')->where($where)->update($update);
        return json(['msg' => '已收货', 'code' => 200]);
    }

    //删除订单
    public function del(){
        $order_id = $this->request->param('order_id');
        $where = [
            "id" => $order_id,
            'uid' => $this->uid == null ? $this->tourist : $this->uid,
        ];
//        print_r($where);die;
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
			if($order['pay'] == 0){
				return json(['code' => 200, 'msg' => '未支付', 'data' => -1]);
			}else{
				return json(['code' => 200, 'msg' => '已支付', 'data' => 1]);
			}
			
            $pay = $pay && $order['pay'] != 1 ? true : false;
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
