<?php

namespace app\admin\controller;

use think\Controller;

/**
 * 后台首页
 * @internal
 */
class Demo extends Controller {



    public function _initialize() {

        parent::_initialize();

    }


    public function index(){

        $domain = "http://www.pinow.cn/";

        $img_domain = "http://tp.pinow.cn/";

        $url = $domain . "index.php?m=home&c=api&a=get_goods_lists";

        $result = file_get_contents($url);

        $result = json_decode($result, true);

        echo '<pre>';

        print_r($result);die;


    }


}
