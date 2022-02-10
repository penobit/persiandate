<?php
use \Penobit\PersianDate\PersianDate;

if (! function_exists('persianDate')) {

    /**
     * @param string $str
     * @return \Penobit\PersianDate\PersianDate
     */
    function persianDate($str = null) {
        return PersianDate::forge($str);
    }
}
