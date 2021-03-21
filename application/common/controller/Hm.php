<?php

namespace app\common\controller;


use think\Cache;
use think\Db;
use think\Session;

/**
 * 公共方法类
 */
class Hm{

    protected static $appid_azf = 10842;

    protected static $secret_key_azf = 'd565e95e935e324441f730d46207e914';

    protected static $api_azf = 'http://azf.5bma.cn/';


    static public function pre($arr){
        echo '<pre>'; print_r($arr);die;
    }

    static public function getGoodsInfo($goods_id){
        $goods = db::name('goods')->where(['id' => $goods_id])->find();

        if(!$goods){
            return null;
        }
        $goods['images'] = explode(',', $goods['images']);
        $goods['cover'] = $goods['images'][0];

        if($goods['type'] == 'own'){

            $attach = [];
            if($goods['attach_id'] > 0 && $goods['deliver'] == 1){
                $attach = db::name('attach')->where(['id' => $goods['attach_id']])->find();
                if($attach){
                    $attach = json_decode($attach['value_json'], true);
                }
            }
            $goods['attach'] = $attach;
        }
        if($goods['type'] == 'jiuwu'){
            $dock_data = json_decode($goods['dock_data'], true);
            $goods['order_params'] = empty($dock_data['order_params']) ? [] : $dock_data['order_params'];
        }
        return $goods;
    }

    /**
     * 获取当前登录用户或游客信息
     */
    static public function getUser(){
        if(session::has('uid')){ //已登录用户
            $user = db::name('user')->where(['id' => session::get('uid')])->find();
            if(!$user){
                session::delete('uid');
                self::getUser();
            }
        }else{ //游客
            if(session::has('tourist_id')){ //已生成游客id
                $user = db::name('user')->where(['id' => session::get('tourist_id')])->find();
                if(!$user){
                    session::delete('tourist_id');
                    self::getUser();
                }
            }else{ //未生成游客id
                $tourist = cookie('tourist');
                if($tourist){ //老游客查找
                    $user = db::name('user')->where(['tourist' => $tourist])->find();
                    if(!$user){
                        cookie('tourist', null);
                        self::getUser();
                    }
                }else{ //新游客生成
                    $timestamp = time();
                    $tourist = $timestamp . mt_rand(1000, 9999); //游客标识
                    cookie('tourist', $tourist, $timestamp + 365 * 24 * 3600);
                    $tourist_num = db::name('options')->where(['option_name' => 'tourist_num'])->value('option_content');
                    $tourist_num++;
                    db::name('options')->where(['option_name' => 'tourist_num'])->setInc('option_content');
                    $insert = [
                        'tourist' => $tourist,
                        'nickname' => '游客' . $tourist_num,
                        'createtime' => $timestamp
                    ];
                    db::name('user')->insert($insert);
                    $user = db::name('user')->where(['tourist' => $tourist])->find();
                }
                session::set('tourist_id', $user['id']);
            }
        }
        return $user;
    }


    //获取指定的爱转发商品信息
    static public function getGoodsAzfInfo($goods_azf_all = null, $id){

        $ids = array_column($goods_azf_all, 'id');
        $key = array_search($id, $ids);
        $goods_azf_info = $goods_azf_all[$key];
        return $goods_azf_info;
    }

    //获取所有爱转发商品信息
    static public function getGoodsAzfAll(){
        if(Cache::has('goods_azf')){
            $goods_azf = Cache::get('goods_azf');
        }else{
            $data = [
                'userid' => self::$appid_azf,
            ];
            $data['sign'] = self::getSign($data);

//            $result = Http::get(self::$api_azf . 'dockapi/index/getallgoods.html', $data);
            $result = [];
            if(!$result){
                return [];
            }
            $result = json_decode($result, true);
            if($result['code'] == -1){
                return [];
            }
            $list = $result['data'];

            $goods_azf = [];
            foreach($list as $val){
                foreach($val['goods'] as &$v){
                    $v['category_name'] = $val['groupname'];
                    $v['goodsprice'] /= 100;
                    $v['goodsprice'] = number_format($v['goodsprice'], 2);
                    $goods_azf[] = $v;
                }
            }
            Cache::set('goods_azf', $goods_azf, 3600);
        }
        return $goods_azf;
    }


    static private function getSign($data){
        ksort($data);
        $signtext='';
        foreach ($data AS $key => $val) { //遍历POST参数
            if ($val == '' || $key == 'sign') continue; //跳过这些不签名
            if ($signtext) $signtext .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
            $signtext .= "$key=$val"; //拼接为url参数形式
        }
        $newsign=md5($signtext . self::$secret_key_azf);
        return $newsign;
    }

}
