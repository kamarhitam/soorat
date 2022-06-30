<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class Constant
{
    protected $config;
    protected $session;
    protected $database;
    protected $query;
    protected $table;
    protected $target;
    protected $type;

    public function __construct()
    {
        $this->session = new Session;
        $this->config = Registry::get('config');
        $this->database = Registry::get('database');
        $this->query = new Query();
        $this->table = 'meta';
        $this->target = 'constant';
        $this->type = 'textarea';
    }

    public function insert($data)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $result = 0;
        try {
            if ($data) {
                $data["target"] = $this->target;
                $data["type"] = $this->type;
            }
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

            $clsMetaData = new MetaData();
            $clsMetaData->deleteByIdMeta($value);
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
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("target = ?", $this->target)
            ;
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        $pages = 0;
        $data = null;

        $tableMetaData = 'meta_data';
        $tableMetaDataClause = "$tableMetaData.idmeta = $table.id";

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            } else {
                $pages = 1;
            }

            try {
                $queryFetch->select(
                    "$table.*" .
                    ", IFNULL($tableMetaData.value, '') AS value"
                )
                    ->from($table)
                    ->leftJoin($tableMetaData, $tableMetaDataClause)
                    ->where("$table.target = ?", $this->target)
                    ->order("$table.id", "ASC");

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
                ->andWhere("target = ?", $this->target)
            ;
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        $pages = 0;
        $data = null;

        $tableParent = 'parent';
        $tableMeta = "meta as $tableParent";
        $tableParentClause = "$tableParent.id = $table.parent";

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            }

            try {
                $queryFetch->select(
                    "$table.*" .
                    ", IFNULL($tableParent.name, '') AS `parent-name`"
                )
                    ->from($table)
                    ->leftJoin($tableMeta, $tableParentClause)
                    ->where("$table.name LIKE ?", "%$key%")
                    ->andWhere("$table.target = ?", $this->target)
                    ->order("$table.parent", "ASC")
                    ->order("$table.id", "ASC");

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

    public function get($value)
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

    public function getBySlug($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select()
                ->from($table)
                ->where("slug = ?", $value)
                ->andWhere("target = ?", $this->target)
            ;
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function set($name, $slug, $value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $result = 0;
        $data = array(
            "name" => $name,
            "slug" => $slug,
            "target" => $this->target,
            "type" => $this->type,
            "parent" => 0,
        );
        try {
            $idMeta = 0;

            $query->update($table, $data)
                ->where("slug = ?", $slug)
                ->andWhere("target = ?", $this->target);
            $idMeta = $database->query($query);
            if (!$result){
                $idMeta = $this->insert($data);
            }
            if ($idMeta) {
                $clsMetaData = new MetaData();
                $clsMetaData->set($idMeta, $value);
                $result = $clsMetaData->get($idMeta);
            }
        } catch (\NG\Exception $e) {}
        return $result;
    }

    public function setById($id, $name, $slug, $value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $result = 0;
        $data = array(
            "name" => $name,
            "slug" => $slug,
            "target" => $this->target,
            "type" => $this->type,
            "parent" => 0,
        );
        try {
            $idMeta = $id;
            $query->update($table, $data)
                ->where("id = ?", $id)
                ->andWhere("target = ?", $this->target);
            $result = $database->query($query);
            echo $idMeta;
            if ($idMeta) {
                $clsMetaData = new MetaData();
                echo $idMeta;
                $clsMetaData->set($idMeta, $value);
                $result = $clsMetaData->get($idMeta);
            }
        } catch (\NG\Exception $e) {}
        return $result;
    }

}
