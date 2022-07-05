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

namespace NG;

/**
 * View
 * @package NG
 * @subpackage library
 * @version 0.1
 * @copyright (c) 2012, Nick Gejadze
 */
class View {

    /**
     * $controller
     * Holds Conroller name
     * @access protected
     * @var string
     */
    protected $controller;

    /**
     * $action
     * Holds Action name
     * @access protected
     * @var string
     */
    protected $action;

    /**
     * $layout
     * @access protected
     * @var boolean
     */
    protected $layout = true;

    /**
     * $noRender
     * @access protected
     * @var boolean
     */
    protected $noRender = false;

    /**
     * $layoutFile
     * @access protected
     * @var string
     */
    protected $layoutFile = 'Layout';

    protected $configData;

    protected $controllerData;
    protected $title;
    /**
     * __construct()
     * Sets controller and action object
     * @param string $controller
     * @param string $action
     * @access public
     */
    public function __construct($controller, $action) {
        $this->controller = $controller;
        $this->action = strtolower($action);
        $this->configData = \NG\Registry::get('config');
        $this->controllerData = \NG\Registry::get('controller');

        if (is_array($this->controllerData)){
            foreach ($this->controllerData as $itemController){
                $itemName = $this->getArrayValue($itemController, "name");
                $itemTitle = $this->getArrayValue($itemController, "title");
                if (strtolower($itemName) == strtolower($controller)){
                    $this->setTitle($itemTitle);
                    break;
                }
            }
        }
    }

    private function getArrayValue($array = null, $key = null, $default = ""){
        $result = $default;
        if ($array !== null){
            if (is_array($array)){
                if (array_key_exists($key, $array)){
                    $result = $array[$key];
                }
            }
        }
        return $result;
    }

    /**
     * setLayout()
     * Sets layout object
     * @access public
     * @param boolean $bool
     */
    public function setLayout($bool = true) {
        $this->layout = $bool;
    }

    public function setAction($action) {
        $this->action = $action;
    }

    public function getTitle() {
        return $this->title;
    }

    public function setTitle($value) {
        $this->title = $value;
    }

    /**
     * setNoRender()
     * Sets noRender object
     * @access public
     * @param boolean $bool
     */
    public function setNoRender($bool = false) {
        $this->noRender = $bool;
    }

    /**
     * setLayoutFile()
     * sets layout filename object
     * @access public
     * @param string $filename
     */
    public function setLayoutFile($filename) {
        $this->layoutFile = $filename;
    }

    /**
     * set()
     * Set object to be used from view
     * @access public
     * @param string $name
     * @param string $value
     */
    public function set($name, $value) {
        $this->{$name} = $value;
    }

    /**
     * loadLayout()
     * Includes layout file
     * @access public
     * @throws Exception
     */
    public function loadLayout() {
        if ($this->layout):
            try {
                $layoutFile = ROOT . DS . APPS . DS . LAYOUT . DS . $this->layoutFile . '.phtml';
                if (!file_exists($layoutFile)):
                    throw new Exception($layoutFile);
                endif;

                $compactMode = 0;
                $configData = $this->configData;
                if (isset($configData['COMPACT_MODE'])){
                    $compactMode = $configData['COMPACT_MODE'];
                }
                if ($compactMode >= 1){
                    ob_start();
                    include ($layoutFile);
                    $content = ob_get_clean();
                    $content = $this->removeWhiteSpace($content);
                    echo $content;
                } else {
                    include ($layoutFile);
                }
            } catch (\NG\Exception $e) {
                if (DEVELOPMENT_ENVIRONMENT):
                    exit("Could not find layout file: " . $e->getMessage());
                endif;
            }
        else:
            $this->render();
        endif;
    }

    /**
     * render()
     * Check is render is enabled and includes view file
     * @access public
     * @throws Exception
     */
    public function render() {
        if (!$this->noRender):
            try {
                $controller = $this->controller;
                if (!empty($this->action)){
                    $viewFile = ROOT . DS . APPS . DS . VIEW . DS . $controller . DS . ucfirst(strtolower($this->action)) . '.phtml';
                    if (!file_exists($viewFile)){
                        $viewFile = ROOT . DS . APPS . DS . VIEW . DS . $controller . DS . $this->action . '.phtml';
                        if (!file_exists($viewFile)){
                            @file_put_contents($viewFile, "");
                        }
                    }
                    include ($viewFile);
                }
                $dialogFile = ROOT . DS . APPS . DS . DIALOG . DS . $controller . DS . $this->action . '.phtml';
                if (!file_exists($dialogFile)){
                    $dialogFile = ROOT . DS . APPS . DS . DIALOG . DS . $controller . DS . ucfirst(strtolower($this->action))  . '.phtml';
                }
                if (file_exists($dialogFile)) include ($dialogFile);
            } catch (Exception $e) {
                if (DEVELOPMENT_ENVIRONMENT):
                    exit("Could not find view file: " . $e->getMessage());
                endif;
            }
        endif;
    }

    public function template($name) {
        if (!$this->noRender):
            try {
                $dir = ROOT . DS . APPS . DS . TEMPLATE;
                $file = $dir . DS . "$name.phtml";

                if (!file_exists($file)){
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                }
                if (file_exists($file)){
                    ob_start();
                    include($file);
                    $content = ob_get_clean();
                    $content = $this->removeWhiteSpace($content);
                    echo $content;
                }
            } catch (Exception $e) {
                if (DEVELOPMENT_ENVIRONMENT):
                    exit("Could not find view file: " . $e->getMessage());
                endif;
            }
        endif;
    }

    public function section($name) {
        if (!$this->noRender):
            try {
                $dir = ROOT . DS . APPS . DS . SECTION;
                $file = $dir . DS . ucfirst(strtolower($name)) . DS . "Index.phtml";
                if (!file_exists($file)){
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                }
                if (file_exists($file)){
                    ob_start();
                    include($file);
                    $content = ob_get_clean();
                    $content = $this->removeWhiteSpace($content);
                    return $content;
                }
            } catch (Exception $e) {
                if (DEVELOPMENT_ENVIRONMENT):
                    exit("Could not find view file: " . $e->getMessage());
                endif;
            }
        endif;
        return "";
    }

    private function removeWhiteSpace($text){
        $text = $this->replaceConfig($text);
        $text = preg_replace('/[\t\n\r\0\x0B]/', '', $text);
        $text = preg_replace('/([\s])\1+/', ' ', $text);
        $text = trim($text);
        return $text;
    }

    private function replaceConfig($text){
        $configData = $this->configData;
        foreach($configData as $key => $value) {
            $text = str_replace("{" . $key . "}", $value, $text);
        }
        return $text;
    }
}
