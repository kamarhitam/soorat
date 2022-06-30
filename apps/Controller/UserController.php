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

class UserController extends NG\Controller {
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
        $config = $this->config;

        $userSession = null;
        $currSession = $session->get("Login");
        $rolesUserLogin = null;
        $idUserLogin = 0;
        $typeUserLogin = 0;
        $codeUserLogin = '';
        $emailUserLogin = '';

        if ($currSession) {
            $userSession = $helper->getArrayValue($currSession, 'user');
            $rolesUserLogin = $helper->getArrayValue($userSession, "roles");
            $idUserLogin = $helper->getArrayValue($userSession, 'id');
            $typeUserLogin = $helper->getArrayValue($userSession, 'idtype');
            $codeUserLogin = $helper->getArrayValue($userSession, 'code');
            $emailUserLogin = $helper->getArrayValue($userSession, 'email');
        } else {
            Route::redirect(Uri::baseUrl());
        }

        $controllerPath = Registry::get('controllerPath');
        $controllerName = Registry::get('controllerName');

        $action = "index";
        $title = "index";
        $success = "";
        $alert = "";
        $viewId = 0;

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

        if (!$title) {
            switch ($param1) {
                case "tipe-pengguna":
                    $title = "Tipe Pengguna";
                    break;
                case "peran-pengguna":
                    $title = "Peran Pengguna";
                    break;
            }
        }

        $breadcrumb[] = array(
            "url" => $baseUrl . $controllerPath,
            "title" => $helper->getTitle($this->menu, 'id', 'text', 310000),
        );

        if ($requests) {
            $breadcrumb[] = array(
                "url" => $baseUrl . $controllerPath . "/$param1",
                "title" => $title,
            );
        }

        if (isset($_POST["action"])) {
            $action = $_POST["action"];
            if ($action){
                switch ($action){
                    case "add":
                        $actionId = 2;
                        if ($helper->isHasRight($rolesUserLogin, $subMenu, $actionId)){
                            switch ($param1){
                                case "index":
                                    $name = $_POST["add-name"];
                                    $email = $_POST["add-email"];
                                    $password = $_POST["add-password"];
                                    $confirm = $_POST["add-confirm"];
                                    $type = $_POST["add-type"];

                                    $clsUser = new User();
                                    $user = $clsUser->getByEmail($email);

                                    if ($user) {
                                        $alert = "Email pengguna sudah terdaftar";
                                    } else {
                                        if ($password == $confirm){
                                            $encKey = $config["ENC_KEY"];
                                            $md5Key = md5($encKey);
                                            $md5User = md5($password);
                                            $encPassword = md5($md5Key . $md5User . $md5Key);
                                            $code = $helper->generateKey("USR", 17);

                                            $userInsert = array(
                                                'name' => trim($name),
                                                'email' => trim($email),
                                                'password' => $encPassword,
                                                'code' => $code,
                                                'idtype' => $type,
                                                'active' => 1,
                                            );

                                            $insertId = $clsUser->insert($userInsert);

                                            if ($insertId){
                                                if ($type == 9){
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
                                                }
                                            }

                                            if ($insertId){
                                                $userCode = $code;
                                                if (isset($_FILES['add-image'])){
                                                    $errors = array();
                                                    $file = $_FILES['add-image'];
                                                    $file_name = $file['name'];
                                                    $file_size = $file['size'];
                                                    $file_tmp = $file['tmp_name'];
                                                    $array = explode('.', $file['name']);
                                                    $file_ext = strtolower(end($array));
                                                    $extensions = array("jpeg","jpg","png");

                                                    if (file_exists($file_name)) {
                                                        $errors[] = "Berkas sudah ada";
                                                    }

                                                    if (in_array($file_ext, $extensions) === false){
                                                        $errors[] = "Ekstensi berkas tidak didukung";
                                                    }

                                                    if ($file_size > 2097152){
                                                        $errors[] = 'Ukuran berkas tidak boleh lebih dari 2MB';
                                                    }

                                                    if (empty($errors)){
                                                        $code = $helper->generateKey("", 20);
                                                        $dir = ROOT . DS . UPLOADS . DS . PROFILE . DS . strtolower($userCode);
                                                        if (!file_exists($dir)){
                                                            if (!is_dir($dir)) mkdir($dir, 0755, true);
                                                        }

                                                        $newFile = strtolower($code) . "." . $file_ext;
                                                        $newImage = $dir . DS . $newFile;

                                                        if (!file_exists($newImage)){
                                                            foreach (new DirectoryIterator($dir . DS) as $fileInfo) {
                                                                if(!$fileInfo->isDot()) {
                                                                    unlink($fileInfo->getPathname());
                                                                }
                                                            }

                                                            @move_uploaded_file($file_tmp, $newImage);
                                                            $clsUser = new User();
                                                            $dataUpdate = array("image" => $newFile);
                                                            $clsUser->update($insertId, $dataUpdate);
                                                        }
                                                    }
                                                }
                                            }
                                            if ($insertId){
                                                $success = "Informasi berhasil disimpan";
                                            } else {
                                                $alert = "Informasi gagal disimpan";
                                            }
                                        }
                                    }
                                    break;
                            }
                        }
                        break;
                    case "edit":
                        $actionId = 3;
                        if ($helper->isHasRight($rolesUserLogin, $subMenu, $actionId)){
                            switch ($param1){
                                case "index":
                                    $id = $_POST["edit-id"];
                                    $name = $_POST["edit-name"];
                                    $email = $_POST["edit-email"];
                                    $userCode = $_POST["edit-code"];
                                    $password = $_POST["edit-password"];
                                    $confirm = $_POST["edit-confirm"];
                                    $type = $_POST["edit-type"];
                                    $active = 0;

                                    if (isset($_POST["edit-active"])) {
                                        $active = $_POST["edit-active"];
                                    }

                                    $dataUpdate = array(
                                        'name' => trim($name),
                                        'email' => trim($email),
                                        'idtype' => $type,
                                        'active' => $active,
                                    );

                                    $cls = new User();

                                    if (!empty($password) && !empty($confirm)){
                                        if ($password == $confirm){
                                            $encKey = $config["ENC_KEY"];
                                            $md5Key = md5($encKey);
                                            $md5User = md5($password);
                                            $encPassword = md5($md5Key . $md5User . $md5Key);
                                            $dataUpdate["password"] = $encPassword;
                                        } else {
                                            $alert = "Konfirmasi Sandi salah";
                                        }
                                    }

                                    $update = $cls->update($id, $dataUpdate);

                                    if (isset($_FILES['edit-image'])){
                                        $errors = array();
                                        $file = $_FILES['edit-image'];
                                        $file_name = $file['name'];
                                        $file_size = $file['size'];
                                        $file_tmp = $file['tmp_name'];
                                        $array = explode('.', $file['name']);
                                        $file_ext = strtolower(end($array));
                                        $extensions = array("jpeg","jpg","png");

                                        if (file_exists($file_name)) {
                                            $errors[] = "Berkas sudah ada";
                                        }

                                        if (in_array($file_ext, $extensions) === false){
                                            $errors[] = "Ekstensi berkas tidak didukung";
                                        }

                                        if ($file_size > 2097152){
                                            $errors[] = 'Ukuran berkas tidak boleh lebih dari 2MB';
                                        }

                                        if (empty($errors)){
                                            $code = $helper->generateKey("", 20);
                                            $dir = ROOT . DS . UPLOADS . DS . PROFILE . DS . strtolower($userCode);
                                            if (!file_exists($dir)){
                                                if (!is_dir($dir)) mkdir($dir, 0755, true);
                                            }
                                            $newFile = strtolower($code) . "." . $file_ext;
                                            $newImage = $dir . DS . $newFile;
                                            if (!file_exists($newImage)){
                                                foreach (new DirectoryIterator($dir . DS) as $fileInfo) {
                                                    if(!$fileInfo->isDot()) {
                                                        unlink($fileInfo->getPathname());
                                                    }
                                                }

                                                @move_uploaded_file($file_tmp, $newImage);
                                                $cls = new User();
                                                $dataUpdate = array("image" => $newFile);
                                                $update = $cls->update($id, $dataUpdate);
                                            }
                                        }
                                    }

                                    if ($update){
                                        $success = "Informasi berhasil disimpan";
                                    } else {
                                        $alert = "Informasi gagal disimpan";
                                    }

                                    break;
                                case "profil":
                                    $idUser = $idUserLogin;
                                    if ($param2) {
                                        if ($typeUserLogin == 9) {
                                            $idUser = (int) $param2;
                                        } else {
                                            $idUser = $idUserLogin;
                                        }
                                    }
                                    $name = $_POST["name"];
                                    $email = $_POST["email"];
                                    $userCode = $codeUserLogin;
                                    $password = $_POST["password"];
                                    $new_password = $_POST["new-password"];
                                    $confirm = $_POST["confirm"];

                                    if ($password || $typeUserLogin == 9) {
                                        $encKey = $config["ENC_KEY"];
                                        $md5Key = md5($encKey);
                                        $md5Pass = md5($password);
                                        $encPassword = md5($md5Key . $md5Pass . $md5Key);

                                        $cls = new User();
                                        $checkLogin = $cls->login($emailUserLogin, $encPassword);

                                        if ($typeUserLogin == 9) {
                                            $checkLogin = true;
                                        }

                                        if ($checkLogin) {
                                            $dataUpdate = array(
                                                'name' => trim($name),
                                                'email' => trim($email),
                                            );

                                            if (!empty($new_password) && !empty($confirm)){
                                                if ($new_password == $confirm){
                                                    $encKey = $config["ENC_KEY"];
                                                    $md5Key = md5($encKey);
                                                    $md5User = md5($new_password);
                                                    $encPassword = md5($md5Key . $md5User . $md5Key);
                                                    $dataUpdate["password"] = $encPassword;
                                                } else {
                                                    $alert = "Konfirmasi Sandi salah";
                                                }
                                            }

                                            $update = $cls->update($idUser, $dataUpdate);
                                            if (isset($_FILES['file'])){
                                                $errors = array();
                                                $file = $_FILES['file'];
                                                $file_name = $file['name'];
                                                $file_size = $file['size'];
                                                $file_tmp = $file['tmp_name'];
                                                $array = explode('.', $file['name']);
                                                $file_ext = strtolower(end($array));
                                                $extensions = array("jpeg","jpg","png");

                                                if (file_exists($file_name)) {
                                                    $errors[] = "Berkas sudah ada";
                                                }

                                                if (in_array($file_ext, $extensions) === false){
                                                    $errors[] = "Ekstensi berkas tidak didukung";
                                                }

                                                if ($file_size > 2097152){
                                                    $errors[] = 'Ukuran berkas tidak boleh lebih dari 2MB';
                                                }

                                                if (empty($errors)){
                                                    $code = $helper->generateKey("", 20);
                                                    $dir = ROOT . DS . UPLOADS . DS . PROFILE . DS . strtolower($userCode);
                                                    if (!file_exists($dir)){
                                                        if (!is_dir($dir)) mkdir($dir, 0755, true);
                                                    }
                                                    $newFile = strtolower($code) . "." . $file_ext;
                                                    $newImage = $dir . DS . $newFile;
                                                    if (!file_exists($newImage)){
                                                        foreach (new DirectoryIterator($dir . DS) as $fileInfo) {
                                                            if(!$fileInfo->isDot()) {
                                                                unlink($fileInfo->getPathname());
                                                            }
                                                        }

                                                        @move_uploaded_file($file_tmp, $newImage);
                                                        $cls = new User();
                                                        $dataUpdate = array("image" => $newFile);
                                                        $update = $cls->update($idUser, $dataUpdate);
                                                    }
                                                }
                                            }

                                            if ($update){
                                                $success = "Informasi berhasil disimpan";
                                                if ($idUser == $idUserLogin) {
                                                    $user = $cls->getById($idUser);
                                                    $clsRoles = new UserRoles();
                                                    $roles = $clsRoles->fetch($idUser, 0);
                                                    if ($roles){
                                                        $dataRoles = $helper->getArrayValue($roles, "data");
                                                        $user["roles"] = $dataRoles;
                                                    }
                                                    $currSession['user'] = $user;
                                                    $session->set("Login", $currSession);
                                                }
                                            } else {
                                                $alert = "Informasi gagal disimpan";
                                            }

                                            if (isset($_POST["meta"])){
                                                $inputPost = $_POST["meta"];
                                                $details = null;
                                                foreach ($inputPost as $postKey => $postValue) {
                                                    $itemKey = $postKey;
                                                    $itemValue = $postValue;
                                                    if (is_array($postValue)) {
                                                        $itemValue = '[' . implode(',', $postValue) . ']';
                                                    }
                                                    if ($itemKey) {
                                                        if ($itemValue) {
                                                            $details[$itemKey] = $itemValue;
                                                        }
                                                    }
                                                }

                                                if ($details) {
                                                    $target = "user";
                                                    $clsDetail = new Detail();
                                                    $clsDetail->deleteByTarget($target, $idUser);
                                                    foreach ($details as $detailKey => $detailValue) {
                                                        $dataDetailInsert = array(
                                                            "idtarget" => $idUser,
                                                            "target" => $target,
                                                            "key" => $detailKey,
                                                            "value" => $detailValue
                                                        );
                                                        $clsDetail->insert($dataDetailInsert);
                                                    }
                                                    $success = "Informasi berhasil disimpan";
                                                    $alert = "";
                                                }
                                            }
                                        } else {
                                            $alert = "Kata sandi salah";
                                        }
                                    } else {
                                        $alert = "Isi kata sandi lama Anda";
                                    }

                                    break;
                            }
                        }
                        break;
                    case "delete":
                        $actionId = 4;
                        if ($helper->isHasRight($rolesUserLogin, $subMenu, $actionId)){
                            switch ($param1){
                                case "index":
                                    $id = $_POST["del-user-id"];
                                    $cls = new User();
                                    $del = $cls->delete($id);
                                    $success = "Data berhasil dihapus";
                                    break;
                            }
                        }
                        break;
                }
            }
        }

        $action = ucfirst($param1);

        $data = null;
        $dataAction = null;
        $dataUserType = null;
        $dataMeta = null;

        switch ($param1) {
            case "index":
                $cls = new UserType();
                $dataUserType = $cls->fetch(0);

                $cls = new User();
                $data = $cls->fetch(0);

                $cls = new Meta();
                $dataMeta = $cls->fetchFilter("user", "", 0);
                break;
            case "profil":
                $idUser = $idUserLogin;
                if ($param2) {
                    if ($typeUserLogin == 9) {
                        $idUser = (int) $param2;
                    } else {
                        $idUser = $idUserLogin;
                    }
                }
                $cls = new User();
                $data = $cls->getById($idUser);

                $cls = new Meta();
                $dataMeta = $cls->fetchFilter("user", "", 0);
                break;
        }

        $this->view->viewAction = $action;
        $this->view->setAction($action);
        $this->view->viewTitle = $title;
        $this->view->viewBreadcrumb = $breadcrumb;
        $this->view->viewDataAction = $dataAction;
        $this->view->viewDataUserType = $dataUserType;
        $this->view->viewDataMeta = $dataMeta;
        $this->view->viewData = $data;
        $this->view->viewId = $viewId;
        $this->view->viewSuccess = $success;
        $this->view->viewAlert = $alert;
    }
}
