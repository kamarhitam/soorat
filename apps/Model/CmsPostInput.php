<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class CmsPostInput
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
        $this->table = 'post_input';
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

    public function fetchByIdPost($idPost, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("$table.idpost = ?", $idPost);
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        $pages = 0;
        $data = null;

        $tableInput = "input";
        $tableInputClause = "$tableInput.id = $table.idinput";

        $tableParent = 'parent';
        $tableInputParent = "input as $tableParent";
        $tableParentClause = "$tableParent.parent = $tableInput.id";

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            }

            try {
                $queryFetch->select("$table.*" .
                    ", IFNULL($tableInput.title, '') AS inputtitle" .
                    ", IFNULL($tableInput.slug, '') AS inputslug" .
                    ", IFNULL($tableInput.type, '') AS inputtype" .
                    ", IFNULL($tableInput.source, '') AS inputsource" .
                    ", IFNULL($tableInput.format, '') AS inputformat"
                    . ", GROUP_CONCAT($tableParent.id ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-ids`"
                    . ", GROUP_CONCAT($tableParent.title ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-titles`"
                    . ", GROUP_CONCAT($tableParent.slug ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-slugs`"
                    . ", GROUP_CONCAT($tableParent.type ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-types`"
                    . ", GROUP_CONCAT($tableParent.source ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-sources`"
                    . ", GROUP_CONCAT($tableParent.format ORDER BY $tableParent.id ASC SEPARATOR '***') as `parent-formats`"
                )
                    ->from($table)
                    ->leftJoin($tableInput, $tableInputClause)
                    ->leftJoin($tableInputParent, $tableParentClause)
                    ->where("$table.idpost = ?", $idPost)
                    ->group("$tableInput.id")
                    ->order("$table.num", "ASC");

                if ($limit > 0)
                    $queryFetch->limit("$start, $limit");
            } catch (\NG\Exception $e) {}

            $rows = $database->fetchAll($queryFetch);

            if ($rows) {
                foreach ($rows as $row) {
                    $inputid = $row["idinput"];
                    $inputtitle = $row["inputtitle"];
                    $inputslug = $row["inputslug"];
                    $inputtype = $row["inputtype"];
                    $inputsource = $row["inputsource"];
                    $inputformat = $row["inputformat"];

                    unset($row["inputslug"]);
                    unset($row["inputtype"]);
                    unset($row["inputsource"]);
                    unset($row["inputformat"]);

                    $row["input"] = array(
                        "id" => $inputid,
                        "title" => $inputtitle,
                        "slug" => $inputslug,
                        "type" => $inputtype,
                        "source" => $inputsource,
                        "format" => $inputformat
                    );
                    $data[] = $row;
                }
            }
        }

        return array(
            "total" => $count,
            "pages" => $pages,
            "page" => $page,
            "limit" => $limit,
            "data" => $data,
        );
    }

    public function getById($value1, $value2)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select()
                ->from($table)
                ->where("idpost = ?", $value1)
                ->andWhere("idinput = ?", $value2);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
    }

    public function deleteById($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->delete()
                ->from($table)
                ->where("idpost = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->query($query);
    }

    public function fetchDetailByIdPost($idPost)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $tableInput = "input";
        $tableInputClause = "$table.idinput = $tableInput.id";

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("$table.idpost = ?", $idPost)
            ;
        } catch (\NG\Exception $e) {}

        $count = 0;
        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        $data = null;

        if (!empty($count)){
            try {
                $queryFetch->select(
                    "$table.*" .
                    ", IFNULL($tableInput.title, '') AS inputtitle" .
                    ", IFNULL($tableInput.slug, '') AS inputslug"
                )
                    ->from($table)
                    ->leftJoin($tableInput, $tableInputClause)
                    ->where("$table.idpost = ?", $idPost);

                $queryFetch->group("$tableInput.id");
                $queryFetch->order("$table.num", "ASC");
                $queryFetch->order("$table.idinput", "ASC");
            } catch (\NG\Exception $e) {}

            $data = $database->fetchAll($queryFetch);
        }

        return array(
            "total" => $count,
            "data" => $data,
        );
    }
}
