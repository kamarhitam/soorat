<?php

use NG\Query;
use NG\Registry;
use NG\Session;

class PostData
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
        $this->table = 'post_data';
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

    public function fetch($type = 0, $postId = 0, $year = 0, $month = 0, $limit = 10, $page = 1)
    {
        $database = $this->database;
        $queryCount = new Query();
        $queryFetch = new Query();
        $table = $this->table;

        $start = ($page - 1) * $limit;

        try {
            $queryCount->select("COUNT(*) AS rowCount")
                ->from($table)
                ->where("id > 0");
            if ($type) {
                $queryCount->andWhere("type = ?", $type);
            }
            if ($postId) {
                $queryCount->andWhere("idpost = ?", $postId);
            }
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

        $tableType = 'cms_type';
        $tableTypeClause = "$tableType.id = $table.type";

        $tableDetail = 'detail';
        $tableDetailClause = "$table.id = $tableDetail.idtarget AND $tableDetail.target = 'data'";

        if (!empty($count)){
            if ($limit > 0){
                $pages = (int) ($count / $limit);
                if (($count % $limit) > 0) $pages += 1;
            }

            try {
                $queryFetch->select("$table.*"
                    . ", IFNULL($tableUser.name, '') AS createbyname"
                    . ", IFNULL($tableType.name, '') AS `type-name`"
                    . ", IFNULL($tableType.slug, '') AS `type-slug`"
                    . ", GROUP_CONCAT($tableDetail.key ORDER BY $tableDetail.id ASC SEPARATOR '***') as `detail-keys`"
                    . ", GROUP_CONCAT($tableDetail.value ORDER BY $tableDetail.id ASC SEPARATOR '***') as `detail-values`"
                )
                    ->from($table)
                    ->leftJoin($tableUser, $tableUserClause)
                    ->leftJoin($tableType, $tableTypeClause)
                    ->leftJoin($tableDetail, $tableDetailClause)
                    ->where("$table.idpost > 0");
                if ($type) {
                    $queryFetch->andWhere("$table.type = ?", $type);
                }
                if ($postId) {
                    $queryFetch->andWhere("$table.idpost = ?", $postId);
                }
                if ($year) {
                    $queryFetch->andWhere("YEAR($table.`createdate`) = ?", $year);
                    if ($month) {
                        $queryFetch->andWhere("MONTH($table.`createdate`) = ?", $month);
                    }
                }
                $groups[] = "$tableDetail.`idtarget`";
                if ($type) {
                    $groups[] = "$table.`type`";
                }
                if ($postId) {
                    $groups[] = "$table.`idpost`";
                }
                $queryFetch->group(implode(",", $groups));
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
        $helper = new Helper();
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $data = null;

        $tableUser = 'user';
        $tableUserClause = "$tableUser.id = $table.createby";

        $tableType = 'cms_type';
        $tableTypeClause = "$tableType.id = $table.type";

        $tableDetail = 'detail';
        $tableDetailClause = "$table.id = $tableDetail.idtarget AND $tableDetail.target = 'data'";

        try {
            $query->select("$table.*"
                . ", IFNULL($tableUser.name, '') AS createbyname"
                . ", IFNULL($tableType.slug, '') AS `type-slug`"
                . ", IFNULL($tableType.name, '') AS `type-name`"
                . ", GROUP_CONCAT($tableDetail.key ORDER BY $tableDetail.id ASC SEPARATOR '***') as `detail-keys`"
                . ", GROUP_CONCAT($tableDetail.value ORDER BY $tableDetail.id ASC SEPARATOR '***') as `detail-values`"
            )
                ->from($table)
                ->leftJoin($tableUser, $tableUserClause)
                ->leftJoin($tableType, $tableTypeClause)
                ->leftJoin($tableDetail, $tableDetailClause)
                ->where("$table.id = ?", $value)
                ->group("$tableDetail.idtarget");
        } catch (\NG\Exception $e) {}

        $row = $database->fetchRow($query);
        if ($row) {
            $detailKeys = $row["detail-keys"];
            $detailValues = $row["detail-values"];
            if ($detailKeys) {
                $arrDetailKeys = explode("***", $detailKeys);
                $arrDetailValues = explode("***", $detailValues);
                for ($i = 0; $i < count($arrDetailKeys); $i++) {
                    $key = $arrDetailKeys[$i];
                    $oldValue = $arrDetailValues[$i];
                    $value = $oldValue;
                    // $value = $helper->renderData($value);
                    $row[$key] = $oldValue;
                    // $row["detail"][$key] = $value;
                }
            }
        }
        return $row;
    }

    public function getByCode($value)
    {
        $helper = new Helper();
        $database = $this->database;
        $query = new Query();
        $table = $this->table;
        $data = null;

        $tableUser = 'user';
        $tableUserClause = "$tableUser.id = $table.createby";

        $tableType = 'cms_type';
        $tableTypeClause = "$tableType.id = $table.type";

        $tableDetail = 'detail';
        $tableDetailClause = "$table.id = $tableDetail.idtarget AND $tableDetail.target = 'data'";

        try {
            $query->select("$table.*"
                . ", IFNULL($tableUser.name, '') AS createbyname"
                . ", IFNULL($tableType.name, '') AS `type-name`"
                . ", GROUP_CONCAT($tableDetail.key ORDER BY $tableDetail.id ASC SEPARATOR '***') as `detail-keys`"
                . ", GROUP_CONCAT($tableDetail.value ORDER BY $tableDetail.id ASC SEPARATOR '***') as `detail-values`"
            )
                ->from($table)
                ->leftJoin($tableUser, $tableUserClause)
                ->leftJoin($tableType, $tableTypeClause)
                ->leftJoin($tableDetail, $tableDetailClause)
                ->where("$table.code = ?", $value)
                ->group("$tableDetail.idtarget");
        } catch (\NG\Exception $e) {}

        $row = $database->fetchRow($query);
        if ($row) {
            $detailKeys = $row["detail-keys"];
            $detailValues = $row["detail-values"];
            if ($detailKeys) {
                $arrDetailKeys = explode("***", $detailKeys);
                $arrDetailValues = explode("***", $detailValues);
                for ($i = 0; $i < count($arrDetailKeys); $i++) {
                    $key = $arrDetailKeys[$i];
                    $oldValue = $arrDetailValues[$i];
                    $value = $oldValue;
                    // $value = $helper->renderData($value);
                    $row[$key] = $oldValue;
                    // $row["detail"][$key] = $value;
                }
            }
        }
        return $row;
    }

}
