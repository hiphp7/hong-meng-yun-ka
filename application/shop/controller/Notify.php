<?php

namespace app\shop\controller;

use app\common\controller\Fun;
use app\shop\controller\pay\Epay;
use app\shop\controller\pay\Vpay;
use fast\Http;
use think\Db;
use think\Controller;

/**
 * 回调类
 */
class Notify extends Base {

    // 表单提交字符集编码
    public $postCharset = "UTF-8";
    private $fileCharset = "UTF-8";

    /**
     * 易支付验签
     * @params $data 回调数据信息
     * @params $mode 回调方式
     * @params $timestamp 回调时间
     * @return $order 订单信息 验签成功返回订单信息，失败返回false
     */
    public function epay_check_sign($data, $mode, $timestamp){
        if(!empty($data)){
            unset($data['mode_type']);
            $epay = new Epay();
            $isSign = $epay->getSignVeryfy($data, $data["sign"]); //生成签名结果
            $responseTxt = 'true'; //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            /**
             * 验签
             * $responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
             * isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
             */
            if (preg_match("/true$/i",$responseTxt) && $isSign) {
                $order_no = $_GET['out_trade_no'];
                $order = db::name('order')->where(['order_no' => $order_no, 'status' => 'weizhifu'])->find();
                if (!$order) {
                    if($mode == "return"){
                        header("location: " . url('/order')); die;
                    }elseif($mode == "notify"){
                        echo 'success'; die;
                    }else{
                        echo "出错啦~"; die;
                    }
                }else{
                    db::name('order')->where(['id' => $order['id']])->update(['status' => 'daifahuo', 'paytime' => $timestamp]);
                    return $order;
                }
            }else{
                db::name('test')->insert(['content' => '易支付验签失败！']);
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * v免签验签
     * @params $data 回调数据信息
     * @params $mode 回调方式
     * @params $timestamp 回调时间
     * @return $order 订单信息 验签成功返回订单信息，失败返回false
     */
    public function vpay_check_sign($data, $mode, $timestamp){
        if(!empty($data)){
            unset($data['mode_type']);
            $epay = new Vpay();
            $key = $epay->secret_key;//通讯密钥
            $payId = $data['payId'];//商户订单号
            $param = $data['param'];//创建订单的时候传入的参数
            $type = $data['type'];//支付方式 ：微信支付为1 支付宝支付为2
            $price = $data['price'];//订单金额
            $reallyPrice = $data['reallyPrice'];//实际支付金额
            $sign = $data['sign'];//校验签名，计算方式 = md5(payId + param + type + price + reallyPrice + 通讯密钥)
            //开始校验签名
            $_sign =  md5($payId . $param . $type . $price . $reallyPrice . $key);
            if ($_sign == $sign) {
                $order = db::name('order')->where(['order_no' => $payId, 'status' => 'weizhifu'])->find();
                if (!$order) {
                    if($mode == "return"){
                        header("location: " . url('/order')); die;
                    }elseif($mode == "notify"){
                        echo 'success'; die;
                    }else{
                        echo "出错啦~"; die;
                    }
                }else{
                    db::name('order')->where(['id' => $order['id']])->update(['status' => 'daifahuo', 'paytime' => $timestamp]);
                    return $order;
                }

            }else{
                db::name('test')->insert(['content' => 'v免签验签失败！']);
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 回调通知
    */
    public function index(){
        $mode_type = $this->request->param('mode_type'); //通知方式
        $mode_type = explode('_', $mode_type);
        $mode = $mode_type[0];
        $type = $mode_type[1];
        $timestamp = time(); //时间戳


        if($type == 'epay'){ //易支付验签
            $data = $this->request->get();
            $order = $this->epay_check_sign($data, $mode, $timestamp);
        }
        if(type == 'vpay'){ //v免签验签
            $data = $this->request->get();
            $order = $this->vpay_check_sign($data, $mode, $timestamp);
        }


        if($order){ //验签成功
            try {
                $goods = db::name('goods')->where(['id' => $order['goods_id']])->find();
                $this->record_user_bill($order, $goods, $timestamp); //记录用户账单，增加用户消费金额
                $this->update_goods($goods, $order); //增加商品销量和商品销售额，减去商品库存
                //给商品发货或去对接站购买商品
                if($goods['type'] == 'own'){
                    $this->handle_order_own($goods, $order, $timestamp);
                }
                if($goods['type'] == 'jiuwu'){
                    $this->handle_order_jiuwu($goods, $order);
                }
                doAction('order_notify', $goods, $order); //订单回调挂载点
                if($mode == 'return'){
                    header("location: " . url('/order')); die;
                }else{
                    echo 'success'; die;
                }
            } catch (\Exception $e) {
                db::name('test')->insert(['content' => $e->getMessage() . $e->getFile() . $e->getLIne()]);
                echo 'error'; die;
            }
        }

    }

    //订单回调
    public function order() {

        $pay_type = $this->request->param('type'); //支付类型



        $check_sign = false; //验签


        if($pay_type == 'codepay'){ //码支付验签

            try{
                $codepay_result = db::name('pay')->where(['type' => 'codepay'])->find();
                $codepay = json_decode($codepay_result['value'], true);

                $codepay_key= $codepay['codepay_key']; //这是您的通讯密钥

                $post = $this->request->post();

                ksort($post); //排序post参数
                reset($post); //内部指针指向数组中的第一个元素

                $sign = '';//初始化
                foreach ($post as $key => $val) { //遍历POST参数
                    if ($val == '' || $key == 'sign'){
                        continue; //跳过这些不签名
                    }
                    if ($sign){
                        $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
                    }
                    $sign .= "$key=$val"; //拼接为url参数形式
                }
                if (!$post['pay_no'] || md5($sign . $codepay_key) != $post['sign']) { //不合法的数据
                    exit('fail');  //返回失败 继续补单
                } else { //合法的数据
                    //业务处理
                    $check_sign = true;
                    $order_no = $post['pay_id']; //需要充值的ID 或订单号 或用户名
                }
            }catch(\Exception $e){
                db::name('test')->insert(['content' => $e->getMessage() . $e->getLine() . $e->getFile()]);
            }



        }elseif($pay_type == 'alipay'){ //支付宝验签

            try {
                //接收回调报文
                $content = file_get_contents("php://input");

                //调用支付宝验签方法
                $check_sign = $this->ali_check_sign($content);
                $order_no = empty($check_sign['order_no']) ? null : $check_sign['order_no'];
            } catch (\Exception $e) {
                db::name('test')->insert(['content' => $e->getMessage() . $e->getLine() . $e->getFile()]);
            }
        }


        if($check_sign){ //验签成功

            $order = db::name('order')->where(['order_no' => $order_no, 'status' => 'weizhifu'])->find();

            if (!$order) {
                echo 'success';
                die;
            }

            // db::startTrans();
            try {

                $goods = db::name('goods')->where(['id' => $order['goods_id']])->find();

                $timestamp = time();

                //1, 记录用户账单，增加用户消费金额
                $this->record_user_bill($order, $goods, $timestamp);
                //2，增加商品销量和商品销售额，减去商品库存
                $this->update_goods($goods, $order);
                //3, 给商品发货或去对接站购买商品
                if($goods['type'] == 'own'){
                    $this->handle_order_own($goods, $order, $timestamp);
                }
                if($goods['type'] == 'jiuwu'){
                    $this->handle_order_jiuwu($goods, $order);
                }
                doAction('order_notify', $goods, $order); //订单回调挂载点
                die('success');
            } catch (\Exception $e) {
                // Db::rollback();
                db::name('test')->insert(['content' => $e->getMessage() . $e->getFile() . $e->getLIne()]);
                echo 'error';
                die;
            }

        }else{
            db::name('test')->insert(['content' => '验签失败']);
        }

    }

    //处理玖伍社区订单
    public function handle_order_jiuwu($goods, $order){
        $site = db::name('docking_site')->where(['id' => $goods['site_id']])->find();
        $site_info = json_decode($site['info'], true);

        $dock_data = json_decode($goods['dock_data'], true);

        $url = $site["domain"] . "index.php?m=home&c=order&a=ly_add";
        $params = [
            "Api_UserName" => $site_info['account'],
            'Api_UserMd5Pass' => md5($site_info['password']),
            'goods_id' => $goods['remote_id'],
            'goods_type' => $dock_data['goods_type'],
            'pay_type' => 1, //余额支付
            'need_num_0' => $dock_data['num'] / $order['goods_num']
        ];
        $attach = json_decode($order['attach'], true);
        foreach($attach as $key => $val){
            $params[$key] = $val;
        }
        $result = Http::post($url, $params);
        // db::name('test')->insert(['content' => $result]);
        $result = json_decode($result, true);
        if($result['status'] == 1){
            db::name('order')->where(['id' => $order['id']])->update(['status' => 'success']);
        }
    }

    //处理自营订单
    public function handle_order_own($goods, $order, $timestamp){
        $status = $goods['deliver'] == 0 ? 'success' : 'daifahuo'; //自动发货=0 已发货=yifahuo 手动发货=1 代发货=daifahuo
        $update = [
            'status' => $status, //发货状态
            'paytime' => $timestamp, //支付时间
        ];

        if ($goods['deliver'] == 0) { //商品类型是自动发货时
            $kami = $this->getKami($goods['id'], $order['goods_num']); //从商品库存中拿出用户购买的卡密并返回剩余卡密
            $update['kami'] = $kami;
        }

        db::name('order')->where(['id' => $order['id']])->update($update); //修改订单记录
    }

    //商品付款后更新商品销量库存等信息
    public function update_goods($goods, $order){
        db::name('goods')->where(['id' => $goods['id']])->setInc('sales'); //增加商品销量
        db::name('goods')->where(['id' => $goods['id']])->setInc('sales_money', $order['money']); //增加商品销售额
        if($goods['type'] == 'own'){
            db::name('goods')->where(['id' => $goods['id']])->setDec('stock', $order['goods_num']); //减去商品库存
        }

    }

    //记录用户账单
    public function record_user_bill($order, $goods, $timestamp){
        $bill_insert = [
            'uid' => $order['uid'],
            'description' => '购买商品 ' . $goods['name'] . ' x' . $order['goods_num'],
            'createtime' => $timestamp,
            'value' => '-' . sprintf("%.2f", $order['money']),
            'type' => 'goods', //购买商品
        ];
        db::name('money_bill')->insert($bill_insert);
        db::name("user")->where(["id" => $order["uid"]])->setInc("consume", $order["money"]);
    }





    //充值回调
    public function recharge() {
        // db::name('test')->insert(['content' => '进入充值回调']);
        try {

            //接收回调报文
            $content = file_get_contents("php://input");

            //调用支付宝验签方法
            $check_sign = $this->ali_check_sign($content);

            if ($check_sign) { //验签成功
                db::startTrans();
                try {
                    $order_no = $check_sign['order_no']; //订单号

                    $money_bill = db::name('money_bill')->where(['order_no' => $order_no, 'status' => 0])->find();

                    if (!$money_bill) {
                        echo 'success';
                        die;
                    }

                    $update = [
                        'status' => 1, 'handletime' => time(),
                    ];
                    db::name('money_bill')->where(['id' => $money_bill['id']])->update($update); //修改用户账单充值记录
                    db::name('user')->where(['id' => $money_bill['uid']])->setInc('money', $money_bill['money']); //增加用户余额
                    db::commit();
                    echo 'success';
                    die;
                } catch (\Exception $e) {
                    Db::rollback();

                    echo 'error';
                    die;
                }
            }
        } catch (\Exception $e) {
            $data = [
                'createtime' => 2, 'content' => $e->getMessage() . $e->getLine()
            ];

            echo 'error';
            echo $e->getMessage() . '--' . $e->getLine();
            die;
        }
        die;

    }


    //获取商品的卡密
    /**
     * return @kami 取出的卡密
     * return @goods_kami 剩余的卡密
     */
    public function getKami($goods_id, $goods_num) {
        $kami_result = db::name('cdkey')->where(['goods_id' => $goods_id])->limit($goods_num)->select();
        $kami = [];
        foreach($kami_result as $key => $val){
            db::name('cdkey')->where(['id' => $val['id']])->delete();
            $kami[] = $val['cdk'];
        }

        $kami = implode("\r\n", $kami);

        return $kami;

    }


    /**
     * 阿里验签
     * return @order_no 商户订单号
     */

    public function ali_check_sign($content) {
        $content = urldecode($content);
        $content = mb_convert_encoding($content, 'utf-8', 'gbk');
        $content = explode('&', $content);
        $params = [];
        foreach ($content as $val) {
            $item = explode('=', $val, "2");
            $params[$item[0]] = $item[1];
        }

        $sign = $params['sign'];

        unset($params['sign']);
        unset($params['sign_type']);

        ksort($params);



        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                $v = mb_convert_encoding($v, 'gbk', 'utf-8');
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);

        $alipay = Db::name('pay')->where(['type' => 'alipay'])->find();
        $alipay = json_decode($alipay['value'], true);

        $pubKey = $alipay['public_key'];
        $public_key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";

        $result = (openssl_verify($stringToBeSigned, base64_decode($sign), $public_key, OPENSSL_ALGO_SHA256) === 1);

        if ($result) {

            return [
                'order_no' => $params['out_trade_no'],
            ];
        } else {
            db::name('test')->insert(['content' => '支付宝验签失败，大概是支付配置信息错了~']);
            return false;
        }

    }


    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value)) return true;
        if ($value === null) return true;
        if (trim($value) === "") return true;

        return false;
    }

}
