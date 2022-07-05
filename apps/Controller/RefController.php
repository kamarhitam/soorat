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

class RefController extends NG\Controller {
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

        if ($currSession) {
            $userSession = $helper->getArrayValue($currSession, 'user');
            $rolesUserLogin = $helper->getArrayValue($userSession, "roles");
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
            "title" => $helper->getTitle($this->menu, 'id', 'text', 210000),
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
                                case "tipe-pengguna":
                                    $id = $_POST["add-id"];
                                    $name = $_POST["add-name"];
                                    $data = array(
                                        "id"=> $id,
                                        "name"=> $name
                                    );
                                    $cls = new UserType();
                                    $insert = $cls->insert($data);
                                    if ($insert){
                                        $success = "Informasi berhasil disimpan";
                                    } else {
                                        $alert = "Informasi gagal disimpan";
                                    }
                                    break;
                                case "peran-pengguna":
                                    $id = $_POST["add-id"];
                                    $idmenu = $_POST["add-menu"];
                                    $idaction = $_POST["add-action"];
                                    $name = $_POST["add-name"];
                                    $description = $_POST["add-description"];
                                    $data = array(
                                        "idmenu"=> $idmenu,
                                        "idaction"=> $idaction,
                                        "name"=> $name,
                                        "description" => $description
                                    );

                                    if (empty($id)){
                                        if (!empty($idmenu)){
                                            if (!empty($idaction)){
                                                $id = $idmenu . sprintf("%03d", $idaction);
                                            }
                                        }
                                    }

                                    if (!empty($id)){
                                        if (is_numeric($id)){
                                            $data["id"] = $id;
                                        }
                                    }

                                    $cls = new Role();
                                    $insert = $cls->insert($data);
                                    if ($insert){
                                        $success = "Informasi berhasil disimpan";
                                    } else {
                                        $alert = "Informasi gagal disimpan";
                                    }
                                    break;
                                case "konstanta":
                                    $name = $_POST["add-name"];
                                    $value = $_POST["add-value"];
                                    $slug = $helper->getSlug($name);
                                    $cls = new Constant();
                                    $insert = $cls->set($name, $slug, $value);
                                    if ($insert){
                                        $success = "Informasi berhasil disimpan";
                                    } else {
                                        $alert = "Informasi gagal disimpan";
                                    }
                                    break;
                                case "meta":
                                    if ($param2) {
                                        $viewId = (int) $param2;
                                        $cls = new Meta();

                                        $dataMetaChildren = null;
                                        $viewDataMetaChildren = $cls->fetchChildren($viewId, 0);
                                        if ($viewDataMetaChildren) {
                                            $dataMetaChildren = $helper->getArrayValue($viewDataMetaChildren, "data");
                                        }

                                        if ($dataMetaChildren) {
                                            $values = null;
                                            if (isset($_POST["add-value"])) {
                                                $values = $_POST["add-value"];
                                            }
                                            $pos = 1;
                                            $cls = new MetaData();
                                            $num = 0;
                                            $insert = 0;
                                            foreach ($values as $id => $value) {
                                                if ($pos == 1) {
                                                    $lastNum = $cls->getLastNum($id);
                                                    $num = $lastNum + 1;
                                                }
                                                $data = array(
                                                    "idmeta" => $id,
                                                    "value" => $value,
                                                    "num" => $num,
                                                );
                                                $insert = $cls->insert($data);
                                                $pos++;
                                            }
                                            if ($insert){
                                                $success = "Informasi berhasil disimpan";
                                            } else {
                                                $alert = "Informasi gagal disimpan";
                                            }
                                        } else {
                                            $value = $_POST["add-value"];
                                            $cls = new MetaData();
                                            $lastNum = $cls->getLastNum($viewId);
                                            $num = $lastNum + 1;
                                            $data = array(
                                                "idmeta" => $param2,
                                                "value" => $value,
                                                "num" => $num,
                                            );
                                            $cls = new MetaData();
                                            $insert = $cls->insert($data);
                                            if ($insert){
                                                $success = "Informasi berhasil disimpan";
                                            } else {
                                                $alert = "Informasi gagal disimpan";
                                            }
                                        }
                                    } else {
                                        $cls = new Meta();
                                        $name = $_POST["add-name"];
                                        $type = $_POST["add-type"];
                                        $target = $_POST["add-target"];
                                        $slug = $helper->getSlug($name);
                                        $parent = 0;
                                        $source = '';
                                        if (isset($_POST["add-parent"])) {
                                            $parent = $_POST["add-parent"];
                                        }
                                        if (isset($_POST["add-source"])) {
                                            $source = $_POST["add-source"];
                                        }

                                        $oldSlug = $cls->getSlugAlready($slug);
                                        if ($oldSlug) {
                                            for ($i = 1; $i <= 1000; $i++){
                                                $oldData = $cls->getSlugAlready($slug . "-" . $i);
                                                if (!$oldData){
                                                    $slug = $slug . "-" . $i;
                                                    break;
                                                }
                                            }
                                        }

                                        if ($parent) {
                                            $dataParent = $cls->getById($parent);
                                            if ($dataParent) {
                                                $slugParent = $helper->getArrayValue($dataParent, "slug");
                                                if ($slugParent) {
                                                    $slug = "$slugParent-$slug";
                                                }
                                            }
                                            /*
                                            if (in_array($type, array("select", "multi-select"))) {
                                                $type = "text";
                                            }
                                            */
                                        }

                                        $data = array(
                                            "name"=> $name,
                                            "slug"=> $slug,
                                            "type"=> $type,
                                            "target"=> $target,
                                            "parent"=> $parent,
                                            "source"=> $source,
                                        );
                                        $insert = $cls->insert($data);
                                        if ($insert){
                                            $success = "Informasi berhasil disimpan";
                                        } else {
                                            $alert = "Informasi gagal disimpan";
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
                                case "tipe-pengguna":
                                    $id = $_POST["edit-id"];
                                    $name = $_POST["edit-name"];
                                    $data = array(
                                        "name"=> $name
                                    );
                                    $cls = new UserType();
                                    $update = $cls->update($id, $data);
                                    if ($update){
                                        $success = "Informasi berhasil disimpan";
                                    } else {
                                        $alert = "Informasi gagal disimpan";
                                    }
                                    break;
                                case "peran-pengguna":
                                    $id = $_POST["edit-id"];
                                    $idmenu = $_POST["edit-menu"];
                                    $idaction = $_POST["edit-action"];
                                    $name = $_POST["edit-name"];
                                    $description = $_POST["edit-description"];
                                    $data = array(
                                        "idmenu"=> $idmenu,
                                        "idaction"=> $idaction,
                                        "name"=> $name,
                                        "description" => $description
                                    );
                                    $cls = new Role();
                                    $update = $cls->update($id, $data);
                                    if ($update){
                                        $success = "Informasi berhasil disimpan";
                                    } else {
                                        $alert = "Informasi gagal disimpan";
                                    }
                                    break;
                                case "meta":
                                    if ($param2) {
                                        $viewId = (int) $param2;
                                        $cls = new Meta();
                                        $dataMetaChildren = null;
                                        if ($viewId) {
                                            $dataMetaChildren = $cls->fetchChildren($viewId, 0);
                                        }
                                        $dataChildren = null;
                                        if ($dataMetaChildren) {
                                            $dataChildren = $helper->getArrayValue($dataMetaChildren, 'data');
                                        }

                                        if ($dataChildren) {
                                            $values = null;
                                            $ids = null;
                                            if (isset($_POST["edit-value"])) {
                                                $values = $_POST["edit-value"];
                                            }
                                            if (isset($_POST["edit-id"])) {
                                                $ids = $_POST["edit-id"];
                                            }
                                            $cls = new MetaData();
                                            $update = 0;
                                            foreach ($ids as $key => $id) {
                                                $value = $values[$key];
                                                if (is_array($value)) {
                                                    $value = implode('***', $value);
                                                }
                                                $data = array(
                                                    "value"=> $value,
                                                );
                                                $update += $cls->update($id, $data);
                                            }
                                            if ($update){
                                                $success = "Informasi berhasil disimpan";
                                            } else {
                                                $alert = "Informasi gagal disimpan";
                                            }
                                        } else {
                                            $value = $_POST["edit-value"];
                                            $id = $_POST["edit-id"];
                                            $data = array(
                                                "value"=> $value,
                                            );
                                            $cls = new MetaData();
                                            $update = $cls->update($id, $data);
                                            if ($update){
                                                $success = "Informasi berhasil disimpan";
                                            } else {
                                                $alert = "Informasi gagal disimpan";
                                            }
                                        }
                                    } else {
                                        $id = $_POST["edit-id"];
                                        $name = $_POST["edit-name"];
                                        $type = $_POST["edit-type"];
                                        $target = $_POST["edit-target"];
                                        $slug = $_POST["edit-slug"];
                                        $parent = 0;
                                        $source = '';
                                        if (isset($_POST["edit-parent"])) {
                                            $parent = $_POST["edit-parent"];
                                        }
                                        if (isset($_POST["edit-source"])) {
                                            $source = $_POST["edit-source"];
                                        }
                                        /*
                                        if ($parent) {
                                            if (in_array($type, array("select", "multi-select"))) {
                                                $type = "text";
                                            }
                                        }
                                        */
                                        $data = array(
                                            "name"=> $name,
                                            "slug"=> $slug,
                                            "type"=> $type,
                                            "target"=> $target,
                                            "parent"=> $parent,
                                            "source"=> $source,
                                        );

                                        $cls = new Meta();
                                        $oldData = $cls->getById($id);

                                        if ($oldData){
                                            if ($oldData["slug"] != $slug) {
                                                $oldSlug = $cls->getSlugAlready($slug);
                                                if ($oldSlug) {
                                                    for ($i = 1; $i <= 1000; $i++){
                                                        $oldData = $cls->getSlugAlready($slug . "-" . $i);
                                                        if (!$oldData){
                                                            $data["slug"] = $slug . "-" . $i;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        $update = $cls->update($id, $data);

                                        if ($update){
                                            $success = "Informasi berhasil disimpan";
                                        } else {
                                            $alert = "Informasi gagal disimpan";
                                        }
                                    }

                                    break;
                                case "konstanta":
                                    $id = $_POST["edit-id"];
                                    $name = $_POST["edit-name"];
                                    $slug = $_POST["edit-slug"];
                                    $value = $_POST["edit-value"];
                                    $cls = new Constant();
                                    $insert = $cls->setById($id, $name, $slug, $value);
                                    if ($insert){
                                        $success = "Informasi berhasil disimpan";
                                    } else {
                                        $alert = "Informasi gagal disimpan";
                                    }
                                    break;
                            }
                        }
                        break;
                    case "delete":
                        $actionId = 4;
                        if ($helper->isHasRight($rolesUserLogin, $subMenu, $actionId)){
                            switch ($param1){
                                case "konstanta":
                                    $id = $_POST["del-meta-id"];
                                    $cls = new Constant();
                                    $del = $cls->delete($id);
                                    $success = "Data berhasil dihapus";
                                    break;
                                case "tipe-pengguna":
                                    $id = $_POST["del-type-id"];
                                    $cls = new UserType();
                                    $del = $cls->delete($id);
                                    $success = "Data berhasil dihapus";
                                    break;
                                case "peran-pengguna":
                                    $id = $_POST["del-role-id"];
                                    $cls = new Role();
                                    $del = $cls->delete($id);
                                    $success = "Data berhasil dihapus";
                                    break;
                                case "meta":
                                    if ($param2) {
                                        $viewId = (int) $param2;
                                        $num = $_POST["del-meta-id"];
                                        $cls = new Meta();
                                        $dataMetaChildren = null;
                                        if ($viewId) {
                                            $dataMetaChildren = $cls->fetchChildren($viewId, 0);
                                        }
                                        $dataChildren = null;
                                        if ($dataMetaChildren) {
                                            $dataChildren = $helper->getArrayValue($dataMetaChildren, 'data');
                                        }
                                        if ($dataChildren) {
                                            $cls = new MetaData();
                                            foreach ($dataChildren as $itemChildren) {
                                                $idMetaChild = $helper->getArrayValue($itemChildren, 'id');
                                                $del = $cls->deleteByNum($idMetaChild, $num);
                                            }
                                            $success = "Data berhasil dihapus";
                                        } else {
                                            $cls = new MetaData();
                                            $del = $cls->delete($num);
                                            $success = "Data berhasil dihapus";
                                        }
                                    } else {
                                        $id = $_POST["del-meta-id"];
                                        $cls = new Meta();
                                        $del = $cls->delete($id);
                                        $success = "Data berhasil dihapus";
                                    }
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
        $dataMeta = null;
        $dataMetaParent = null;
        $dataMetaChildren = null;
        $dataCmsType = null;

        switch ($param1) {
            case "tipe-pengguna":
                $cls = new UserType();
                $data = $cls->fetch(0);
                break;
            case "peran-pengguna":
                $cls = new Role();
                $data = $cls->fetch(0);
                $clsAction = new Action();
                $dataAction = $clsAction->fetch(0);
                break;
            case "konstanta":
                $cls = new Constant();
                $data = $cls->fetch(0);
                break;
            case "meta":
                $cls = new Meta();
                $viewId = (int) $param2;

                if ($viewId) {
                    $dataMeta = $cls->getById($viewId);
                    if ($dataMeta) {
                        $action = "Daftar-meta";
                        $dataMetaChildren = $cls->fetchChildren($viewId, 0);
                        $dataChildren = null;
                        if ($dataMetaChildren) {
                            $dataChildren = $helper->getArrayValue($dataMetaChildren, 'data');
                        }
                        if ($dataChildren) {
                            $cls = new MetaData();
                            $dataChildren = $helper->getArrayValue($dataMetaChildren, 'data');
                            if ($dataChildren) {
                                $columns = array();
                                $rows = array();
                                foreach ($dataChildren as $itemChildren) {
                                    $colId = $helper->getArrayValue($itemChildren, 'id');
                                    $columns[] = $itemChildren;
                                    $dataRaw = $cls->fetchByIdMeta($colId, 0);
                                    if ($dataRaw) {
                                        $rows[] = $helper->getArrayValue($dataRaw, 'data');
                                    }
                                }
                                $data = array(
                                    "columns" => $columns,
                                    "rows" => $rows,
                                );
                            }
                        } else {
                            $cls = new MetaData();
                            $data = $cls->fetchByIdMeta($viewId, 0);
                        }

                        $subTitle = $helper->getArrayValue($dataMeta, 'name', $title);
                        $title = "$title: $subTitle";

                        $breadcrumb[] = array(
                            "url" => $baseUrl . "$controllerPath/$param1/$viewId",
                            "title" => $subTitle,
                        );
                    }
                } else {
                    $data = $cls->fetch(0);
                    $dataMetaParent = $cls->fetchParent(0);
                }

                $cls = new CmsType();
                $dataCmsType = $cls->fetch(0);

                $cls = new Meta();
                $dataMeta = $cls->fetchSelect(0);

                break;
        }

        $this->view->viewActiveMenu = $activeMenu;
        $this->view->viewAction = $action;
        $this->view->setAction($action);
        $this->view->viewTitle = $title;
        $this->view->viewBreadcrumb = $breadcrumb;
        $this->view->viewDataAction = $dataAction;
        $this->view->viewDataMeta = $dataMeta;
        $this->view->viewDataMetaParent = $dataMetaParent;
        $this->view->viewDataMetaChildren = $dataMetaChildren;
        $this->view->viewDataCmsType = $dataCmsType;
        $this->view->viewData = $data;
        $this->view->viewId = $viewId;
        $this->view->viewSuccess = $success;
        $this->view->viewAlert = $alert;
    }
}
