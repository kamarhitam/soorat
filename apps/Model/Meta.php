<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class Meta
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
        $this->table = 'meta';
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

    public function fetch($limit = 10, $page = 1){
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("target <> ?", "constant")
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
            } else {
                $pages = 1;
            }

            try {
                $queryFetch->select(
                    "$table.id, $table.type, $table.target"
                    . ", $table.slug, $table.source, $table.parent"
                    . ", CONCAT(IFNULL(CONCAT($tableParent.name, '-'), ''), $table.`name`) as alias"
                    . ", $table.name"
                    . ", IFNULL($tableParent.name, '') AS `parent-name`"
                )
                    ->from($table)
                    ->leftJoin($tableMeta, $tableParentClause)
                    ->where("$table.target <> ?", "constant")
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

    public function fetchSelect($parent = 0, $limit = 0, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("target <> ?", "constant")
                ->andWhere("type in ('select', 'multi-select')")
                ->andWhere("parent = $parent")
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

            $tableParent = 'parent';
            $tableMeta = "meta as $tableParent";
            $tableParentClause = "$tableParent.id = $table.parent";

            try {
                $queryFetch->select(
                    "$table.id, $table.type, $table.target"
                    . ", $table.slug, $table.source, $table.parent"
                    . ", CONCAT(IFNULL(CONCAT($tableParent.name, '-'), ''), $table.`name`) as alias"
                    . ", $table.name"
                )
                    ->from($table)
                    ->leftJoin($tableMeta, $tableParentClause)
                    ->where("$table.target <> ?", "constant")
                    ->andWhere("$table.type in ('select', 'multi-select')")
                    ->andWhere("$table.parent = $parent")
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

    public function fetchFilter($target, $type = '', $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("target in ('$target', 'all')")
            ;
            if ($type) {
                $queryCount->andWhere("type = ?", $type);
            }
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
            } else {
                $pages = 1;
            }

            try {
                $queryFetch->select(
                    "$table.id, $table.type, $table.target"
                    . ", $table.slug, $table.source, $table.parent"
                    . ", CONCAT(IFNULL(CONCAT($tableParent.name, '-'), ''), $table.`name`) as alias"
                    . ", $table.name"
                )
                    ->from($table)
                    ->leftJoin($tableMeta, $tableParentClause)
                    ->where("$table.target in ('$target', 'all')");

                if ($type) {
                    $queryFetch->andWhere("type = ?", $type);
                }

                $queryFetch->order("$table.parent", "ASC")
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

    public function fetchParent($limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("parent = 0")
                ->andWhere("type in ('select', 'multi-select')")
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
            } else {
                $pages = 1;
            }

            try {
                $queryFetch->select(
                    "$table.id, $table.type, $table.target"
                    . ", $table.slug, $table.source, $table.parent"
                    . ", CONCAT(IFNULL(CONCAT($tableParent.name, '-'), ''), $table.`name`) as alias"
                    . ", $table.name"
                )
                    ->from($table)
                    ->leftJoin($tableMeta, $tableParentClause)
                    ->where("$table.parent = 0")
                    ->andWhere("$table.type in ('select', 'multi-select')")
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

    public function fetchChildren($parent, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("parent = $parent")
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
            } else {
                $pages = 1;
            }

            try {
                $queryFetch->select(
                    "$table.id, $table.type, $table.target"
                    . ", $table.slug, $table.source, $table.parent"
                    . ", CONCAT(IFNULL(CONCAT($tableParent.name, '-'), ''), $table.`name`) as alias"
                    . ", $table.name"
                )
                    ->from($table)
                    ->leftJoin($tableMeta, $tableParentClause)
                    ->where("$table.parent = $parent")
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

    public function getById($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        $tableParent = 'parent';
        $tableMeta = "meta as $tableParent";
        $tableParentClause = "$tableParent.id = $table.parent";

        try {
            $query->select(
                "$table.id, $table.type, $table.target"
                . ", $table.slug, $table.source, $table.parent"
                . ", CONCAT(IFNULL(CONCAT($tableParent.name, '-'), ''), $table.`name`) as alias"
                . ", $table.name"
            )
                ->from($table)
                ->leftJoin($tableMeta, $tableParentClause)
                ->where("$table.id = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getBySlug($slug, $type = "", $target = "")
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        $tableParent = 'parent';
        $tableMeta = "meta as $tableParent";
        $tableParentClause = "$tableParent.id = $table.parent";

        try {
            $query->select(
                "$table.id, $table.type, $table.target"
                . ", $table.slug, $table.source, $table.parent"
                . ", CONCAT(IFNULL(CONCAT($tableParent.name, '-'), ''), $table.`name`) as alias"
                . ", $table.name"
            )
                ->from($table)
                ->leftJoin($tableMeta, $tableParentClause)
                ->where("$table.slug = ?", $slug);
                if ($type) {
                    $query->andWhere("$table.type = ?", $type);
                }
                if ($target) {
                    $query->andWhere("$table.target in ('$target', 'all')");
                }
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getSlugAlready($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select("slug")
                ->from($table)
                ->where("slug = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

}
