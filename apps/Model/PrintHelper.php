<?php

use NG\Registry;
use NG\Session;
use NG\Uri;

class PrintHelper {
    protected $session;
    protected $config;
    protected $pdf;
    protected $render;

    public function __construct($pageFormat = "A4", $orientation = "P") {
        $this->session = new Session;
        $this->config = Registry::get('config');
        $this->render = new Render();

        $this->pdf = new Pdf(
            $pageFormat,
            PDF_UNIT,
            $orientation,
            true,
            'UTF-8',
            false
        );
    }

    public function getPdf(){
        return $this->pdf;
    }
}
