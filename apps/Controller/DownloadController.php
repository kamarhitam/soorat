<?php

use NG\Cache;
use NG\Cookie;
use NG\Registry;
use NG\Route;
use NG\Session;

class DownloadController extends NG\Controller {
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
    }

    public function IndexAction() {
        $requests = Route::getRequests();

        $session = $this->session;
        $cookie = $this->cookie;
        $cache = $this->cache;
    }
}
