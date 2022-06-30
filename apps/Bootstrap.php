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
require_once (ROOT . DS . LIBS . DS . NG . 'Bootstrap.php');

use NG\Bootstrap as FrameworkBootstrap;
use NG\Database;
use NG\Registry;
use NG\Route;

/**
 * Bootstrap
 * @package NG
 * @subpackage library
 * @version 0.1
 * @copyright (c) 2012, Nick Gejadze
 */
class Bootstrap extends FrameworkBootstrap {
    public function _initConfig() {
        $fileConfig = ROOT . DS . APPS . DS . 'Config' . DS . 'setting.json';
        $configs = null;
        if (file_exists($fileConfig)){
            $fileContent = @file_get_contents($fileConfig);
            if ($fileContent){
                $dataConfigs = json_decode($fileContent, true);
                $newConfigs = null;
                if ($dataConfigs != null){
                    foreach($dataConfigs as $key => $value) {
                        $configs[] = array('key' => $key, 'value' => $value);
                    }

                    foreach($dataConfigs as $key => $value) {
                        $newValue = $value;

                        foreach($configs as $config) {
                            $newValue = str_replace("{" . $config['key'] . "}", $config['value'], $newValue);
                        }

                        if (!defined(strtoupper($key)))
                            define(strtoupper($key), $newValue);

                        if (strtoupper($key) == "SUB_DIR"){
                            $subdir = $newValue;
                        }
                        $newConfigs[strtoupper($key)] = $value;
                    }

                    if (!empty($subdir)){
                        if (!defined("PUBLIC_PATH")) define('PUBLIC_PATH', ROOT . "/" . $subdir);
                    } else {
                        if (!defined("PUBLIC_PATH")) define('PUBLIC_PATH', ROOT);
                    }

                }

                Registry::set("config", $newConfigs);

                $protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https://' : 'http://';
                $requestURI = $protocol . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

                Registry::set("url", $requestURI);
            }
        }
    }

    public function _initRoute() {
        $route = \NG\Configuration::loadINIFile(ROOT . DS . APPS . DS . CONFIG . DS . 'route.ini');
        Route::addRoute($route['routes']['single']);
        Route::addRoute($route['routes']['first']);
        Route::addRoute($route['routes']['second']);
        Route::addRoute($route['routes']['third']);
        Route::addRoute($route['routes']['fourth']);
        Route::addRoute($route['routes']['fifth']);
        Route::addRoute($route['routes']['sixth']);
        Route::addRoute($route['routes']['seventh']);
        Route::addRoute($route['routes']['eight']);
        Route::addRoute($route['routes']['ninth']);
        Route::addRoute($route['routes']['teen']);
    }

    public function _initController() {
        $jsonController = file_get_contents(ROOT . DS . APPS . DS . 'Config' . DS . 'controller.json');
        $arrController = json_decode($jsonController, true);
        Registry::set("controller", $arrController);

        $controller = Route::getController();
        $controller = strtolower($controller);
        $controllerPath = $controller;
        $controllerName = $controller;

        foreach ($arrController as $item) {
            $name = $item['name'];
            $path = $item['path'];
            if (strtolower($name) == strtolower($controller) && $name != $path) {
                $controllerPath = $path;
                $controllerName = $name;
                break;
            }
            if (strtolower($path) == strtolower($controller) && $name != $path) {
                $controllerPath = $path;
                $controllerName = $name;
                break;
            }
        }

        Registry::set("controllerName", $controllerName);
        Registry::set("controllerPath", $controllerPath);
    }

    public function _initDB() {
        $dbFileName = "database";
        $dbFileNamePath = ROOT . DS . APPS . DS . 'Config' . DS . $dbFileName . ".json";

        $jsonDB = file_get_contents($dbFileNamePath);
        $dbConfig = json_decode($jsonDB, true);

        if (is_array($dbConfig)){
            $dbName = $dbConfig['dbname'];
            if ($dbName) {
                $database = new Database($dbConfig);
                Registry::set("database", $database);

                if ($database){
                    $ping = $database->ping();
                    if ($ping){
                        $dbDir = ROOT . DS . APPS . DS . DATABASE;
                        $dbFiles = array();
                        foreach (new DirectoryIterator($dbDir . DS) as $fileInfo) {
                            if(!$fileInfo->isDot()) {
                                $filePath = $fileInfo->getPathname();
                                $fileName = $fileInfo->getFilename();

                                $array = explode('.' , $fileName);
                                $fileExt = strtolower(end($array));

                                if (strtolower($fileExt) == "sql"){
                                    $dbFiles[] = array(
                                        "path" => $filePath,
                                        "file" => $fileName,
                                        "name" => substr($fileName, 0, strlen($fileName) - strlen($fileExt) - 1),
                                    );
                                }
                            }
                        }

                        $clsDBHelper = new DBHelper();
                        $tables = $clsDBHelper->showTables();

                        if (is_array($dbFiles)){
                            foreach ($dbFiles as $file){
                                $continue = 1;
                                $tableName = $file["name"];
                                $filePath = $file["path"];
                                if (is_array($tables)){
                                    if (in_array($tableName, $tables)){
                                        $continue = 0;
                                    }
                                }
                                if ($continue == 1){
                                    if (file_exists($filePath)){
                                        $query = @file_get_contents($filePath);
                                        if ($query){
                                            $database->query($query);
                                            $clsDBHelper->createDefaultRow($tableName);
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

    public function _initMenu() {
        $jsonMenu = file_get_contents(ROOT . DS . APPS . DS . 'Config' . DS . 'menu.json');
        $arrMenu = json_decode($jsonMenu, true);
        Registry::set("menu", $arrMenu);
    }
}
