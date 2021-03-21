<?php

namespace app\shop\controller;

use app\shop\controller\pay\Vpay;
use think\Db;
use app\shop\controller\pay\Epay;
use app\shop\controller\pay\Alipay;
use app\common\controller\Hm;

/**
 * 提交支付订单
*/
class Buy extends Base {



    public function _initialize() {
        parent::_initialize();

        if ($this->site['tourist_buy'] < 1 && !$this->uid) { //不允许游客购买
            $this->redirect(url("/login"));
        }
    }


    /**
     * 确认订单页面
    */
    public function confirm(){

        $post = $this->request->param();
        $goods = Hm::getGoodsInfo($post['goods_id']);

        if($goods['type'] == 'jiuwu' && $goods['max_int'] < $post['goods_num']){
            $this->error('该商品最多可以购买' . $goods['max_int'] . '件');
        }

        $attach = []; //订单附加数据
        foreach($post as $key => $val){
            if(strstr($key, 'attach_')){
                $temp_key = ltrim($key, 'attach_');
                $attach[$temp_key] = $val;
            }
        }

        $this->assign([
            'post' => $post,
            'attach' => $attach,
            'goods' => $goods,

        ]);

        return view($this->template_path . "confirm_order.html");
    }


    //提交订单支付
    public function pay() {

        $post = $this->request->param();
        $goods = Hm::getGoodsInfo($post['goods_id']);
        if (!$goods) {
            $this->error('商品不存在');
        }
        if ($goods['type'] == 'own' && $post['goods_num'] > $goods['stock']) {
            $this->error('库存不足，请联系客服添加库存');
        }
        $user = Hm::getUser(); //获取当前用户信息

        //写入订单
        $order_id = $post['order_id']; //订单编号
        $order_money = $goods['price'] * $post['goods_num']; //订单金额

        $attach = [];
        foreach($post as $key => $val){
            if(strstr($key, 'attach_')){
                $temp_key = ltrim($key, 'attach_');
                $attach[$temp_key] = $val;
            }
        }

        //写入订单表
        $insert = [
            'order_no' => $this->generateOrderNo(), //订单号
            'createtime' => time(), //订单生成时间
            'pay_type' => $post['pay_type'], //支付方式
            'uid'         => $user["id"], //用户id
            'goods_id'    => $post['goods_id'], //商品id
            'goods_num'   => $post['goods_num'], //商品数量
            'goods_name'  => $goods['name'], //商品名称
            'goods_cover' => $goods['cover'], //商品封面图
            'goods_money' => $goods['price'], //商品单价
            'goods_type'  => $goods['goods_type'], //商品类型 激活码 卡密等
            'money'       => $order_money, //订单金额
            'remote_money' => $goods['buy_price'] * $post['goods_num'], //进货价
            'attach' => json_encode($attach), //附加内容
        ];

//        echo '<pre>';
//        print_r($insert);die;

        if ($order_id == 0) { //新订单
            $id = db::name('order')->insertGetId($insert);
            $order = $insert;
            $order['id'] = $id;
        } else { //未付款订单
            $order = db::name('order')->where(['id' => $order_id, 'uid' => $user['id']])->find();
            if(!$order){
                $this->error('订单已失效或不存在');
            }
            $order['pay_type'] = $insert['pay_type'];
            db::name('order')->where(['id' => $order_id, 'uid' => $user['id']])->update(['pay_type' => $insert['pay_type']]);
        }


        //获取用户的支付方式
        $pay_type = $this->get_pay_type($order['pay_type']);
//        echo $pay_type;die;

        //开始判断商品来源
        if ($goods['type'] == 'own') { //自营产品
            return $this->pay_own($order, $goods, $user);
        } elseif ($goods['type'] == 'jiuwu') { //玖伍社区
            $this->pay_jiuwu($order, $goods, $pay_type);
        } else {
            $this->error('系统错误！');
        }


    }



    /**
     * 购买玖伍社区产品
     * $post 订单信息
     * $goods 商品信息
     */
    public function pay_jiuwu($order, $goods, $pay_type) {
        if($order['pay_type'] == 'alipay' || $order['pay_type'] == 'alipay_wap'){
            if($pay_type == 'alipay_pc'){
                $_pay = db::name('pay')->where(['type' => 'alipay'])->find();
                $payInfo = json_decode($_pay['value'], true);
                $alipay = new Alipay();
                $alipay->pay($payInfo, $goods, $order, 'pc', 'order');
            }
            if($pay_type == 'alipay_wap'){
                $_pay = db::name('pay')->where(['type' => 'alipay'])->find();
                $payInfo = json_decode($_pay['value'], true);
                $alipay = new Alipay();
                $alipay->pay($payInfo, $goods, $order, 'wap', 'order'); //手机网站支付
            }
            if($pay_type == 'alipay_sm'){
                $_pay = db::name('pay')->where(['type' => 'alipay'])->find();
                $payInfo = json_decode($_pay['value'], true);
                $alipay = new Alipay();
                $alipay->pay($payInfo, $goods, $order, 'sm', 'order'); //当面付
            }
            if($pay_type == 'vpay'){ //发起v免签支付
                $vpay = new Vpay();
                $vpay->pay($order, $goods, 2);
            }

            if($pay_type == 'epay'){ //发起易支付
                $epay = new Epay();
                $epay->pay($order, $goods, 'alipay');
            }


            if($pay_type == 'codepay'){ //发起码支付
                $codepay = new Codepay();
                $codepay->pay($order, 1);
            }
        }
        if($order['pay_type'] == 'wxpay'){
            if($pay_type == 'codepay'){
                $codepay = new Codepay();
                $codepay->pay($order, 3);
            }
            if($pay_type == 'epay'){
                $epay = new Epay();
                $epay->pay($order, $goods, 'wxpay');
            }

            if($pay_type == 'vpay'){
                $vpay = new Vpay();
                $vpay->pay($order, $goods, 1);
            }
        }

        if($order['pay_type'] == 'qqpay'){
            if($pay_type == 'codepay'){
                $codepay = new Codepay();
                $codepay->pay($order, 2);
            }
            if($pay_type == 'epay'){
                $epay = new Epay();
                $epay->pay($order, $goods, 'qqpay');
            }
        }

    }

    /**
     * 购买自营产品
     * $post 订单信息
     * $goods 商品信息
     * $user 用户信息
     */
    public function pay_own($order, $goods, $user) {
        $timestamp = time();

        if ($order['pay_type'] == 'money') { //余额支付
            if ($user['money'] < 100) {
                $this->assign([
                    'title' => '余额不足！', 'content' => '您的余额不足，<a style="color: #fff;" href="' . url('wallet/yue') . '">请充值</a>'
                ]);
                return view('errPage');
            }
            Db::startTrans();
            try {
                //减去用户余额
                db::name('user')->where(['id' => $this->uid])->setDec('money', $order_money);
                //记录用户账单
                $bill_insert = [
                    'uid' => $this->uid, 'description' => '购买商品 ' . $goods['name'] . ' x' . $post['goods_num'], 'createtime' => $timestamp, 'value' => '-' . sprintf("%.2f", $order_money)
                ];
                db::name('money_bill')->insert($bill_insert);
                //增加商品销量
                db::name('goods')->where(['id' => $post['goods_id']])->setInc('sales');
                $status = $goods['deliver'] == 0 ? 1 : 0; //自动发货=已发货(1) 手动发货=代发货(0)
                $insert['pay'] = $post['pay_type']; //支付方式与支付状态 -1为未支付 因为是余额支付，所以不存在-1的情况
                $insert['status'] = $status; //发货状态
                $insert['paytime'] = $timestamp; //支付时间
//                $insert["remote_money"] =

                if ($goods['goods_type'] == 0 && $goods['deliver'] == 0) { //商品类型为卡密并且是自动发货时
                    $temp = $this->getKami($goods['kami'], $post['goods_num']); //从商品库存中拿出用户购买的卡密并返回剩余卡密
                    $insert['kami'] = $temp['kami'];
                    $goods_kami = $temp['goods_kami'];
                    db::name('goods')->where(['id' => $goods['id']])->update(['kami' => $goods_kami]); //修改商品剩余卡密
                    db::name('goods')->where(['id' => $goods['id']])->setDec('stock', $post['goods_num']); //减去商品库存
                }
                if (1 == 0) {
                    db::name('order')->insert($insert);
                } else {
					unset($insert['createtime']);
					unset($insert['order_no']);
                    db::name('order')->where(['id' => 2, 'uid' => $this->uid])->update($insert);
                }

                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                $this->assign([
                    'title' => '提交失败！', 'content' => '订单提交失败[' . $e->getMessage() . ']，请返回重试! 如无法解决，请 <a style="color: #fff;" href="' . url('service/index') . '">联系客服</a>'
                ]);
                return view('errPage');
            }
            header("location: " . url('buySuccess', ['goods_id' => $post['goods_id']]));
            die;
        }

        //支付配置列表
		$pay_list = db::name('pay')->where(['status' => 1])->order('weigh desc')->select();

        //支付宝
        if ($order['pay_type'] == 'alipay' || $order['pay_type'] == 'alipay_wap') { //支付宝付款

            if($order['pay_type'] == 'alipay_wap'){
                $_pay = db::name('pay')->where(['type' => 'alipay'])->find();
                $payInfo = json_decode($_pay['value'], true);
                $alipay = new Alipay();
                $alipay->pay($payInfo, $goods, $order, 'wap', 'order'); //手机网站支付
                die;
            }

            // 区分出官方支付宝,码支付,易支付的优先级
            $pay_type = null;
            foreach($pay_list as $key => $val){
                $payInfo = json_decode($val['value'], true); //支付账号配置信息

                if($val['type'] == 'alipay'){
                    $pay_type = 'alipay';
                    break;
                }

                if($val['type'] == 'codepay' && isset($payInfo['alipay'])){
                    $pay_type = 'codepay';
                    break;
                }

                if($val['type'] == 'epay' && isset($payInfo['alipay'])){
                    $pay_type = 'epay';
                    break;
                }

                if($val['type'] == 'vpay' && isset($payInfo['wxpay'])){
                    $pay_type = 'vpay';
                    break;
                }
            }

            if($pay_type == 'vpay'){ //发起v免签支付
                $vpay = new Vpay();
                $vpay->pay($order, $goods, 2);
            }

            if($pay_type == 'epay'){ //发起易支付
                $epay = new Epay();
                $epay->pay($order, $goods, 'alipay');
            }


			if($pay_type == 'codepay'){ //发起码支付
				$codepay = new Codepay();
				$codepay->pay($order, 1);
			}

			if($pay_type == 'alipay' || $pay_type == "alipay_wap"){ //发起支付宝官方支付

                $alipay = new Alipay();
                if(is_mobile()){

                    if(empty($payInfo['sm'])){
                        $alipay->pay($payInfo, $goods, $order, 'wap', 'order'); //手机网站支付
                    }else{
                        $alipay->pay($payInfo, $goods, $order, 'sm', 'order'); //当面付
                    }
                }else {
                    if(empty($payInfo['pc'])){
                        $alipay->pay($payInfo, $goods, $order, 'sm', 'order'); //当面付
                    }else{
                        //此处应该调用pc网站支付 -----暂无pc支付 临时调用当面付
                        $alipay->pay($payInfo, $goods, $order, 'pc', 'order'); //支付宝当面付
                    }

                }

			}


        }

		if($order['pay_type'] == 'wxpay'){ //微信付款

            // 区分出官方微信，码支付,易支付的微信付款优先级
            $pay_type = null;
            foreach($pay_list as $key => $val){
                $payInfo = json_decode($val['value'], true); //支付账号配置信息

                if($val['type'] == 'wxpay'){
                    $pay_type = 'wxpay';
                    break;
                }

                if($val['type'] == 'codepay' && isset($payInfo['wxpay'])){
                    $pay_type = 'codepay';
                    break;
                }

                if($val['type'] == 'epay' && isset($payInfo['wxpay'])){
                    $pay_type = 'epay';
                    break;
                }

                if($val['type'] == 'vpay' && isset($payInfo['wxpay'])){
                    $pay_type = 'vpay';
                    break;
                }
            }


            if($pay_type == 'codepay'){
                $codepay = new Codepay();
                $codepay->pay($order, 3);
            }
            if($pay_type == 'epay'){
                $epay = new Epay();
                $epay->pay($order, $goods, 'wxpay');
            }

            if($pay_type == 'vpay'){
                $vpay = new Vpay();
                $vpay->pay($order, $goods, 1);
            }


		}

		if($order['pay_type'] == 'qqpay'){ //qq付款
            // 区分出官方qq，码支付,易支付的qq付款优先级
            $pay_type = null;
            foreach($pay_list as $key => $val){
                $payInfo = json_decode($val['value'], true); //支付账号配置信息

                if($val['type'] == 'qqpay'){
                    $pay_type = 'qqpay';
                    break;
                }

                if($val['type'] == 'codepay' && isset($payInfo['qqpay'])){
                    $pay_type = 'codepay';
                    break;
                }

                if($val['type'] == 'epay' && isset($payInfo['qqpay'])){
                    $pay_type = 'epay';
                    break;
                }
            }

            if($pay_type == 'codepay'){
                $codepay = new Codepay();
                $codepay->pay($order, 2);
            }
            if($pay_type == 'epay'){
                $epay = new Epay();
                $epay->pay($order, $goods, 'qqpay');
            }

		}

    }


    public function buySuccess() {
        $goods_id = $this->request->param('goods_id');
        $this->assign([
            'goods_id' => $goods_id,
        ]);
        return view();
    }


    /**
     * 提交订单页面
     * @params $goods_id 商品id   or  @params $order_id 订单id
    */
    public function detail() {
        $goods_num = 1;
        $order_id = 0;
        if ($this->request->has('goods_id')) {
            $goods_id = $this->request->param('goods_id');
        } else {
            $order_id = $this->request->param('order_id');
            $order_info = db::name('order')->where(['id' => $order_id])->find();
            $goods_id = $order_info['goods_id'];
            $goods_num = $order_info['goods_num'];
        }
        $info = Fun::getGoodsInfo($goods_id);
        $this->assign([
            'info' => $info, 'goods_num' => $goods_num, 'order_id' => $order_id,
        ]);
        return view();
    }


    /**
     * 通过用户选择的支付方式得出系统需要执行的支付方式
     */
    public function get_pay_type($u_pay_type){
        //支付配置列表
        $pay_list = db::name('pay')->where(['status' => 1])->order('weigh desc')->select();

        if($u_pay_type == 'wxpay'){ //微信付款

            // 区分出官方微信，码支付,易支付的微信付款优先级
            $pay_type = null;
            foreach($pay_list as $key => $val){
                $payInfo = json_decode($val['value'], true); //支付账号配置信息

                if($val['type'] == 'wxpay'){
                    $pay_type = 'wxpay';
                    break;
                }

                if($val['type'] == 'codepay' && isset($payInfo['wxpay'])){
                    $pay_type = 'codepay';
                    break;
                }

                if($val['type'] == 'epay' && isset($payInfo['wxpay'])){
                    $pay_type = 'epay';
                    break;
                }

                if($val['type'] == 'vpay' && isset($payInfo['wxpay'])){
                    $pay_type = 'vpay';
                    break;
                }
            }

        }

        //支付宝
        if($u_pay_type == 'alipay_wap'){
            $pay_type = 'alipay_wap';
        }
        if($u_pay_type == 'alipay'){
            // 区分出官方支付宝,码支付,易支付的优先级
            foreach($pay_list as $key => $val){
                $payInfo = json_decode($val['value'], true); //支付账号配置信息

                if($val['type'] == 'alipay'){
                    $alipay = new Alipay();
                    if(is_mobile()){

                        if(empty($payInfo['sm'])){
                            $pay_type = 'alipay_wap';
                        }else{
                            $pay_type = 'alipay_sm';
                        }
                    }else {
                        if(empty($payInfo['pc'])){
                            $pay_type = 'alipay_sm';
                        }else{
                            $pay_type = 'alipay_pc';
                        }
                    }
                    break;
                }

                if($val['type'] == 'codepay' && isset($payInfo['alipay'])){
                    $pay_type = 'codepay';
                    break;
                }

                if($val['type'] == 'epay' && isset($payInfo['alipay'])){
                    $pay_type = 'epay';
                    break;
                }

                if($val['type'] == 'vpay' && isset($payInfo['wxpay'])){
                    $pay_type = 'vpay';
                    break;
                }
            }
        }


        if($u_pay_type == 'qqpay'){ //qq付款
            // 区分出官方qq，码支付,易支付的qq付款优先级
            $pay_type = null;
            foreach($pay_list as $key => $val){
                $payInfo = json_decode($val['value'], true); //支付账号配置信息

                if($val['type'] == 'qqpay'){
                    $pay_type = 'qqpay';
                    break;
                }

                if($val['type'] == 'codepay' && isset($payInfo['qqpay'])){
                    $pay_type = 'codepay';
                    break;
                }

                if($val['type'] == 'epay' && isset($payInfo['qqpay'])){
                    $pay_type = 'epay';
                    break;
                }
            }

        }
        return $pay_type;
    }

}
