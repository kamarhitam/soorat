<?php

use NG\Registry;
use NG\Session;

class Helper{
    protected $config;
    protected $session;

    public function __construct() {
        $this->session = new Session;
        $this->config = Registry::get('config');
    }

    public function getSlug($str, $delimiter = '-', $options = array()){
        $str = str_replace('"', "", $str);
        $str = str_replace('\"', "", $str);
        $str = str_replace("'", "", $str);
        $str = str_replace("039", "", $str);
        $str = str_replace("&", "", $str);
        $str = htmlentities($str);

        $str = filter_var($str, FILTER_SANITIZE_STRING);

        $defaults = array(
            'delimiter' => $delimiter,
            'limit' => null,
            'lowercase' => true,
            'replacements' => array(),
        );

        $options = array_merge($defaults, $options);
        $str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
        $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);
        $str = substr($str, 0, ($options['limit'] ? $options['limit'] : strlen($str)) );
        $str = trim($str, $options['delimiter']);
        $str = str_ireplace("-amp-", "-", $str);
        return $options['lowercase'] ? strtolower($str) : $str;
    }

    public function getArrayValue($array = null, $key = null, $default = ""){
        $result = $default;
        if ($array !== null){
            if (is_array($array)){
                if (array_key_exists($key, $array)){
                    $result = $array[$key];
                }
            }
        }
        return $result;
    }

    public function removeWhiteSpace($text){
        $text = preg_replace('/[\t\n\r\0\x0B]/', '', $text);
        $text = preg_replace('/([\s])\1+/', ' ', $text);
        $text = str_replace(array('{ ', ' {', ' { '), '{', $text);
        $text = str_replace(array(' }', '} ', ' { '), '}', $text);
        $text = str_replace(array(' =', '= ', ' = '), '=}', $text);
        $text = str_replace(array(' ,', ', ', ' , '), ',', $text);
        $text = str_replace(array(' :', ': ', ' : '), ':', $text);
        $text = str_replace(array(' ;', '; ', ' ; '), ';', $text);
        $text = trim($text);
        return $text;
    }

    function findArray($array, $key1, $key2, $value){
        $results = array();

        if (is_array($array)){
            if (isset($array[$key1]) && key($array) == $key1){
                if ($array[$key1] == $value){
                    $results[] = $array[$key2];
                }
            }

            foreach ($array as $sub_array){
                $results = array_merge($results, $this->findArray($sub_array, $key1, $key2, $value));
            }
        }

        return  $results;
    }

    function collectMenu($array){
        $jsonController = file_get_contents(ROOT . DS . APPS . DS . 'Config' . DS . 'controller.json');
        $arrController = json_decode($jsonController, true);

        $results = array();

        if (is_array($array)){
            foreach ($array as $item){
                $children = $this->getArrayValue($item, "children");
                $path = $this->getArrayValue($item, "path");
                if ($path) {
                    $paths = explode("/", $path);
                    $parent = "";

                    foreach ($arrController as $controller){
                        if ($controller["name"] == $paths[0]){
                            $parent = $controller["title"];
                            break;
                        }
                    }

                    $id = $this->getArrayValue($item, "id");
                    $text = $this->getArrayValue($item, "text");
                    $icon = $this->getArrayValue($item, "icon");
                    $path = $this->getArrayValue($item, "path");

                    $menu = array(
                        "id" => $id,
                        "text" => $text,
                        "icon" => $icon,
                        "path" => $path,
                        "children" => is_array($children) ? 1 : 0,
                        "parent" => is_array($children) ? "" : $parent,
                    );
                    if (is_array($children)){
                        $results = array_merge($results, $this->collectMenu($children));
                    }
                    $results[] = $menu;
                }
            }
        }

        return  $results;
    }

    function getTitle($array, $key1, $key2, $value){
        $results = $this->findArray($array, $key1, $key2, $value);
        if (is_array($array)){
            return end($results);
        }
        return "";
    }

    function isHasRight($array, $menu, $action){
        $currSession = $this->session->get("Login");
        $userType = 0;

        if ($currSession) {
            $userSession = $this->getArrayValue($currSession, 'user');
            $userType = $this->getArrayValue($userSession, 'idtype');
        }

        if ($userType == 9){
            return true;
        } else {
            foreach ($array as $item){
                $idMenu = $item["idmenu"];
                $idAction = $item["idaction"];
                if ($menu == $idMenu){
                    if ($idAction == $action){
                        return true;
                    }
                }
            }
        }

        return false;
    }

    function getMenuId($array, $path){
        foreach ($array as $item){
            $pathMenu = $item["path"];
            $idMenu = $item["id"];
            if ($path == $pathMenu){
                return $idMenu;
            }
        }
        return 0;
    }

    public function generateKey($prefix, $length = 10){
        $pool = array_merge(range(1, 9), range('A', 'Z'), range(1, 9), range('A', 'Z'));
        $key = "";
        for ($i = 0; $i < $length; $i++) {
            $key .= $pool[mt_rand(0, count($pool) - 1)];
        }
        return $prefix . $key;
    }

    public function validateDir($dir){
        if (!is_dir($dir)):
            mkdir($dir, 0755, true);
        endif;
    }

    public function reverseDate($date){
        $ret = "";
        if (!empty($date)){
            $arrDate = explode("-", $date);
            if (count($arrDate) > 2){
                $year = $arrDate[2];
                $month = intval($arrDate[1]);
                $day = intval($arrDate[0]);

                $ret = sprintf("%04d-%02d-%02d", $year, $month, $day);
            }
        }
        return $ret;
    }

    public function longDate($dateTime){
        $ret = "";
        if (!empty($dateTime)){
            if ($dateTime != "0000-00-00"){
                $arrDateTime = explode(" ", $dateTime);
                $date = $arrDateTime[0];
                // $time = $arrDateTime[1];

                $arrDate = explode("-", $date);
                $year = $arrDate[0];
                $month = intval($arrDate[1]);
                $day = intval($arrDate[2]);

                $arrMonth = array("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
                $monthName = $arrMonth[$month - 1];

                $ret = $day . " " . $monthName . " " . $year;
            }
        }

        return trim($ret);
    }

    public function longDateId($dateTime){
        $ret = "";
        if (!empty($dateTime)){
            $arrDateTime = explode(" ", $dateTime);
            $date = $arrDateTime[0];

            $arrDate = explode("-", $date);
            $year = $arrDate[2];
            $month = intval($arrDate[1]);
            $day = intval($arrDate[0]);

            $arrMonth = array("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
            $monthName = $arrMonth[$month - 1];

            $ret = $day . " " . $monthName . " " . $year;
        }

        return trim($ret);
    }

    public function longDateTime($dateTime){
        $ret = "";
        if (!empty($dateTime)){
            if ($dateTime != "0000-00-00"){
                $arrDateTime = explode(" ", $dateTime);
                $date = $arrDateTime[0];
                $time = $arrDateTime[1];

                $arrDate = explode("-", $date);
                $year = $arrDate[0];
                $month = intval($arrDate[1]);
                $day = intval($arrDate[2]);

                $arrMonth = array("Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
                $monthName = $arrMonth[$month - 1];

                $ret = $day . " " . $monthName . " " . $year . " " . $time;
            }
        }

        return trim($ret);
    }

    public function reverseDateTime($datetime){
        $ret = "";
        if (!empty($datetime)){
            $arrDateTime = explode(" ", $datetime);
            $date = $arrDateTime[0];
            $time = $arrDateTime[1];
            $ret = $this->reverseDate($date) . " " . $time;
        }
        return $ret;
    }

    public function unreverseDate($datetime){
        $arrDateTime = explode(" ", $datetime);
        $date = $arrDateTime[0];
        $ret = "";
        if (!empty($date)){
            $arrDate = explode("-", $date);
            if (count($arrDate) > 2){
                $year = $arrDate[0];
                $month = intval($arrDate[1]);
                $day = intval($arrDate[2]);
                $ret = sprintf("%02d-%02d-%04d", $day, $month, $year);
            }
        }
        return $ret;
    }

    public function unreverseDateTime($datetime){
        $ret = "";
        $time = "";
        if (!empty($datetime)){
            $arrDateTime = explode(" ", $datetime);
            $date = $arrDateTime[0];
            if (count($arrDateTime) == 2) $time = $arrDateTime[1];
            $ret = $this->unreverseDate($date) . " " . $time;
        }
        return $ret;
    }

    function romawi($number) {
        $map = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }

    public function terbilang($satuan) {
        $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        if ($satuan < 12)
            return "" . $huruf[$satuan];
        elseif ($satuan < 20)
            return $this->terbilang($satuan - 10) . "belas ";
        elseif ($satuan < 100)
            return $this->terbilang($satuan / 10) . " puluh " . $this->terbilang($satuan % 10);
        elseif ($satuan < 200)
            return " seratus" . $this->terbilang($satuan - 100);
        elseif ($satuan < 1000)
            return $this->terbilang($satuan / 100) . " ratus " . $this->terbilang($satuan % 100);
        elseif ($satuan < 2000)
            return " seribu" . $this->terbilang($satuan - 1000);
        elseif ($satuan < 1000000)
            return $this->terbilang($satuan / 1000) . " ribu " . $this->terbilang($satuan % 1000);
        elseif ($satuan < 1000000000)
            return $this->terbilang($satuan / 1000000) . " juta " . $this->terbilang($satuan % 1000000);
        elseif ($satuan < 100000000000)
            return $this->terbilang($satuan / 1000000000) . " milyar " . $this->terbilang($satuan % 1000000000);
        elseif ($satuan <= 100000000000)
            return "";
    }

    function isDecimal( $val ){
        return is_numeric( $val ) && floor( $val ) != $val;
    }

    function formatSizeUnits($bytes){
        if ($bytes >= 1073741824){
            if ($this->isDecimal($bytes / 1073741824)){
                $bytes = number_format($bytes / 1073741824, 2, ",", ",") . ' GB';
            } else {
                $bytes = number_format($bytes / 1073741824, 0, ",", ",") . ' GB';
            }
        } elseif ($bytes >= 1048576){
            if ($this->isDecimal($bytes / 1048576)){
                $bytes = number_format($bytes / 1048576, 2, ",", ",") . ' MB';
            } else {
                $bytes = number_format($bytes / 1048576, 0, ",", ",") . ' MB';
            }
        } elseif ($bytes >= 1024){
            if ($this->isDecimal($bytes / 1024)){
                $bytes = number_format($bytes / 1024, 2, ",", ",") . ' KB';
            } else {
                $bytes = number_format($bytes / 1024, 0, ",", ",") . ' KB';
            }
        } elseif ($bytes > 1){
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1){
            $bytes = $bytes . ' byte';
        } else{
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    function getGalleryDir($date){
        return ROOT . DS . UPLOADS . DS . GALLERY . DS . str_replace("-", DS, $date);
    }

    function dayDate($datetime) {
        $ret = '';
        $arrDateTime = explode(" ", $datetime);
        $date = $arrDateTime[0];
        $arrDate = explode("-", $date);
        if (count($arrDate) > 2){
            $year = $arrDate[0];
            if (strlen($year) == 2) {
                $date = $this->reverseDate($date);
            }
            $days = array('Ahad', 'Senin', 'Selasa', 'Rabu','Kamis','Jumat', 'Sabtu');
            $dayofweek = date('w', strtotime($date));
            $ret = $days[$dayofweek];
        }
        return $ret;
    }

    function renderInput($inputTitle, $inputSlug, $inputType, $inputFormat, $inputSource, $inputData,
                         $dataUser, $dataYear, $dataMonth, $dataDate, $dataDays
    ) {
        $todayYear = date("Y");
        $todayMonth = date("m");

        switch ($inputType) {
            case "text":
                ?>
                <input type="text" class="form-control" id="edit-<?php echo $inputSlug ?>"
                       name="<?php echo $inputSlug ?>" placeholder="<?php echo $inputTitle ?>"
                       value="<?php echo $this->getArrayValue($inputData, $inputSlug); ?>">
                <?php
                break;
            case "number":
                ?>
                <input type="number" min="0" class="form-control" id="edit-<?php echo $inputSlug ?>"
                       name="<?php echo $inputSlug ?>" placeholder="<?php echo $inputTitle ?>"
                       value="<?php echo $this->getArrayValue($inputData, $inputSlug); ?>">
                <?php
                break;
            case "date":
                ?>
                <div class="today-date input-group date" id="div-<?php echo $inputSlug ?>"
                     data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input"
                           data-target="#div-<?php echo $inputSlug ?>" id="edit-<?php echo $inputSlug ?>" name="<?php echo $inputSlug ?>"
                           value="<?php echo $this->getArrayValue($inputData, $inputSlug); ?>" >
                    <div class="input-group-append" data-target="#div-<?php echo $inputSlug ?>"
                         data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                </div>
                <?php
                break;
            case "time":
                ?>
                <div class="today-time input-group date" id="div-<?php echo $inputSlug ?>"
                     data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input"
                           data-target="#div-<?php echo $inputSlug ?>" id="edit-<?php echo $inputSlug ?>" name="<?php echo $inputSlug ?>"
                           value="<?php echo $this->getArrayValue($inputData, $inputSlug); ?>" >
                    <div class="input-group-append" data-target="#div-<?php echo $inputSlug ?>"
                         data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                </div>
                <?php
                break;
            case "datetime":
                ?>
                <div class="today-datetime input-group date" id="div-<?php echo $inputSlug ?>"
                     data-target-input="nearest">
                    <input type="text" class="form-control datetimepicker-input"
                           data-target="#div-<?php echo $inputSlug ?>" id="edit-<?php echo $inputSlug ?>" name="<?php echo $inputSlug ?>"
                           value="<?php echo $this->getArrayValue($inputData, $inputSlug); ?>" >
                    <div class="input-group-append" data-target="#div-<?php echo $inputSlug ?>"
                         data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                    </div>
                </div>
                <?php
                break;
            case "select":
                $selectList = null;
                switch ($inputSource) {
                    case "user":
                        $selectList = $dataUser;
                        break;
                    case "year":
                        $oldDataYear = $dataYear;
                        $dataYear = null;
                        switch ($inputFormat) {
                            case "roman":
                                foreach ($oldDataYear as $itemYear) {
                                    $itemYear["name"] = $this->romawi($itemYear["id"]);
                                    $dataYear[] = $itemYear;
                                }
                                break;
                            case "lcase":
                                foreach ($oldDataYear as $itemYear) {
                                    $itemYear["name"] = strtolower($itemYear["name"]);
                                    $dataYear[] = $itemYear;
                                }
                                break;
                            case "ucase":
                                foreach ($oldDataYear as $itemYear) {
                                    $itemYear["name"] = strtoupper($itemYear["name"]);
                                    $dataYear[] = $itemYear;
                                }
                                break;
                            default:
                                $dataYear = $oldDataYear;
                                break;
                        }
                        $selectList = $dataYear;
                        break;
                    case "month":
                        $oldDataMonth = $dataMonth;
                        $dataMonth = null;
                        switch ($inputFormat) {
                            case "roman":
                                foreach ($oldDataMonth as $itemMonth) {
                                    $itemMonth["name"] = $this->romawi($itemMonth["id"]);
                                    $dataMonth[] = $itemMonth;
                                }
                                break;
                            case "lcase":
                                foreach ($oldDataMonth as $itemMonth) {
                                    $itemMonth["name"] = strtolower($itemMonth["name"]);
                                    $dataMonth[] = $itemMonth;
                                }
                                break;
                            case "ucase":
                                foreach ($oldDataMonth as $itemMonth) {
                                    $itemMonth["name"] = strtoupper($itemMonth["name"]);
                                    $dataMonth[] = $itemMonth;
                                }
                                break;
                            default:
                                $dataYear = $oldDataMonth;
                                break;
                        }
                        $selectList = $dataMonth;
                        break;
                    case "date":
                        $selectList = $dataDate;
                        break;
                    case "day":
                        $selectList = $dataDays;
                        break;
                    default:
                        $arrInputSource = explode("#", $inputSource);
                        if (count($arrInputSource) == 2){
                            if ($arrInputSource[0] == "meta"){
                                $clsMeta = new Meta();
                                $meta = $clsMeta->getBySlug($arrInputSource[1]);
                                if ($meta) {
                                    $metaId = $meta["id"];

                                    $dataMetaChildren = null;
                                    if ($metaId) {
                                        $dataMetaChildren = $clsMeta->fetchChildren($metaId, 0);
                                    }
                                    $dataChildren = null;
                                    if ($dataMetaChildren) {
                                        $dataChildren = $this->getArrayValue($dataMetaChildren, 'data');
                                    }
                                    $clsMetaData = new MetaData();
                                    if ($dataChildren) {
                                        $metaData = $clsMetaData->fetchByIdParent($metaId);
                                        if ($metaData) {
                                            $dataMeta = $this->getArrayValue($metaData, 'data');
                                            if ($dataMeta) {
                                                $itemList = array();
                                                $newIdMeta = 0;
                                                foreach ($dataMeta as $itemMeta) {
                                                    $item = array();
                                                    foreach ($itemMeta as $num => $itemSubMeta) {
                                                        $item = $itemSubMeta[0];
                                                        $item["name"] = $item["value"];
                                                        $oldId = $item["id"];
                                                        $item["id"] = $item["num"] . "#" . $item["idparent"];
                                                        unset ($item["value"]);
                                                        unset ($item["idparent"]);
                                                        unset ($item["num"]);
                                                        unset ($item["idmeta"]);
                                                        $item["selected"] = "";
                                                    }
                                                    $selectList[] = $item;
                                                }
                                            }
                                        }
                                    } else {
                                        $metaData = $clsMetaData->fetchByIdMeta($metaId, 0);
                                        if ($metaData) {
                                            $dataMeta = $this->getArrayValue($metaData, 'data');
                                            if ($dataMeta) {
                                                $itemList = array();
                                                $newIdMeta = 0;
                                                foreach ($dataMeta as $itemMeta) {
                                                    $itemMeta["name"] = $itemMeta["value"];
                                                    // $itemMeta["id"] = $itemMeta["idmeta"] . "#" . $itemMeta["num"];
                                                    unset ($itemMeta["value"]);
                                                    unset ($itemMeta["num"]);
                                                    unset ($itemMeta["idmeta"]);
                                                    $itemMeta["selected"] = "";
                                                    $selectList[] = $itemMeta;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }
                ?>
                <select class="form-control select2bs4" id="edit-<?php echo $inputSlug ?>" name="<?php echo $inputSlug ?>">
                    <option value="">Tidak Ada</option>
                    <?php
                    if ($selectList) {
                        $rawSelectedIds = $this->getArrayValue($inputData, $inputSlug);
                        $selectedIds = null;
                        if ($rawSelectedIds) {
                            preg_match_all("^\[(.*?)]^", $rawSelectedIds, $matches);
                            if (count($matches) > 1){
                                if ($matches[1]) {
                                    if (count($matches[1]) > 0){
                                        $selectedVal = $matches[1][0];
                                        if ($selectedVal) {
                                            $selectedIds = explode(',', $selectedVal);
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($selectList == $dataYear) {
                                $rawSelectedIds = $todayYear;
                            }
                            if ($selectList == $dataMonth) {
                                $rawSelectedIds = $todayMonth;
                            }
                        }
                        if ($selectList == $dataYear) {
                            $selectedIds[] = $rawSelectedIds;
                        }
                        if ($selectList == $dataMonth) {
                            $selectedIds[] = $rawSelectedIds;
                        }
                        foreach ($selectList as $itemList) {
                            $selected = "";
                            $idItem = $inputSource . ':' . $itemList["id"];
                            $nameItem = $itemList["name"];
                            $itemValue = "[$idItem]";
                            $selected = "";
                            if ($selectList == $dataYear) {
                                $itemValue = $itemList["id"];
                                $idItem = $itemValue;
                            }
                            if ($selectList == $dataMonth) {
                                $itemValue = $itemList["id"];
                                $idItem = $itemValue;
                            }
                            if ($selectedIds) {
                                foreach ($selectedIds as $selectedId) {
                                    $selected = "";
                                    if ($selectedId == $idItem) {
                                        $selected = "selected";
                                        break;
                                    }
                                }
                            }
                            ?>
                            <option value="<?php echo $itemValue ?>" <?php echo $selected ?>><?php echo $nameItem ?></option>
                            <?php
                        }
                    }
                    ?>
                </select>
                <?php
                break;
            case "multi-select":
                $selectList = null;
                $rawSelectedIds = $this->getArrayValue($inputData, $inputSlug);
                $selectedIds = null;
                if ($rawSelectedIds) {
                    preg_match_all("^\[(.*?)]^", $rawSelectedIds, $matches);
                    if (count($matches) > 1){
                        if ($matches[1]) {
                            if (count($matches[1]) > 0){
                                $selectedVal = $matches[1][0];
                                if ($selectedVal) {
                                    $selectedIds = explode(',', $selectedVal);
                                }
                            }
                        }
                    }
                }
                switch ($inputSource) {
                    case "user":
                        $selectList = $dataUser;
                        break;
                    default:
                        $arrInputSource = explode("#", $inputSource);
                        $metaData = null;
                        if (count($arrInputSource) == 2){
                            if ($arrInputSource[0] == "meta"){
                                $clsMeta = new Meta();
                                $meta = $clsMeta->getBySlug($arrInputSource[1]);
                                if ($meta) {
                                    $metaId = $meta["id"];

                                    $dataMetaChildren = null;
                                    if ($metaId) {
                                        $dataMetaChildren = $clsMeta->fetchChildren($metaId, 0);
                                    }
                                    $dataChildren = null;
                                    if ($dataMetaChildren) {
                                        $dataChildren = $this->getArrayValue($dataMetaChildren, 'data');
                                    }
                                    $clsMetaData = new MetaData();
                                    if ($dataChildren) {
                                        $metaData = $clsMetaData->fetchByIdParent($metaId);
                                        if ($metaData) {
                                            $dataMeta = $this->getArrayValue($metaData, 'data');
                                            if ($dataMeta) {
                                                $itemList = array();
                                                $newIdMeta = 0;
                                                foreach ($dataMeta as $itemMeta) {
                                                    $item = array();
                                                    foreach ($itemMeta as $num => $itemSubMeta) {
                                                        $item = $itemSubMeta[0];
                                                        $item["name"] = $item["value"];
                                                        $oldId = $item["id"];
                                                        $item["id"] = $item["num"] . "#" . $item["idparent"];
                                                        $item["selected"] = "";
                                                        unset ($item["value"]);
                                                        unset ($item["idparent"]);
                                                        unset ($item["num"]);
                                                        unset ($item["idmeta"]);
                                                    }
                                                    $selectList[] = $item;
                                                }
                                            }
                                        }
                                    } else {
                                        $metaData = $clsMetaData->fetchByIdMeta($metaId, 0);
                                        if ($metaData) {
                                            $dataMeta = $this->getArrayValue($metaData, 'data');
                                            if ($dataMeta) {
                                                $itemList = array();
                                                $newIdMeta = 0;
                                                foreach ($dataMeta as $itemMeta) {
                                                    $itemMeta["name"] = $itemMeta["value"];
                                                    $itemMeta["selected"] = "";
                                                    unset ($itemMeta["value"]);
                                                    unset ($itemMeta["num"]);
                                                    unset ($itemMeta["idmeta"]);
                                                    $selectList[] = $itemMeta;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }
                $newSelectedList = array();
                if ($selectedIds) {
                    foreach ($selectList as $num => $itemList) {
                        $idItem = $inputSource . ':' . $itemList["id"];
                        $nameItem = $itemList["name"];
                        $itemList["selected"] = "";
                        $itemList["index"] = $num + 999999;
                        foreach ($selectedIds as $index => $selectedId) {
                            if ($selectedId == $idItem) {
                                $itemList["selected"] = "selected";
                                $itemList["index"] = ($index + 1);
                            }
                        }
                        $newSelectedList[] = $itemList;
                    }
                    $index = array_column($newSelectedList, 'index');
                    array_multisort($index, SORT_ASC, $newSelectedList);
                } else {
                    $newSelectedList = $selectList;
                }
                ?>
                <select class="form-control select2bs4" id="edit-<?php echo $inputSlug ?>" name="<?php echo $inputSlug ?>[]" multiple="multiple">
                    <?php
                    if ($selectList) {
                        foreach ($newSelectedList as $itemList) {
                            $selected = $itemList["selected"];
                            $idItem = $inputSource . ':' . $itemList["id"];
                            $nameItem = $itemList["name"];
                            ?>
                            <option value="<?php echo $idItem ?>" <?php echo $selected ?>><?php echo $nameItem ?></option>
                            <?php
                        }
                    }
                    ?>
                </select>
                <?php
                break;
            case "textarea":
                ?>
                <textarea rows="2" class="form-control" id="edit-<?php echo $inputSlug ?>" name="<?php echo $inputSlug ?>"></textarea>
                <?php
                break;
            case "auto-number":
                $value = $this->getArrayValue($inputData, $inputSlug);
                $readonly = "";
                if (!$value) {
                    $value = "(auto)";
                    $readonly = "readonly";
                }
                ?>
                <!--<div class="input-group">-->
                <input type="text" class="form-control" size="5" id="edit-<?php echo $inputSlug ?>"
                       name="<?php echo $inputSlug ?>" placeholder="<?php echo $inputTitle ?>"
                       value="<?php echo $value; ?>" <?php echo $readonly; ?>>
                <!--</div>-->
                <?php
                break;
        }
    }
}
