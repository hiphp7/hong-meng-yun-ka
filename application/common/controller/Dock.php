<?php

namespace app\common\controller;


use think\Cache;
use think\Db;
use think\Session;

/**
 * 对接商品类
 */
class Dock {


    /**
     * 通过对接站id获取商品列表
     */
    static public function get_goods_list($id){
        $site = db::name('docking_site')->where(['id' => $id])->find();
        $info = json_decode($site['info'], true);

        $domain = $site['domain'];

        if ($site['type'] == 'jiuwu'){
            $cache_name = "dock_goods_list_{$id}";
            if (Cache::has($cache_name)){
                $list = Cache::get($cache_name);
            } else{
                $account = $info['account'];
                $password = md5($info['password']);
                $url = $domain . 'index.php?m=home&c=api&a=user_get_goods_lists_details&Api_UserName=' . $account . '&Api_UserMd5Pass=' . $password;
                $result = hmCurl($url);
                $result = json_decode($result, true);
                $list = $result['user_goods_lists_details'];
                $list = self::handle_list_wujiu($list);
                Cache::set($cache_name, $list, 60); //最多缓存60秒
            }


        }
        return $list;
    }


    //处理五九社区的商品信息列表
    static public function handle_list_wujiu($list){
        foreach($list as &$val) {
            $price = $val['goods_unitprice'];
            if($price == 0){
                continue;
            }
            $price_info = self::calc_price($price, 1, $val['minbuynum_0']);
            $val['num'] = $price_info['num'];
            $val['price'] = upDecimal($price_info['price']);
            $look_num = self::look_num($val['num']);
            $val['look_price'] = $look_num . $val['unit'] . '=' . $val['price'] . '元';
        }

        return $list;
    }


    /**
     * 计算点数价格
     */
    static public function calc_price($price, $num = 1, $min){
        $num *= 10;
        $price *= 10;

        if ($price < 0.1 || $num <= $min){
            return self::calc_price($price, $num, $min);
        }else{
            $num = $num / 10;
            $price = $price / 10;
        }
        return ['num' => $num, 'price' => $price];

    }

    static public function look_num($num){
        if ($num == 1000){
            return '1千';
        } else if ($num == 10000){
            return '1万';
        } else if ($num == 100000){
            return '10万';
        } else{
            return $num;
        }
    }


    /**
     * 获取订单所需参数
     */
    static public function getParams($type, $url, $data){
        if ($type == "jiuwu"){
            return self::getParamsJiuwu($url, $data);
        }
    }


    static public function getParamsJiuwu($url, $data){
        $info = json_decode($data['info'], true);
        //开始模拟登录
        $login_url = "{$data['domain']}index.php?m=Home&c=User&a=login";
        $cookie = dirname(__FILE__) . '/jiuwu' . time() . '.txt';

        $post = "username={$info['account']}&username_password={$info['password']}";


        $curl = curl_init();//初始化curl模块
        curl_setopt($curl, CURLOPT_URL, $login_url);//登录提交的地址
        curl_setopt($curl, CURLOPT_HEADER, false);//不自动输出头信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//不自动输出数据
        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie);//设置Cookie信息保存在指定的文件中
        curl_setopt($curl, CURLOPT_POST, 1);//post方式提交
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);//要提交的信息
        curl_exec($curl);//执行cURL
        curl_close($curl);//关闭cURL资源，并且释放系统资源


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);//读取cookie
        $html = curl_exec($ch);//执行cURL抓取页面内容
        curl_close($ch);

        unlink($cookie);


        $html = preg_replace("/[\t\n\r]+/", "", $html);

        $html = preg_replace("/<!--[^\!\[]*?(?<!\/\/)-->/", "", $html); //过滤掉html注释

        // echo $html;die;


        $partern = '/<form role="form" method="post" class="order_post_form" action=".*?">(.*?)<\/form>/';

        preg_match_all($partern, $html, $result);

        $html = $result[1][0];

        $partern = '/<li>(.*?)<input type="hidden"/';

        preg_match_all($partern, $html, $result);

        $html = $result[1][0];

        $partern = '/<span class="fixed-width-right-80">(.*?)：<\/span>/';

        preg_match_all($partern, $html, $result);

        $params_title = $result[1];

        $partern = '/<input.*?name="(.*?)".*?>/';

        preg_match_all($partern, $html, $result);

        $params_name = $result[1];

        $partern = '/<input.*?placeholder="(.*?)".*?>/';

        preg_match_all($partern, $html, $result);


        $params_placeholder = $result[1];

        $order_params = [];

        foreach($params_name as $key => $val) {
            if ($val != 'need_num_0'){
                $order_params[] = ['name' => $val, 'title' => $params_title[$key], 'placeholder' => $params_placeholder[$key]];
            }
        }

        return $order_params;
    }


}
