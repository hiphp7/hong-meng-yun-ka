<?php

namespace app\shop\controller;

use app\common\controller\Fun;
use think\Db;
use think\Session;

class Index extends Base {

    public function neice(){
        return view();
    }

    public function index() {
//        echo session::get("uid");die;
//        echo $this->template_path;die;
        $category_id = $this->request->has('category_id') ? $this->request->param('category_id') : 0;
        $this->assign([
            'page' => 'index',
            'category_id' => $category_id
        ]);
        return view($this->template_path . "index.html");
    }


    /**
     * 游客登录
    */
    public function tourist_login(){
        $tourist = $this->request->param("tourist");
        $user = db::name("user")->where(["tourist" => $tourist])->find();
        if(!$user){
            return $this->get_tourist_key();
        }
        session::set("tourist", $user["id"]);
        return json(["msg" => "老游客已登录", "code" => 200, "data" => ""]);
    }

    /**
     * 获取游客标识
    */
    public function get_tourist_key(){
        db::startTrans();
        $tourist_key = time() . mt_rand(10000, 99999);
        $tourist_num = db::name('options')->where(['option_name' => 'tourist_num'])->value("option_content");
        $tourist_num++;
        $insert = [
            "nickname" => "游客" . $tourist_num,
            "tourist" => $tourist_key,
            "createtime" => time(),
        ];
        $uid = db::name("user")->insertGetId($insert);
        session::set("tourist", $uid);
        db::name("options")->where(["option_name" => "tourist_num"])->setInc("option_content");
        db::commit();
        return json(['data' => $tourist_key, 'msg' => '新游客已登录', 'code' => 200]);
    }

}
