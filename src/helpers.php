<?php

if (! function_exists('persianDate')) {

    /**
     * @param string $str
     * @return \Penobit\PersianDate\PersianDate
     */
    function persianDate($str = null)
    {
        return \Penobit\PersianDate\PersianDate::forge($str);
    }
}