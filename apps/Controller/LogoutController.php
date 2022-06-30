<?php

use NG\Registry;
use NG\Route;
use NG\Session;
use NG\Uri;

class LogoutController extends NG\Controller {
    protected $config;
    protected $cache;
    protected $session;
    protected $cookie;
    protected $helper;

    public function init() {
        $this->config = $this->view->config = Registry::get('config');
        $this->session = $this->view->session = new Session();
        $this->view->setLayout(false);
        $this->view->setNoRender(true);
    }

    public function IndexAction() {
        $baseUrl = Uri::baseUrl();

        $session = $this->session;
        $currSession = $session->get("Login");

        if ($currSession){
            $session->remove("Login");
            $session->remove("checkup");
            $session->remove("supervision");
        }

        Route::redirect($baseUrl . "masuk");
    }
}

