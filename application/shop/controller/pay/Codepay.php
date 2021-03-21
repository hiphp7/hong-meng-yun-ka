<?php

namespace app\shop\controller;

use app\common\controller\Fun;
use fast\Http;
use think\Config;
use think\Controller;
use think\Db;
use app\shop\controller\Base;

/**
 * 码支付 支付类
 */
class Codepay extends Base {


    public $codepay_url = 'http://api5.xiuxiu888.com/';

    /**
     * 码支付
     * @order 订单信息
     * @pay_type 支付类型 1支付宝 2QQ 3微信
     * @new_orer 新订单
     */
    public function pay($order, $pay_type) {

        $codepay_result = db::name('pay')->where(['type' => 'codepay'])->find();
        $codepay = json_decode($codepay_result['value'], true);

        $codepay_id = $codepay['codepay_id'];//这里改成码支付ID
        $codepay_key = $codepay['codepay_key']; //这是您的通讯密钥

        $data = [
            "id"         => $codepay_id,//你的码支付ID
            "pay_id"     => $order['order_no'], //唯一标识 可以是用户ID,用户名,session_id(),订单ID,ip 付款后返回
            "type"       => $pay_type,//1支付宝支付 3微信支付 2QQ钱包
            "price"      => $order['money'],//金额100元
            "param"      => "",//自定义参数
            "notify_url" => $this->domain . 'shop/notify/order/type/codepay',//通知地址
            "page"       => 4, 'outTime' => 600
        ]; //构造需要传递的参数

        $sign = $this->getSignUrl($data, $codepay_key);


        $result = file_get_contents($sign['url']);
        $result = json_decode($result, true);


        if (isset($result['status']) && $result['status'] == 0) { //请求成功
            //写入支付二维码和应付款金额
            if ($pay_type == 1) {
                $codepay_type = 'codepay_alipay';
            }
            if ($pay_type == 2) {
                $codepay_type = 'codepay_qqpay';
            }
            if ($pay_type == 3) {
                $codepay_type = 'codepay_wxpay';
            }
            $update = [ //修改订单信息 支付二维码 应支付金额 支付类型
                'qr_code' => $result['qrcode'], 'money' => $result['money'], 'pay_type' => $codepay_type
            ];
            db::name('order')->where(['order_no' => $result['pay_id']])->update($update);
            $this->redirect(url("/aliprecreate/" . $result['pay_id'] . "/" . 'order/im')); // im代表二维码是图片内容
        } else {
            $this->error('支付请求失败，请返回重试！');
        }


    }


    /**
     * 获取码支付签名和支付请求地址
     */
    public function getSignUrl($data, $codepay_key) {
        ksort($data); //重新排序$data数组
        reset($data); //内部指针指向数组中的第一个元素

        $sign = ''; //初始化需要签名的字符为空
        $urls = ''; //初始化URL参数为空

        foreach ($data as $key => $val) { //遍历需要传递的参数
            if ($val == '' || $key == 'sign') continue; //跳过这些不参数签名
            if ($sign != '') { //后面追加&拼接URL
                $sign .= "&";
                $urls .= "&";
            }

            $sign .= "$key=$val"; //拼接为url参数形式
            $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值

        }
        $sign = md5($sign . $codepay_key);
        $query = $urls . '&sign=' . $sign; //创建订单所需的参数
        $url = $this->codepay_url . "creat_order/?{$query}"; //支付页面

        return [
            'sign' => $sign, 'url' => $url,
        ];
    }


}
