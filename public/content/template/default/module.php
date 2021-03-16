<?php

use think\Db;
use think\Cache;
use think\Session;
use app\shop\controller\Hm;



/**
 * 获取模板配置
*/
function get_config(){
    $template_config = file_get_contents(ROOT_PATH . "public/content/template/default/setting.json");
    $template_config = json_decode($template_config, true);
    return $template_config;
}


/**
 * 获取订单列表
*/
function order_list(){
    $params = [
        'offset' => input('offset'),
        'limit' => input('limit'),
    ];
    $order = Hm::orderList($params);

    echo $order;die;

}

/**
 * 获取可支付列表
 */
function pay_list(){
	$where = [
		'status' => 1,
	];
	$list = [
		'alipay' => false,
		'wxpay' => false,
		'qqpay' => false,
	];

	$pay = db::name('pay')->where($where)->select();

//	echo '<pre>'; print_r($pay);die;

	foreach($pay as $key => $val){
		$pay_info = json_decode($val['value'], true);
		if($val['type'] == 'alipay' || isset($pay_info['alipay'])){
			$list['alipay'] = true;
		}
		if(isset($pay_info['wxpay'])){
			$list['wxpay'] = true;
		}

		if(isset($pay_info['qqpay'])){
			$list['qqpay'] = true;
		}
	}



	return $list;

}


/**
 * 获取单个商品信息
*/
function goods_info($id, $field = "goods_id"){
//    var_dump($id);die;
    if($field == "goods_id"){
        $goods = db::name('goods')->where(['id' => $id])->find();
    }
    if($field == "order_no"){

    }

//echo '<pre>'; print_r($goods);die;

    $images = empty($goods["images"]) ? '' : explode(',', $goods['images']);
    $goods['cover'] = empty($images[0]) ? '' : $images[0];
    $attach = [];
    if($goods['attach_id'] > 0 && $goods['deliver'] == 1){
        $attach = db::name('attach')->where(['id' => $goods['attach_id']])->find();
        if($attach){
            $attach = json_decode($attach['value_json'], true);
        }
    }
    $goods['attach'] = $attach;
//    echo '<pre>';
//    print_r($goods);die;
    return $goods;
}

/**
 * 首页分类和商品
*/
function goods_list(){
    $category = db::name('category')->select();

    $goods = db::name('goods')->where('deletetime is null and shelf=0')->order('id desc')->select();

    $list = [];

    foreach($category as $key => $val){

		if($val['goods_sort'] == 0 || $val['goods_sort'] == 1){
			$sort_field = array_column($goods,'id');
			array_multisort($sort_field,SORT_DESC,$goods);
		}else if($val['goods_sort'] == 2){
			$sort_field = array_column($goods,'id');
			array_multisort($sort_field,SORT_ASC,$goods);
		}else if($val['goods_sort'] == 3){
			$sort_field = array_column($goods,'price');
			array_multisort($sort_field,SORT_DESC,$goods);
		}else if($val['goods_sort'] == 4){
			$sort_field = array_column($goods,'price');
			array_multisort($sort_field,SORT_ASC,$goods);
		}else if($val['goods_sort'] == 5){
			$sort_field = array_column($goods,'sales');
			array_multisort($sort_field,SORT_DESC,$goods);
		}else if($val['goods_sort'] == 6){
			$sort_field = array_column($goods,'sales');
			array_multisort($sort_field,SORT_ASC,$goods);
		}




        $list[$key] = $val;
        $list[$key]['goods'] = [];
        $list[$key]['goods_num'] = 0;
        foreach($goods as $k => &$v){
            if($val['id'] == $v['category_id']){

                if($v['stock'] == -1){
                    $v['stock'] = '正常';
                }

				$cover_arr = explode(',', $v['images']);
				$v["cover"] = isset($cover_arr[0]) ? $cover_arr[0] : "";
				unset($v["images"]);

                $list[$key]['goods'][] = $v;
                unset($goods[$k]);
                $list[$key]['goods_num']++;




            }
        }
    }



    return $list;

}
