<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class CmsInput
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
        $this->table = 'input';
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
            $queryCount->select("COUNT(*) AS rowCount")
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

            $tableParent = 'parent';
            $tableInput = "input as $tableParent";
            $tableParentClause = "$tableParent.id = $table.parent";

            try {
                $queryFetch->select(
                    "$table.*"
                    . ", IFNULL($tableParent.title, '') AS `parent-name`"
                )
                    ->from($table)
                    ->leftJoin($tableInput, $tableParentClause)
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
            $tableInput = "input as $tableParent";
            $tableParentClause = "$tableParent.parent = $table.id";

            try {
                $queryFetch->select(
                    "$table.*"
                     . ", GROUP_CONCAT($tableParent.id ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-ids`"
                     . ", GROUP_CONCAT($tableParent.slug ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-slugs`"
                     . ", GROUP_CONCAT($tableParent.type ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-types`"
                     . ", GROUP_CONCAT($tableParent.source ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-sources`"
                     . ", GROUP_CONCAT($tableParent.format ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-formats`"
                )
                    ->from($table)
                    ->leftJoin($tableInput, $tableParentClause)
                    ->where("$table.parent = 0")
                    ->group("$table.id")
                    ->order("$table.id", "ASC")
                ;

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

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            } else {
                $pages = 1;
            }

            $tableParent = 'parent';
            $tableMeta = "input as $tableParent";
            $tableParentClause = "$tableParent.id = $table.parent";

            try {
                $queryFetch->select("$table.*")
                    ->from($table)
                    ->where("$table.parent = $parent")
                    ->order("$table.id", "ASC")
                ;

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
                $queryFetch->select("$table.*")
                    ->from($table)
                    ->where("$table.name LIKE ?", "%$key%")
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

        $tableParent = 'tparent';
        $tableInput = "input as $tableParent";
        $tableParentClause = "$tableParent.id = $table.parent";

        try {
            $query->select(
                "$table.*"
                . ", IFNULL($tableParent.slug, '') AS `parent-slug`"
            )
                ->from($table)
                ->leftJoin($tableInput, $tableParentClause)
                ->where("$table.id = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function getBySlug($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        $tableParent = 'tparent';
        $tableInput = "input as $tableParent";
        $tableParentClause = "$tableParent.id = $table.parent";

        try {
            $query->select(
                "$table.*"
                . ", IFNULL($tableParent.slug, '') AS `parent-slug`"
            )
                ->from($table)
                ->leftJoin($tableInput, $tableParentClause)
                ->where("$table.slug = ?", $value)
            ;
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
