<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
* @filename test.php
* @touch date Sun 05 Jul 2020 00:00:00 AM CST
* @author: Fred<fred@kuaixu.ltd>
* @license: http://www.zend.com/license/3_0.txt PHP License 3.0"
* @version 1.0.0
*/

$app->hook('slim.before', function() use($app) {
    if (!isset($app->i18n)) {
        $lang = $app->request->headers->get('Lang');
        $name = 'data'.DIRECTORY_SEPARATOR.'i18n'.DIRECTORY_SEPARATOR;
        if (in_array($lang, ['cn', 'zh_CN'])) {
            $name .= 'cn.php';
        } else {
            $name .= 'en.php';
        }
        if (file_exists($name)) {
            require($name);
            $app->i18n = $i18n; 
        }
    }
});
?>
