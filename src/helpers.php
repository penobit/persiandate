<?php

if (! function_exists('jdate')) {

    /**
     * @param string $str
     * @return \Penobit\PersianDate\PersianDate
     */
    function jdate($str = null)
    {
        return \Penobit\PersianDate\PersianDate::forge($str);
    }
}