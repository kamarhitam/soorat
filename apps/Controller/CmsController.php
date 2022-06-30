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

class CmsController extends NG\Controller {
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

        $breadcrumb[] = array(
            "url" => $baseUrl . $controllerPath,
            "title" => $helper->getTitle($this->menu, 'id', 'text', 410000),
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
                                case "posting":
                                    if ($param2) {
                                        switch ($param2) {
                                            case "new":
                                            case "baru":
                                                $title = $_POST["add-title"];
                                                $slug = $helper->getSlug($title);

                                                $type = $_POST["add-type"];
                                                $content = $_POST["add-editor"];

                                                $date = date("Y-m-d H:i:s");
                                                $dataInsert = array(
                                                    "type" => $type,
                                                    "date" => $date,
                                                    "title" => $title,
                                                    "slug" => $slug,
                                                    "content" => $content,
                                                );

                                                $cls = new CmsPost();
                                                $oldData = $cls->getSlugAlready($slug);

                                                if ($oldData){
                                                    for ($i = 1; $i <= 1000; $i++){
                                                        $oldData = $cls->getSlugAlready($slug . "-" . $i);
                                                        if (!$oldData){
                                                            $dataInsert["slug"] = $slug . "-" . $i;
                                                            break;
                                                        }
                                                    }
                                                }
                                                $idPost = $cls->insert($dataInsert);
                                                if ($idPost){
                                                    $success = "Informasi berhasil disimpan";

                                                    if (isset($_POST["meta"])){
                                                        $meta = $_POST["meta"];
                                                        $target = "posting";
                                                        $clsDetail = new Detail();
                                                        $clsDetail->deleteByTarget($target, $idPost);
                                                        foreach ($meta as $keyMeta => $itemMeta) {
                                                            if (is_array($itemMeta)) {
                                                                foreach ($itemMeta as $valueMeta) {
                                                                    $dataMetaInsert = array(
                                                                        "idtarget" => $idPost,
                                                                        "target" => $target,
                                                                        "key" => $keyMeta,
                                                                        "value" => $valueMeta
                                                                    );
                                                                    $clsDetail->insert($dataMetaInsert);
                                                                }
                                                                $success = "Informasi berhasil disimpan";
                                                                $alert = "";
                                                            }
                                                        }
                                                    }

                                                    Route::redirect($baseUrl . strtolower($controllerPath) . "/posting/edit/$idPost");
                                                }
                                                break;
                                        }
                                    }
                                    break;
                                case "galeri":
                                    if (isset($_POST)) {
                                        $dataUpdate = $_POST;
                                        if ($_FILES){
                                            $file = $helper->getArrayValue($_FILES, "file");
                                            $dataUpdate["file"] = $file;
                                            $cls = new CmsGallery();
                                            $cls->uploadFile($dataUpdate);
                                        }
                                    }
                                    break;
                                case "input":
                                    $title = $_POST["add-title"];
                                    $type = $_POST["add-type"];
                                    $source = '';
                                    $format = '';
                                    $parent = 0;
                                    $slug = $helper->getSlug($title);

                                    if (isset($_POST["edit-parent"])){
                                        $parent = $_POST["add-parent"];
                                    }

                                    if (isset($_POST["add-source"])){
                                        $source = $_POST["add-source"];
                                    }

                                    if (isset($_POST["add-format"])){
                                        $format = $_POST["add-format"];
                                    }

                                    $data = array(
                                        "title"=> $title,
                                        "slug"=> $slug,
                                        "type"=> $type,
                                        "source"=> $source,
                                        "parent"=> $parent,
                                        "format"=> $format,
                                    );

                                    $clsInput = new CmsInput();
                                    $oldData = $clsInput->getSlugAlready($slug);

                                    if ($oldData){
                                        for ($i = 1; $i <= 1000; $i++){
                                            $oldData = $clsInput->getSlugAlready($slug . "-" . $i);
                                            if (!$oldData){
                                                $data["slug"] = $slug . "-" . $i;
                                                break;
                                            }
                                        }
                                    }

                                    $insert = $clsInput->insert($data);

                                    if ($insert){
                                        $success = "Informasi berhasil disimpan";
                                    } else {
                                        $alert = "Informasi gagal disimpan";
                                    }
                                    break;
                            }
                        }
                        break;
                    case "edit":
                        $actionId = 3;
                        if ($helper->isHasRight($rolesUserLogin, $subMenu, $actionId)){
                            switch ($param1){
                                case "posting":
                                    $idPost = $_POST["edit-id"];
                                    $title = $_POST["edit-title"];
                                    $slug = $_POST["edit-slug"];

                                    if (!$slug){
                                        $slug =  $helper->getSlug($title);
                                    }

                                    $type = $_POST["edit-type"];
                                    $content = $_POST["edit-editor"];

                                    $dataUpdate = array(
                                        "type" => $type,
                                        "title" => $title,
                                        "slug" => $slug,
                                        "content" => $content,
                                    );

                                    $cls = new CmsPost();
                                    $update = $cls->update($idPost, $dataUpdate);

                                    if ($update){
                                        $success = "Informasi berhasil diperbarui";
                                    } else {
                                        $alert = "Informasi gagal diperbarui";
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
                                            $clsDetail = new Detail();
                                            $target = "posting";
                                            $clsDetail->deleteByTarget($target, $idPost);
                                            foreach ($details as $detailKey => $detailValue) {
                                                $dataDetailInsert = array(
                                                    "idtarget" => $idPost,
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
                                    break;
                                case "galeri":
                                    $cls = new CmsGallery();
                                    $name = $_POST["edit-name"];
                                    $idGal = $_POST["edit-id"];
                                    $dataUpdate = array(
                                        "name" => $name,
                                    );
                                    $update = $cls->update($idGal, $dataUpdate);
                                    if ($update){
                                        $success = "Informasi berhasil diperbarui";
                                    } else {
                                        $alert = "Informasi gagal diperbarui";
                                    }
                                    break;
                                case "input":
                                    $id = $_POST["edit-id"];
                                    $title = $_POST["edit-title"];
                                    $type = $_POST["edit-type"];
                                    $slug = $_POST["edit-slug"];
                                    $parent = 0;
                                    $source = '';
                                    $format = '';

                                    if (isset($_POST["edit-source"])){
                                        $source = $_POST["edit-source"];
                                    }

                                    if (isset($_POST["edit-parent"])){
                                        $parent = $_POST["edit-parent"];
                                    }

                                    if (isset($_POST["edit-format"])){
                                        $format = $_POST["edit-format"];
                                    }

                                    $dataUpdate = array(
                                        "title"=> $title,
                                        "slug"=> $slug,
                                        "type"=> $type,
                                        "source"=> $source,
                                        "parent"=> $parent,
                                        "format"=> $format,
                                    );

                                    $clsInput = new CmsInput();
                                    $oldData = $clsInput->getById($id);

                                    if ($oldData){
                                        if ($oldData["slug"] != $slug) {
                                            $oldSlug = $clsInput->getSlugAlready($slug);
                                            if ($oldSlug) {
                                                for ($i = 1; $i <= 1000; $i++){
                                                    $oldData = $clsInput->getSlugAlready($slug . "-" . $i);
                                                    if (!$oldData){
                                                        $dataUpdate["slug"] = $slug . "-" . $i;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    $update = $clsInput->update($id, $dataUpdate);

                                    if ($update){
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
                                case "posting":
                                    $id = $_POST["del-post-id"];
                                    $cls = new CmsPost();
                                    $del = $cls->delete($id);

                                    $success = "Data berhasil dihapus";
                                    break;
                                case "galeri":
                                    $id = $_POST["del-gallery-id"];
                                    $cls = new CmsGallery();
                                    $gallery = $cls->getById($id);
                                    if ($gallery){
                                        $codeGal = $gallery["code"];
                                        $dateGal = $gallery["date"];
                                        $extGal = $gallery["ext"];
                                        $del = $cls->delete($id);

                                        $galleryDir = $helper->getGalleryDir($dateGal);
                                        $filePath = $galleryDir . DS . $codeGal . "." . $extGal;

                                        if (file_exists($filePath)){
                                            @unlink($filePath);
                                        }

                                        $success = "Data berhasil dihapus";
                                    }
                                    break;
                                case "input":
                                    $id = $_POST["del-input-id"];
                                    $cls = new CmsInput();
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
        $dataMeta = null;

        switch ($param1) {
            case "posting":
                $cls = new Meta();
                $dataMeta = $cls->fetchFilter("posting", "", 0);

                $cls = new CmsPost();
                if ($param2) {
                    switch ($param2) {
                        case "new":
                        case "baru":
                            $action = "Baru";
                            $subTitle = "Baru";
                            $title = "Posting Baru";
                            $breadcrumb[] = array(
                                "url" => $baseUrl . "$controllerPath/$param1/$param2",
                                "title" => $subTitle,
                            );
                            break;
                        case "edit":
                            $viewId = (int) $param3;
                            if ($viewId) {
                                $cls = new CmsPost();
                                $data = $cls->getById($viewId);
                                if ($data) {
                                    $action = "Edit";
                                    $subTitle = "Edit";
                                    $title = "Edit Posting";

                                    $postTitle = $helper->getArrayValue($data, 'title');

                                    $breadcrumb[] = array(
                                        "url" => $baseUrl . "$controllerPath/$param1/$param2/$viewId",
                                        "title" => "$subTitle: $postTitle",
                                    );
                                } else {
                                    Route::redirect($baseUrl . "$controllerPath/$param1");
                                }
                            }
                            break;
                    }
                } else {
                    $data = $cls->fetch(0);
                }
                break;
            case "galeri":
                $cls = new CmsGallery();
                $data = $cls->fetch(0);
                break;
            case "input":
                $cls = new CmsInput();
                $data = $cls->fetch(0);
                break;
        }

        $this->view->viewActiveMenu = $activeMenu;
        $this->view->viewAction = $action;
        $this->view->setAction($action);
        $this->view->viewTitle = $title;
        $this->view->viewBreadcrumb = $breadcrumb;
        $this->view->viewDataAction = $dataAction;
        $this->view->viewDataMeta = $dataMeta;
        $this->view->viewData = $data;
        $this->view->viewId = $viewId;
        $this->view->viewSuccess = $success;
        $this->view->viewAlert = $alert;
    }
}
