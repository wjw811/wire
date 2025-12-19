<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename Docx.php
* @touch date Mon 22 May 2023 04:33:12 PM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

namespace Next\Helper;

class Docx {
    const DOC = 'word/document.xml';
    private $app;
    private $zip;

/*{{{ __construct */
    public function __construct() {
        if (!extension_loaded('zip')) {
            throw new \RuntimeException('Zip module not loaded.');
        }

        $this->app = \Slim\Slim::getInstance();
        $this->zip = new \ZipArchive();
    }
/*}}}*/
/*{{{ parse */
    public function parse($file) {
        $out = [];
        $path = $this->path($file);
        if ($this->zip->open($path) === true) {
            $content = $this->zip->getFromName(self::DOC);
            $regex = "/\{([^}]+)\}/";
            preg_match_all($regex, $content, $matches);
            $data = is_array($matches[1])? $matches[1]: [];

            $flag = false;
            for ($i = 0; $i < count($data); $i++) {
                $v = strip_tags($data[$i]);
                if ($v && !in_array($v, $out)) {
                    $out[] = $v;
                }
                // fix docx file
                if ($v != $data[$i]) {
                    $content = str_replace($data[$i], $v, $content);
                    $flag = true;
                }
            }

            if ($flag) {
                $this->zip->deleteName(self::DOC);
                $this->zip->addFromString(self::DOC, $content);
            }
            $this->zip->close();
        }

        return $out;
    }
/*}}}*/
/*{{{ wirte */
    public function write($file, $d, $extra = []) {
        $path = $this->path($file);
        if ($this->zip->open($path) === true) {
            $content = $this->zip->getFromName(self::DOC);
            
            $content = str_replace(array_keys($d), array_values($d), $content);
            if ($extra) {
                $str = $this->extra($extra);
                $content = str_replace('</w:body>', $str.'</w:body>', $content);
            }
            $this->app->log->debug($content);
            $this->zip->deleteName(self::DOC);
            $this->zip->addFromString(self::DOC, $content);
            $this->zip->close();

            return true;
        }

        return false;
    }
/*}}}*/
/*{{{ path */
    public function path($file) {
        $config = $this->app->config('upload');
        return sprintf('%stpl/%s', $config['save_path'], $file);
    }
/*}}}*/
/*{{{ */
    private function extra($extra) {
        $tc = '<w:tc><w:tcPr><w:tcW w:w="0" w:type="auto"/><w:tcBorders><w:top w:val="single" w:color="auto" w:sz="4" w:space="0" w:shadow="0" w:frame="0"></w:top><w:left w:val="single" w:color="auto" w:sz="4" w:space="0" w:shadow="0" w:frame="0"></w:left><w:bottom w:val="single" w:color="auto" w:sz="4" w:space="0" w:shadow="0" w:frame="0"></w:bottom><w:right w:val="single" w:color="auto" w:sz="4" w:space="0" w:shadow="0" w:frame="0"></w:right></w:tcBorders></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>%s</w:t></w:r></w:p></w:tc>';
        $col = 0;
        $rows = [];
        foreach ($extra as $row) {
            if (!$col) {
                $col = count($row);
            }

            $cells = [];
            foreach ($row as $v) {
                $cells[] = sprintf($tc, $v);
            }
            $rows[] = sprintf('<w:tr>%s</w:tr>', implode('', $cells));
        }

        return sprintf(
            '<w:p><w:r><w:t>附页</w:t></w:r></w:p><w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/></w:tblPr><w:tblGrid>%s</w:tblGrid>%s</w:tbl>', 
            str_repeat('<w:gridCol w:w="10296"/>', $col),
            implode('', $rows)
        );
    }
/*}}}*/
}
