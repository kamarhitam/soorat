<?php

use NG\Cache;
use NG\Cookie;
use NG\Registry;
use NG\Route;
use NG\Session;

class PluginController extends NG\Controller {
    protected $config;
    protected $cache;
    protected $session;
    protected $cookie;
    protected $helper;

    public function init() {
        $this->config = $this->view->config = Registry::get('config');
        $this->session = $this->view->session = new Session();
        $this->cookie = $this->view->cookie = new Cookie();
        $this->cache = $this->view->cache = new Cache();
        $this->helper = $this->view->helper = new Helper();

        $this->view->setLayout(false);
        $this->view->setNoRender(true);
    }

    public function IndexAction() {
        $helper = $this->helper;
        $config = $this->config;
        $requests = Route::getRequests();

        $file_ext = '';

        $path = implode(DS, $requests);
        $file_name = end($requests);

        $compactMode = $config['COMPACT_MODE'];
        $file_path = ROOT . DS . PLUGINS . DS . $path;
        $arr_file = explode('.', $file_name);

        if (count($arr_file) > 1) $file_ext = end($arr_file);

        header('Cache-Control: max-age=2592000');
        switch($file_ext){
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: text/javascript');
                break;
            case 'jpg':
                header('Content-Type: image/jpeg');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            case 'ico':
                header('Content-Type: image/x-icon');
                break;
            case 'xml':
                header('Content-Type: text/xml');
                break;
            case 'json':
                header('Content-Type: application/json');
                break;
        }

        if (file_exists($file_path)){
            if ($compactMode == 2){
                switch($file_ext){
                    case 'css':
                    case 'js':
                    case 'json':
                    case 'xml':
                        ob_start();
                        include ($file_path);
                        $content = ob_get_clean();
                        $result = $helper->removeWhiteSpace($content);
                        exit($result);
                        break;
                    case 'jpg':
                    case 'jpeg':
                        $result = $this->compressImage($file_path, 75);
                        exit($result);
                        break;
                    case 'png':
                        $result = $this->compressImage($file_path, 9);
                        exit($result);
                        break;
                    case 'ico':
                        $result = $file_path;
                        readfile($result);
                        break;
                }
            } else {
                readfile($file_path);
            }
        }
    }

    private function compressImage($source, $quality = 9){
        $info = getimagesize($source);
        if ($info['mime'] == 'image/jpeg'){
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, NULL, $quality);
            imagesavealpha($image, true);
            imagealphablending($image, false);
            imagedestroy($image);
        } else if ($info['mime'] == 'image/png'){
            $image = imagecreatefrompng($source);
            imagesavealpha($image, true);
            imagealphablending($image, true);
            imagepng($image, NULL, $quality, NULL);
            imagedestroy($image);
        }
    }
}
