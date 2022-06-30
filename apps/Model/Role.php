<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class Role
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
        $this->table = 'role';
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

        $tableAction = 'action';
        $tableActionClause = "$table.idaction = $tableAction.id";

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

            try {
                $queryFetch->select("$table.*" .
                    ", IFNULL($tableAction.name, '') AS action"
                )
                    ->from($table)
                    ->leftJoin($tableAction, $tableActionClause)
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

    public function getById($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        $tableAction = 'action';
        $tableActionClause = "$table.idaction = $tableAction.id";

        try {
            $query->select("$table.*" .
                ", IFNULL($tableAction.name, '') AS action"
            )
                ->from($table)
                ->leftJoin($tableAction, $tableActionClause)
                ->where("id = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }
}
