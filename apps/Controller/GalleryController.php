<?php

use NG\Cache;
use NG\Cookie;
use NG\Registry;
use NG\Route;
use NG\Session;

class GalleryController extends NG\Controller {
    protected $config;
    protected $cache;
    protected $session;
    protected $cookie;

    public function init() {
        $this->config = $this->view->config = Registry::get('config');
        $this->session = $this->view->session = new Session();
        $this->cookie = $this->view->cookie = new Cookie();
        $this->cache = $this->view->cache = new Cache();

        $this->view->setLayout(false);
        $this->view->setNoRender(true);
    }

    public function IndexAction() {
        $requests = Route::getRequests();

        $param1 = "";
        $param2 = "";

        if (isset($requests['param1'])){
            $param1 = $requests['param1'];
            $param1 = urldecode($param1);

            if (isset($requests['param2'])){
                $param2 = $requests['param2'];
                $param2 = urldecode($param2);
            }
        }

        if ($param1 && $param2){
            $array = explode('.', $param2);

            $file_ext = strtolower(end($array));
            $file_name = substr($param2, 0, strlen($param2) - (strlen($file_ext) + 1));

            if (strlen($param1) == 8){
                $dir = ROOT . DS . UPLOADS . DS . GALLERY;

                $datePath = substr($param1, 0, 4) . DS;
                $datePath .= substr($param1, 4, 2). DS;
                $datePath .= substr($param1, 6, 2);

                if (!is_dir($dir)):
                    mkdir($dir, 0755, true);
                endif;

                $cls = new CmsGallery();
                $data = $cls->getByDateAndName($param1, $file_name);

                if ($data){
                    $dataExt = $data["ext"];
                    $dataCode = $data["code"];
                    $newFile = $dataCode . "." . $dataExt;
                    $path = $dir . DS . $datePath . DS . $newFile;

                    if (file_exists($path)){
                        header('Cache-Control: max-age=2592000');
                    }

                    switch($dataExt){
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
                        case 'pdf':
                            header('Content-Type: application/pdf');
                            break;
                    }
                    readfile($path);
                }
            }
        }
    }
}
