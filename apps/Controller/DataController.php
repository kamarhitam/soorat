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

class DataController extends NG\Controller {
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
        $param5 = "";

        $filterYear = 0;
        $filterMonth = 0;
        $filterType = 0;
        $filterTypeSlug = '';
        $filterTypeName = '';

        if (isset($requests['param1'])) {
            $param1 = $requests['param1'];
            if (isset($requests['param2'])) {
                $param2 = $requests['param2'];
                if (isset($requests['param3'])) {
                    $param3 = $requests['param3'];
                    if (isset($requests['param4'])) {
                        $param4 = $requests['param4'];
                        if (isset($requests['param5'])) {
                            $param5 = $requests['param5'];
                        }
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
            $activeMenu[] = intval($mainMenu);
            $title = $helper->getTitle($this->menu, 'id', 'text', $activeMenu[0]);
        }

        $subMenu = $helper->getMenuId($allMenus, $controllerPath . '/' . $param1);
        if (!$subMenu){
            $subMenu = intval($mainMenu);
        }
        $activeMenu[] = $subMenu;

        if ($param1 != "index") {
            $title = $helper->getTitle($this->menu, 'id', 'text', end($activeMenu));
            if (is_numeric($param1)) {
                $title = $helper->getTitle($this->menu, 'id', 'text', 510000);
            }
        }

        $breadcrumb[] = array(
            "url" => $baseUrl . $controllerPath,
            "title" => $helper->getTitle($this->menu, 'id', 'text', 510000),
        );

        if (isset($_POST["action"])) {
            $action = $_POST["action"];
            if ($action){
                switch ($action){
                    case "add":
                        $actionId = 2;
                        if ($helper->isHasRight($rolesUserLogin, $subMenu, $actionId)){
                            if ($param1) {
                                $filterTypeSlug = $param1;
                                $cls = new CmsType();
                                $dataType = $cls->getBySlug($filterTypeSlug);
                                if ($dataType) {
                                    $filterType = $helper->getArrayValue($dataType, "id");
                                    $filterTypeName = $helper->getArrayValue($dataType, "name");
                                    $prefixCodes = array(
                                        "" => "",
                                        "surat" => "LTR",
                                        "form" => "FRM",
                                    );
                                    $prefixCode = $prefixCodes[$filterTypeSlug];
                                    $postId = (int) $param2;
                                    $cls = new CmsPost();
                                    $viewId = $postId;
                                    $dataPost = $cls->getById($postId);
                                    if ($dataPost) {
                                        $cls = new PostData();
                                        $inputPost = $_POST;

                                        $code = $helper->generateKey($prefixCode . sprintf('%04d', $postId), 13);
                                        $date = date("Y-m-d H:i:s");

                                        unset($inputPost["action"]);

                                        $dataInsert = array(
                                            "idpost" => $postId,
                                            "code" => $code,
                                            "type" => $filterType,
                                            "createdate" => $date,
                                            "createby" => $idUserLogin,
                                        );

                                        $insert = $cls->insert($dataInsert);

                                        if ($insert) {
                                            $idPostData = $insert;
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
                                                $cls = new Detail();
                                                $target = "data";
                                                $cls->deleteByTarget($target, $idPostData);
                                                foreach ($details as $detailKey => $detailValue) {
                                                    if ($detailValue == "(auto)") {
                                                        $detailValue = $cls->getAutoNumber($target, $detailKey);
                                                    }
                                                    $dataDetailInsert = array(
                                                        "idtarget" => $idPostData,
                                                        "target" => $target,
                                                        "key" => $detailKey,
                                                        "value" => $detailValue
                                                    );
                                                    $cls->insert($dataDetailInsert);
                                                }
                                            }
                                            $success = "Informasi berhasil disimpan";
                                            Route::redirect($baseUrl . strtolower($controllerPath) . "/$filterTypeSlug/$postId/edit/$idPostData");
                                        } else {
                                            $alert = "Informasi gagal disimpan";
                                        }

                                    }
                                }
                            }
                        }
                        break;
                    case "edit":
                        $actionId = 3;
                        if ($helper->isHasRight($rolesUserLogin, $subMenu, $actionId)){
                            if ($param2) {
                                $filterTypeSlug = $param1;
                                switch ($param3){
                                    case "edit":
                                        if ($param4) {
                                            $idPostData = (int) $param4;

                                            $inputPost = $_POST;
                                            unset($inputPost["action"]);

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
                                                        if ($itemKey == "edit-id") {
                                                            unset($details[$itemKey]);
                                                        }
                                                    }
                                                }
                                            }

                                            if ($details) {
                                                $cls = new Detail();
                                                $target = "data";
                                                $cls->deleteByTarget($target, $idPostData);
                                                foreach ($details as $detailKey => $detailValue) {
                                                    $dataDetailInsert = array(
                                                        "idtarget" => $idPostData,
                                                        "target" => $target,
                                                        "key" => $detailKey,
                                                        "value" => $detailValue
                                                    );
                                                    $cls->insert($dataDetailInsert);
                                                }
                                            }

                                            if ($details){
                                                $success = "Informasi berhasil diperbarui";
                                            } else {
                                                $alert = "Informasi gagal diperbarui";
                                            }
                                        }
                                        break;
                                }
                            }
                        }
                        break;
                    case "delete":
                        $actionId = 4;
                        if ($helper->isHasRight($rolesUserLogin, $subMenu, $actionId)){
                            if ($param1) {
                                $id = $_POST["del-letter-id"];
                                $cls = new PostData();
                                $del = $cls->delete($id);
                                $success = "Data berhasil dihapus";
                            }
                        }
                        break;
                    case "get":
                        switch ($param1){
                            case "index":
                                if (isset($_POST["data"])) {
                                    $data = $_POST["data"];
                                    $idPost = $data['id'];

                                    $this->view->setLayout(false);
                                    $this->view->setNoRender(true);

                                    $clsPostInput = new CmsPostInput();
                                    $data = $clsPostInput->fetchByIdPost($idPost, 0, 0);

                                    $result = array(
                                        "status" => 1,
                                        "data" => $data,
                                    );

                                    $print_text = json_encode($result);
                                    header('Content-type: application/json');
                                    exit($print_text);
                                }
                                break;
                        }
                        break;
                    case "meta-input":
                        $actionId = 2;
                        if ($helper->isHasRight($rolesUserLogin, $subMenu, $actionId)){
                            if (isset($_POST)) {
                                $cls = new CmsPostInput();
                                $idPost = $_POST["input-post-id"];
                                $forms = $_POST["forms"];
                                $nums = $_POST["nums"];
                                $cls->deleteById($idPost);
                                if ($forms){
                                    foreach ($forms as $item){
                                        $idInput = $item;
                                        $data = array(
                                            "idpost"=> $idPost,
                                            "idinput"=> $idInput,
                                            "num"=> $helper->getArrayValue($nums, $idInput, 0),
                                        );
                                        $cls->insert($data);
                                    }
                                }
                            }
                        } else {
                            $alert = "Anda tidak mempunyai hak akses";
                        }
                        break;
                    case "filter":
                        $filterYear = $_POST["filter-year"];
                        $filterMonth = $_POST["filter-month"];
                        $todayYear = date('Y');

                        $filterTypeSlug = $param1;
                        $idPost = (int) $param2;

                        if ($filterYear == $todayYear) {
                            $redirect = $baseUrl . strtolower($controllerPath) . "/$filterTypeSlug/$idPost";
                            if ($filterMonth) {
                                $redirect = $baseUrl . strtolower($controllerPath) . "/$filterTypeSlug/$idPost/$filterYear/$filterMonth";
                            }
                            Route::redirect($redirect);
                        } else {
                            if (is_numeric($filterYear)) {
                                $redirect = $baseUrl . strtolower($controllerPath) . "/$filterTypeSlug/$idPost/$filterYear";
                                if ($filterMonth) {
                                    $redirect = $baseUrl . strtolower($controllerPath) . "/$filterTypeSlug/$idPost/$filterYear/$filterMonth";
                                }
                                Route::redirect($redirect);
                            } else {
                                $redirect = $baseUrl . strtolower($controllerPath) . "/$filterTypeSlug/$idPost";
                                Route::redirect($redirect);
                            }
                        }

                        break;
                }
            }
        }

        $action = ucfirst($param1);

        $data = null;
        $dataInput = null;
        $dataUserType = null;
        $dataAction = null;
        $dataPost = null;
        $dataUser = null;
        $dataMeta = null;
        $dataContent = '';

        $cls = new Meta();
        $dataMeta = $cls->fetchFilter("data", "", 0);

        if ($param1) {
            $cls = new CmsType();
            $dataType = $cls->getBySlug($param1);
            if ($dataType) {
                $idType = $helper->getArrayValue($dataType, "id");
                $slugType = $helper->getArrayValue($dataType, "slug");
                $nameType = $helper->getArrayValue($dataType, "name");
                $filterType = $idType;
                $filterTypeSlug = $slugType;
                $filterTypeName = $nameType;
            }
            if ($filterType) {
                $action = "Index";
                $cls = new CmsPost();
                $data = $cls->fetch($filterType, 0, 0);

                $cls = new CmsInput();
                $dataInput = $cls->fetch($filterTypeSlug, 0, 0, 0);

            }
            if ($param2) {
                $filterYear = 0;
                $filterMonth = 0;
                $idPost = (int) $param2;
                $cls = new PostData();
                if ($idPost) {
                    $viewId = $idPost;
                    if ($param3) {
                        switch ($param3) {
                            case "edit":
                                if ($param4) {
                                    $action = "Edit";
                                    $idData = (int) $param4;
                                    $viewId = $idData;
                                    $data = $cls->getById($idData);

                                    if ($data) {
                                        $title = "Edit $filterTypeName";
                                        $subTitle = $helper->getArrayValue($dataPost, 'title', $title);

                                        $breadcrumb[] = array(
                                            "url" => $baseUrl . "$controllerPath/$filterTypeSlug/$idPost",
                                            "title" => $subTitle,
                                        );

                                        $subTitle = $helper->getArrayValue($data, 'code', "");
                                        $title = "$title: $subTitle";

                                        $breadcrumb[] = array(
                                            "url" => $baseUrl . "$controllerPath/$filterTypeSlug/$idPost/$idData",
                                            "title" => $subTitle,
                                        );
                                    }
                                }
                                break;
                            default:
                                $filterYear = (int) $param3;
                                if ($param4) {
                                    $filterMonth = (int) $param4;
                                }
                                $action = "Daftar";
                                $data = $cls->fetch($filterType, $idPost, $filterYear, $filterMonth);
                                // print_r($data);

                                break;
                        }
                    } else {
                        $action = "Daftar";
                        $data = $cls->fetch($filterType, $idPost, $filterYear, $filterMonth);
                        // print_r($data);
                    }

                    $cls = new CmsPostInput();
                    $dataInput = $cls->fetchByIdPost($idPost, 0, 0);

                    $cls = new User();
                    $dataUser = $cls->fetch(0);
                }
            }
            /*
            if ($viewId) {
                $idPost = $viewId;
                $dataPost = $cls->getById($idPost);
                if ($dataPost) {
                    if ($param2) {
                        switch ($param2) {
                            case "edit":
                                $action = "Edit";
                                if ($param3) {
                                    $viewId = (int) $param3;
                                    $idLetter = (int) $viewId;
                                    $cls = new Letter();
                                    $data = $cls->getById($idLetter);
                                    if ($data) {
                                        $title = "Edit Surat";
                                        $subTitle = $helper->getArrayValue($dataPost, 'title', $title);

                                        $breadcrumb[] = array(
                                            "url" => $baseUrl . "$controllerPath/$idPost",
                                            "title" => $subTitle,
                                        );

                                        $subTitle = $helper->getArrayValue($data, 'code', "");
                                        $title = "$title: $subTitle";

                                        $breadcrumb[] = array(
                                            "url" => $baseUrl . "$controllerPath/$idPost/$param2/$idLetter",
                                            "title" => $subTitle,
                                        );

                                        $clsPostInput = new CmsPostInput();
                                        $dataInput = $clsPostInput->fetchByIdPost($idPost, 0, 0);

                                        $clsUser = new User();
                                        $dataUser = $clsUser->fetch(0);
                                    }
                                }
                                break;
                            default:
                                $filterYear = (int) $param2;
                                if ($param3) {
                                    $filterMonth = (int) $param3;
                                }

                                $action = "Daftar";
                                $subTitle = $helper->getArrayValue($dataPost, 'title', $title);
                                $title = "$title: $subTitle";

                                $cls = new Letter();
                                $data = $cls->fetchByPostId($idPost, $filterYear, $filterMonth);

                                $clsPostInput = new CmsPostInput();
                                $dataInput = $clsPostInput->fetchByIdPost($idPost, 0, 0);

                                $clsUser = new User();
                                $dataUser = $clsUser->fetch(0);

                                $breadcrumb[] = array(
                                    "url" => $baseUrl . "$controllerPath/$idPost",
                                    "title" => $subTitle,
                                );
                                break;
                        }
                    } else {
                        $action = "Daftar";
                        $subTitle = $helper->getArrayValue($dataPost, 'title', $title);
                        $title = "$title: $subTitle";

                        $cls = new Letter();
                        $data = $cls->fetchByPostId($idPost, $filterYear, $filterMonth);

                        $clsPostInput = new CmsPostInput();
                        $dataInput = $clsPostInput->fetchByIdPost($idPost, 0, 0);

                        $clsUser = new User();
                        $dataUser = $clsUser->fetch(0);

                        $breadcrumb[] = array(
                            "url" => $baseUrl . "$controllerPath/$idPost",
                            "title" => $subTitle,
                        );
                    }
                }
            }
            */
        }

        $this->view->viewActiveMenu = $activeMenu;
        $this->view->viewAction = $action;
        $this->view->setAction($action);
        $this->view->viewTitle = $title;
        $this->view->viewBreadcrumb = $breadcrumb;
        $this->view->viewData = $data;

        $this->view->viewDataInput = $dataInput;
        $this->view->viewDataPost = $dataPost;
        $this->view->viewDataUser = $dataUser;
        $this->view->viewDataContent = $dataContent;
        $this->view->viewDataMeta = $dataMeta;

        $this->view->viewFilterType = $filterType;
        $this->view->viewFilterTypeSlug = $filterTypeSlug;
        $this->view->viewFilterTypeName = $filterTypeName;
        $this->view->viewFilterMonth = $filterMonth;
        $this->view->viewFilterYear = $filterYear;

        $this->view->viewId = $viewId;
        $this->view->viewSuccess = $success;
        $this->view->viewAlert = $alert;
    }
}
