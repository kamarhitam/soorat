<?php

/**
 * NG Framework
 * Version 0.1 Beta
 * Copyright (c) 2012, Nick Gejadze
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

use NG\Cache;
use NG\Cookie;
use NG\Registry;
use NG\Route;
use NG\Session;
use NG\Uri;

/**
 * IndexController
 * @package NG
 * @subpackage library
 * @version 0.1
 * @copyright (c) 2012, Nick Gejadze
 */

class PageController extends NG\Controller {
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

        $this->view->setLayoutFile("Front");
    }

    public function IndexAction() {
        $requests = Route::getRequests();
        $baseUrl = Uri::baseUrl();

        $controllerPath = Registry::get('controllerPath');
        $controllerName = Registry::get('controllerName');

        $action = "index";
        $title = "index";
        $success = "";
        $alert = "";
        $viewId = 0;
        $filterType = 1;

        $param1 = "";
        $param2 = "";
        $param3 = "";
        $param4 = "";

        if (isset($requests['param1'])) {
            $param1 = $requests['param1'];
            if (isset($requests['param2'])) {
                $param2 = $requests['param2'];
                if (isset($requests['param3'])) {
                    $param3 = $requests['param3'];
                    if (isset($requests['param4'])) {
                        $param4 = $requests['param4'];
                    }
                }
            }
        }

        $cls = new CmsPost();
        $data = $cls->getBySlug($param1);
        $content = '';
        if ($data) {
            $title = $data["title"];
            $content = $data["content"];
        } else {
            Route::redirect(Uri::baseUrl() . "kesalahan", "404");
        }

        $this->view->viewAction = $action;
        $this->view->setAction($action);
        $this->view->viewTitle = $title;
        // $this->view->viewData = $data;
        $this->view->viewContent = $content;
        $this->view->viewSuccess = $success;
        $this->view->viewAlert = $alert;
    }
}
