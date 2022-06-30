<?php

use NG\Cache;
use NG\Cookie;
use NG\Registry;
use NG\Route;
use NG\Session;

class ScriptController extends NG\Controller {
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

        $path = implode(DS, $requests);

        $compactMode = $config['COMPACT_MODE'];
        $file_path = ROOT . DS . APPS . DS . SCRIPT . DS . $path;

        header('Cache-Control: max-age=2592000');
        header('Content-Type: text/javascript');

        if (file_exists($file_path)){
            if ($compactMode == 2){
                ob_start();
                include ($file_path);
                $content = ob_get_clean();
                $result = $helper->removeWhiteSpace($content);
                exit($result);
            } else {
                readfile($file_path);
            }
        }

    }
}
