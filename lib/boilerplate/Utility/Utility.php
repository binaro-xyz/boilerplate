<?php

namespace boilerplate\Utility;

class Utility
{
    public static function varDumpPre($expression) {
        echo '<pre>';
        var_dump($expression);
        echo '</pre>';
    }

    // see https://secure.php.net/manual/en/function.var-dump.php#51119
    public static function varDumpReturn($expression) {
        ob_start();
        var_dump($expression);
        $return = ob_get_contents();
        ob_end_clean();
        return $return;
    }

    public static function printRPre($expression) {
        echo '<pre>' . print_r($expression, true) . '</pre>';
    }

    // use for navigation menus: determine if a certain page is active and add a class accordingly
    public static function activePageClass(string $filename) : string {
        if(strpos(basename($_SERVER['PHP_SELF']), $filename) !== false) {
            return 'class="active"';
        }
        return '';
    }
}
