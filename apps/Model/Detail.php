<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class Detail
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
        $this->table = 'detail';
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

    public function deleteByTarget($target, $idTarget)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->delete()
                ->from($table)
                ->where("idtarget = ?", $idTarget)
                ->andWhere("target = ?", $target);
        } catch (\NG\Exception $e) {}

        return $database->query($query);
    }

    public function fetchByTarget($target, $idTarget, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("idtarget = ?", $idTarget)
                ->andWhere("target = ?", $target)
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
                $queryFetch->select("$table.*"
                )
                    ->from($table)
                    ->where("$table.idtarget = ?", $idTarget)
                    ->andWhere("target = ?", $target)
                    ->order("$table.idtarget", "ASC");

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

    public function fetchByTargetAndMeta($target, $idTarget, $key, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("idtarget = ?", $idTarget)
                ->andWhere("target = ?", $target)
                ->andWhere("key = ?", $key)
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
                $queryFetch->select("$table.*"
                )
                    ->from($table)
                    ->where("$table.idtarget = ?", $idTarget)
                    ->andWhere("target = ?", $target)
                    ->andWhere("key = ?", $key)
                    ->order("$table.idtarget", "ASC");

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

    public function getByTargetAndMeta($target, $idTarget, $key, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryFetch = new Query();
        $table = $this->table;

        try {
            $queryFetch->select("$table.*"
            )
                ->from($table)
                ->where("$table.idtarget = ?", $idTarget)
                ->andWhere("target = ?", $target)
                ->andWhere("key = ?", $key)
                ->order("$table.idtarget", "ASC");
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($queryFetch);
    }
    public function getAutoNumber($target, $key)
    {
        $database = $this->database;
        $queryFetch = new Query();
        $table = $this->table;
        $lastNum = 1;
        try {
            $queryFetch->select("CAST(value AS INT) AS nomor"
            )
                ->from($table)
                ->where("target = ?", $target)
                ->andWhere("key = ?", $key)
                ->order("nomor", "DESC")
                ->limit("0, 1");
        } catch (\NG\Exception $e) {}

        $row = $database->fetchRow($queryFetch);
        if (is_array($row)) {
            $count = $row['nomor'];
            $lastNum = $count + 1;
        }
        return $lastNum;
    }
}
