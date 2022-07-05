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

class IndexController extends NG\Controller {
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

        $this->view->setLayoutFile("Front");
    }

    public function IndexAction() {
        $helper = $this->helper;
        $requests = Route::getRequests();
        $baseUrl = Uri::baseUrl();

        $controllerPath = Registry::get('controllerPath');
        $controllerName = Registry::get('controllerName');

        $cls = new CmsType();
        $dataTypeRaw = $cls->fetch(0, 0);
        $seksiType = 0;
        $posts = array();

        if ($dataTypeRaw) {
            $dataType = $helper->getArrayValue($dataTypeRaw, 'data');
            foreach ($dataType as $itemType) {
                if ($itemType["slug"] == "seksi") {
                    $seksiType = $itemType["id"];
                    break;
                }
            }
            if ($seksiType) {
                $cls = new CmsPost();
                $clsMetaData = new MetaData();
                $clsUser = new User();

                $dataPostRaw = $cls->fetchByType($seksiType, 0, 0);
                if ($dataPostRaw) {
                    $dataPost = $helper->getArrayValue($dataPostRaw, 'data');
                    if ($dataPost) {
                        foreach ($dataPost as $itemPost) {
                            $detailKeys = $helper->getArrayValue($itemPost, "detail-keys");
                            $detailValues = $helper->getArrayValue($itemPost, "detail-values");
                            if ($detailKeys){
                                $arrDetailKeys = explode("***", $detailKeys);
                                $arrDetailValues = explode("***", $detailValues);
                                for ($k = 0; $k < count($arrDetailKeys); $k++) {
                                    $itemDetailKeys = $arrDetailKeys[$k];
                                    $itemDetailValues = $arrDetailValues[$k];
                                    $itemPost[$itemDetailKeys] = $itemDetailValues;
                                    preg_match_all("^\[(.*?)]^", $itemDetailValues, $matches);
                                    if (count($matches) > 1){
                                        if ($matches[1]) {
                                            if (count($matches[1]) > 0){
                                                $selectedVal = $matches[1][0];
                                                if ($selectedVal) {
                                                    $selectedIds = explode(',', $selectedVal);
                                                    $selectedIdVals = array();
                                                    if ($selectedIds) {
                                                        foreach ($selectedIds as $selectedId) {
                                                            $arrSelectedId = explode(':', $selectedId);
                                                            if ($arrSelectedId) {
                                                                $selectedIdKey = $arrSelectedId[0];
                                                                $selectedIdVal = $arrSelectedId[1];
                                                                $arrSelectedIdVal = explode('#', $selectedIdVal);
                                                                if (count($arrSelectedIdVal) == 2) {
                                                                    $selectedIdVals[] = $arrSelectedIdVal[1];
                                                                } else {
                                                                    $selectedIdVals[] = $selectedIdVal;
                                                                }
                                                                if ($selectedIdKey) {
                                                                    $arrSelectedIdKey = explode('#', $selectedIdKey);
                                                                    if ($arrSelectedIdKey) {
                                                                        $selectedIdKeyType = $arrSelectedIdKey[0];
                                                                        if (count($arrSelectedIdKey) > 1) {
                                                                            if ($selectedIdKeyType == "meta") {
                                                                                $valSelectedId = implode(",", $selectedIdVals);
                                                                                $itemPost[$itemDetailKeys] = $clsMetaData->fetchByIds($valSelectedId);
                                                                            }
                                                                        } else {
                                                                            switch ($selectedIdKeyType) {
                                                                                case "user":
                                                                                    $valSelectedId = implode(",", $selectedIdVals);
                                                                                    $itemPost[$itemDetailKeys] = $clsUser->fetchByIds($valSelectedId);
                                                                                    break;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                unset($itemPost["detail-keys"]);
                                unset($itemPost["detail-values"]);
                            }
                            $posts[] = $itemPost;
                        }
                    }
                }
            }
        }

        if ($posts) {
            $oldPosts = $posts;
            $posts = array();
            foreach ($oldPosts as $post) {
                $kategori = $helper->getArrayValue($post, 'kategori');
                if ($kategori) {
                    unset($post['kategori']);
                    $posts[strtolower($kategori)][] = $post;
                }
            }
        }

        $cls = new CmsGallery();
        $dataGallery = $cls->fetchByTag("event");

        $title = "Beranda";

        $this->view->viewDataPosts = $posts;
        $this->view->viewDataGallery = $dataGallery;
        $this->view->viewTitle = $title;
    }
}
