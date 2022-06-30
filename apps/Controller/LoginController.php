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

class LoginController extends NG\Controller {
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

        $this->view->setLayoutFile("Wizard");
    }

    public function IndexAction() {
        $requests = Route::getRequests();
        $baseUrl = Uri::baseUrl();

        $session = $this->session;
        $config = $this->config;
        $helper = $this->helper;

        $controllerPath = Registry::get('controllerPath');
        $controllerName = Registry::get('controllerName');

        $action = "index";
        $title = "Masuk";
        $alert = "";
        $success = "";

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
        }

        $subMenu = $helper->getMenuId($allMenus, $controllerPath . '/' . $param1);
        if (!$subMenu){
            $subMenu = intval($mainMenu) + 1;
        }
        $activeMenu[] = $subMenu;

        if ($param1 != "index") {
            $title = $helper->getTitle($this->menu, 'id', 'text', end($activeMenu));
        }

        $clsUser = new User();
        $userCount = $clsUser->getCount();

        if ($userCount == 0){
            Route::redirect($baseUrl . "pasang");
        }

        $userSession = null;
        $currSession = $session->get("Login");
        if ($currSession){
            $userSession = $helper->getArrayValue($currSession, 'user');
        }

        if ($userSession) {
            Route::redirect($baseUrl . "pengguna/profil");
        } else {
            if (isset($_POST)) {
                if (isset($_POST['email']) &&
                    isset($_POST['password'])) {

                    $email = $_POST['email'];
                    $password = $_POST['password'];

                    $continue = true;

                    if (empty($email)){
                        $alert = "Email tidak boleh kosong";
                        $continue = false;
                    }

                    if (empty($password)){
                        $alert = "Kata Sandi tidak boleh kosong";
                        $continue = false;
                    }

                    if ($continue){
                        $encKey = $config["ENC_KEY"];
                        $md5Key = md5($encKey);
                        $md5Pass = md5($password);
                        $encPassword = md5($md5Key . $md5Pass . $md5Key);
                        $user = $clsUser->login($email, $encPassword);
                        if ($user) {
                            $active = $helper->getArrayValue($user, "active", 0);
                            if (empty($active)){
                                $alert = "Akun Anda belum aktif";
                            } else {
                                $clsRoles = new UserRoles();
                                $userId = $helper->getArrayValue($user, "id", 0);
                                $roles = $clsRoles->fetch($userId, 0);
                                if ($roles){
                                    $dataRoles = $helper->getArrayValue($roles, "data");
                                    $user["roles"] = $dataRoles;
                                }
                                $currSession['user'] = $user;
                                $session->set("Login", $currSession);
                                Route::redirect($baseUrl . "pengguna/profil");
                            }
                        } else {
                            $alert = "Email dan/atau Kata Sandi salah";
                        }
                    }
                }
            }
        }

        $this->view->viewAction = $action;
        $this->view->setAction($action);
        $this->view->viewTitle = $title;
        $this->view->viewAlert = $alert;
        $this->view->viewSuccess = $success;

    }
}
