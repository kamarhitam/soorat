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

class DashboardController extends NG\Controller {
    protected $config;
    protected $cache;
    protected $session;
    protected $cookie;
    protected $helper;
    protected $menu;

    public function init() {
        $this->config = $this->view->config = Registry::get('config');
        $this->session = $this->view->session = new Session();
        $this->cookie = $this->view->cookie = new Cookie();
        $this->cache = $this->view->cache = new Cache();
        $this->helper = $this->view->helper = new Helper();
        $this->menu = $this->view->menu = Registry::get('menu');

        $this->view->setLayoutFile("Dashboard");
    }

    public function IndexAction() {
        $requests = Route::getRequests();
        $baseUrl = Uri::baseUrl();

        $session = $this->session;
        $cookie = $this->cookie;
        $cache = $this->cache;
        $helper = $this->helper;

        $userSession = null;
        $currSession = $session->get("Login");
        $rolesUserLogin = null;
        $idUserLogin = 0;

        if ($currSession) {
            $userSession = $helper->getArrayValue($currSession, 'user');
            $rolesUserLogin = $helper->getArrayValue($userSession, "roles");
            $idUserLogin = $helper->getArrayValue($userSession, 'id');
        } else {
            Route::redirect(Uri::baseUrl() . "masuk");
        }

        $controllerPath = Registry::get('controllerPath');
        $controllerName = Registry::get('controllerName');

        $action = "index";
        $title = "index";

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

        $allMenus = $helper->collectMenu($this->menu);
        $mainMenu = $helper->getMenuId($allMenus, $controllerPath);
        if ($mainMenu) {
            $mod = intval($mainMenu)  % 10;
            if ($mod > 0){
                $mainMenu = intval($mainMenu) - $mod;
            }
        }
        $activeMenu = array($mainMenu);
        if (!$param1) {
            $param1 = "index";
            $activeMenu[] = intval($mainMenu) + 1;
            $title = $helper->getTitle($this->menu, 'id', 'text', $activeMenu[0]);
        }

        $subMenu = $helper->getMenuId($allMenus, $controllerPath . '/' . $param1);
        if (!$subMenu){
            $subMenu = intval($mainMenu) + 1;
        }
        $activeMenu[] = $subMenu;

        if ($param1 != "index") {
            $title = $helper->getTitle($this->menu, 'id', 'text', end($activeMenu));
        }

        if ($requests) {
            $breadcrumb[] = array(
                "url" => $baseUrl . $controllerPath . "/" . implode('/', $requests),
                "title" => $title,
            );
        } else {
            $breadcrumb[] = array(
                "url" => $baseUrl . $controllerPath,
                "title" => $title,
            );
        }
        $cls = new CmsPost();
        $countPostType = $cls->getCountByType(2);

        $cls = new Letter();
        $countLetter = $cls->getCount();

        $cls = new CmsGallery();
        $countGallery = $cls->getCount();

        $this->view->viewCountPostType = $countPostType;
        $this->view->viewCountLetter = $countLetter;
        $this->view->viewCountGallery = $countGallery;

        $this->view->viewActiveMenu = $activeMenu;
        $this->view->viewAction = $action;
        $this->view->setAction($action);
        $this->view->viewTitle = $title;
        $this->view->viewBreadcrumb = $breadcrumb;
    }
}
