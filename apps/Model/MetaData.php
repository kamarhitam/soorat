<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class MetaData
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
        $this->table = 'meta_data';
    }

    public function insert($data)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $result = 0;
        try {
            $query->insert($table, $data);
            $result = $database->query($query);
        } catch (\NG\Exception $e) {}
        return $result;
    }

    public function update($id, $data)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $result = 0;
        try {
            $query->update($table, $data)
                ->where("id = ?", $id);
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

    public function deleteByIdMeta($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->delete()
                ->from($table)
                ->where("idmeta = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->query($query);
    }

    public function deleteByNum($idMeta, $num)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->delete()
                ->from($table)
                ->where("idmeta = ?", $idMeta)
                ->andWhere("num = ?", $num);
        } catch (\NG\Exception $e) {}

        return $database->query($query);
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

    public function fetchByIdMeta($idMeta, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("$table.idmeta = ?", $idMeta);
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
                $queryFetch->select("$table.*")
                    ->from($table)
                    ->where("$table.idmeta = ?", $idMeta)
                    ->order("$table.num", "ASC")
                    ->order("$table.idmeta", "ASC");

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

    public function set($idMeta, $value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $result = 0;
        $data = array("idmeta" => $idMeta, "value" => $value);

        try {
            $query->update($table, $data)
                ->where("idmeta = ?", $idMeta);
            $result = $database->query($query);
            if (!$result){
                $data = array(
                    "idmeta" => $idMeta,
                    "num" => 0,
                    "value" => $value
                );
                $query->insert($table, $data);
                $result = $database->query($query);
            }
        } catch (\NG\Exception $e) {}
        return $result;
    }

    public function get($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select()
                ->from($table)
                ->where("idmeta = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getLastNum($idMeta)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $lastNum = 0;
        try {
            $query->select("$table.num")
                ->from($table)
                ->where("$table.idmeta = ?", $idMeta)
                ->order("$table.num", "DESC");

        } catch (\NG\Exception $e) {}

        $data = $database->fetchAll($query);
        if ($data) {
            if (is_array($data)) {
                if (count($data) > 0) {
                    $lastNum = $data[0]["num"];
                }
            }
        }
        return $lastNum;
    }

    public function fetchByIdParent($idParent, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;
        $tableMeta = "meta";
        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($tableMeta)
                ->where("parent = $idParent");
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
                $queryFetch->select()
                    ->from($tableMeta)
                    ->where("parent = $idParent");
            } catch (\NG\Exception $e) {}
            $childes = $database->fetchAll($queryFetch);

            if ($childes) {
                $ids = array();
                foreach ($childes as $children) {
                    $ids[] = $children["id"];
                }
                if ($ids) {
                    $idMetas = implode(",", $ids);
                    try {
                        $queryFetch->select("$table.*")
                            ->from($table)
                            ->where("$table.idmeta IN ($idMetas)")
                            ->order("$table.num", "ASC")
                            ->order("$table.idmeta", "ASC");
                    } catch (\NG\Exception $e) {}

                    $rows = $database->fetchAll($queryFetch);
                    $newRows = array();
                    if ($rows) {
                        foreach ($rows as $row) {
                            $num = $row["num"];
                            /*
                            $idMeta = $row["idmeta"];
                            $value = $row["value"];
                            unset($row["id"]);
                            unset($row["idmeta"]);
                            unset($row["value"]);
                            */
                            $row["idparent"] = $idParent;
                            $newRows[$num][] = $row;
                        }
                    }

                    if ($newRows) {
                        $rows = array();
                        $count = count($newRows);
                        $no = 0;
                        foreach ($newRows as $num => $row) {
                            foreach ($row as $id => $cell) {
                                $rows[$no][$num][$id] = $cell;
                            }
                            $no++;
                        }
                        if ($limit > 0){
                            $pages = (int) ($count / $limit);
                            if (($count % $limit) > 0) $pages += 1;
                        }
                        $end = ($page) * $limit;
                        for ($i = $start; $i < $end; $i++) {
                            if (count($rows) > $i) {
                                $data[] = $rows[$i];
                            }
                        }
                    }
                }
            }
        }

        //print_r($data);

        return array(
            "total" => $count,
            "pages" => $pages,
            "page" => $page,
            "limit" => $limit,
            "data" => $data,
        );
    }

    public function getByIdMetaAndNum($idMeta, $num)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select()
                ->from($table)
                ->where("idmeta = ?", $idMeta)
                ->andWhere("num = ?", $num);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function fetchByIds($ids)
    {
        $database = $this->database;
        $queryFetch = new Query();
        $table = $this->table;
        $data = null;
        $result = "";
        try {
            $queryFetch->select("value")
                ->from($table)
                ->where("id IN ($ids)");
        } catch (\NG\Exception $e) {}
        $rows = $database->fetchAll($queryFetch);
        if ($rows) {
            foreach ($rows as $row) {
                $name = $row["value"];
                $data[] = $name;
            }
            $result = implode(", ", $data);
        }
        return $result;
    }
}
