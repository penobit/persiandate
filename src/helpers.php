<?php
use \Penobit\PersianDate\PersianDate;

if (! function_exists('persianDate')) {

    /**
     * @param string $dateTime
     * @return \Penobit\PersianDate\PersianDate
     */
    function persianDate($dateTime = null) {
        return PersianDate::forge($dateTime);
    }
}
