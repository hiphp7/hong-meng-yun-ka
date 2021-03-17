<?php

namespace app\admin\controller\docking;

use think\Cache;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 对接站点公用
 *
 * @icon fa fa-circle-o
 */
class Dock{

    /**
     * 获取订单所需参数
    */
    static public function getParams($type, $url, $data){
        if($type == "jiuwu"){
            return self::getParamsJiuwu($url, $data);
        }
    }


    static public function getParamsJiuwu($url, $data){
        $info = json_decode($data['info'], true);
        //开始模拟登录
        $login_url = "{$data['domain']}index.php?m=Home&c=User&a=login";
        $cookie = dirname(__FILE__) . '/jiuwu' . time() . '.txt';

        $post = "username={$info['account']}&username_password={$info['password']}";


        $curl=curl_init();//初始化curl模块
        curl_setopt($curl,CURLOPT_URL,$login_url);//登录提交的地址
        curl_setopt($curl,CURLOPT_HEADER,false);//不自动输出头信息
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);//不自动输出数据
        curl_setopt($curl,CURLOPT_COOKIEJAR,$cookie);//设置Cookie信息保存在指定的文件中
        curl_setopt($curl,CURLOPT_POST,1);//post方式提交
        curl_setopt($curl,CURLOPT_POSTFIELDS,$post);//要提交的信息
        curl_exec($curl);//执行cURL
        curl_close($curl);//关闭cURL资源，并且释放系统资源


        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,false);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_COOKIEFILE,$cookie);//读取cookie
        $html = curl_exec($ch);//执行cURL抓取页面内容
        curl_close($ch);

        unlink($cookie);

        $html=preg_replace("/[\t\n\r]+/","",$html);

        $partern = '/<form role="form" method="post" class="order_post_form" action=".*?">(.*?)<\/form>/';

        preg_match_all($partern,$html,$result);

        $html = $result[1][0];

        $partern = '/<li>(.*?)<input type="hidden"/';

        preg_match_all($partern,$html,$result);

        $html = $result[1][0];

        $partern = '/<span class="fixed-width-right-80">(.*?)：<\/span>/';

        preg_match_all($partern,$html,$result);

        $params_title = $result[1];

        $partern = '/<input.*?name="(.*?)".*?>/';

        preg_match_all($partern,$html,$result);

        $params_name = $result[1];

        $order_params = [];

        foreach($params_name as $key => $val){
            if($val != 'need_num_0'){
                $order_params[] = [
                    'name' => $val,
                    'title' => $params_title[$key]
                ];
            }
        }

        return $order_params;
    }


}
