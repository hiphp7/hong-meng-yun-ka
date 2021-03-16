<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Http;
use think\Db;
use think\Cache;

/**
 * 系统更新类
*/
class Upgrade extends Backend {
	
	
	public $gengxin = false; //是否执行了更新
	


    public function index() {
		
		//更新包检测地址
		$version = $this->version;
        $domain = "http://cmd.hmyblog.com";
		$url = $domain . "/upgrade/shop/{$version}";
        try {
			//检测更新包
            $result = file_get_contents($url);
        } catch (\Exception $e) {
            return json(["msg" => "更新包获取失败，请重试！", "code" => 400]);
        }
        $result = json_decode($result, true);
		
		if($result["code"] == 400 && $this->gengxin = true){
		    //更新完成后刷新配置文件
            $this->refreshFile();
			return json(["msg" => "更新完成！请刷新页面", "code" => 200]);
		}
		
		//code为400的时候代表没有更新包
        if ($result["code"] == 400) {
            return json(["msg" => $result["msg"], "code" => 400]);
        }
		$this->gengxin = true;
		
		//更新包信息
        $upgrade = $result["data"];

        $file_url = $domain . $upgrade["file"]; //更新包下载地址
        $filename = basename($file_url); //更新包文件名称e2876e138e4d82e51774e9cbea8d9a10.zip

        $dir = ROOT_PATH . "runtime/upgrade/"; //更新包本地存储路径

        if (!file_exists($dir)) { //新建文件夹用来放置下载的更新包
            mkdir($dir, 0777, true);
        }

        /**
		 * 下载更新包到本地并赋值文件路径变量
		 */
        $path = file_exists($dir . $filename) ? $dir . $filename : $this->download_file($file_url, $dir, $filename);


        $zip = new \ZipArchive();
		
		//打开压缩包
        if ($zip->open($path) === true) {
            $toPath = ROOT_PATH;
            try {
                //解压文件到toPath路径下，用于覆盖差异文件
                $zip->extractTo($toPath);
            } catch (\Exception $e) {
                return json(["msg" => "没有该目录[" . $toPath . "]的写入权限", "code" => 400]);
            }
			
			//文件差异覆盖完成，开始更新数据库
			include ROOT_PATH . "/sql.php";
			
			chmod(ROOT_PATH . "/sql.php",0777);
			unlink(ROOT_PATH . "/sql.php");

			//更新后台静态文件版本
            db::name('config')->where(['name' => 'version'])->update(['value' => time()]);
			//清除站点缓存
            rmdirs(CACHE_PATH, false);
            Cache::clear();

            $this->version = $upgrade["version"]; //更新本次版本号准备检测下个版本

			$this->index(); //递归更新

        } else {
            unlink($path);
            return json(["msg" => "更新包解压失败，请重试！", "code" => 400]);
        }

    }


    /**
     * 刷新配置文件
     */
    protected function refreshFile(){
        $config = [];
        $list = db::name('config')->select();
        foreach ($list as $k => $v) {
            $value = $v;
            if (in_array($value['type'], ['selects', 'checkbox', 'images', 'files'])) {
                $value['value'] = explode(',', $value['value']);
            }
            if ($value['type'] == 'array') {
                $value['value'] = (array)json_decode($value['value'], true);
            }
            $config[$value['name']] = $value['value'];
        }
        file_put_contents(
            CONF_PATH . 'extra' . DS . 'site.php',
            '<?php' . "\n\nreturn " . var_export_short($config) . ";\n"
        );
    }



    public function download_file($url, $dir, $filename = '') {
        if (empty($url)) {
            return false;
        }
        $ext = strrchr($url, '.');
        /*if($ext != '.gif' && $ext != ".jpg" && $ext != ".bmp"){
            echo "格式不支持！";
            return false;
        }*/

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


}
