<?php

namespace app\shop\controller;

use app\common\controller\Fun;
use fast\Http;
use think\Db;

/**
 * 支付宝支付类
 */
class Alipay extends Base {

    public $gateway_url = "https://openapi.alipay.com/gateway.do"; //支付宝支付网关

	/**
	 * 当面付展示二维码页面
	 */
	public function aliprecreate() {
		$out_trade_no = $this->request->param('out_trade_no');
		$table = $this->request->param('table');
		$user = Hm::getUser();
		$where = [
			'order_no' => $out_trade_no,
			"uid" => $user['id'],
		];

		$order = db::name($table)->where($where)->find();

		if (empty($order) || time() - $order['createtime'] >= 600) {
			$order = null;
		}

		$alipay_info = db::name('pay')->where(['type' => 'alipay'])->find();
		$alipay_info = json_decode($alipay_info['value'], true);

		$alipay_wap = isset($alipay_info["wap"]) ? true : false;


		$this->assign([
			'qr_code' => empty($order['qr_code']) ? '' : $order['qr_code'],
			'order_no' => $out_trade_no,
			'order' => $order,
			'table' => $table,
			'img' => $this->request->has('img') ? true : false,
			"alipay_wap" => $alipay_wap,
			"is_mobile" => is_mobile()
		]);

		return view(ROOT_PATH . "public/content/template/" . $this->template_name . "/aliprecreate.html");
	}



	/**
     * 发起支付宝支付
     * @params $payInfo 支付宝配置信息
     * @params $goods 商品信息
     * @params $order 订单信息
     * @params $pay_type 发其支付类型 sm当面付 wap手机网站支付 pc电脑网站支付
     * @table 订单表 一般有商品订单表和余额充值订单表
	*/
	public function pay($payInfo, $goods, $order, $pay_type, $table = null){

        if(empty($payInfo['app_id']) || empty($payInfo['public_key']) || empty($payInfo['private_key'])){
            $this->error('支付宝支付配置无效');
        }

        $data = [
            'app_id' => $payInfo['app_id'], //应用id
            'format' => 'JSON', //返回数据类型
            'charset' => 'UTF-8',
            'sign_type' => 'RSA2', //加密方式
            'timestamp' => date('Y-m-d H:i:s', time()), //发送请求的时间
            'version' => '1.0', //api版本
            'notify_url' => $this->domain . 'shop/notify/order/type/alipay', //支付完成后的异步回调通知
        ];

        $biz_content = [
            'body' => '', //对一笔交易的具体描述信息
            'subject' => empty($goods['diy_name']) ? $goods['name'] : $goods['diy_name'], //商品标题
            'out_trade_no' => $order['order_no'], //商户订单号
            'timeout_express' => '10m', //关闭订单时间
            'total_amount' => $order['money'], //订单金额，单位/元
        ];



        if($pay_type == 'sm'){
            $data['method'] = 'alipay.trade.precreate'; //接口名称  - 当面付
            $data['biz_content'] = json_encode($biz_content); //请求参数的集合
            $data['sign'] = $this->getAlipaySign($data, ['private_key' => $payInfo['private_key']]);
            $result = Http::post($this->gateway_url, $data);
            $result = json_decode($result, true);
            if (empty($result)) {
                $this->error("发起支付宝当面付出现系统错误，请重试");
            }
            $result = $result['alipay_trade_precreate_response'];
            if ($result['code'] == 10000) {
                //写入支付二维码
                db::name($table)->where(['id' => $order['id']])->update(['qr_code' => $result['qr_code']]);
                $this->redirect(url("/aliprecreate/" . $order['order_no'] . "/" . $table));
                die;
            } else {
                $this->error("发起支付宝当面付出现系统错误，请重试");
            }
        }

        if($pay_type == 'wap'){
            $data['method'] = 'alipay.trade.wap.pay'; //接口名称 - 手机网站支付
            $data['return_url'] = $this->domain . 'order/all.html'; //付款完成后跳转的地址
            $biz_content['goods_type'] = 0; //商品主类型 0虚拟 1实物
            $biz_content['quit_url'] = $this->domain . "goods/{$goods['id']}.html"; //用户取消付款返回商户网站的地址
            $biz_content['product_code'] = 'QUICK_WAP_WAY'; //销售产品码， 商家和支付宝签约的产品码
            $data['biz_content'] = json_encode($biz_content); //请求参数的集合
            $data['sign'] = $this->getAlipaySign($data, ['private_key' => $payInfo['private_key']]);
            $this->submitAlipayForm($data);
        }

        if($pay_type == 'pc'){
            $data['method'] = 'alipay.trade.page.pay'; //接口名称 - 手机网站支付
            $data['return_url'] = $this->domain . 'order/all.html'; //付款完成后跳转的地址
            $biz_content['goods_type'] = 0; //商品主类型 0虚拟 1实物
            $biz_content['quit_url'] = $this->domain . "goods/{$goods['id']}.html"; //用户取消付款返回商户网站的地址
            $biz_content['product_code'] = 'FAST_INSTANT_TRADE_PAY'; //销售产品码， 商家和支付宝签约的产品码
            $data['biz_content'] = json_encode($biz_content); //请求参数的集合
            $data['sign'] = $this->getAlipaySign($data, ['private_key' => $payInfo['private_key']]);
            $this->submitAlipayForm($data);
        }

    }


	/**
	 * 发起支付宝wap支付
	 */
	public function submitAlipayForm($data) {

		$sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->gateway_url . "' method='POST'>";
		foreach ($data as $key => $val) {
			$val = str_replace("'", "&apos;", $val);
			$sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
		}
		//submit按钮控件请不要含有name属性
		$sHtml = $sHtml . "<input type='submit' value='ok' style='display:none;''></form>";
		$sHtml = $sHtml . "<script>document.forms['alipaysubmit'].submit();</script>";
		echo $sHtml;
		die();
	}

	/**
	 * 支付宝签名
	 */
	public function getAlipaySign($data, $alipay) {
		ksort($data);
		$data_str = "";
		foreach ($data as $key => $val) {
			if ($key != "sign") {
				$data_str .= $key . "=" . $val . "&";
			}
		}
		$data_str = rtrim($data_str, "&");
		$sign = "";
		$private_key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($alipay['private_key'], 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
		try {
			openssl_sign($data_str, $sign, $private_key, OPENSSL_ALGO_SHA256);
		} catch (\Exception $e) {
			$this->error("支付配置有误！");
		}

		$sign = base64_encode($sign);
		return $sign;
	}

}
