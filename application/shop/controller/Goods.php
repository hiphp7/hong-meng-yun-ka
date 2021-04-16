<?php

namespace app\shop\controller;
use app\common\controller\Hm;
use think\Db;

class Goods extends Base {

    //
    public function detail(){
        if(!$this->request->has('id')){
            $this->error('参数错误');
        }
        $goods_id = $this->request->param('id');
        $goods = Hm::getGoodsInfo($goods_id);
        if(!$goods){
            $this->error('商品不存在！');
        }
//        echo '<pre>'; print_r($goods);die;
        $user = Hm::getUser();
        $order = db::name('order')->where(['uid' => $user['id']])->order('id desc')->find();

        $this->assign([
            'title' => $goods['name'],
            'goods' => $goods,
            'order' => $order,
        ]);
        return view($this->template_path . "goods.html");

    }


    //分类下商品列表
    public function lists() {

        if($this->request->isAjax()){
            $post = $this->request->param();
            $order = 'id desc';
            $where = [
                'deletetime' => null,
                'category_id' => empty($post['category']) ? 0 : $post['category']
            ];
            $list = db::name('goods')->where($where)->order($order)->limit($post['offset'], $post['limit'])->select();

            $goods_azf_all = Fun::getGoodsAzfAll();
            $azf_ids = array_column($goods_azf_all, 'id');

            $goods_list = [];
            foreach($list as &$val){
                if($val['type'] == 'own'){
                    $images = explode(',', $val['images']);
                    $val['cover'] = $images[0];
                    $goods_list[] = $val;
                }else if($val['type'] == 'azf'){
                    $key = array_search($val['remote_id'], $azf_ids);
                    if($key){
                        $azf_goods_info = $goods_azf_all[$key];
                        $val['name'] = $azf_goods_info['goodsname'];
                        $val['cover'] = $azf_goods_info['imgurl'];
                        $val['sales'] = $azf_goods_info['salesvolume'];
                        $goods_list[] = $val;
                    }

                }
            }
            $this->success('ok', $goods_list);
        }


        $category_nickname = $this->request->has('category') ? $this->request->param('category') : null;

        $category_info = [];
        if($category_nickname){
            $where = [
                'type' => 'goods',
                'nickname' => $category_nickname
            ];
            $category_info = db::name('category')->where($where)->find();
        }


        $this->assign([
            'category_nickname' => $category_nickname,
            'category_info' => $category_info,
            'footer_active' => 'category',
        ]);

        return view($this->template_path . "list.html");
    }

}
