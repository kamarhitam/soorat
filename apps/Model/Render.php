<?php

use NG\Registry;
use NG\Session;
use NG\Uri;

class Render {
    protected $session;
    protected $config;

    public function __construct() {
        $this->session = new Session;
        $this->config = Registry::get('config');
    }

    private function putTemplateOld($slug){
        $path = ROOT . DS . APPS . DS . "Template" . DS . "Post" . DS . "$slug.html";
        $content = "";
        if (file_exists($path)){
            ob_start();
            include($path);
            $content = ob_get_clean();
        }
        return $content;
    }

    public function putTemplateReport($slug){
        $path = ROOT . DS . APPS . DS . "Template" . DS . "Report" . DS . "$slug.html";
        $content = "";
        if (file_exists($path)){
            ob_start();
            include($path);
            $content = ob_get_clean();
        }
        return $content;
    }

    private function putTemplate($slug){
        $clsPosting = new CmsPost();
        $posting = $clsPosting->getBySlug($slug);
        if ($posting) {
            $content = $posting["content"];
        } else {
            $content = $this->putTemplateOld($slug);
        }
        return $content;
    }

    private function is_assoc(array $array){
        if (array_keys($array) === range(0, count($array) - 1)) {
            return true;
        }
        return false;
    }

    public function generateData($data, $code) {
        $dataContent = null;
        $baseUrl = Uri::baseUrl();
        $printURL = $baseUrl . "cetak/post/";
        if ($data != null) {
            if (array_key_exists("data", $data)){
                $dataContent = $data["data"];
                if (is_array($dataContent)){
                    $clsUser = new User();
                    $clsMeta = new Meta();
                    $clsPost = new CmsPost();
                    $clsType = new CmsType();

                    $dataRefMetaRaw = $clsMeta->fetch(0);
                    $dataRefMeta = null;
                    if ($dataRefMetaRaw) {
                        $dataRefMeta = $dataRefMetaRaw['data'];
                    }
                    $clsMetaData = new MetaData();
                    $clsDetail = new Detail();

                    foreach ($dataContent as $keyData => $valueData) {
                        if (!is_array($valueData)) {
                            preg_match_all("^\[(.*?)]^", $valueData, $matches);
                            if (count($matches) > 1){
                                $matchZero = $matches[1];
                                if ($matchZero) {
                                    if (is_array($matchZero)) {
                                        $selectedVal = $matchZero[0];
                                        if ($selectedVal) {
                                            $selectedIds = explode(',', $selectedVal);
                                            if ($selectedIds) {
                                                $dataSelected = array();
                                                $countSelected = count($selectedIds);
                                                if ($countSelected == 1) {
                                                    $arrSelected = explode(':', $selectedIds[0]);
                                                    $keySelected = $arrSelected[0];
                                                    $valSelected = $arrSelected[1];
                                                    switch ($keySelected) {
                                                        case "user":
                                                            $dataUser = $clsUser->getById($valSelected);
                                                            $codeUser = $dataUser["code"];
                                                            $dataUser["num-index"] = 1;
                                                            $dataUser["sign"] = $printURL . base64_encode("$codeUser#$code");

                                                            $dataUserId = $dataUser["id"];
                                                            if ($dataRefMeta) {
                                                                foreach ($dataRefMeta as $itemRefMeta) {
                                                                    $refMetaSlug = $itemRefMeta['slug'];
                                                                    $refMetaType = $itemRefMeta['type'];
                                                                    if (in_array($refMetaType, array($keySelected, 'all'))) {
                                                                        $detailUserDataRaw = $clsDetail->fetchByTargetAndMeta("user", $dataUserId, $refMetaSlug);
                                                                        if ($detailUserDataRaw) {
                                                                            $detailUserData = $detailUserDataRaw['data'];
                                                                            if ($detailUserData) {
                                                                                $indexDetail = 1;
                                                                                foreach ($detailUserData as $itemDetailUserData) {
                                                                                    $valueDetailUserData = $clsMetaData->getById($itemDetailUserData['value']);
                                                                                    if ($valueDetailUserData) {
                                                                                        $dataUser["$refMetaSlug-$indexDetail"] = $valueDetailUserData["name"];
                                                                                        $indexDetail++;
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }

                                                            $dataSelected = $dataUser;
                                                            break;
                                                        default:
                                                            $arrKeySelected = explode("#", $keySelected);
                                                            if (count($arrKeySelected) == 2){
                                                                if ($arrKeySelected[0] == "meta") {
                                                                    $keyMeta = $arrKeySelected[1];
                                                                    $metaData = $clsMeta->getBySlug($keyMeta);
                                                                    if ($metaData) {
                                                                        $metaDataId = $metaData["id"];
                                                                        $dataChildrenRaw = $clsMeta->fetchChildren($metaDataId, 0);
                                                                        $dataChildren = null;
                                                                        if ($dataChildrenRaw) {
                                                                            $dataChildren = $dataChildrenRaw["data"];
                                                                        }
                                                                        if ($dataChildren) {
                                                                            $arrValSelected = explode("#", $valSelected);
                                                                            $numMeta = $arrValSelected[0];
                                                                            $valMeta = $arrValSelected[1];
                                                                            $dataMeta["num-index"] = 1;
                                                                            $dataMeta["sign"] = $printURL . base64_encode("$valMeta#$code");
                                                                            foreach ($dataChildren as $itemChild) {
                                                                                $childData = $clsMetaData->getByIdMetaAndNum($itemChild["id"], $numMeta);
                                                                                $valueChildData = '';
                                                                                if ($childData) {
                                                                                    $valueChildData = $childData["value"];
                                                                                }
                                                                                $dataMeta[$itemChild["slug"]] = $valueChildData;
                                                                            }
                                                                            $dataSelected = $dataMeta;
                                                                        } else {
                                                                            $dataMetaData = $clsMetaData->getById($valSelected);
                                                                            if ($dataMetaData) {
                                                                                $dataMetaName = $dataMetaData["value"];
                                                                                $dataMeta = array(
                                                                                    "num-index" => 1,
                                                                                    "nama" => $dataMetaName,
                                                                                    "nilai" => $dataMetaName,
                                                                                );
                                                                                $dataMeta["sign"] = $printURL . base64_encode("$valSelected#$code");
                                                                                $dataSelected = $dataMeta;
                                                                            }
                                                                        }
                                                                    }
                                                                } else if ($arrKeySelected[0] == "post") {
                                                                    $keyPost = $arrKeySelected[1];
                                                                    $postData = $clsPost->getBySlug($keyPost);
                                                                    if ($postData) {
                                                                        $postDataType = $postData["type"];
                                                                        if ($postDataType) {
                                                                            $cmsType = $clsType->getById($postDataType);
                                                                            if ($cmsType) {
                                                                                $typeSlug = "data" ; //$cmsType['slug'];
                                                                                $clsDetail = new Detail();
                                                                                $dataDetailRaw = $clsDetail->fetchByTarget($typeSlug, $valSelected);
                                                                                if ($dataDetailRaw) {
                                                                                    $dataDetail = $dataDetailRaw["data"];
                                                                                    if ($dataDetail) {
                                                                                        $dataMeta["num-index"] = 1;
                                                                                        foreach ($dataDetail as $itemDetail) {
                                                                                            $valueDetail = $itemDetail["value"];
                                                                                            preg_match_all("^\[(.*?)]^", $valueDetail, $detailMatches);
                                                                                            if (count($detailMatches) > 1){
                                                                                                if ($detailMatches[1]) {
                                                                                                    if (count($detailMatches[1]) > 0){
                                                                                                        $selectedVal = $detailMatches[1][0];
                                                                                                        if ($selectedVal) {
                                                                                                            $selectedIds = explode(',', $selectedVal);
                                                                                                            $selectedIdVals = array();
                                                                                                            if ($selectedIds) {
                                                                                                                foreach ($selectedIds as $selectedId) {
                                                                                                                    $arrSelectedId = explode(':', $selectedId);
                                                                                                                    if ($arrSelectedId) {
                                                                                                                        $selectedIdKey = $arrSelectedId[0];
                                                                                                                        $selectedIdVal = $arrSelectedId[1];
                                                                                                                        $arrSelectedIdVal = explode('#', $selectedIdVal);
                                                                                                                        if (count($arrSelectedIdVal) == 2) {
                                                                                                                            $selectedIdVals[] = $arrSelectedIdVal[1];
                                                                                                                        } else {
                                                                                                                            $selectedIdVals[] = $selectedIdVal;
                                                                                                                        }
                                                                                                                        if ($selectedIdKey) {
                                                                                                                            $arrSelectedIdKey = explode('#', $selectedIdKey);
                                                                                                                            if ($arrSelectedIdKey) {
                                                                                                                                $selectedIdKeyType = $arrSelectedIdKey[0];
                                                                                                                                if (count($arrSelectedIdKey) > 1) {
                                                                                                                                    if ($selectedIdKeyType == "meta") {
                                                                                                                                        $valSelectedId = implode(",", $selectedIdVals);
                                                                                                                                        $valueDetail = $clsMetaData->fetchByIds($valSelectedId, "/");
                                                                                                                                    }
                                                                                                                                } else {
                                                                                                                                    switch ($selectedIdKeyType) {
                                                                                                                                        case "user":
                                                                                                                                            $valSelectedId = implode(",", $selectedIdVals);
                                                                                                                                            $valueDetail = $clsUser->fetchByIds($valSelectedId);
                                                                                                                                            break;
                                                                                                                                    }
                                                                                                                                }
                                                                                                                            }
                                                                                                                        }
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                            $dataMeta[$itemDetail["key"]] = $valueDetail;
                                                                                        }
                                                                                        $dataMeta["sign"] = $printURL . base64_encode("$valSelected#$code");
                                                                                        $dataSelected = $dataMeta;
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            break;
                                                    }
                                                } else if ($countSelected > 1) {
                                                    $index = 1;
                                                    foreach ($selectedIds as $selectedId) {
                                                        $arrSelected = explode(':', $selectedId);
                                                        $keySelected = $arrSelected[0];
                                                        $valSelected = $arrSelected[1];
                                                        switch ($keySelected) {
                                                            case "user":
                                                                $dataUser = $clsUser->getById($valSelected);
                                                                $codeUser = $dataUser["code"];
                                                                $dataUser["sign"] = $printURL . base64_encode("$codeUser#$code");
                                                                $dataUser["num-index"] = $index;
                                                                $dataUserId = $dataUser["id"];
                                                                if ($dataRefMeta) {
                                                                    foreach ($dataRefMeta as $itemRefMeta) {
                                                                        $refMetaSlug = $itemRefMeta['slug'];
                                                                        $refMetaType = $itemRefMeta['type'];
                                                                        if (in_array($refMetaType, array($keySelected, 'all'))) {
                                                                            $detailUserDataRaw = $clsDetail->fetchByTargetAndMeta("user", $dataUserId, $refMetaSlug);
                                                                            if ($detailUserDataRaw) {
                                                                                $detailUserData = $detailUserDataRaw['data'];
                                                                                if ($detailUserData) {
                                                                                    $indexDetail = 1;
                                                                                    foreach ($detailUserData as $itemDetailUserData) {
                                                                                        $valueDetailUserData = $clsMetaData->getById($itemDetailUserData['value']);
                                                                                        if ($valueDetailUserData) {
                                                                                            $dataUser["$refMetaSlug-$indexDetail"] = $valueDetailUserData["name"];
                                                                                            $indexDetail++;
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                $dataSelected[] = $dataUser;
                                                                break;
                                                            default:
                                                                $arrKeySelected = explode("#", $keySelected);
                                                                if (count($arrKeySelected) == 2){
                                                                    if ($arrKeySelected[0] == "meta") {
                                                                        $keyMeta = $arrKeySelected[1];
                                                                        $metaData = $clsMeta->getBySlug($keyMeta);
                                                                        if ($metaData) {
                                                                            $metaDataId = $metaData["id"];
                                                                            $dataChildrenRaw = $clsMeta->fetchChildren($metaDataId, 0);
                                                                            $dataChildren = null;
                                                                            if ($dataChildrenRaw) {
                                                                                $dataChildren = $dataChildrenRaw["data"];
                                                                            }
                                                                            if ($dataChildren) {
                                                                                $arrValSelected = explode("#", $valSelected);
                                                                                $numMeta = $arrValSelected[0];
                                                                                $valMeta = $arrValSelected[1];
                                                                                $dataMeta = array();
                                                                                $dataMeta["num-index"] = $index;
                                                                                $dataMeta["sign"] = $printURL . base64_encode("$valMeta#$code");
                                                                                foreach ($dataChildren as $itemChild) {
                                                                                    $childData = $clsMetaData->getByIdMetaAndNum($itemChild["id"], $numMeta);
                                                                                    $valueChildData = '';
                                                                                    if ($childData) {
                                                                                        $valueChildData = $childData["value"];
                                                                                    }
                                                                                    $dataMeta[$itemChild["slug"]] = $valueChildData;
                                                                                }
                                                                                $dataSelected[] = $dataMeta;
                                                                            } else {
                                                                                $dataMetaData = $clsMetaData->getById($valSelected);
                                                                                if ($dataMetaData) {
                                                                                    $dataMetaName = $dataMetaData["value"];
                                                                                    $dataMeta = array(
                                                                                        "num-index" => $index,
                                                                                        "nama" => $dataMetaName,
                                                                                        "nilai" => $dataMetaName,
                                                                                    );
                                                                                    $dataMeta["sign"] = $printURL . base64_encode("$valSelected#$code");
                                                                                    $dataSelected[] = $dataMeta;
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                } else if ($arrKeySelected[0] == "post") {
                                                                    $keyPost = $arrKeySelected[1];
                                                                    $postData = $clsPost->getBySlug($keyPost);
                                                                    if ($postData) {
                                                                        $postDataType = $postData["type"];
                                                                        if ($postDataType) {
                                                                            $cmsType = $clsType->getById($postDataType);
                                                                            if ($cmsType) {
                                                                                $typeSlug = "data" ; //$cmsType['slug'];
                                                                                $clsDetail = new Detail();
                                                                                $dataDetailRaw = $clsDetail->fetchByTarget($typeSlug, $valSelected);
                                                                                if ($dataDetailRaw) {
                                                                                    $dataDetail = $dataDetailRaw["data"];
                                                                                    if ($dataDetail) {
                                                                                        $dataMeta["num-index"] = $index;
                                                                                        foreach ($dataDetail as $itemDetail) {
                                                                                            $valueDetail = $itemDetail["value"];
                                                                                            preg_match_all("^\[(.*?)]^", $valueDetail, $detailMatches);
                                                                                            if (count($detailMatches) > 1){
                                                                                                if ($detailMatches[1]) {
                                                                                                    if (count($detailMatches[1]) > 0){
                                                                                                        $selectedVal = $detailMatches[1][0];
                                                                                                        if ($selectedVal) {
                                                                                                            $selectedIds = explode(',', $selectedVal);
                                                                                                            $selectedIdVals = array();
                                                                                                            if ($selectedIds) {
                                                                                                                foreach ($selectedIds as $selectedId) {
                                                                                                                    $arrSelectedId = explode(':', $selectedId);
                                                                                                                    if ($arrSelectedId) {
                                                                                                                        $selectedIdKey = $arrSelectedId[0];
                                                                                                                        $selectedIdVal = $arrSelectedId[1];
                                                                                                                        $arrSelectedIdVal = explode('#', $selectedIdVal);
                                                                                                                        if (count($arrSelectedIdVal) == 2) {
                                                                                                                            $selectedIdVals[] = $arrSelectedIdVal[1];
                                                                                                                        } else {
                                                                                                                            $selectedIdVals[] = $selectedIdVal;
                                                                                                                        }
                                                                                                                        if ($selectedIdKey) {
                                                                                                                            $arrSelectedIdKey = explode('#', $selectedIdKey);
                                                                                                                            if ($arrSelectedIdKey) {
                                                                                                                                $selectedIdKeyType = $arrSelectedIdKey[0];
                                                                                                                                if (count($arrSelectedIdKey) > 1) {
                                                                                                                                    if ($selectedIdKeyType == "meta") {
                                                                                                                                        $valSelectedId = implode(",", $selectedIdVals);
                                                                                                                                        $valueDetail = $clsMetaData->fetchByIds($valSelectedId, "/");
                                                                                                                                    }
                                                                                                                                } else {
                                                                                                                                    switch ($selectedIdKeyType) {
                                                                                                                                        case "user":
                                                                                                                                            $valSelectedId = implode(",", $selectedIdVals);
                                                                                                                                            $valueDetail = $clsUser->fetchByIds($valSelectedId);
                                                                                                                                            break;
                                                                                                                                    }
                                                                                                                                }
                                                                                                                            }
                                                                                                                        }
                                                                                                                    }
                                                                                                                }
                                                                                                            }
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                            $dataMeta[$itemDetail["key"]] = $valueDetail;
                                                                                        }
                                                                                        $dataMeta["sign"] = $printURL . base64_encode("$valSelected#$code");
                                                                                        $dataSelected[] = $dataMeta;
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                break;
                                                        }
                                                        $index++;
                                                    }
                                                }
                                                $data[$keyData] = $dataSelected;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $data;
    }

    public function clearFormat($content){
        $result = $content;
        preg_match_all('/(?s)\[(?!\/)([^\s\]]+)[^]]*](.*?)\[\/\1]/i', $result, $matches);
        if (count($matches) == 3){
            for ($i = 0; $i < count($matches[0]); $i++){
                $text = $matches[0][$i];
                $result = str_replace($text, '', $result);
            }
        }
        return $result;
    }

    public function childRender($content, $data) {
        $config = Registry::get('config');
        $baseUrl = Uri::baseUrl();
        $helper = new Helper();
        $clsMetaData = new MetaData();
        $clsConstant = new Constant();

        $signImageStart = '<img width="72" height="72" src="';
        $signImageEnd = '" />';

        $scanning = $this->scanning($content);

        if ($scanning) {
            $sources = $helper->getArrayValue($scanning, 'source');
            $tags = $helper->getArrayValue($scanning, 'tag');
            $attrs = $helper->getArrayValue($scanning, 'attr');
            $texts = $helper->getArrayValue($scanning, 'text');

            for ($i = 0; $i < count($sources); $i++) {
                $source = $sources[$i];
                $tag = $tags[$i];
                $attr = $attrs[$i];
                $text = $texts[$i];
                $target = $text;

                switch (strtolower($tag)) {
                    case "config":
                        if (strtolower($target) == "base_url"){
                            $target = $baseUrl;
                        } else {
                            $target = $config[$target];
                        }
                        break;
                    case "const":
                    case "setting":
                    case "constant":
                        $dataConstant = $clsConstant->getBySlug($text);
                        if ($dataConstant) {
                            $idConstant = $dataConstant["id"];
                            $dataMeta = $clsMetaData->get($idConstant);
                            $target = $dataMeta["value"];
                        }
                        break;
                    case "template":
                    case "loop":
                    case "report":
                        $target = $source;
                        break;
                    default:
                        if ($data) {
                            if (array_key_exists($tag, $data)){
                                $values = $data[$tag];
                                if (is_array($values)){
                                    if (array_key_exists($text, $values)){
                                        if ($text == "sign") {
                                            $signVal = $values[$text];
                                            $signURL = base64_encode($signVal);
                                            $signImage = $baseUrl . "image/qrcode/$signURL";
                                            $newSignVal = $signImageStart . $signImage . $signImageEnd;
                                            $target = $newSignVal;
                                        } else {
                                            $target = $values[$text];
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }

                if ($attr) {
                    foreach ($attr as $itemAttr) {
                        $name = $itemAttr["name"];
                        $value = $itemAttr["value"];
                        if ($value !== "false") {
                            switch (strtolower($name)) {
                                case "ucase":
                                    $target = strtoupper($target);
                                    break;
                                case "lcase":
                                    $target = strtolower($target);
                                    break;
                                case "bold":
                                    $target = "<strong>$target</strong>";
                                    break;
                                case "spell":
                                    $target = $helper->terbilang($target);
                                    $target = trim($target);
                                    break;
                                case "roman":
                                    $target = $helper->romawi($target);
                                    $target = trim($target);
                                    break;
                                case "format":
                                    switch ($value) {
                                        case "spell":
                                            $target = $helper->terbilang($target);
                                            $target = trim($target);
                                            break;
                                        case "roman":
                                            $target = $helper->romawi($target);
                                            $target = trim($target);
                                            break;
                                        case "numeric":
                                            $target = number_format($target, 0, ",", ".");
                                            $target = trim($target);
                                            break;
                                        default:
                                            if (substr($value, 1, 1) == "0") {
                                                $length = strlen($value);
                                                $target = sprintf("%0" . $length . "d", $target);
                                                $target = trim($target);
                                            }
                                    }
                                    break;
                                case "ldate":
                                case "longdate":
                                    $target = $helper->longDateId($target);
                                    $target = trim($target);
                                    break;
                                case "day":
                                    $target = $helper->dayDate($target);
                                    break;
                                case "range":
                                    $newTarget = $target;
                                    if ($data) {
                                        if (array_key_exists(strtolower($tag), $data)) {
                                            $values = $data[strtolower($tag)];
                                            if (is_array($values)) {
                                                $arrText = explode("#", $text);
                                                if (count($arrText) == 2) {
                                                    $text1 = $arrText[0];
                                                    $text2 = $arrText[1];
                                                    $newTarget1 = '';
                                                    $newTarget2 = '';
                                                    if (array_key_exists($text1, $values)){
                                                        $newTarget1 = $values[$text1];
                                                        $newTarget1 = $helper->longDateId($newTarget1);
                                                    }
                                                    if (array_key_exists($text2, $values)){
                                                        $newTarget2 = $values[$text2];
                                                        $newTarget2 = $helper->longDateId($newTarget2);
                                                    }
                                                    if ($newTarget1 == $newTarget2) {
                                                        $newTarget = $newTarget1;
                                                    } else {
                                                        $arrNewTarget2 = explode(" ", $newTarget2);
                                                        if (count($arrNewTarget2) == 3) {
                                                            $monthYear2 = $arrNewTarget2[1] . " " . $arrNewTarget2[2];
                                                            $newTarget1 = str_replace($monthYear2, "", $newTarget1);
                                                        }
                                                        $newTarget = "$newTarget1 s.d. $newTarget2";
                                                    }
                                                } else {
                                                    if (array_key_exists($text, $values)){
                                                        $newTarget = $values[$text];
                                                        $newTarget = $helper->longDateId($newTarget);
                                                    }
                                                }
                                                $target = $newTarget;
                                            }
                                        }
                                    }
                                    break;
                                case "calc":
                                    $newTarget = 0;
                                    if ($data) {
                                        if (array_key_exists(strtolower($tag), $data)) {
                                            $values = $data[strtolower($tag)];
                                            if (is_array($values)) {
                                                $arrText = explode("#", $text);
                                                $text1 = $arrText[0];
                                                if (array_key_exists($text1, $values)){
                                                    $newTarget = $values[$text1];
                                                }
                                                if (count($arrText) > 1) {
                                                    for ($num = 2; $num < count($arrText); $num++) {
                                                        $subTarget = 0;
                                                        if (array_key_exists($arrText[$num], $values)){
                                                            $subTarget = $values[$arrText[$num]];
                                                        }
                                                        switch ($value) {
                                                            case "sub":
                                                            case "-":
                                                                $newTarget -=  $subTarget;
                                                                break;
                                                            case "add":
                                                            case "+":
                                                                $newTarget +=  $subTarget;
                                                                break;
                                                            case "mul":
                                                            case "*":
                                                                $newTarget *=  $subTarget;
                                                                break;
                                                            case "div":
                                                                $newTarget = $newTarget / $subTarget;
                                                                break;
                                                        }
                                                    }
                                                }

                                                $target = $newTarget;
                                            }
                                        }
                                    }
                                    break;
                                case "numeric":
                                    $target = number_format($target, 0, ",", ".");
                                    $target = trim($target);
                                    break;
                                case "tag":
                                    $target = "[$value]" . $target . "[/$value]";
                                    $target = trim($target);
                                    break;
                                default:
                                    if (substr($name, 1, 1) == "0") {
                                        $length = strlen($name);
                                        $target = sprintf("%0" . $length . "d", $target);
                                        $target = trim($target);
                                    }
                            }
                        }
                    }
                }
                $content = str_replace($source, $target, $content);
            }
        }
        return $content;
    }

    public function render($content, $data) {
        $helper = new Helper();

        $content = $this->childRender($content, $data);
        $scanning = $this->scanning($content);

        if ($scanning) {
            $sources = $helper->getArrayValue($scanning, 'source');
            $tags = $helper->getArrayValue($scanning, 'tag');
            $attrs = $helper->getArrayValue($scanning, 'attr');
            $texts = $helper->getArrayValue($scanning, 'text');

            for ($i = 0; $i < count($sources); $i++) {
                $source = $sources[$i];
                $tag = $tags[$i];
                $attr = $attrs[$i];
                $text = $texts[$i];
                $target = '';

                $isTemplateHasField = 0;
                $isLoopHasField = 0;
                $templateField = '';
                $loopField = '';
                $loopCount = 0;
                $loopResult = '';

                if ($attr) {
                    foreach ($attr as $itemAttr) {
                        $name = $itemAttr["name"];
                        $value = $itemAttr["value"];
                        if ($value !== "false") {
                            switch (strtolower($name)) {
                                case "field":
                                    switch ($tag) {
                                        case "template":
                                        case "report":
                                            $isTemplateHasField = 1;
                                            $templateField = $value;
                                            break;
                                        case "loop":
                                            $isLoopHasField = 1;
                                            $loopField = $value;
                                            break;
                                    }
                                    break;
                                case "count":
                                    switch ($tag) {
                                        case "loop":
                                            $isLoopHasField = 0;
                                            $loopField = '';
                                            $loopCount = $value;
                                            break;
                                    }
                                    break;
                            }
                        }
                    }
                }

                switch (strtolower($tag)) {
                    case "template":
                    case "report":
                        if (!$isTemplateHasField) {
                            if ($tag !== "report") {
                                $template = $this->putTemplate($text);
                            } else {
                                $template = $this->putTemplateReport($text);
                            }
                            $target = $this->render($template, $data);
                        } else {
                            if ($data != null){
                                if (is_array($data)){
                                    if (array_key_exists($templateField, $data)){
                                        $arrTemplateData = $data[$templateField];
                                        $addTemplate = "";
                                        if ($arrTemplateData) {
                                            if ($this->is_assoc($arrTemplateData)) {
                                                $countRepeater = count($arrTemplateData);
                                                for ($r = 0; $r < $countRepeater; $r++) {
                                                    $templateFieldVal = $arrTemplateData[$r];
                                                    $newData = array($templateField => $templateFieldVal);
                                                    //$curTemplate = $this->putTemplate($text);
                                                    if ($tag !== "report") {
                                                        $curTemplate = $this->putTemplate($text);
                                                    } else {
                                                        $curTemplate = $this->putTemplateReport($text);
                                                    }
                                                    $curTemplate = str_replace("#field", $templateField, $curTemplate);
                                                    $curTemplate = $this->render($curTemplate, $newData);
                                                    $addTemplate .= $curTemplate;
                                                }
                                            } else {
                                                $templateFieldVal = $arrTemplateData;
                                                $newData = array($templateField => $templateFieldVal);
                                                //$curTemplate = $this->putTemplate($text);
                                                if ($tag !== "report") {
                                                    $curTemplate = $this->putTemplate($text);
                                                } else {
                                                    $curTemplate = $this->putTemplateReport($text);
                                                }
                                                $curTemplate = str_replace("#field", $templateField, $curTemplate);
                                                $curTemplate = $this->render($curTemplate, $newData);
                                                $addTemplate .= $curTemplate;
                                            }
                                        }
                                        $target = $addTemplate;
                                    }
                                }
                            }
                        }
                        break;
                    case "loop":
                        if (!$isLoopHasField) {
                            if ($loopCount > 0) {
                                for ($loop = 0; $loop < $loopCount; $loop++) {
                                    $loopResult .= $text;
                                }
                            }
                            $target = $loopResult;
                        } else {
                            if ($data != null){
                                if (is_array($data)){
                                    if (array_key_exists($loopField, $data)){
                                        $arrLoopData = $data[$loopField];
                                        $addList = "";
                                        if ($arrLoopData) {
                                            if ($this->is_assoc($arrLoopData)) {
                                                $countRepeater = count($arrLoopData);
                                                for ($r = 0; $r < $countRepeater; $r++) {
                                                    $loopFieldVal = $arrLoopData[$r];
                                                    $newData = array($loopField => $loopFieldVal);
                                                    $curList = $text;
                                                    $curList = str_replace("#field", $loopField, $curList);
                                                    $curList = $this->render($curList, $newData);
                                                    $addList .= $curList;
                                                }
                                            } else {
                                                $loopFieldVal = $arrLoopData;
                                                $newData = array($loopField => $loopFieldVal);
                                                $curList = $text;
                                                $curList = str_replace("#field", $loopField, $curList);
                                                $curList = $this->render($curList, $newData);
                                                $addList .= $curList;
                                            }
                                        }
                                        $target = $addList;
                                    }
                                }
                            }
                        }
                        break;
                }
                $content = str_replace($source, $target, $content);
            }
        }
        return $content;
    }

    public function scanning($content){
        $tags = null;
        $texts = null;
        $sources = null;
        $rawAttrs = null;
        $attrs = null;
        $scanning = null;
        $helper = new Helper();

        preg_match_all('/(?s)\[(?!\/)([^\s\]]+)[^]]*](.*?)\[\/\1]/i', $content, $matches);

        if ($matches) {
            foreach ($matches as $num => $match) {
                if ($num == 0) $sources = $match;
                if ($num == 1) $tags = $match;
                if ($num == 2) $texts = $match;
            }

            foreach ($sources as $item) {
                $rawAttrs[] = null;
            }

            preg_match_all('#\[(\w+)((?:\s|=)[^]]*)]((?:[^[]|\[(?!/?\1((?:\s|=)[^]]*)?])|(?R))*)\[/\1]#i', $content, $matches);

            if ($matches) {
                $newSources = null;
                $newRawAttrs = null;
                foreach ($matches as $num => $match) {
                    if ($num == 0) $newSources = $match;
                    if ($num == 2) $newRawAttrs = $match;
                }
                if ($newSources) {
                    $sameIds = null;
                    foreach ($sources as $key => $itemSource){
                        if (in_array($itemSource, $newSources)) {
                            $sameIds[] = $key;
                        }
                    }
                    if ($sameIds) {
                        if ($newRawAttrs) {
                            //print_r($sameIds);
                            foreach ($newRawAttrs as $num => $item) {
                                //print_r($num);
                                $rawAttrs[$sameIds[$num]] = $item;
                            }
                        }
                    }
                }
                $tmpAttrs = null;

                // print_r($sources);
                // print_r($tags);
                // print_r($texts);

                $rawTexts = null;
                if ($sources) {
                    for ($i = 0; $i < count($sources); $i++){
                        $rawText = $sources[$i];
                        if ($rawText) {
                            $replacer = "[" . $tags[$i];
                            $rawText = str_replace($replacer, '', $rawText);
                            $replacer = "]" . $texts[$i];
                            $rawText = str_replace($replacer, '', $rawText);
                            $replacer = "[/" . $tags[$i] . "]";
                            $rawText = str_replace($replacer, '', $rawText);
                            $rawText = $helper->removeWhiteSpace($rawText);
                            $rawText = str_replace('"', '', $rawText);
                            $rawText = str_replace("'", '', $rawText);
                        }
                        $rawTexts[$i] = $rawText;
                    }
                }

                $attrs = null;
                if ($rawTexts) {
                    for ($i = 0; $i < count($sources); $i++){
                        $rawText = $rawTexts[$i];
                        $arrRawText = null;
                        $arrItem = null;
                        if ($rawText) {
                            $arrRawText = explode(" ", $rawText);
                            if ($arrRawText) {
                                for ($j = 0; $j < count($arrRawText); $j++){
                                    $arrRawData = explode("=", $arrRawText[$j]);
                                    if ($arrRawData) {
                                        $arrItem[$j]["name"] = $arrRawData[0];
                                        if (count($arrRawData) == 2) {
                                            $arrItem[$j]["value"] = $arrRawData[1];
                                        } else {
                                            $arrItem[$j]["value"] = "true";
                                        }
                                    }
                                }
                            }
                        }
                        $attrs[$i] = $arrItem;
                    }
                }
            }

            if ($sources) {
                $scanning = array(
                    "source" => $sources,
                    "tag" => $tags,
                    "attr" => $attrs,
                    "text" => $texts,
                );
            }
        }
        return $scanning;
    }
}
