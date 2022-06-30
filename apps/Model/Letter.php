<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class Letter
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
        $this->table = 'letter';
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

    public function fetch($limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table);
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        $pages = 0;
        $data = null;

        $tableUser = 'user';
        $tableUserClause = "$tableUser.id = $table.createby";

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            }

            try {
                $queryFetch->select("$table.*"  .
                    ", IFNULL($tableUser.name, '') AS createbyname"
                )
                    ->from($table)
                    ->leftJoin($tableUser, $tableUserClause)
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

    public function fetchByPostId($postId, $year = 0, $month = 0, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("idpost = ?", $postId);
            if ($year) {
                $queryCount->andWhere("YEAR(`createdate`) = ?", $year);
                if ($month) {
                    $queryCount->andWhere("MONTH(`createdate`) = ?", $month);
                }
            }
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        $pages = 0;
        $data = null;

        $tableUser = 'user';
        $tableUserClause = "$tableUser.id = $table.createby";

        $tableDetail = 'detail';
        $tableDetailClause = "$table.id = $tableDetail.idtarget AND $tableDetail.target = 'letter'";

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            }

            try {
                $queryFetch->select("$table.*" .
                    ", IFNULL($tableUser.name, '') AS createbyname" .
                    ", GROUP_CONCAT($tableDetail.key ORDER BY $tableDetail.id ASC SEPARATOR '***') as `detail-keys`" .
                    ", GROUP_CONCAT($tableDetail.value ORDER BY $tableDetail.id ASC SEPARATOR '***') as `detail-values`"
                )
                    ->from($table)
                    ->leftJoin($tableUser, $tableUserClause)
                    ->leftJoin($tableDetail, $tableDetailClause)
                    ->where("$table.idpost = ?", $postId);
                if ($year) {
                    $queryFetch->andWhere("YEAR(`createdate`) = ?", $year);
                    if ($month) {
                        $queryFetch->andWhere("MONTH(`createdate`) = ?", $month);
                    }
                }
                $queryFetch->group("$tableDetail.idtarget");
                $queryFetch->order("$table.id", "DESC");

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
        $data = null;

        $tableUser = 'user';
        $tableUserClause = "$tableUser.id = $table.createby";

        try {
            $query->select("$table.*" .
                ", IFNULL($tableUser.name, '') AS createbyname"
            )
                ->from($table)
                ->leftJoin($tableUser, $tableUserClause)
                ->where("$table.id = ?", $value);
        } catch (\NG\Exception $e) {}

        $data = $database->fetchRow($query);

        if ($data) {
            $cls = new Detail();
            $detail = $cls->fetchByTarget("letter", $value, 0);
            if ($detail) {
                $dataDetail = $detail["data"];
                if ($dataDetail) {
                    foreach ($dataDetail as $item) {
                        $itemKey = $item['key'];
                        $itemVal = $item['value'];
                        $data[$itemKey] = $itemVal;
                    }
                }
            }
        }

        return $data;
    }

    public function getByCode($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $data = null;

        $tableUser = 'user';
        $tableUserClause = "$tableUser.id = $table.createby";

        try {
            $query->select("$table.*" .
                ", IFNULL($tableUser.name, '') AS createbyname"
            )
                ->from($table)
                ->leftJoin($tableUser, $tableUserClause)
                ->where("$table.code = ?", $value);
        } catch (\NG\Exception $e) {}

        $data = $database->fetchRow($query);

        if ($data) {
            $cls = new Detail();
            $id = $data["id"];
            $detail = $cls->fetchByTarget("letter", $id, 0);
            if ($detail) {
                $dataDetail = $detail["data"];
                if ($dataDetail) {
                    foreach ($dataDetail as $item) {
                        $itemKey = $item['key'];
                        $itemVal = $item['value'];
                        $data[$itemKey] = $itemVal;
                    }
                }
            }
        }

        return $data;
    }
}
