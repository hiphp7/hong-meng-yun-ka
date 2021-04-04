<?php

namespace app\common\controller;


use think\Cache;
use think\Db;
use think\Session;

/**
 * 公共方法类
 */
class Hm{


    static public function pre($arr){
        echo '<pre>'; print_r($arr);die;
    }



    /**
     * 处理商品信息
     */
    static public function handle_goods($goods){
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
            foreach($attach as $key => &$val){
                $val = [
                    'title' => $key,
                    'name' => $key,
                    'placeholder' => $val
                ];
            }
            $goods['order_params'] = $attach;
        }
        if($goods['type'] == 'jiuwu'){
            $dock_data = json_decode($goods['dock_data'], true);
            $goods['order_params'] = empty($dock_data['order_params']) ? [] : $dock_data['order_params'];
            $goods['max_int'] = $dock_data['max_int'];
            $goods['stock'] = '正常';
            $goods['increase_id'] = empty($dock_data['increase_id']) ? 0 : $dock_data['increase_id'];
            $goods['num'] = $dock_data['num'];
        }

        return $goods;
    }

    /**
     * 获取商品信息
     */
    static public function getGoodsInfo($goods_id){
        $goods = db::name('goods')->where(['id' => $goods_id])->find();

        if(!$goods){
            return null;
        }

        $goods = self::handle_goods($goods);

        return $goods;
    }


    /**
     * 获取订单列表
     */
    static public function orderList($params = []) {

        $params = empty($params) ? input() : $params;
        $offset = $params['offset'];
        $limit = $params['limit'];
        $user = Hm::getUser();

        $where = [
            'uid' => $user['id'],
        ];

        $list = db::name('order')->where($where)->order('id desc')->limit($offset, $limit)->select();

        $timestamp = time();

        foreach($list as &$val){

            if($val['status'] == -1){
                $val['s'] = '订单已失效';
            }elseif($val['pay'] == 0 && $timestamp - $val['createtime'] >= 600){
                $val['s'] = '订单已失效';
                db::name('order')->where(['id' => $val['id']])->update(['status' => -1]);
                $val['status'] = -1;
            }elseif($val['pay'] == 0){
                $val['s'] = '待付款';
            }elseif($val['pay'] == 1 && ($val['status'] == '1' || $val['status'] == '0')){

                $val['s'] = '待发货';
            }elseif($val['pay'] == 1 && ($val['status'] == '2' || $val['status'] == 'yifahuo')){
                $val['s'] = '待收货';
            }elseif($val['pay'] == 1 && $val['status'] == '9'){
                $val['s'] = '交易完成';
                $val['s_color'] = '#52c41a';
            }else{
                $val['s'] = '订单状态错误';
                $val['s_color'] = '#d20707';
            }
            $val['timestamp'] = date('Y-m-d H:i:s', $val['createtime']);
        }
//        return $list;
        return json_encode(['data' => $list, 'info' => 'ok', 'status' => 0]);

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




}
