<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

use think\Route;

//页面
Route::get('upgrade','index/index/upgrade'); //升级

Route::get('/','shop/index/index'); //首页
Route::get('category/:category_id','shop/index/index'); //分类商品列表
Route::get('category','shop/category/index'); //分类
Route::get('service','shop/service/index'); //客服
Route::get('login','shop/login/index'); //登录
Route::get('register','shop/register/index'); //注册
Route::get('user','shop/user/index'); //个人中心
Route::get('notice','shop/notice/index'); //站内消息
Route::get('balance','shop/wallet/balance'); //余额
Route::get('setting','shop/user/setting'); //设置
Route::get('logout','shop/user/logout'); //退出登录
Route::get('cashout','shop/wallet/cashout'); //提现
Route::get('recharge/[:money]','shop/wallet/recharge'); //充值
Route::get('bill','shop/wallet/bill'); //账单
Route::get('goods/:id','shop/goods/detail'); //商品详情
Route::rule('avatar/[:id]/[:name]/[:type]/[:lastModifiedDate]/[:size]/[:file]','shop/user/avatar'); //上传头像
Route::rule('nickname','shop/user/nickname'); //修改昵称
Route::rule('gender','shop/user/gender'); //修改性别
Route::rule('email','shop/user/email'); //绑定邮箱
Route::rule('alipay','shop/user/alipay'); //绑定支付宝
Route::rule('order/[:search_type]','shop/order/index'); //订单中心
Route::get('list/:category','shop/goods/lists'); //商品列表
Route::rule('buy/:goods_id','shop/buy/detail'); //提交订单
Route::rule('buy_order/:order_id','shop/buy/detail'); //提交订单
Route::rule('aliprecreate/:out_trade_no/:table/[:img]','shop/pay.alipay/aliprecreate'); //支付宝当面付
Route::rule('aliprecreate','shop/pay.alipay/aliprecreate'); //支付宝当面付
Route::rule('getorderstatus/:out_trade_no/:table','shop/order/getorderstatus'); //获取订单支付状态
Route::rule('tourist_key','shop/index/get_tourist_key'); //获取游客标识
Route::rule('tourist_login','shop/index/tourist_login'); //游客登录
Route::rule('orderContent/:order_id','shop/order/orderContent'); //查看订单内容
Route::rule('confirm','shop/buy/confirm'); //确认订单页面
Route::rule('pay','shop/buy/pay'); //提交支付
Route::rule('notify','shop/notify/index'); //回调通知地址

//接口
Route::post('login','shop/login/index'); //登录
Route::post('register','shop/register/index'); //注册

Route::rule("module/:func", "shop/module/index"); //分配方法


return [
    //别名配置,别名只能是映射到控制器且访问时必须加上请求的方法
    '__alias__'   => [
    ],
    //变量规则
    '__pattern__' => [
    ],
//        域名绑定到模块
//        '__domain__'  => [
//            'admin' => 'admin',
//            'api'   => 'api',
//        ],
];
