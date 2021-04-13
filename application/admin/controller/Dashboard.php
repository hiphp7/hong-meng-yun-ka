<?php

namespace app\admin\controller;

use app\admin\model\User;
use app\admin\model\Goods;
use app\common\controller\Backend;
use think\Cache;
use think\Config;
use think\Db;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend {

    /**
     * 查看
     */
    public function index() {
        $seventtime = \fast\Date::unixtime('day', -7);
        $paylist = $createlist = [];
        for ($i = 0; $i < 7; $i++) {
            $day = date("Y-m-d", $seventtime + ($i * 86400));
            $createlist[$day] = mt_rand(20, 200);
            $paylist[$day] = mt_rand(1, mt_rand(1, $createlist[$day]));
        }
        $hooks = config('addons.hooks');
        $uploadmode = isset($hooks['upload_config_init']) && $hooks['upload_config_init'] ? implode(',', $hooks['upload_config_init']) : 'local';
        $addonComposerCfg = ROOT_PATH . '/vendor/karsonzhang/fastadmin-addons/composer.json';
        Config::parse($addonComposerCfg, "json", "composer");
        $config = Config::get("composer");
        $addonVersion = isset($config['version']) ? $config['version'] : __('Unknown');
        db::startTrans();


        //自定义配置信息
        $options = [];
        $options_result = db::name("options")->select();
        foreach ($options_result as $key => $val) {
            $options[$val['option_name']] = $val['option_content'];
        }

        $timestamp = time();
        $start_time = strtotime(date("Y-m-d 00:00:00", $timestamp));
        $end_time = strtotime(date("Y-m-d 23:59:59", $timestamp));

        //今日注册人数
        $where = "createtime >= {$start_time} and createtime <= {$end_time}";
        $today_register = db::name("user")->where($where . "  and `tourist` IS NULL")->field('id')->count();
        //分类总数
        $category_total = db::name("category")->field("id")->count();
        //今日订单数量
        $today_order_result = db::name("order")->where($where . " and status>0")->field("id, status, goods_money, money, goods_num, remote_money")->select();
        $today_order = count($today_order_result);
        //今日待处理订单
        $today_wait_order = 0;
        //今日成交金额
        $today_order_money = 0;
        //今日盈利金额
        $today_order_profit = 0;
        foreach ($today_order_result as $val) {
            if ($val["status"] == 1) {
                $today_wait_order++;
            }
            $today_order_money += $val["money"];
            $today_order_profit += $val["money"] - $val["remote_money"];
        }

        //商品销量top10
        $goods_list = db::name("goods")->where('deletetime is null and sales > 0')->order("sales desc")->limit(10)->select();
        foreach ($goods_list as &$val) {
            $images = explode(",", $val["images"]);
            $val["cover"] = $images[0];
        }

        //用户消费top10
        $user_list = User::withCount('order')->where("consume > 0")->limit(10)->select();


        db::commit();

        $version = $this->version;
        $upgrade_url = "http://www.hmy3.com/api/upgrade/check_upgrade/type/shop/version/" . $version;

        if(Cache::has('upgrade_result')){
            $result = Cache::get('upgrade_result');
        }else{
            try {
                $result = json_decode(file_get_contents($upgrade_url), true);
            }catch (\Exception $e){
                $result = [];
            }
            Cache::set('upgrade_result', $result, 3600 * 12);
        }




        if (empty($result) || $result["code"] == 400) {
            $this->assign("upgrade", false);
        } else {
            $upgrade_data = $result["data"];
            $this->assign("upgrade", true);
            $this->assign("new_version", $upgrade_data);
        }



        $this->view->assign([
            'version' => $version,
            "options" => $options,
            "today_register" => $today_register,
            "category_total" => $category_total,
            "today_order" => $today_order,
            "today_wait_order" => $today_wait_order,
            "today_order_money" => $today_order_money,
            "today_order_profit" => $today_order_profit,
            "goods_list" => $goods_list,
            "user_list" => $user_list,
        ]);

        return $this->view->fetch();
    }





}
