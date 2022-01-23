<?php

namespace Penobit\PersianDate\Tests;

use Penobit\PersianDate\PersianDate;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function test_jdate_function()
    {
        $this->assertTrue(function_exists('jdate'));

        $jdate = jdate('now');
        $this->assertTrue($jdate instanceof PersianDate);
    }
}
