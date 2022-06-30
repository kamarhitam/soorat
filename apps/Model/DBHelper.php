<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class DBHelper
{
    protected $config;
    protected $session;
    protected $database;
    protected $query;

    public function __construct()
    {
        $this->session = new Session;
        $this->config = Registry::get('config');
        $this->database = Registry::get('database');
        $this->query = new Query();
    }

    public function showTables()
    {
        $database = $this->database;
        $result = null;
        $query = "SHOW TABLES";
        $data = $database->query($query);
        if ($data){
            if (is_array($data)){
                foreach ($data as $index => $item){
                    if (is_array($item)){
                        foreach ($item as $key => $value){
                            $result[] = $value;
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function createDefaultRow($table){
        $helper = new Helper();
        switch ($table){
            case "action":
                $cls = new Action();
                $cls->insert(array("id" => 1, "name" => "View"));
                $cls->insert(array("id" => 2, "name" => "Add"));
                $cls->insert(array("id" => 3, "name" => "Edit"));
                $cls->insert(array("id" => 4, "name" => "Delete"));
                break;
            case "user_type":
                $cls = new UserType();
                $cls->insert(array("id" => 1, "name" => "Generic User"));
                $cls->insert(array("id" => 9, "name" => "Administrator"));
                break;
            case "role":
                $cls = new Role();
                $jsonMenu = file_get_contents(ROOT . DS . APPS . DS . 'Config' . DS . 'menu.json');
                $arrMenu = json_decode($jsonMenu, true);
                $menus = $helper->collectMenu($arrMenu);
                $actions = array(1, 2, 3, 4);
                $actionTypes = array("View", "Add", "Edit", "Delete");
                foreach ($menus as $menu){
                    $id = $helper->getArrayValue($menu, "id");
                    $children = $helper->getArrayValue($menu, "children");
                    // $parent = $helper->getArrayValue($menu, "parent");
                    $text = $helper->getArrayValue($menu, "text");
                    if (!$children){
                        $i = 0;
                        foreach ($actions as $action){
                            $idRole = $id . sprintf("%03d", $action);
                            $actionType = $actionTypes[$i];

                            $nameRole = "$actionType $text";
                            /*
                            if ($parent == $text){
                                $nameRole = "$actionType $text";
                            }
                            */
                            if (empty($parent)){
                                $nameRole = "$actionType $text";
                            }

                            $cls->insert(array("id" => $idRole, "idmenu" => $id, "idaction" => $action, "name" => $nameRole, "description" => $nameRole));
                            $i++;
                        }
                    }
                }
                break;
            case "meta":
                $cls = new Meta();
                $cls->insert(array("id" => 1, "type" => "text", "target" => "constant",
                    "name" => "Nama Instansi", "slug" => "nama-instansi", "parent" => '0'));
                $cls->insert(array("id" => 2, "type" => "text", "target" => "constant",
                    "name" => "Alamat Instansi (1)", "slug" => "alamat-instansi-1", "parent" => '0'));
                $cls->insert(array("id" => 3, "type" => "text", "target" => "constant",
                    "name" => "Alamat Instansi (2)", "slug" => "alamat-instansi-2", "parent" => '0'));
                $cls->insert(array("id" => 4, "type" => "text", "target" => "constant",
                    "name" => "Kota Instansi", "slug" => "kota-instansi", "parent" => '0'));
                $cls->insert(array("id" => 5, "type" => "text", "target" => "constant",
                    "name" => "Kode Pos Instansi", "slug" => "kode-pos-instansi", "parent" => '0'));
                $cls->insert(array("id" => 6, "type" => "text", "target" => "constant",
                    "name" => "Telepon Instansi (1)", "slug" => "telepon-instansi-1", "parent" => '0'));
                $cls->insert(array("id" => 7, "type" => "text", "target" => "constant",
                    "name" => "Telepon Instansi (2)", "slug" => "telepon-instansi-2", "parent" => '0'));
                $cls->insert(array("id" => 8, "type" => "text", "target" => "constant",
                    "name" => "Tanggal Pemasangan", "slug" => "tanggal-pemasangan", "parent" => '0'));
                $cls->insert(array("id" => 9, "type" => "select", "target" => "",
                    "name" => "Pengembang", "slug" => "pengembang", "parent" => '0'));
                $cls->insert(array("id" => 10, "type" => "text", "target" => "",
                    "name" => "Nama", "slug" => "pengembang-nama", "parent" => 9));
                $cls->insert(array("id" => 11, "type" => "text", "target" => "",
                    "name" => "Jabatan", "slug" => "pengembang-jabatan", "parent" => 9));
                break;
            case "meta_data":
                $cls = new MetaData();
                $installDate = date("Ymd");
                $cls->insert(array("idmeta" => 1, "num" => "0", "value" => "Inochi Software"));
                $cls->insert(array("idmeta" => 2, "num" => "0", "value" => "Perumahan Grand Metro Pamengkang Blok D No. 23"));
                $cls->insert(array("idmeta" => 3, "num" => "0", "value" => "Desa Pamengkang Kecamatan Mundu"));
                $cls->insert(array("idmeta" => 4, "num" => "0", "value" => "Kabupaten Cirebon"));
                $cls->insert(array("idmeta" => 5, "num" => "0", "value" => "45173"));
                $cls->insert(array("idmeta" => 6, "num" => "0", "value" => "08988543210"));
                $cls->insert(array("idmeta" => 7, "num" => "0", "value" => "0231-123456"));
                $cls->insert(array("idmeta" => 8, "num" => "0", "value" => $installDate));
                $cls->insert(array("idmeta" => 10, "num" => 1, "value" => "Agung Novian"));
                $cls->insert(array("idmeta" => 11, "num" => 1, "value" => "Ketua"));
                break;
            case "gallery":
                $installDate = date("Y-m-d");
                $cls = new CmsGallery();
                $uniqueCode = $helper->generateKey("", 20);
                $fileCode = md5($uniqueCode);
                $dir = ROOT . DS . UPLOADS . DS . GALLERY . DS . str_replace("-", DS, $installDate);
                if (!file_exists($dir))
                    if (!is_dir($dir)) mkdir($dir, 0755, true);

                $newFile = strtolower($fileCode) . ".png";
                $newImage = $dir . DS . $newFile;

                $oldImage = ROOT . DS . ASSETS . DS . "img" . DS . "inochi.png";
                @copy($oldImage, $newImage);
                $cls->insert(array("date" => $installDate, "code" => $fileCode, "name" => "logo", "ext" => "png"));
                break;
            case "post":
                $cls = new CmsPost();
                $cls->insert(array("id" => 1, "type" => 1, "title" => "Header 1", "slug" => "header-1",
                    "date" => date("Y-m-d H:i:s"), "content" => $this->putTemplate('header-1')));
                $cls->insert(array("id" => 2, "type" => 1, "title" => "Sign 1", "slug" => "sign-1",
                    "date" => date("Y-m-d H:i:s"), "content" => $this->putTemplate('sign-1')));
                $cls->insert(array("id" => 3, "type" => 1, "title" => "Footer 1", "slug" => "footer-1",
                    "date" => date("Y-m-d H:i:s"), "content" => $this->putTemplate('footer-1')));
                $cls->insert(array("id" => 4, "type" => 2, "title" => "Ucapan Selamat Datang", "slug" => "ucapan-selamat-datang",
                    "date" => date("Y-m-d H:i:s"), "content" => $this->putTemplate('ucapan-selamat-datang')));
                break;
            case "input":
                $cls = new CmsInput();
                $cls->insert(array("id" => 1, "type" => "auto-number", "title" => "Nomor Surat", "slug" => "nomor-surat",
                    "source" => "", "format" => "0000", "parent" => 0));
                $cls->insert(array("id" => 2, "type" => "text", "title" => "Jenis Surat", "slug" => "jenis-surat",
                    "source" => "", "format" => "ucase", "parent" => 1));
                $cls->insert(array("id" => 3, "type" => "select", "title" => "Bulan Surat", "slug" => "bulan-surat",
                    "source" => "month", "format" => "roman", "parent" => 1));
                $cls->insert(array("id" => 4, "type" => "select", "title" => "Tahun Surat", "slug" => "tahun-surat",
                    "source" => "year", "format" => "", "parent" => 1));
                $cls->insert(array("id" => 5, "type" => "text", "title" => "Judul Surat", "slug" => "judul-surat",
                    "source" => "", "format" => "", "parent" => 0));
                $cls->insert(array("id" => 6, "type" => "date", "title" => "Tanggal Surat", "slug" => "tanggal-surat",
                    "source" => "", "format" => "", "parent" => 0));
                $cls->insert(array("id" => 7, "type" => "select", "title" => "Pengembang", "slug" => "pengembang",
                    "source" => "meta#pengembang", "format" => "", "parent" => 0));
                break;
            case "post_input":
                $cls = new CmsPostInput();
                $cls->insert(array("idpost" => 4, "idinput" => 1, "num" => 1));
                /*
                $cls->insert(array("idpost" => 4, "idinput" => 2, "num" => 2));
                $cls->insert(array("idpost" => 4, "idinput" => 3, "num" => 3));
                $cls->insert(array("idpost" => 4, "idinput" => 4, "num" => 4));
                */
                $cls->insert(array("idpost" => 4, "idinput" => 5, "num" => 2));
                $cls->insert(array("idpost" => 4, "idinput" => 6, "num" => 3));
                $cls->insert(array("idpost" => 4, "idinput" => 7, "num" => 4));
                break;
            case "letter":
                $installDate = date("Y-m-d H:i:s");
                $cls = new Letter();
                $prefixCode = "LTR";
                $postId = 4;
                $code = $helper->generateKey($prefixCode . sprintf('%04d', $postId), 13);
                $cls->insert(array("idpost" => $postId, "code" => $code, "createdate" => $installDate, "createby" => 1));
                break;
            case "detail":
                $cls = new Detail();
                $installDate = date("d-m-Y");
                $year = date("Y");
                $month = date("m");
                $cls->insert(array("idtarget" => 1, "target" => "letter",
                    "key" => "judul-surat", "value" => "Ucapan Selamat Datang"));
                $cls->insert(array("idtarget" => 1, "target" => "letter",
                    "key" => "nomor-surat", "value" => 1));
                $cls->insert(array("idtarget" => 1, "target" => "letter",
                    "key" => "jenis-surat", "value" => "SD"));
                $cls->insert(array("idtarget" => 1, "target" => "letter",
                    "key" => "bulan-surat", "value" => (int) $month));
                $cls->insert(array("idtarget" => 1, "target" => "letter",
                    "key" => "tahun-surat", "value" => (int) $year));
                $cls->insert(array("idtarget" => 1, "target" => "letter",
                    "key" => "pengembang", "value" => "[meta#pengembang:1#9]"));
                $cls->insert(array("idtarget" => 1, "target" => "letter",
                    "key" => "tanggal-surat", "value" => $installDate));
                break;
        }
    }
    private function putTemplate($slug){
        $content = "";
        $path = ROOT . DS . APPS . DS . "Template" . DS . "Post" . DS . "$slug.html";
        if (file_exists($path)){
            ob_start();
            include($path);
            $content = ob_get_clean();
        }
        return $content;
    }
}
