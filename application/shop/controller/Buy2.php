<?php

namespace app\shop\controller;
use app\common\controller\Fun;
use fast\Http;
use think\Db;

class Buy extends Auth {


    //提交订单支付
    public function pay() {
        $post = $this->request->param();
        $goods_id = $post['goods_id'];
        $goods_num = $post['goods_num'];
        $goods = Fun::getGoodsInfo($goods_id);
        if(!$goods){
            $this->assign([
                'title' => '商品不存在',
                'content' => '商品被管理员下架或者删除了~'

            ]);
            return view('errPage');
        }

        if($goods_num > $goods['stock']){
            $this->assign([
                'title' => '库存不足',
                'content' => '商品库存不足，请联系客户添加库存'

            ]);
            return view('errPage');
        }

        $user = db::name('user')->where(['id' => $this->uid])->find();

        //商品状态和库存检测完毕
        //开始判断商品来源
        if($goods['type'] == 'own'){ //自营产品
            return $this->pay_own($post, $goods, $user);
        }elseif($goods['type'] == 'azf'){ //爱转发
            $this->pay_azf($post, $goods, $user);
        }else{
            $this->error('系统错误！');
        }




    }



    //生成爱转发的签名
    public function generateAzfSign($data, $secret_key){
        ksort($data); //排序post参数
        reset($data); //内部指针指向数组中的第一个元素
        $signtext='';
        foreach ($data AS $key => $val) { //遍历POST参数
            if ($val == '' || $key == 'sign') continue; //跳过这些不签名
            if ($signtext) $signtext .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
            $signtext .= "$key=$val"; //拼接为url参数形式
        }
        $newsign=md5($signtext.$secret_key);
        return $newsign;
    }

    public function pay_azf($post, $goods, $user){
        $user_id = 10842;
        $secret_key = 'd565e95e935e324441f730d46207e914';

        $data = [
            'userid' => $user_id,
            'outorderno' => $this->generateOrderNo(),
            'goodsid' => $goods['remote_id'],
            'buynum' => $post['goods_num'],
            'maxmoney' => $goods['price'],
        ];
        $data['sign'] = $this->generateAzfSign($data, $secret_key);
        $url = 'http://azf.5bma.cn/dockapi/index/buy.html'; //商品统一下单接口地址
        $result = Http::post($url, $data);
        $result = json_decode($result, true);

        if(isset($result['code']) && $result['code'] == -1){
            header("location: " . url('error/index', ['title' => '系统错误', 'content' => $result['msg']]));
            die;
        }
        /**
         * 进货成功后
         * 写入本地订单表
        */
        $insert = [
            'order_no' => $data['outorderno'],
            'remote_order_no' => $result['orderno'],
            'uid' => $this->uid,
            'goods_id' => $goods['id'],
            'goods_name' => $goods['name'],
            'goods_cover' => $goods['cover'],
            'goods_num' => $post['goods_num'],
            'goods_money' => $goods['price'],
            'money' => $goods['price'] * $post['goods_num'],
            'pay' => $post['pay_type'],
            'status' => '',
            'remote_money' => $result['money'],
        ];
        $cardlist=isset($result['cardlist'])?$result['cardlist']:'';
        $kami = '';
        foreach($cardlist as $val){
            $kami .= $val."\r\n";
        }
        $insert['kami'] = $kami;
        $insert['goods_type'] = 0; //0卡密
        $insert['status'] = 1; //1已发货
        db::startTrans();
        try {
            /**
             * 1，减去用户余额
             * 2，写入订单列表
            */
            db::name('user')->where(['id' => $this->uid])->setDec('money', $insert['money']);
            db::name('order')->insert($insert);
            db::commit();
        }catch(\Exception $e){
            db::rollback();
            $this->assign([
                'title' => '出错啦~',
                'content' => '订单数据有误，请联系客服解决'
            ]);
            return view('errPage');
        }
        header("location: " . url('buySuccess', ['goods_id' => $post['goods_id']]));
        die;
    }

    /**
     * $post 提交的参数
     * $goods 商品信息
     * $user 用户信息
    */
    public function pay_own($post, $goods, $user){

        $order_money = $goods['price'] * $post['goods_num']; //订单金额
        $timestamp = time(); //生成订单时间
        $order_no = $this->generateOrderNo(); //订单号
        //写入订单表
        $insert = [
            'order_no' => $order_no, //订单号
            'uid' => $this->uid, //用户id
            'goods_id' => $post['goods_id'], //商品id
            'goods_num' => $post['goods_num'], //商品数量
            'goods_name' => $goods['name'], //商品名称
            'goods_cover' => $goods['cover'], //商品封面图
            'goods_money' => $goods['price'], //商品单价
            'goods_type' => $goods['goods_type'], //商品类型 激活码 卡密等
            'money' => $order_money, //订单金额
            'createtime' => $timestamp, //订单生成时间
        ];

        if($post['pay_type'] == 0){ //余额支付
            if($user['money'] < $order_money){
                $this->assign([
                    'title' => '余额不足！',
                    'content' => '您的余额不足，<a style="color: #fff;" href="' . url('wallet/yue') . '">请充值</a>'
                ]);
                return view('errPage');
            }
            Db::startTrans();
            try{
                //减去用户余额
                db::name('user')->where(['id' => $this->uid])->setDec('money', $order_money);
                //记录用户账单
                $bill_insert = [
                    'uid' => $this->uid,
                    'description' => '购买商品 ' . $goods['name'] . ' x' . $post['goods_num'],
                    'createtime' => $timestamp,
                    'value' => '-' . $order_money
                ];
                db::name('money_bill')->insert($bill_insert);
                //增加商品销量
                db::name('goods')->where(['id' => $post['goods_id']])->setInc('sales');
                $status = $goods['deliver'] == 0 ? 1 : 0; //自动发货=已发货(1) 手动发货=代发货(0)
                $insert['pay'] = $post['pay_type']; //支付方式与支付状态 -1为未支付 因为是余额支付，所以不存在-1的情况
                $insert['status'] = $status; //发货状态
                $insert['paytime'] = time(); //支付时间

                if($goods['goods_type'] == 0 && $goods['deliver'] == 0){ //商品类型为卡密并且是自动发货时
                    $temp = $this->getKami($goods['kami'], $post['goods_num']); //从商品库存中拿出用户购买的卡密并返回剩余卡密
                    $insert['kami'] = $temp['kami'];
                    $goods_kami = $temp['goods_kami'];
                    db::name('goods')->where(['id' => $goods['id']])->update(['kami' => $goods_kami]); //修改商品剩余卡密
                    db::name('goods')->where(['id' => $goods['id']])->setDec('stock', $post['goods_num']); //减去商品库存
                }
                db::name('order')->insert($insert);
                Db::commit();
            }catch(\Exception $e){
                Db::rollback();
                $this->assign([
                    'title' => '提交失败！',
                    'content' => '订单提交失败['. $e->getMessage() .']，请返回重试! 如无法解决，请 <a style="color: #fff;" href="' . url('service/index') . '">联系客服</a>'
                ]);
                return view('errPage');
            }
            header("location: " . url('buySuccess', ['goods_id' => $post['goods_id']]));
            die;
        }

        if($post['pay_type'] == 1){ //支付宝付款
            Db::startTrans();
            try {
                //写入订单
                Db::name('order')->insert($insert);
                $token = 'RUXTG2D7D2ZDEZR96IH1AQO6TY5R1JPZ';
                $url = 'https://pay.020zf.com';
                $notify_url = htmlspecialchars(url('notify/order')); //回调地址
                $return_url = htmlspecialchars(url('buySuccess', ['goods_id' => $goods['id']])); //支付成功后的跳转地址
                $data = [
                    'identification' => 'PFQJFZ9ZJXXIU3DU', //020支付的商户唯一标识
                    'price' => 1 * 100, //订单金额，单位分
                    'type' => 2, //支付宝
                    'notify_url' => $notify_url, //回调地址
                    'return_url' => $return_url, //支付成功后的跳转地址
                    'orderid' => $order_no, //订单号
                    'orderuid' => $this->uid, //自定义客户编号
                    'goods_name' => $goods['name'], //商品名称
                ];

                $data['key'] = md5($data['goods_name']. $data['identification']. $data['notify_url']. $order_no. $this->uid. $data['price']. $data['return_url']. $token. $data['type']);
                $content = <<<EOT
                        <form name='fr' action='{$url}' method='POST'>
                            <input type='hidden' name='identification' value="{$data['identification']}">
                            <input type='hidden' name='price' value="{$data['price']}">
                            <input type='hidden' name='type' value="{$data['type']}">
                            <input type='hidden' name='notify_url' value="{$data['notify_url']}">
                            <input type='hidden' name='return_url' value="{$data['return_url']}">
                            <input type='hidden' name='orderid' value="{$data['orderid']}">
                            <input type='hidden' name='orderuid' value="{$data['orderuid']}">
                            <input type='hidden' name='goodsname' value="{$data['goods_name']}">
                            <input type='hidden' name='key' value="{$data['key']}">
                        </form>
                        <script type='text/javascript'>
                            document.fr.submit();
                        </script>
EOT;
                Db::commit();
            }catch(\Exception $e){
                Db::rollback();
            }
            echo $content;die;

        }


    }

    public function getKami($post_kami, $goods_num){
        $kami_arr = explode("\r\n", $post_kami);
        $kami = "";
        for($i = 0; $i < $goods_num; $i++){
            $kami .= $kami_arr[$i] . "\r\n";
            unset($kami_arr[$i]);
        }
        $kami = rtrim($kami, "\r\n");
        return [
            'kami' => $kami,
            'goods_kami' => implode("\r\n", $kami_arr),
        ];
    }

    public function buySuccess(){
        $goods_id = $this->request->param('goods_id');
        $this->assign([
            'goods_id' => $goods_id,
        ]);
        return view();
    }



    public function detail(){
        $goods_id = $this->request->param('goods_id');
        $info = Fun::getGoodsInfo($goods_id);
        $this->assign([
            'info' => $info,
        ]);
        return view();
    }

}
