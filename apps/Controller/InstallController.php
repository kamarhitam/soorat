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

class InstallController extends NG\Controller {
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

        $config = $this->config;
        $session = $this->session;
        $cookie = $this->cookie;
        $cache = $this->cache;
        $helper = $this->helper;

        $controllerPath = Registry::get('controllerPath');
        $controllerName = Registry::get('controllerName');

        $action = "index";
        $title = "Pemasangan";
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

        $clsUser = new User();
        $userCount = $clsUser->getCount();

        if ($userCount > 0){
            Route::redirect($baseUrl . "masuk");
        } else {
            $encKey = $helper->getArrayValue($config, "ENC_KEY");
            $email = $helper->getArrayValue($config, "MAIL_ADMIN");
            $password = "admin";
            $name = "Administrator";

            $md5Key = md5($encKey);
            $md5User = md5($password);
            $encPassword = md5($md5Key . $md5User . $md5Key);
            $userCode = $helper->generateKey("USR", 17);

            $userInsert = array(
                'name' => $name,
                'email' => $email,
                'password' => $encPassword,
                'code' => $userCode,
                'image' => '',
                'idtype' => 9,
                'active' => 1,
            );

            $success = "Aplikasi sudah membuatkan pengguna secara otomatis: Email: $email, Password: $password";

            $insertId = $clsUser->insert($userInsert);
            if ($insertId){
                $clsRole = new Role();
                $dataRoleFetch = $clsRole->fetch(0);
                if ($dataRoleFetch){
                    $dataRole = $helper->getArrayValue($dataRoleFetch, "data");
                    if ($dataRole){
                        $userId = $insertId;
                        foreach ($dataRole as $itemRole){
                            $roleId = $itemRole["id"];
                            $clsRoles = new UserRoles();
                            $dataRoles = array(
                                "iduser" => $userId,
                                "idrole" => $roleId,
                            );
                            $clsRoles->insert($dataRoles);
                        }
                    }
                }

                $imageCode = $helper->generateKey("", 20);
                $dir = ROOT . DS . UPLOADS . DS . PROFILE . DS . strtolower($userCode);
                if (!file_exists($dir)){
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                }
                $newFile = strtolower($imageCode) . ".png";
                $newImage = $dir . DS . $newFile;
                if (!file_exists($newImage)){
                    foreach (new DirectoryIterator($dir . DS) as $fileInfo) {
                        if(!$fileInfo->isDot()) {
                            unlink($fileInfo->getPathname());
                        }
                    }
                    $oldImage = ROOT . DS . ASSETS . DS . "img" . DS . "inochi.png";
                    @copy($oldImage, $newImage);

                    $cls = new User();
                    $dataUpdate = array("image" => $newFile);
                    $update = $cls->update($insertId, $dataUpdate);
                }

                $success = "Aplikasi sudah membuatkan pengguna secara otomatis: Email: $email, Password: $password";
            }
        }

        $this->view->viewAction = $action;
        $this->view->setAction($action);
        $this->view->viewTitle = $title;
        $this->view->viewAlert = $alert;
        $this->view->viewSuccess = $success;

    }
}
