<?php

use NG\Cache;
use NG\Cookie;
use NG\Registry;
use NG\Route;
use NG\Session;

class ImageController extends NG\Controller {
    protected $config;
    protected $cache;
    protected $session;
    protected $cookie;

    public function init() {
        $this->config = $this->view->config = Registry::get('config');
        $this->session = $this->view->session = new Session();
        $this->cookie = $this->view->cookie = new Cookie();
        $this->cache = $this->view->cache = new Cache();

        $this->view->setLayout(false);
        $this->view->setNoRender(true);
    }

    public function IndexAction() {
        $requests = Route::getRequests();

        $param1 = "";
        $param2 = "";
        $param3 = "";
        $param4 = "";

        if (isset($requests['param1'])){
            $param1 = $requests['param1'];
            $param1 = urldecode($param1);

            if (isset($requests['param2'])){
                $param2 = $requests['param2'];
                $param2 = urldecode($param2);

                if (isset($requests['param3'])){
                    $param3 = $requests['param3'];
                    $param3 = urldecode($param3);

                    if (isset($requests['param4'])){
                        $param4 = $requests['param4'];
                        $param4 = urldecode($param4);

                        if (isset($requests['param5'])){
                            $param5 = $requests['param5'];
                            $param5 = urldecode($param5);

                            if (isset($requests['param6'])){
                                $param6 = $requests['param6'];
                                $param6 = urldecode($param6);

                                if (isset($requests['param7'])){
                                    $param7 = $requests['param7'];
                                    $param7 = urldecode($param7);

                                    if (isset($requests['param8'])){
                                        $param8 = $requests['param8'];

                                        if (isset($requests['param9'])){
                                            $param9 = $requests['param9'];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        switch ($param1){
            case "qrcode":
                if ($param2) {
                    header('Content-Type: image/png');
                    header('Cache-Control: max-age=2592000');
                    $url = base64_decode($param2);
                    $qr = NGQRCode::getMinimumQRCode($url, QR_ERROR_CORRECT_LEVEL_L);
                    $imageQR = $qr->createImage(3, 4);
                    imagepng($imageQR, null, 9);
                    imagedestroy($imageQR);
                } else {
                    header('Content-Type: image/png');
                    header('Cache-Control: max-age=2592000');
                    $img = imagecreatetruecolor(200, 200);
                    imagesavealpha($img, true);
                    $color = imagecolorallocatealpha($img, 0, 0, 0, 127);
                    imagefill($img, 0, 0, $color);
                    imagepng($img, null, 9);
                }
                break;
            case "text-logo":
                if ($param2 && $param3) {
                    $text = base64_decode($param3);
                    header('Content-Type: image/png');
                    header('Cache-Control: max-age=2592000');

                    $font_size = 18;
                    $font_height = imagefontheight($font_size);
                    $font_dir = ROOT . DS . ASSETS . DS . "font" . DS;
                    $font = $font_dir . "57930.ttf";
                    $text_box = imagettfbbox($font_size, 0, $font, $text);
                    $text_width = $text_box[2];
                    $text_height = 15;

                    $width = $text_width + 10;
                    $height = $text_height + $font_height;
                    $canvas = imagecreatetruecolor($width, $height);
                    imagesavealpha($canvas, true);

                    $black = imagecolorallocate($canvas, 30, 30, 200);
                    $white = imagecolorallocate($canvas, 255, 255, 210);

                    $text_color = $white;
                    if ($param2 == "dark") {
                        $text_color = $black;
                    }
                    $color = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
                    imagefill($canvas, 0, 0, $color);

                    $start_left = (($width - $text_width) / 2);
                    $start_top = $font_height + (($height - $text_height) / 2);

                    imagettftext($canvas, $font_size, 0, $start_left, $start_top, $text_color, $font, $text);
                    imagepng($canvas, null, 9);
                }
                break;
            case "profile":
                if ($param2){
                    if ($param3){
                        $array = explode('.', $param3);
                        $file_ext = strtolower(end($array));
                        $dir = ROOT . DS . UPLOADS . DS . "profile";
                        if (!is_dir($dir)):
                            mkdir($dir, 0755, true);
                        endif;
                        $path = $dir . DS . strtolower($param2) . DS . $param3;
                        if (file_exists($path)){
                            header("Content-Type: image/$file_ext");
                            header('Cache-Control: max-age=2592000');
                            readfile($path);
                        }
                    }
                }
                break;
            case "gallery":
                if ($param2){
                    if ($param3){
                        $array = explode('.', $param3);

                        $file_ext = strtolower(end($array));
                        $file_name = substr($param3, 0, strlen($param3) - (strlen($file_ext) + 1));

                        if (strlen($param2) == 8){
                            $dir = ROOT . DS . UPLOADS . DS . "gallery";

                            $datePath = substr($param2, 0, 4) . DS;
                            $datePath .= substr($param2, 4, 2). DS;
                            $datePath .= substr($param2, 6, 2);

                            if (!is_dir($dir)):
                                mkdir($dir, 0755, true);
                            endif;

                            $path = $dir . DS . $datePath . DS . $param3;

                            if (!file_exists($path)){
                                $cls = new CmsGallery();
                                $data = $cls->getByDateAndName($param2, $file_name);

                                if ($data){
                                    $dataExt = $data["ext"];
                                    $dataCode = $data["code"];
                                    $newFile = $dataCode . "." . $dataExt;
                                    $path = $dir . DS . $datePath . DS . $newFile;
                                }
                            }

                            if (file_exists($path)){
                                header("Content-Type: image/$file_ext");
                                header('Cache-Control: max-age=2592000');
                                readfile($path);
                            }
                        }
                    }
                }
                break;
        }
    }
}

?>
