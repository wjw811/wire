<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Gd.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/
namespace Next\Helper;

class Gd {

/*{{{ variable*/
    private $app;
/*}}}*/
/*{{{ construct */
    public function __construct() {
        if (!extension_loaded('gd')) {
           throw new \RuntimeException('GD module not loaded.');
        }
        $this->app = \Slim\Slim::getInstance();
    }
/*}}}*/

/*{{{ captcha */
    public function captcha($option = array()) {
        $default = array(
            "word" => "",
            "font" => "",
            "width" => 100,
            "height" => 40,
        );
        $option = array_merge($default, $option);

        // -----------------------------------
        // Do we have a "word" yet?
        // -----------------------------------
       if ($option["word"] == '') {
            $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

            $str = '';
            for ($i = 0; $i < 4; $i++) {
                $str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
            }

            $option["word"] = $str;
       }

       if ($option["font"] == '') {
           $option["font"] = 'Gd/font/OleoScript-Bold.ttf';
       }

        // -----------------------------------
        // Determine angle and position
        // -----------------------------------
        $length = strlen($option["word"]);
        $angle  = ($length >= 6) ? rand(-($length-6), ($length-6)) : 0;
        $x_axis = rand(6, (360/$length)-16);
        $y_axis = ($angle >= 0 ) ? rand($option["height"], $option["width"]) : rand(6, $option["height"]);

        // -----------------------------------
        // Create image
        // -----------------------------------
        // PHP.net recommends imagecreatetruecolor(), but it isn't always available
        if (function_exists('imagecreatetruecolor')) {
            $im = imagecreatetruecolor($option["width"], $option["height"]);
        } else {
            $im = imagecreate($option["width"], $option["height"]);
        }

        // -----------------------------------
        //  Assign colors
        // -----------------------------------
        $bg_color       = imagecolorallocate($im, 255, 255, 255);
        $border_color   = imagecolorallocate($im, 255, 255, 255);
        $text_color     = imagecolorallocate($im, 59, 89, 152);
        $grid_color     = imagecolorallocate($im, 105, 175, 35);
        $shadow_color   = imagecolorallocate($im, 255, 240, 240);

        // -----------------------------------
        //  Create the rectangle
        // -----------------------------------
        imagefilledrectangle($im, 0, 0, $option["width"], $option["height"], $bg_color);

        // -----------------------------------
        //  Create the spiral pattern
        // -----------------------------------
        $theta      = 1;
        $thetac     = 7;
        $radius     = 16;
        $circles    = 20;
        $points     = 32;

        for ($i = 0; $i < ($circles * $points) - 1; $i++) {
            $theta = $theta + $thetac;
            $rad = $radius * ($i / $points );
            $x = ($rad * cos($theta)) + $x_axis;
            $y = ($rad * sin($theta)) + $y_axis;
            $theta = $theta + $thetac;
            $rad1 = $radius * (($i + 1) / $points);
            $x1 = ($rad1 * cos($theta)) + $x_axis;
            $y1 = ($rad1 * sin($theta )) + $y_axis;
            imageline($im, $x, $y, $x1, $y1, $grid_color);
            $theta = $theta - $thetac;
        }

        // -----------------------------------
        //  Write the text
        // -----------------------------------
        $use_font = ($option["font"] != '' && file_exists($option["font"]) && function_exists('imagettftext')) ? TRUE : FALSE;

        if ($use_font == FALSE) {
            $font_size = 5;
            $x = rand(0, $option["width"]/($length/3));
            $y = 0;
        } else {
            $font_size  = 28;
            $x = rand(0, $option["width"]/($length));
            $y = $font_size+2;
        }

        for ($i = 0; $i < strlen($option["word"]); $i++) {
            if ($use_font == FALSE) {
                $y = rand(0 , $option["height"]/2);
                imagestring($im, $font_size, $x, $y, substr($option["word"], $i, 1), $text_color);
                $x += ($font_size*2);
            } else {
                $y = $option["height"]/2 + 13; 
                //$y = rand($option["height"]/2, $option["height"]);
                imagettftext($im, $font_size, $angle, $x, $y, $text_color, $option["font"], substr($option["word"], $i, 1));
                $x += $font_size;
            }
        }

        // -----------------------------------
        //  Create the border
        // -----------------------------------
        imagerectangle($im, 0, 0, $option["width"]-1, $option["height"]-1, $border_color);

        // -----------------------------------
        //  Generate the image
        // -----------------------------------
        $this->app->session->set("captcha", $option["word"]);
        @ob_clean();
        header("Content-type: image/jpeg");
        imagejpeg($im);
        imagedestroy($im);
    }
/*}}}*/
/*{{{ thumb */
    public function thumb() {
        // TODO
    }
/*}}}*/

}
