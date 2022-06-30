<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class CmsPost
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
        $this->table = 'post';
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
            } else {
                $pages = 1;
            }

            try {
                $queryFetch->select("$table.*")
                    ->from($table);
                $queryFetch
                    ->order("$table.type","ASC")
                    ->order("$table.slug","ASC")
                    ->order("$table.id", "ASC");
                if ($limit > 0)
                    $queryFetch->limit("$start, $limit");
            } catch (\NG\Exception $e) {}

            $rows = $database->fetchAll($queryFetch);
            if ($rows){
                $data = $rows;
                /*
                $clsPostMeta = new CmsPostMeta();
                $helper = new Helper();

                foreach ($rows as $row){
                    $postid = $row["id"];
                    $content = $row["content"];

                    $excerpt = $helper->getExcerpt($content);
                    $row["excerpt"] = $excerpt;

                    $posttype = $row["type"];
                    $typeslug = $row["typeslug"];
                    $typetext = $row["typetext"];
                    $typeparent = $row["typeparent"];

                    unset($row["typeslug"]);
                    unset($row["typetext"]);
                    unset($row["typeparent"]);

                    $row["type"] = array(
                        "id" => $posttype,
                        "slug" => $typeslug,
                        "name" => $typetext,
                        "parent" => $typeparent,
                    );

                    $meta = $clsPostMeta->fetchByPostId($postid);

                    if ($meta){
                        $dataMeta = $meta["data"];
                        if ($dataMeta){
                            foreach ($dataMeta as $itemMeta){
                                $metaType = $itemMeta["type"];
                                $metaValue = $itemMeta["value"];
                                if ($metaType == 2){
                                    $metaSlug = $itemMeta["categoryslug"];
                                    $metaName = $itemMeta["categoryname"];
                                    $metaCat = array(
                                        "id" => $metaValue,
                                        "slug" => $metaSlug,
                                        "name" => $metaName,
                                    );
                                    $row["categories"][] = $metaCat;
                                } else if ($metaType == 3){
                                    $metaSlug = $itemMeta["tagslug"];
                                    $metaName = $itemMeta["tagname"];
                                    $metaTag = array(
                                        "id" => $metaValue,
                                        "slug" => $metaSlug,
                                        "name" => $metaName,
                                    );
                                    $row["tags"][] = $metaTag;
                                }
                            }
                        }
                    }
                    $data[] = $row;
                }
                */
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

    public function fetchByType($type, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        $count = 0;
        $pages = 0;
        $mode = 0;
        $data = null;

        try {
            $queryCount->select("COUNT($table.id) AS rowCount")
                ->from($table)
                ->where("$table.type = ?", $type);
        } catch (\NG\Exception $e) {}

        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

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
                    ->where("$table.type = ?", $type);
                $queryFetch
                    ->order("$table.type","ASC")
                    ->order("$table.slug","ASC")
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

    public function find($text, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryFetch = new Query();
        $table = $this->table;

        $tablePostMeta = 'cms_post_meta';
        $tablePostMetaClause = "$table.id = $tablePostMeta.postid";

        $tableMeta = 'cms_meta';
        $tableMetaClause = "$table.type = $tableMeta.id";

        $pages = 0;
        $count = 0;

        $data = null;

        try {
            $queryFetch->select("DISTINCT $table.*" .
                ", IFNULL($tableMeta.name, '') AS typetext" .
                ", IFNULL($tableMeta.slug, '') AS typeslug"
            )
                ->from($table)
                ->leftJoin($tableMeta, $tableMetaClause)
                ->leftJoin($tablePostMeta, $tablePostMetaClause)
                //->where("$table.type = ?", $type)
                ->order("$table.type", "ASC")
                ->order("$table.id", "DESC")
            ;

        } catch (\NG\Exception $e) {}

        $resRows = $database->fetchAll($queryFetch);
        $selRows = array();

        if ($resRows){
            $clsPostMeta = new CmsPostMeta();
            $helper = new Helper();
            $rejects = array(
                "di", 'ke', "dari", "yang"
            );

            foreach ($resRows as $row) {
                $content = $row["content"];
                $len = strlen($content);

                $excerpt = $helper->getExcerpt($content, $len);
                if ($excerpt){
                    $lowExcerpt = strtolower($excerpt);
                    $textSlug = $helper->getSlug($text);
                    $words = explode("-", $textSlug);

                    foreach ($words as $word) {
                        if (!in_array($word, $rejects)){
                            if (strpos($lowExcerpt, $word) !== false){
                                $selRows[] = $row;
                                break;
                            }
                        }
                    }
                }
            }

            if ($selRows){
                array_unique($selRows);

                $count = count($selRows);

                if ($count > 0){
                    if ($limit > 0){
                        $pages = (int) ($count / $limit);
                        if (($count % $limit) > 0) $pages += 1;
                    } else {
                        $limit = 20;
                        $pages = 1;
                    }

                    if (!$page){
                        $page = 1;
                    }

                    $startPos = ($page - 1) * $limit;
                    $endPos = ($page * $limit) - 1;

                    $rows = array();

                    for ($i = $startPos; $i < $endPos; $i++){
                        if ($i < count($selRows)){
                            $rows[] = $selRows[$i];
                        }
                    }

                    if ($rows){
                        foreach ($rows as $row){
                            $postid = $row["id"];
                            $content = $row["content"];

                            $excerpt = $helper->getExcerpt($content);
                            $row["excerpt"] = $excerpt;

                            $posttype = $row["type"];
                            $typeslug = $row["typeslug"];
                            $typetext = $row["typetext"];

                            unset($row["typeslug"]);
                            unset($row["typetext"]);

                            $row["type"] = array(
                                "id" => $posttype,
                                "slug" => $typeslug,
                                "name" => $typetext,
                            );

                            $meta = $clsPostMeta->fetchByPostId($postid);

                            if ($meta){
                                $dataMeta = $meta["data"];
                                if ($dataMeta){
                                    foreach ($dataMeta as $itemMeta){
                                        $metaType = $itemMeta["type"];
                                        $metaValue = $itemMeta["value"];
                                        if ($metaType == 2){
                                            $metaSlug = $itemMeta["categoryslug"];
                                            $metaName = $itemMeta["categoryname"];
                                            $metaCat = array(
                                                "id" => $metaValue,
                                                "slug" => $metaSlug,
                                                "name" => $metaName,
                                            );
                                            $row["categories"][] = $metaCat;
                                        } else if ($metaType == 3){
                                            $metaSlug = $itemMeta["tagslug"];
                                            $metaName = $itemMeta["tagname"];
                                            $metaTag = array(
                                                "id" => $metaValue,
                                                "slug" => $metaSlug,
                                                "name" => $metaName,
                                            );
                                            $row["tags"][] = $metaTag;
                                        }
                                    }
                                }
                            }
                            $data[] = $row;
                        }
                    }
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

    public function getById($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select("$table.*")
                ->from($table)
                ->where("$table.id = ?", $value);
        } catch (\NG\Exception $e) {}

        return $database->fetchRow($query);
        /*
        $data = null;

        if ($row){
            $clsPostMeta = new CmsPostMeta();
            $helper = new Helper();

            $postid = $row["id"];
            $content = $row["content"];
            $excerpt = $helper->getExcerpt($content);
            $row["excerpt"] = $excerpt;

            $posttype = $row["type"];
            $typeslug = $row["typeslug"];
            $typetext = $row["typetext"];

            unset($row["typeslug"]);
            unset($row["typetext"]);

            $row["type"] = array(
                "id" => $posttype,
                "slug" => $typeslug,
                "name" => $typetext,
            );

            $meta = $clsPostMeta->fetchByPostId($postid);
            if ($meta){
                $dataMeta = $meta["data"];
                if ($dataMeta){
                    foreach ($dataMeta as $itemMeta){
                        $metaType = $itemMeta["type"];
                        $metaValue = $itemMeta["value"];
                        if ($metaType == 2){
                            $metaSlug = $itemMeta["categoryslug"];
                            $metaName = $itemMeta["categoryname"];
                            $metaCat = array(
                                "id" => $metaValue,
                                "slug" => $metaSlug,
                                "name" => $metaName,
                            );
                            $row["categories"][] = $metaCat;
                        } else if ($metaType == 3){
                            $metaSlug = $itemMeta["tagslug"];
                            $metaName = $itemMeta["tagname"];
                            $metaTag = array(
                                "id" => $metaValue,
                                "slug" => $metaSlug,
                                "name" => $metaName,
                            );
                            $row["tags"][] = $metaTag;
                        }
                    }
                }
            }
            $data = $row;
        }

        return $data;
        */
    }

    public function getBySlug($value)
    {
        $database = $this->database;
        $query = new Query();
        $table = $this->table;

        try {
            $query->select("$table.*")
                ->from($table)
                ->where("$table.slug = ?", $value);
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

    public function fetchSimple($type, $slug, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        $tablePostMeta = 'cms_post_meta';
        $tablePostMetaClause = "$table.id = $tablePostMeta.postid";

        $tableMeta = 'cms_meta';
        $tableMetaClause = "$tableMeta.id = $tablePostMeta.value";

        $count = 0;
        $pages = 0;
        $mode = 0;
        $data = null;

        try {
            $queryCount->select("COUNT($table.id) AS rowCount")
                ->from($table)
                ->innerJoin($tablePostMeta, $tablePostMetaClause)
                ->innerJoin($tableMeta, $tableMetaClause)
                ->where("$tableMeta.type = ?", $type)
                ->andWhere("$tableMeta.slug = ?", $slug);
        } catch (\NG\Exception $e) {}

        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }

        if (empty($count)){
            try {
                $queryCount->select("COUNT($table.id) AS rowCount")
                    ->from($table)
                    ->innerJoin($tablePostMeta, $tablePostMetaClause)
                    ->innerJoin($tableMeta, $tableMetaClause)
                    ->where("$tableMeta.type = ?", $type)
                    ->andWhere("$tableMeta.id = ?", $slug);
            } catch (\NG\Exception $e) {}

            $row = $database->fetchRow($queryCount);
            if (is_array($row)) {
                $count = $row['rowCount'];
            }
            $mode = 1;
        }

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            } else {
                $pages = 1;
            }

            try {
                $queryFetch->select("$table.id, $table.type, $table.title, $table.slug" .
                    ", IFNULL($tableMeta.name, '') AS typetext" .
                    ", IFNULL($tableMeta.slug, '') AS typeslug"
                )
                    ->from($table)
                    ->leftJoin($tablePostMeta, $tablePostMetaClause)
                    ->leftJoin($tableMeta, $tableMetaClause)
                    ->where("$tableMeta.type = ?", $type);

                if ($mode == 1){
                    $queryFetch->andWhere("$tableMeta.id = ?", $slug);
                } else {
                    $queryFetch->andWhere("$tableMeta.slug = ?", $slug);
                }
                $queryFetch
                    ->order("$table.type","ASC")
                    ->order("$table.slug","ASC")
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

    public function getCountByType($type)
    {
        $database = $this->database;
        $queryCount = new Query();
        $table = $this->table;

        $count = 0;

        try {
            $queryCount->select("COUNT($table.id) AS rowCount")
                ->from($table)
                ->where("$table.type = ?", $type);
        } catch (\NG\Exception $e) {}

        $row = $database->fetchRow($queryCount);
        if (is_array($row)) {
            $count = $row['rowCount'];
        }
        return $count;
    }
}
