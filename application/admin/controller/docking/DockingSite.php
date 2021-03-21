<?php

namespace app\admin\controller\docking;

use app\common\controller\Backend;
use fast\Http;
use think\Cache;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 对接站点管理
 *
 * @icon fa fa-circle-o
 */
class DockingSite extends Backend
{

    /**
     * DockingSite模型对象
     * @var \app\admin\model\docking\DockingSite
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\docking\DockingSite;

        $type = [
            [
                'name' => '玖伍社区',
                'value' => 'jiuwu',
            ]

        ];

        $where = [
            'type' => 'goods',
        ];
        $category = db::name('category')->where($where)->select();
        $attach = db::name('attach')->select();
        $this->assign([
            'category' => $category,
            'attach' => $attach
        ]);

        $this->assign('type', $type);

    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 同步商品
     */
    public function sync()
    {
        $site_id = $this->request->param('site_id'); //对接站id
        $site = db::name('docking_site')->where(['id' => $site_id])->find();
        $goods_id = $this->request->param('ids'); //对接站商品id
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $params['site_id'] = $site_id; //对接站点id
                    $params['remote_id'] = $goods_id; //对接站商品id
                    $params['type'] = $site['type'];


                    if($params['num'] < $params['min_buy_num']){
                        throw new Exception("默认数量不能小于最小下单量");
                    }
                    if($params['num'] > $params['max_buy_num']){
                        throw new Exception("默认数量不能大于最大下单量");
                    }

                    if ($params['price'] < $params['buy_price']) {
                        throw new Exception('售价不能低于进货价');
                    }

                    if($params['default_num'] / $params['buy_price'] < $params['num'] / $params['price']){
                        throw new Exception('售价不能低于进货价');
                    }

                    $order_params = unserialize(base64_decode($params['order_params']));

                    $dock_data = [ //对接订单所需数据
                        'order_params' => $order_params, //订单所需参数信息
                        'num' => $params['num'], //购买数量
                        'goods_type' => $params['goods_type'],
                    ];
                    $params['dock_data'] = json_encode($dock_data);
                    $params['stock'] = -1; //该库存代表对接站没有库存字段，则显示正常字样
                    $params['createtime'] = time();

                    unset($params['goods_type']);
                    unset($params['order_params']);
                    unset($params['num']);
                    unset($params['default_num']);



//                    echo '<pre>'; print_r($params);die;




//                    print_r($params);die;

                    $result = db::name('goods')->insert($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $list = $this->get_goods_list($site_id); //获取对接站所有商品

        //根据对接站商品id获取商品详情
        $key = array_search($goods_id, array_column($list, 'id'));
        $goods = $list[$key];


        $url = "{$site['domain']}index.php?m=home&c=goods&a=detail&id={$goods['id']}&goods_type={$goods['goods_type']}";

        $order_params = Dock::getParams("jiuwu", $url, $site);

        //获取加价模板列表
        $increase = db::name('docking_increase')->select();

        //对接站信息
        $docking_site = db::name('docking_site')->where(['id' => $site_id])->find();



        $this->assign([
            'goods' => $goods,
            'increase' => $increase,
            'docking_site' => $docking_site,
            'order_params' => base64_encode(serialize($order_params)),
        ]);

//        echo '<pre>'; print_r($order_params);die;

        return view();
    }


    public function demo() {
        //开始模拟登录
        $url = "http://www.pinow.cn/index.php?m=Home&c=User&a=login";
        $cookie = dirname(__FILE__) . '/jiuwu' . time() . '.txt';

        $post = "username=vsiis&username_password=97882032&id=392&goods_type=765";


        $curl=curl_init();//初始化curl模块
        curl_setopt($curl,CURLOPT_URL,$url);//登录提交的地址
        curl_setopt($curl,CURLOPT_HEADER,false);//不自动输出头信息
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);//不自动输出数据
        curl_setopt($curl,CURLOPT_COOKIEJAR,$cookie);//设置Cookie信息保存在指定的文件中
        curl_setopt($curl,CURLOPT_POST,1);//post方式提交
        curl_setopt($curl,CURLOPT_POSTFIELDS,$post);//要提交的信息
        curl_exec($curl);//执行cURL
        curl_close($curl);//关闭cURL资源，并且释放系统资源


        $ch=curl_init();
        curl_setopt($ch,CURLOPT_URL,"http://www.pinow.cn/index.php?m=Home&c=Goods&a=detail&id=392&goods_type=765");
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


    }


    /**
     * 通过对接站id获取商品列表
     */
    public function get_goods_list($id) {
        $site = db::name('docking_site')->where(['id' => $id])->find();
        $info = json_decode($site['info'], true);

        $domain = $site['domain'];

        if (Cache::has('goods_list_' . $domain)) {
            $list = Cache::get('goods_list_' . $domain);
        } else {
            if ($site['type'] == 'jiuwu') {
                $url = $domain . "index.php?m=home&c=api&a=get_goods_lists";
                $account = $info['account'];
                $password = md5($info['password']);
                $url = $domain . 'index.php?m=home&c=api&a=user_get_goods_lists_details&Api_UserName=' . $account . '&Api_UserMd5Pass=' . $password;
                $result = file_get_contents($url);
                $result = json_decode($result, true);
                $list = $result['user_goods_lists_details'];
                $list = $this->handle_list_wujiu($list);
                Cache::set('goods_list_' . $domain, $list);
            }
        }
        return $list;
    }

    /**
     * 商品列表
     */
    public function goods_list()
    {

        $ids = $this->request->param('ids');

        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            $post = $this->request->param();
//            print_r($post);die;
            $list = $this->get_goods_list($ids);


            $total = count($list);

            $result = ["total" => $total, "rows" => $list];
            return json($result);
        }

        $this->assign([
            'id' => $ids
        ]);
        return $this->view->fetch();
    }

    public function handle_list_wujiu($list)
    {
        foreach ($list as &$val) {
            $price = $val['goods_unitprice'];
            $price_info = $this->calc_price($price, 1, $val['minbuynum_0']);
            $val['num'] = $price_info['num'];
            $val['price'] = upDecimal($price_info['price']);
            $look_num = $this->look_num($val['num']);
            $val['look_price'] = $look_num . $val['unit'] . '=' . $val['price'] . '元';
        }

        return $list;
    }

    public function look_num($num)
    {
        if ($num == 1000) {
            return '1千';
        } else if ($num == 10000) {
            return '1万';
        } else if ($num == 100000) {
            return '10万';
        } else {
            return $num;
        }
    }

    /**
     * 计算点数价格
     */
    public function calc_price($price, $num = 1, $min)
    {
        $num *= 10;
        $price *= 10;

        if ($price < 0.1 || $num <= $min) {
            return $this->calc_price($price, $num, $min);
        } else {
            $num /= 10;
            $price /= 10;
        }
        return [
            'num' => $num,
            'price' => $price
        ];

    }


    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $params = $this->handle_params($params);
                    $result = $this->model->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $params = $this->handle_params($params);
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $info = json_decode($row->info, true);
        if ($row->type == 'jiuwu') {
            $row->account = empty($info['account']) ? '' : $info['account'];
            $row->password = empty($info['password']) ? '' : $info['password'];
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    public function handle_params($params)
    {
        if ($params['type'] == 'jiuwu') { //玖伍社区
            $info = [
                'account' => $params['account'],
                'password' => $params['password']
            ];
            unset($params['account']);
            unset($params['password']);
            $params['info'] = json_encode($info);
        }
        return $params;
    }


}
