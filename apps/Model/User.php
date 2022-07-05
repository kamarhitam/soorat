<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class User
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
        $this->table = 'user';
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

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            }

            $tableType = 'user_type';
            $tableTypeClause = "$table.idtype = $tableType.id";

            $tableDetail = 'detail';
            $tableDetailClause = "$tableDetail.target = 'user' AND $table.id = $tableDetail.idtarget";

            try {
                $queryFetch->select("$table.*" .
                    ", IFNULL($tableType.name, '') AS `type-name`" .
                    ", GROUP_CONCAT($tableDetail.key) as `detail-keys`" .
                    ", GROUP_CONCAT($tableDetail.value) as `detail-values`"
                )
                    ->from($table)
                    ->leftJoin($tableType, $tableTypeClause)
                    ->leftJoin($tableDetail, $tableDetailClause)
                    ->group("$tableDetail.idtarget")
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
        $queryWhere = new Query();
        $queryOrder = new Query();
        $table = $this->table;

        $start = 0;
        if ($limit){
            $start = ($page - 1) * $limit;
        }

        $tableType = 'user_type';
        $tableTypeClause = "$table.idtype = $tableType.id";

        $tableDetail = 'detail';
        $tableDetailClause = "$tableDetail.target = 'user' AND $table.id = $tableDetail.idtarget";

        $queryWhere->orWhere("$table.name LIKE ?", "%$key%")
            ->orWhere("$table.nip LIKE ?", "%$key%")
        ;

        $queryWhere = substr($queryWhere, 3);

        try {
            $queryCount->select("COUNT($table.id) AS rowCount")
                ->from($table)
                ->leftJoin($tableType, $tableTypeClause)
                ->where("$table.id > 0")
            ;
            $queryCount .= " AND ($queryWhere)";
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        $pages = 0;
        $data = null;
        $rows = null;

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            } else {
                $pages = 1;
            }

            try {
                $queryFetch->select("$table.*" .
                    ", IFNULL($tableType.name, '') AS `type-name`" .
                    ", GROUP_CONCAT($tableDetail.key) as `detail-keys`" .
                    ", GROUP_CONCAT($tableDetail.value) as `detail-values`"
                )
                    ->from($table)
                    ->leftJoin($tableType, $tableTypeClause)
                    ->leftJoin($tableDetail, $tableDetailClause)
                    ->where("$table.id > 0");

                $queryFetch .= " AND ($queryWhere)";
                $queryFetch .= " GROUP BY $tableDetail.idtarget";

                $queryOrder->order("$table.id", "ASC");
                if ($limit > 0)
                    $queryOrder->limit("$start, $limit");

                $queryFetch .= " $queryOrder";
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

    public function login($value, $password)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        $tableType = 'user_type';
        $tableTypeClause = "$table.idtype = $tableType.id";

        $tableDetail = 'detail';
        $tableDetailClause = "$tableDetail.target = 'user' AND $table.id = $tableDetail.idtarget";

        try {
            $query->select("$table.*" .
                ", IFNULL($tableType.name, '') AS `type-name`" .
                ", GROUP_CONCAT($tableDetail.key) as `detail-keys`" .
                ", GROUP_CONCAT($tableDetail.value) as `detail-values`"
            )
                ->from($table)
                ->leftJoin($tableType, $tableTypeClause)
                ->leftJoin($tableDetail, $tableDetailClause)
                ->where("$table.email = ?", $value)
                ->andWhere("$table.password = ?", $password)
                ->group("$tableDetail.idtarget");
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getByEmail($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        $tableType = 'user_type';
        $tableTypeClause = "$table.idtype = $tableType.id";

        $tableDetail = 'detail';
        $tableDetailClause = "$tableDetail.target = 'user' AND $table.id = $tableDetail.idtarget";

        try {
            $query->select("$table.*" .
                ", IFNULL($tableType.name, '') AS `type-name`" .
                ", GROUP_CONCAT($tableDetail.key) as `detail-keys`" .
                ", GROUP_CONCAT($tableDetail.value) as `detail-values`"
            )
                ->from($table)
                ->leftJoin($tableType, $tableTypeClause)
                ->leftJoin($tableDetail, $tableDetailClause)
                ->where("$table.email = ?", $value)
                ->group("$tableDetail.idtarget");
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getById($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        $tableType = 'user_type';
        $tableTypeClause = "$table.idtype = $tableType.id";

        $tableDetail = 'detail';
        $tableDetailClause = "$tableDetail.target = 'user' AND $table.id = $tableDetail.idtarget";

        try {
            $query->select("$table.*" .
                ", IFNULL($tableType.name, '') AS `type-name`" .
                ", GROUP_CONCAT($tableDetail.key) as `detail-keys`" .
                ", GROUP_CONCAT($tableDetail.value) as `detail-values`"
            )
                ->from($table)
                ->leftJoin($tableType, $tableTypeClause)
                ->leftJoin($tableDetail, $tableDetailClause)
                ->where("$table.id = ?", $value)
                ->group("$tableDetail.idtarget")
            ;
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getByIdAndPassword($value, $password)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        $tableType = 'user_type';
        $tableTypeClause = "$table.idtype = $tableType.id";

        $tableDetail = 'detail';
        $tableDetailClause = "$tableDetail.target = 'user' AND $table.id = $tableDetail.idtarget";

        try {
            $query->select("$table.*" .
                ", IFNULL($tableType.name, '') AS `type-name`" .
                ", GROUP_CONCAT($tableDetail.key) as `detail-keys`" .
                ", GROUP_CONCAT($tableDetail.value) as `detail-values`"
            )
                ->from($table)
                ->leftJoin($tableType, $tableTypeClause)
                ->leftJoin($tableDetail, $tableDetailClause)
                ->where("$table.id = ?", $value)
                ->andWhere("$table.password = ?", $password)
                ->group("$tableDetail.idtarget");
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function fetchByIds($ids, $glue = ",")
    {
        $database = $this->database;
        $queryFetch = new Query();
        $table = $this->table;
        $data = null;
        $result = "";
        try {
            $queryFetch->select("name")
                ->from($table)
                ->where("id IN ($ids)");
        } catch (\NG\Exception $e) {}
        $rows = $database->fetchAll($queryFetch);
        if ($rows) {
            foreach ($rows as $row) {
                $name = $row["name"];
                $data[] = $name;
            }
            $result = implode($glue, $data);
        }
        return $result;
    }
}
