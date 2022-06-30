<?php

use NG\Registry;
use NG\Route;
use NG\Session;
use NG\Uri;

class PrintController extends NG\Controller {
    protected $config;
    protected $session;
    protected $helper;
    protected $menu;

    protected $pdfA4P;
    protected $pdfA4L;

    protected $pdfF4P;
    protected $pdfF4L;

    protected $printHelper;

    public function init() {
        $this->config = $this->view->config = Registry::get('config');
        $this->session = $this->view->session = new Session();
        $this->helper = $this->view->helper = new Helper();
        $this->menu = $this->view->menu = Registry::get('menu');

        $this->view->setLayout(false);
        $this->view->setNoRender(true);

        $printHelper = new PrintHelper("A4", "P");
        $this->pdfA4P = $printHelper->getPdf();

        $printHelper = new PrintHelper("A4", "L");
        $this->pdfA4L = $printHelper->getPdf();

        $printHelper = new PrintHelper("F4", "P");
        $this->pdfF4P = $printHelper->getPdf();

        $printHelper = new PrintHelper("F4", "L");
        $this->pdfF4L = $printHelper->getPdf();

        $this->printHelper = $printHelper;
    }

    public function IndexAction() {
        $requests = Route::getRequests();

        $printHelper = $this->printHelper;
        $pdf = $this->pdfA4P;

        $alert = "";
        $success = "";
        $action = "";

        $controller = Route::getController();
        $controllers = Registry::get('controller');
        $controllerPath = strtolower($controller);

        $session = $this->session;
        $userSession = null;
        $currSession = $session->get("Login");

        if ($controllers){
            foreach ($controllers as $item){
                $itemName = $item["name"];
                $itemPath = $item["path"];
                if ($itemName == strtolower($controller)){
                    $controllerPath = $itemPath;
                    break;
                }
            }
        }

        $helper = $this->helper;
        $allMenus = $helper->collectMenu($this->menu);
        $mainMenu = $helper->getMenuId($allMenus, strtolower($controller));
        $activeMenu = array($mainMenu);

        $param1 = "";
        $param2 = "";
        $param3 = "";
        $param4 = "";
        $param5 = "";

        if (isset($requests['param1'])) {
            $param1 = $requests['param1'];
            if (isset($requests['param2'])) {
                $param2 = $requests['param2'];
                if (isset($requests['param3'])) {
                    $param3 = $requests['param3'];
                    if (isset($requests['param4'])) {
                        $param4 = $requests['param4'];
                        if (isset($requests['param5'])) {
                            $param5 = $requests['param5'];
                        }
                    }
                }
            }
        }

        $subMenu = $helper->getMenuId($allMenus, strtolower($controller) . '/' . $param1);
        $activeMenu[] = $subMenu;

        $title = $helper->getTitle($this->menu, 'id', 'text', end($activeMenu));

        if ($param1){
            switch ($param1) {
                case "post":
                    if ($param2){
                        $codeLetter = $param2;
                        $clsLetter = new Letter();
                        $dataLetter = $clsLetter->getByCode($codeLetter);
                        if (!$dataLetter) {
                            $baseCode = base64_decode($param2);
                            // print_r($baseCode);
                            if ($baseCode) {
                                $arrBaseCode = explode("#", $baseCode);
                                if (count($arrBaseCode) == 2) {
                                    $codeLetter = $arrBaseCode[1];
                                    $dataLetter = $clsLetter->getByCode($codeLetter);
                                }
                            }
                        }

                        if ($dataLetter) {
                            $postId = $helper->getArrayValue($dataLetter, 'idpost');
                            $title = $helper->getArrayValue($dataLetter, 'judul-surat');
                            $slug = $helper->getSlug($title);
                            $clsPost = new CmsPost();
                            $dataPost = $clsPost->getById($postId);
                            $genData = $printHelper->generateData(array("data" => $dataLetter), $codeLetter);
                            if ($param3) {
                                if ($param3 == "unsign"){
                                    $newGenData = array();
                                    if (is_array($genData)) {
                                        foreach ($genData as $keyData => $itemData) {
                                            $newSubItem = null;
                                            if (is_array($itemData)) {
                                                foreach ($itemData as $keyData2 => $itemData2) {
                                                    $newVal = $itemData2;
                                                    if (!is_array($itemData2)) {
                                                        if ($keyData2 == "sign") {
                                                            $newVal = '';
                                                        }
                                                    }
                                                    $newSubItem[$keyData2] = $newVal;
                                                }
                                                $newGenData[$keyData] = $newSubItem;
                                            }
                                        }
                                    }
                                    $genData = $newGenData;
                                }
                            }

                            $content = $helper->getArrayValue($dataPost, 'content');
                            $dataContent = $printHelper->render($content, $genData);
                            //print_r($dataContent);

                            $dataContent = $printHelper->clearFormat($dataContent);

                            // print_r($genData);

                            $pdf->SetTitle($title);
                            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                            $pdf->setPrintHeader(false);
                            $pdf->setPrintFooter(false);
                            $pdf->setFontSubsetting(true);
                            $pdf->SetMargins(20, 10, 20);
                            $pdf->AddPage('P', "A4", false, false);
                            $fontName = 'helvetica';
                            //$pdf->AddFont($fontName,'','HelveticaNeue LightCond.ttf',true);
                            //$pdf->AddFont($fontName,'B','HelveticaNeue MediumCond.ttf',true);
                            $pdf->SetFont($fontName,'', 12);
                            $pdf->writeHTML($dataContent, true, false, true, false, '');
                            ob_get_contents();
                            ob_end_clean();
                            $path = ROOT . DS . TMP . DS;
                            $this->helper->validateDir(ROOT . DS . TMP);
                            $file = $path . "$slug.pdf";
                            $pdf->Output($file, 'FI');
                        }
                    }
                    break;
                case "laporan":
                    if (!$currSession) {
                        Route::redirect(Uri::baseUrl());
                    }
                    if ($param2){
                        switch ($param2) {
                            case "surat":
                                $postId = $param3;
                                $filterYear = 0;
                                $filterMonth = 0;
                                if ($param4) {
                                    $filterYear = $param4;
                                }
                                if ($param5) {
                                    $filterMonth = $param5;
                                }

                                if (!$filterYear) $filterYear = date("Y");

                                $clsPost = new CmsPost();
                                $clsInput = new CmsInput();
                                $dataPost = $clsPost->getById($postId);
                                if ($dataPost) {
                                    $arrayMonth = array(
                                        "Semua",
                                        "Januari", "Februari", "Maret", "April", "Mei", "Juni",
                                        "Juli", "Agustus", "September", "Oktober", "November", "Desember",
                                    );
                                    $dataPost["judul-surat"] = "Laporan Surat " . $dataPost["title"];
                                    $dataPost["bulan"] = $arrayMonth[$filterMonth];
                                    $dataPost["tahun"] = $filterYear;
                                    $title = $dataPost["judul-surat"];
                                    $slug = $helper->getSlug($title);
                                    unset ($dataPost["content"]);
                                    unset ($dataPost["type"]);
                                    unset ($dataPost["categories"]);
                                    unset ($dataPost["excerpt"]);
                                    unset ($dataPost["slug"]);
                                    unset ($dataPost["title"]);
                                    $clsLetter = new Letter();
                                    $dataLetterRaw = $clsLetter->fetchByPostId($postId, $filterYear, $filterMonth, 0);
                                    if ($dataLetterRaw) {
                                        $dataLetter = $helper->getArrayValue($dataLetterRaw, "data");
                                        if ($dataLetter) {
                                            $rows = array();
                                            $cols = array();
                                            $clsMeta = new Meta();
                                            $clsMetaData = new MetaData();
                                            $clsUser = new User();
                                            $no = 1;
                                            foreach ($dataLetter as $item) {
                                                $createdate = $helper->getArrayValue($item, "createdate");
                                                if ($createdate) {
                                                    $createdate = $helper->unreverseDate($createdate);
                                                    $item["createdate"] = $createdate;
                                                }
                                                $detailKeys = $helper->getArrayValue($item, "detail-keys");
                                                $detailValues = $helper->getArrayValue($item, "detail-values");
                                                if ($detailKeys){
                                                    $arrDetailKeys = explode("***", $detailKeys);
                                                    $arrDetailValues = explode("***", $detailValues);
                                                    for ($k = 0; $k < count($arrDetailKeys); $k++) {
                                                        $itemDetailKeys = $arrDetailKeys[$k];

                                                        $itemDetailValues = $arrDetailValues[$k];
                                                        $item[$itemDetailKeys] = $itemDetailValues;
                                                        preg_match_all("^\[(.*?)]^", $itemDetailValues, $matches);
                                                        if (count($matches) > 1){
                                                            if ($matches[1]) {
                                                                if (count($matches[1]) > 0){
                                                                    $selectedVal = $matches[1][0];
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
                                                                                                    $item[$itemDetailKeys] = $clsMetaData->fetchByIds($valSelectedId);
                                                                                                }
                                                                                            } else {
                                                                                                switch ($selectedIdKeyType) {
                                                                                                    case "user":
                                                                                                        $valSelectedId = implode(",", $selectedIdVals);
                                                                                                        $item[$itemDetailKeys] = $clsUser->fetchByIds($valSelectedId);
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
                                                    }
                                                    unset($item["detail-keys"]);
                                                    unset($item["detail-values"]);
                                                }
                                                unset($item["id"]);
                                                unset($item["idpost"]);
                                                unset($item["code"]);
                                                unset($item["createby"]);
                                                unset($item["status"]);
                                                unset ($item["judul-surat"]);
                                                $item["num-index"] = $no;

                                                if ($no == 1) {
                                                    $keys = array_keys($item);
                                                    foreach ($keys as $key) {
                                                        if (substr($key, -4) == "long") {
                                                            unset($item[$key]);
                                                        }
                                                    }
                                                    $itemCols = sizeof($item);
                                                    $keys = array_keys($item);
                                                    $cols[] = "num-index";
                                                    for ($c = 0; $c < $itemCols - 1; $c++) {
                                                        $key = $keys[$c];
                                                        $cols[] = $key;
                                                    }
                                                }

                                                $row = array();
                                                $c = 0;
                                                foreach ($cols as $col) {
                                                    $parent = '';
                                                    $format = '';
                                                    $dataInput = $clsInput->getBySlug($col);
                                                    if ($dataInput) {
                                                        $dataInputParent = $helper->getArrayValue($dataInput, "parent-slug", '');
                                                        $format = $helper->getArrayValue($dataInput, "format", '');
                                                        if ($dataInputParent) {
                                                            $parent = $dataInputParent;
                                                        }
                                                    }
                                                    $value = $item[$col];
                                                    if ($format) {
                                                        switch ($format) {
                                                            case "roman":
                                                                $value = $helper->romawi($value);
                                                                break;
                                                            case "spell":
                                                                $value = $helper->terbilang($value);
                                                                break;
                                                            case "ucase":
                                                                $value = strtoupper($value);
                                                                break;
                                                            case "lcase":
                                                                $value = strtolower($value);
                                                                break;
                                                            case "numeric":
                                                                $value = number_format($value, 0, ",", ".");
                                                                break;
                                                            default:
                                                                if (substr($format, 1, 1) == "0") {
                                                                    $length = strlen($format);
                                                                    $value = sprintf("%0" . $length . "d", $value);
                                                                    $value = trim($value);
                                                                }
                                                                break;
                                                        }
                                                    }
                                                    if ($parent) {
                                                        $row[$parent][] = $value;
                                                        unset($cols[$c]);
                                                    } else {
                                                        $row[$col][] = $value;
                                                    }
                                                    $c++;
                                                }
                                                $oldRow = $row;
                                                if ($oldRow) {
                                                    foreach ($oldRow as $keyRow => $valueRow) {
                                                        $row[$keyRow] = implode("/", $valueRow);
                                                    }
                                                }
                                                $rows[] = $row;
                                                $no++;
                                            }
                                            $oldCols = $cols;
                                            $cols = array();
                                            $clsInput = new CmsInput();
                                            $wDefault = 0;
                                            $sDefault = 0;
                                            foreach ($oldCols as $col) {
                                                $name = "";
                                                $align = "left";
                                                $size = 0;
                                                switch ($col) {
                                                    case "num-index":
                                                        $name = "No";
                                                        $align = "right";
                                                        $size = 40;
                                                        $wDefault += 1;
                                                        $sDefault += $size;
                                                        break;
                                                    case "number":
                                                        $name = "No. Surat";
                                                        $size = 80;
                                                        $wDefault += 1;
                                                        $sDefault += $size;
                                                        break;
                                                    case "createdate":
                                                        $name = "Tgl. Buat";
                                                        $size = 60;
                                                        $wDefault += 1;
                                                        $sDefault += $size;
                                                        break;
                                                    case "status-name":
                                                        $name = "Status";
                                                        $size = 60;
                                                        $wDefault += 1;
                                                        $sDefault += $size;
                                                        break;
                                                    case "createbyname":
                                                        $name = "Pembuat";
                                                        $size = 80;
                                                        $wDefault += 1;
                                                        $sDefault += $size;
                                                        break;
                                                    default:
                                                        $input = $clsInput->getBySlug($col);
                                                        if ($input) {
                                                            $name = $helper->getArrayValue($input, 'title');
                                                            $source = $helper->getArrayValue($input, 'source');
                                                            if (strpos($name, "Tanggal") !== false) {
                                                                $name = str_replace("Tanggal", "Tgl.", $name);
                                                                $size = 60;
                                                                $wDefault += 1;
                                                                $sDefault += $size;
                                                            }
                                                            if (strpos($name, "Waktu") !== false) {
                                                                $size = 40;
                                                                $wDefault += 1;
                                                                $sDefault += $size;
                                                            }
                                                            if ($source == "month" || $source == "year") {
                                                                $size = 40;
                                                                $wDefault += 1;
                                                                $sDefault += $size;
                                                            }
                                                        }
                                                        break;
                                                }
                                                $cols[] = array(
                                                    "key" => $col,
                                                    "name" => $name,
                                                    "size" => $size,
                                                    "align" => $align,
                                                );
                                            }
                                            $wLeft = 785 - $sDefault;
                                            $oldCols = $cols;
                                            $cols = array();
                                            foreach ($oldCols as $col) {
                                                $size = $col["size"];
                                                if ($size == 0) {
                                                    $size = (int) (($wLeft / (count($oldCols) - $wDefault)));
                                                    $col["size"] = $size;
                                                }
                                                $cols[] = $col;
                                            }
                                            $dataPost["rows"] = $rows;
                                            $data = array(
                                                "data" => $dataPost,
                                                "cols" => $cols
                                            );
                                            $genData = $printHelper->generateData($data, '');
                                            $dataHeader = $printHelper->putTemplateReport("surat");
                                            $dataHeader = $printHelper->render($dataHeader, $genData);
                                            $dataBody = $printHelper->putTemplateReport("surat-body");
                                            $dataBody = $printHelper->render($dataBody, $genData);
                                            $bodyContent = "";
                                            foreach ($rows as $row) {
                                                $bodyContent .= "\r" . $printHelper->childRender($dataBody, array("data" => $row));
                                            }
                                            $dataContent = $dataHeader;
                                            $dataContent = str_replace("{{content}}", $bodyContent, $dataContent);

                                            $pdf->SetTitle($title);
                                            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
                                            $pdf->setPrintHeader(false);
                                            $pdf->setPrintFooter(false);
                                            $pdf->setFontSubsetting(true);
                                            $pdf->SetMargins(10, 10, 10);
                                            $pdf->AddPage('L', "A4", false, false);
                                            $fontName = 'Helvetica';
                                            $pdf->AddFont($fontName,'','HelveticaNeue LightCond.ttf',true);
                                            $pdf->AddFont($fontName,'B','HelveticaNeue MediumCond.ttf',true);
                                            $pdf->SetFont($fontName,'',12);
                                            $pdf->writeHTML($dataContent, true, false, true, false, '');
                                            ob_get_contents();
                                            ob_end_clean();
                                            $path = ROOT . DS . TMP . DS;
                                            $this->helper->validateDir(ROOT . DS . TMP);
                                            $file = $path . "$slug.pdf";
                                            $pdf->Output($file, 'FI');

                                        }
                                    }
                                }
                                break;
                        }
                    }
                    break;
            }
        }

        $this->view->viewAlert = $alert;
        $this->view->viewSuccess = $success;

        $this->view->setAction($action);
        $this->view->viewTitle = $title;
        $this->view->viewActiveMenu = $activeMenu;

        $this->view->viewController = $controller;
        $this->view->viewControllerPath = $controllerPath;
    }
}
