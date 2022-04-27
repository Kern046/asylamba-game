<?php

namespace App\Classes\Library;

class Bug
{
    // LIBRAIRIE

    // AFFICHE $DATA
    public static function pre($data)
    {
        $nbr = count($data);
        if (0 == $nbr) {
            echo 'vide';
        } else {
            echo '<pre>';
            var_export($data);
            echo '</pre>';
        }
    }

    // DONNE LE TEMPS ACTUEL EN MICROSECONDE

    private static $benchTime = 0;

    public static function benchTime()
    {
        list($usec, $sec) = explode(' ', microtime());
        $ret = ((float) $usec + (float) $sec);

        if (0 == self::$benchTime) {
            self::$benchTime = $ret;
        } else {
            self::$benchTime = $ret - self::$benchTime;
        }
    }

    public static function benchReturn($mode = '')
    {
        $ret = self::$benchTime;
        self::$benchTime = 0;

        if ('ms' == $mode) {
            return floor($ret * 1000).'ms';
        } elseif ('se' == $mode) {
            return $ret.'s';
        }
    }

    public static function writeLog($target, $content)
    {
        file_put_contents($target, $content."\n\r", FILE_APPEND);
    }
}
