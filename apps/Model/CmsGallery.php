<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class CmsGallery
{
    protected $config;
    protected $session;
    protected $database;
    protected $query;
    protected $table;

    public function __construct()
    {
        $this->session = new Session;
        $this->config = Registry::get('config');
        $this->database = Registry::get('database');
        $this->query = new Query();
        $this->table = 'gallery';
    }

    public function insert($data)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $result = 0;
        try {
            $query->insert($table, $data);
            $insert = $database->query($query);
            if ($insert) {
                $result = $database->lastInsertId();
            }
        } catch (\NG\Exception $e) {}
        return $result;
    }

    public function update($value, $data)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $result = 0;
        try {
            $query->update($table, $data)
                ->where("id = ?", $value);
            $result = $database->query($query);
        } catch (\NG\Exception $e) {}
        return $result;
    }

    public function delete($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->delete()
                ->from($table)
                ->where("id = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->query($query);
    }

    public function fetch($limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;
        try {
            $queryCount->select("COUNT($table.id) AS rowCount")
                ->from($table)
            ;
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        $pages = 0;
        $data = null;

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            } else {
                $pages = 1;
            }

            try {
                $queryFetch->select("$table.*")
                    ->from($table)
                    ->order("$table.id", "DESC");

                if ($limit > 0)
                    $queryFetch->limit("$start, $limit");
            } catch (\NG\Exception $e) {}

            $data = $database->fetchAll($queryFetch);
        }

        return array(
            "total" => $count,
            "pages" => $pages,
            "page" => $page,
            "limit" => $limit,
            "data" => $data,
        );
    }

    public function find($key, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("name LIKE ?", "%$key%")
            ;
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        $pages = 0;
        $data = null;

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            }

            try {
                $queryFetch->select("$table.code AS id, $table.name")
                    ->from($table)
                    ->where("$table.name LIKE ?", "%$key%")
                    ->order("id", "ASC");

                if ($limit > 0)
                    $queryFetch->limit("$start, $limit");
            } catch (\NG\Exception $e) {}

            $data = $database->fetchAll($queryFetch);
        }

        return array(
            "total" => $count,
            "pages" => $pages,
            "page" => $page,
            "limit" => $limit,
            "data" => $data,
        );
    }

    public function getById($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select()
                ->from($table)
                ->where("id = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getByName($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select()
                ->from($table)
                ->where("name = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getByDateAndName($date, $name)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select()
                ->from($table)
                ->where("date = ?", $date)
                ->andWhere("name = ?", $name);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getByCode($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select()
                ->from($table)
                ->where("code = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function uploadFile($data){
        $helper = new Helper();
        $result = "";

        $file = $helper->getArrayValue($data, "file");
        $date = date("Y-m-d");

        if ($file){
            $file_name = $helper->getArrayValue($file, "name");
            if ($file_name){
                $file_size = $helper->getArrayValue($file, "size");
                if ($file_size){
                    $file_tmp = $helper->getArrayValue($file, "tmp_name");
                    $array = explode('.', $file_name);
                    $file_ext = strtolower(end($array));

                    if ($file_ext){
                        if ($file_size <= 10485760){
                            $unique = $helper->generateKey("", 20);
                            $fileCode = md5($unique);

                            $dir = ROOT . DS . UPLOADS . DS . GALLERY . DS . str_replace("-", DS, $date);
                            if (!file_exists($dir))
                                if (!is_dir($dir)) mkdir($dir, 0755, true);

                            $newFile = strtolower($fileCode) . "." . $file_ext;
                            $newGallery = $dir . DS . $newFile;

                            if (!file_exists($newGallery)){
                                @move_uploaded_file($file_tmp, $newGallery);
                                if (file_exists($newGallery)){
                                    $dataInsert = array(
                                        "date" => $date,
                                        "code" => $fileCode,
                                        "name" => $fileCode,
                                        "ext" => $file_ext,
                                        "tag" => 0
                                    );
                                    $insert = $this->insert($dataInsert);
                                    $result = $insert;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function getCount()
    {
        $database = $this->database;
        $queryCount = new Query();
        $table = $this->table;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table);
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        return $count;
    }

}
