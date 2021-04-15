<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Http;
use think\Cache;
use think\Db;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class Plugin extends Backend {

    public $domain = "http://www.hmy3.com";

    /**
     * Plugin模型对象
     * @var \app\admin\model\Plugin
     */
    protected $model = null;

    public function _initialize(){
        parent::_initialize();
        $this->model = new \app\admin\model\Plugin;

    }

    public function import(){
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 插件设置
     */
    public function setting(){
        $plugin = $this->request->param('plugin_name');

        $plugin_path = ROOT_PATH . "public/content/plugin/{$plugin}/";

        if($this->request->isPost()){
            $post = $this->request->post("row/a");
            $post = json_encode($post);
            file_put_contents("{$plugin_path}{$plugin}_setting.json", $post);
            $this->success('操作成功');
        }

        $info = file_get_contents("{$plugin_path}{$plugin}_setting.json");
        $info = json_decode($info, true);
        $this->assign([
            'info' => $info
        ]);
        return view("{$plugin_path}{$plugin}_setting.php");
    }

    /**
     * 安装插件
     */
    public function install(){
        $id = $this->request->param('ids');


        //获取插件信息
        $result = json_decode(hmCurl($this->domain . '/api/plugin/detail/id/' . $id), true);
        $info = $result['data'];
        if($this->version != '开发版' && $this->version < $info['support']){
            $this->error('当前程序版本过低，请更新程序');
        }


        $dir = ROOT_PATH . "runtime/plugin/"; //插件本地临时存储路径

        if (!file_exists($dir)) { //新建文件夹用来放置下载的更新包
            mkdir($dir, 0777, true);
        }
        $filename = $info['english_name'] . '.zip';
        /**
         * 下载插件压缩包到本地并赋值文件路径变量
         */
        $file_url = $this->domain . $info['file'];
        $path = file_exists($dir . $filename) ? $dir . $filename : $this->download_file($file_url, $dir, $filename);

        $zip = new \ZipArchive();

        //打开压缩包
        if ($zip->open($path) === true) {
            $toPath = ROOT_PATH;
            try {
                //解压文件到toPath路径下
                $zip->extractTo($toPath . 'public/content/plugin');
                $zip->close();
                unlink($path);
            } catch (\Exception $e) {
                echo $e->getMessage();die;
                $this->error("没有该目录[" . $toPath . "]的写入权限");
            }

            $this->success('安装成功');

        } else {
            unlink($path);
            $this->error("压缩包解压失败，请重试！");
        }
    }


    /**
     * 远程下载文件到本地
     */
    public function download_file($url, $dir, $filename = '') {
        if (empty($url)) {
            return false;
        }
        $ext = strrchr($url, '.');
        $dir = realpath($dir);
        //目录+文件
        $filename = (empty($filename) ? '/' . time() . '' . $ext : '/' . $filename);
        $filename = $dir . $filename;
        //开始捕捉
        ob_start();
        readfile($url);
        $img = ob_get_contents();
        ob_end_clean();
        $size = strlen($img);
        $fp2 = fopen($filename, "a");
        fwrite($fp2, $img);
        fclose($fp2);
        return $filename;
    }

    /**
     * 卸载插件
     */
    public function del($ids = ''){
        Db::startTrans();
        try {
            $plugin = $this->request->param('plugin_name');
            if(empty($plugin) || $plugin == 'undefined'){
                throw new \Exception('插件不存在');
            }
            $value = $plugin . '/' . $plugin . '.php';
            $active_plugins = Db::name('options')->where(['option_name' => 'active_plugin'])->value('option_content');
            $active_plugins = empty($active_plugins) ? [] : unserialize($active_plugins);

            foreach($active_plugins as $key => $val) {
                if ($value == $val){
                    unset($active_plugins[$key]);
                }
            }

            db::name('options')->where(['option_name' => 'active_plugin'])->update(['option_content' => serialize($active_plugins)]);

            rmdirs(ROOT_PATH . 'public/content/plugin/' . $plugin);

            db::commit();
        }catch (\Exception $e){
            db::rollback();
            $this->error($e->getMessage());
        }

        $this->success('已卸载');

    }

    /**
     * 启用插件
     */
    public function enable(){
        $plugin = $this->request->param('plugin');
        $value = $plugin . '/' . $plugin . '.php';
        $active_plugins = Db::name('options')->where(['option_name' => 'active_plugin'])->value('option_content');
        $active_plugins = empty($active_plugins) ? [] : unserialize($active_plugins);
        if (!in_array($value, $active_plugins)){
            $active_plugins[] = $value;
        }
        db::name('options')->where(['option_name' => 'active_plugin'])->update(['option_content' => serialize($active_plugins)]);
        return json(['code' => 200, 'msg' => '已启用']);
    }

    /**
     * 禁用插件
     */
    public function disable(){
        $plugin = $this->request->param('plugin');
        $value = $plugin . '/' . $plugin . '.php';
        $active_plugins = Db::name('options')->where(['option_name' => 'active_plugin'])->value('option_content');
        $active_plugins = empty($active_plugins) ? [] : unserialize($active_plugins);

        foreach($active_plugins as $key => $val) {
            if ($value == $val){
                unset($active_plugins[$key]);
            }
        }

        db::name('options')->where(['option_name' => 'active_plugin'])->update(['option_content' => serialize($active_plugins)]);
        return json(['code' => 200, 'msg' => '已禁用']);
    }

    /**
     * 插件市场
     */
    public function cjsc(){
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()){
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')){
                return $this->selectpage();
            }
            $url = $this->domain . "/api/plugin/list";
            // echo $url;die;
            $result = json_decode(hmCurl($url), true);



            $hmPlugins = [];
            $pluginFiles = [];
            $pluginPath = ROOT_PATH . 'public/content/plugin';
            //            echo $pluginPath;die;
            $pluginDir = @dir($pluginPath);

            if ($pluginDir){
                while (($file = $pluginDir->read()) !== false) {
                    if (preg_match('|^\.+$|', $file)){
                        continue;
                    }
                    if (is_dir($pluginPath . '/' . $file)){
                        $pluginsSubDir = @ dir($pluginPath . '/' . $file);
                        if ($pluginsSubDir){
                            while (($subFile = $pluginsSubDir->read()) !== false) {
                                if (preg_match('|^\.+$|', $subFile)){
                                    continue;
                                }
                                if ($subFile == $file . '.php'){
                                    $pluginFiles[] = "$file/$subFile";
                                }
                            }
                        }
                    }
                }
            }

            sort($pluginFiles);

            $active_plugins = Db::name('options')->where(['option_name' => 'active_plugin'])->value('option_content');
            $active_plugins = empty($active_plugins) ? [] : unserialize($active_plugins);


            foreach($pluginFiles as $key => $pluginFile) {
                $pluginData = $this->getPluginData($pluginFile, $key + 1);
                if (empty($pluginData['name'])){
                    continue;
                }
                $pluginData['status'] = in_array($pluginFile, $active_plugins) ? 'enable' : 'disable';
                $hmPlugins[] = $pluginData;
            }


            foreach($result['data'] as &$val){
                $val['install'] = false;
                foreach($hmPlugins as $v){
                    if($val['english_name'] == $v['plugin']){
                        $val['install'] = true;
                        $val['setting'] = $v['setting'];
                        $val['plugin'] = $v['plugin'];
                    }
                }
            }


            $result = ["total" => $result['total'], "rows" => $result['data']];

            return json($result);


            $result = ["total" => count($hmPlugins), "rows" => $hmPlugins];

            return json($result);
        }

        return $this->view->fetch();
    }



    /**
     * 查看
     */
    public function index(){
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()){
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')){
                return $this->selectpage();
            }

            $hmPlugins = [];
            $pluginFiles = [];
            $pluginPath = ROOT_PATH . 'public/content/plugin';
            $pluginDir = @dir($pluginPath);

            if ($pluginDir){
                while (($file = $pluginDir->read()) !== false) {
                    if (preg_match('|^\.+$|', $file)){
                        continue;
                    }
                    if (is_dir($pluginPath . '/' . $file)){
                        $pluginsSubDir = @ dir($pluginPath . '/' . $file);
                        if ($pluginsSubDir){
                            while (($subFile = $pluginsSubDir->read()) !== false) {
                                if (preg_match('|^\.+$|', $subFile)){
                                    continue;
                                }
                                if ($subFile == $file . '.php'){
                                    $pluginFiles[] = "$file/$subFile";
                                }
                            }
                        }
                    }
                }
            }

            $active_plugins = Db::name('options')->where(['option_name' => 'active_plugin'])->value('option_content');
            $active_plugins = empty($active_plugins) ? [] : unserialize($active_plugins);


            foreach($pluginFiles as $key => $pluginFile) {
                $pluginData = $this->getPluginData($pluginFile, $key + 1);
                if (empty($pluginData['name'])){
                    continue;
                }
                $pluginData['status'] = in_array($pluginFile, $active_plugins) ? 'enable' : 'disable';
                $hmPlugins[] = $pluginData;
            }

            $result = ["total" => count($hmPlugins), "rows" => $hmPlugins];

            return json($result);
        }

        return $this->view->fetch();
    }


    /**
     * 获取插件信息
     *
     * @param string $pluginFile
     * @return array
     */
    function getPluginData($pluginFile, $key){
        $pluginPath = ROOT_PATH . 'public/content/plugin/';
        $pluginData = implode('', file($pluginPath . $pluginFile));
        preg_match("/Plugin Name:(.*)/i", $pluginData, $plugin_name);
        preg_match("/Version:(.*)/i", $pluginData, $version);
        preg_match("/Plugin URL:(.*)/i", $pluginData, $plugin_url);
        preg_match("/Description:(.*)/i", $pluginData, $description);
        preg_match("/ForEmlog:(.*)/i", $pluginData, $foremlog);
        preg_match("/Author:(.*)/i", $pluginData, $author_name);
        preg_match("/Author URL:(.*)/i", $pluginData, $author_url);


        $ret = explode('/', $pluginFile);
        $plugin = $ret[0];
        $setting = file_exists($pluginPath . $plugin . '/' . $plugin . '_setting.php') ? true : false;

        $plugin_name = isset($plugin_name[1]) ? strip_tags(trim($plugin_name[1])) : '';
        $version = isset($version[1]) ? strip_tags(trim($version[1])) : '';
        $description = isset($description[1]) ? strip_tags(trim($description[1])) : '';
        $plugin_url = isset($plugin_url[1]) ? strip_tags(trim($plugin_url[1])) : '';
        $author = isset($author_name[1]) ? strip_tags(trim($author_name[1])) : '';
        $foremlog = isset($foremlog[1]) ? strip_tags(trim($foremlog[1])) : '';
        $author_url = isset($author_url[1]) ? strip_tags(trim($author_url[1])) : '';

        return [
            'id' => $key,
            'name' => $plugin_name,
            'version' => $version,
            'description' => $description,
            'url' => $plugin_url,
            'author' => '<a href="' . $author_url . '" target="_blank">' . $author . '</a>',
            'forEmlog' => $foremlog,
            'authorUrl' => $author_url,
            'setting' => $setting, 'plugin' => $plugin
        ];
    }

}
