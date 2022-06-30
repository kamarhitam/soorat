<?php

use NG\Cache;
use NG\Cookie;
use NG\Registry;
use NG\Route;
use NG\Session;

class ApiController extends NG\Controller {
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
        $requests = Route::getRequests();

        $session = $this->session;
        $cookie = $this->cookie;
        $cache = $this->cache;

        $result = "";

        if ($result){
            $print_text = json_encode($result);

            header('Content-type: application/json');
            exit($print_text);
        }
    }
}
