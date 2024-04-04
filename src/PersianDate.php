<?php

namespace Penobit\PersianDate;

use Assert\Assertion;
use Carbon\Carbon;

class PersianDate {
    /**
     * @var int
     */
    public $year;

    /**
     * @var int
     */
    public $month;

    /**
     * @var int
     */
    public $day;

    /**
     * @var int
     */
    public $hour;

    /**
     * @var int
     */
    public $minute;

    /**
     * @var int
     */
    public $second;

    /**
     * @var \DateTimeZone
     */
    public $timezone;

    /**
     * PersianDate constructor.
     */
    public function __construct(
        int $year,
        int $month,
        int $day,
        int $hour = 0,
        int $minute = 0,
        int $second = 0,
        \DateTimeZone $timezone = null
    ) {
        Assertion::between($year, 1000, 3000);
        Assertion::between($month, 1, 12);
        Assertion::between($day, 1, 31);

        if (6 < $month) {
            Assertion::between($day, 1, 30);
        }

        if (!CalendarUtils::isLeapPersianDateYear($year) && 12 === $month) {
            Assertion::between($day, 1, 29);
        }
        Assertion::between($hour, 0, 24);
        Assertion::between($minute, 0, 59);
        Assertion::between($second, 0, 59);

        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
        $this->hour = $hour;
        $this->minute = $minute;
        $this->second = $second;
        $this->timezone = $timezone;
    }

    /**
     * Converts the PersianDate object to string.
     *
     * @return string the string representation of the PersianDate object
     */
    public function __toString(): string {
        return $this->toString();
    }

    /**
     * create a new instance from now.
     */
    public static function now(\DateTimeZone $timeZone = null): PersianDate {
        return static::fromCarbon(Carbon::now($timeZone));
    }

    /**
     * create a new instance from carbon instance.
     */
    public static function fromCarbon(Carbon $carbon): PersianDate {
        $jDate = CalendarUtils::toPersianDate($carbon->year, $carbon->month, $carbon->day);

        return new static(
            $jDate[0],
            $jDate[1],
            $jDate[2],
            $carbon->hour,
            $carbon->minute,
            $carbon->second,
            $carbon->getTimezone()
        );
    }

    /**
     * create a new instance from string date and format.
     */
    public static function fromFormat(string $format, string $timestamp, \DateTimeZone $timeZone = null): PersianDate {
        return static::fromCarbon(CalendarUtils::createCarbonFromFormat($format, $timestamp, $timeZone));
    }

    /**
     * create a new instance (auto detect format).
     */
    public static function forge(null|Carbon|\DateTime|string $timestamp, \DateTimeZone $timeZone = null): PersianDate {
        if (empty($timestamp)) {
            return static::fromCarbon(new Carbon());
        }

        $format = CalendarUtils::detectFormat($timestamp);

        if ($timestamp instanceof \DateTime) {
            return static::fromDateTime($timestamp, $timeZone);
        }

        if ($format && strtotime($timestamp) < 0) {
            return static::fromFormat($format, $timestamp, $timeZone);
        }

        if ($timestamp instanceof Carbon) {
            return static::fromCarbon($timestamp);
        }

        return static::fromDateTime($timestamp, $timeZone);
    }

    /**
     * @param \DateTimeInterface|string $dateTime
     */
    public static function fromDateTime($dateTime, \DateTimeZone $timeZone = null): PersianDate {
        $dateTime = is_numeric($dateTime) ? Carbon::createFromTimestamp($dateTime, $timeZone) : new Carbon($dateTime, $timeZone);

        return static::fromCarbon($dateTime);
    }

    /**
     * get persian month's days.
     */
    public function getMonthDays() {
        if ($this->getMonth() <= 6) {
            return 31;
        }

        if ($this->getMonth() < 12 || $this->isLeapYear()) {
            return 30;
        }

        return 29;
    }

    /**
     * Get the month.
     *
     * @return int the month
     */
    public function getMonth(): int {
        // Returns the month of the date.
        return $this->month;
    }

    /**
     * get persian month's name.
     *
     * @return string month's name
     */
    public function getMonthName() {
        $months = [
            'فروردین',
            'اردیبهشت',
            'خرداد',
            'تیر',
            'مرداد',
            'شهریور',
            'مهر',
            'آبان',
            'آذر',
            'دی',
            'بهمن',
            'اسفند',
        ];

        return $months[$this->getMonth() - 1];
    }

    /**
     * returns whether the year is leap or not.
     */
    public function isLeapYear(): bool {
        return CalendarUtils::isLeapPersianDateYear($this->getYear());
    }

    /**
     * @return int
     */
    public function getYear() {
        return $this->year;
    }

    /**
     * subtract months from date.
     */
    public function subMonths(int $months = 1): PersianDate {
        Assertion::greaterOrEqualThan($months, 1);

        $diff = ($this->getMonth() - $months);

        if (1 <= $diff) {
            $day = $this->getDay();
            $targetMonthDays = $this->getDaysOf($diff);
            $targetDay = $day <= $targetMonthDays ? $day : $targetMonthDays;

            return new static(
                $this->getYear(),
                $diff,
                $targetDay,
                $this->getHour(),
                $this->getMinute(),
                $this->getSecond(),
                $this->getTimezone()
            );
        }

        $years = abs((int) ($diff / 12));
        $date = 0 < $years ? $this->subYears($years) : clone $this;
        $diff = 12 - abs($diff % 12) - $date->getMonth();

        return 0 < $diff ? $date->subYears(1)->addMonths($diff) : $date->subYears(1);
    }

    /**
     * subtract month from date.
     */
    public function subMonth(): PersianDate {
        return $this->subMonths(1);
    }

    /**
     * Get the day of the month.
     *
     * @return int the day of the month (1-31)
     */
    public function getDay(): int {
        return $this->day;
    }

    /**
     * get days of a specific month.
     */
    public function getDaysOf(int $monthNumber = 1): int {
        Assertion::between($monthNumber, 1, 12);

        $months = [
            1 => 31,
            2 => 31,
            3 => 31,
            4 => 31,
            5 => 31,
            6 => 31,
            7 => 30,
            8 => 30,
            9 => 30,
            10 => 30,
            11 => 30,
            12 => $this->isLeapYear() ? 30 : 29,
        ];

        return $months[$monthNumber];
    }

    /**
     * Get the hour of the date.
     *
     * @return int the hour
     */
    public function getHour(): int {
        // Returns the hour of the date.
        return $this->hour;
    }

    /**
     * Get the minute of the date.
     *
     * @return int the minute
     */
    public function getMinute(): int {
        // Returns the minute of the date.
        return $this->minute;
    }

    /**
     * Get the second of the date.
     *
     * @return int the second
     */
    public function getSecond(): int {
        // Returns the second of the date.
        return $this->second;
    }

    /**
     * @return null|\DateTimeZone
     */
    public function getTimezone() {
        return $this->timezone;
    }

    /**
     * subtract years to date.
     */
    public function subYears(int $years = 1): PersianDate {
        Assertion::greaterOrEqualThan($years, 1);

        return new static(
            $this->getYear() - $years,
            $this->getMonth(),
            $this->getDay(),
            $this->getHour(),
            $this->getMinute(),
            $this->getSecond(),
            $this->getTimezone()
        );
    }

    /**
     * subtract year to date.
     *
     * */
    public function subYear(): PersianDate {
        return $this->subYears(1);
    }

    /**
     * add months to date.
     */
    public function addMonths(int $months = 1): PersianDate {
        Assertion::greaterOrEqualThan($months, 1);

        $years = (int) ($months / 12);
        $months = (int) ($months % 12);
        $date = 0 < $years ? $this->addYears($years) : clone $this;

        while (0 < $months) {
            $nextMonth = ($date->getMonth() + 1) % 12;
            $nextMonthDays = $date->getDaysOf(0 === $nextMonth ? 12 : $nextMonth);
            $nextMonthDay = $date->getDay() <= $nextMonthDays ? $date->getDay() : $nextMonthDays;

            $days = ($date->getMonthDays() - $date->getDay()) + $nextMonthDay;

            $date = $date->addDays($days);
            --$months;
        }

        return $date;
    }

    /**
     * add month to date.
     */
    public function addMonth(): PersianDate {
        return $this->addMonths(1);
    }

    /**
     * add years to date.
     */
    public function addYears(int $years = 1): PersianDate {
        Assertion::greaterOrEqualThan($years, 1);

        $year = $this->getYear() + $years;
        if (false === CalendarUtils::isLeapPersianDateYear($year) && $this->getMonth() === 12 && $this->getDay() === $this->getDaysOf(12)) {
            $day = 29;
        } else {
            $day = $this->getDay();
        }

        return new static(
            $year,
            $this->getMonth(),
            $day,
            $this->getHour(),
            $this->getMinute(),
            $this->getSecond(),
            $this->getTimezone()
        );
    }

    /**
     * add year to date.
     */
    public function addYear(): PersianDate {
        return $this->addYears(1);
    }

    /**
     * add days to date.
     */
    public function subDays(int $days = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->subDays($days));
    }

    /**
     * add day to date.
     */
    public function addWeek(int $days = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->addWeek($days));
    }

    /**
     * Adds a specified number of weeks to the current PersianDate object.
     *
     * @param int $days The number of weeks to add. Default is 1.
     *
     * @return PersianDate the new PersianDate object after adding the weeks
     */
    public function addWeeks(int $days = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->addWeeks($days));
    }

    /**
     * Subtracts a specified number of weeks from the current PersianDate object.
     *
     * @param int $days The number of weeks to subtract. Default is 1.
     *
     * @return PersianDate the new PersianDate object after subtracting the weeks
     */
    public function subWeek(int $days = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->subWeek($days));
    }

    /**
     * Subtracts a specified number of weeks from the current PersianDate object.
     *
     * @param int $days The number of weeks to subtract. Default is 1.
     *
     * @return PersianDate the new PersianDate object after subtracting the weeks
     */
    public function subWeeks(int $days = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->subWeeks($days));
    }

    /**
     * Get a Carbon instance with PersianDate date and time as gregorian.
     */
    public function toCarbon(): Carbon {
        $gDate = CalendarUtils::toGregorian($this->getYear(), $this->getMonth(), $this->getDay());
        $carbon = Carbon::createFromDate($gDate[0], $gDate[1], $gDate[2], $this->getTimezone());

        $carbon->setTime($this->getHour(), $this->getMinute(), $this->getSecond());

        return $carbon;
    }

    /**
     * Adds a specified number of days to the current PersianDate object.
     */
    public function addHours(int $hours = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->addHours($hours));
    }

    /**
     * Adds a specified number of days to the current PersianDate object.
     */
    public function addHour(): PersianDate {
        return $this->addHours(1);
    }

    /**
     * Subtracts a specified number of hours from the current PersianDate object.
     */
    public function subHours(int $hours = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->subHours($hours));
    }

    /**
     * Subtracts 1 hour from the current PersianDate object.
     *
     * @return PersianDate the new PersianDate object after subtracting 1 hour
     */
    public function subHour(): PersianDate {
        return $this->subHours(1);
    }

    /**
     * Adds a specified number of minutes to the current PersianDate object.
     */
    public function addMinutes(int $minutes = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->addMinutes($minutes));
    }

    /**
     * Adds 1 minute to the current PersianDate object.
     *
     * @return PersianDate the new PersianDate object after adding 1 minute
     */
    public function addMinute(): PersianDate {
        return $this->addMinutes(1);
    }

    /**
     * Subtracts a specified number of minutes from the current PersianDate object.
     */
    public function subMinutes(int $minutes = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->subMinutes($minutes));
    }

    /**
     * Subtracts 1 minute from the current PersianDate object.
     *
     * @return PersianDate the new PersianDate object after subtracting 1 minute
     */
    public function subMinute(): PersianDate {
        return $this->subMinutes(1);
    }

    /**
     * Adds a specified number of seconds to the current PersianDate object.
     */
    public function addSeconds(int $secs = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->addSeconds($secs));
    }

    /**
     * Adds 1 second to the current PersianDate object.
     *
     * @return PersianDate the new PersianDate object after adding 1 second
     */
    public function addSecond(): PersianDate {
        return $this->addSeconds(1);
    }

    /**
     * Subtracts a specified number of seconds from the current PersianDate object.
     */
    public function subSeconds(int $secs = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->subSeconds($secs));
    }

    /**
     * Subtracts 1 second from the current PersianDate object.
     *
     * @return PersianDate the new PersianDate object after subtracting 1 second
     */
    public function subSecond(): PersianDate {
        return $this->subSeconds(1);
    }

    /**
     * Checks if the current PersianDate object is equal to the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if both dates are equal, false otherwise
     */
    public function equalsTo(Carbon|PersianDate $dateTime): bool {
        return $this->equalTo($dateTime);
    }

    /**
     * Checks if the current PersianDate object is equal to the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if both dates are equal, false otherwise
     */
    public function equalTo(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) {
            $dateTime = $dateTime->toCarbon();
        }

        // Check if the current PersianDate object is equal to the given one.
        return $this->toCarbon()->equalTo($dateTime);
    }

    /**
     * Checks if the current PersianDate object is greater than the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if the current PersianDate object is greater, false otherwise
     */
    public function greaterThan(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) {
            $dateTime = $dateTime->toCarbon();
        }

        // Check if the current PersianDate object is greater than the given one.
        return $this->toCarbon()->greaterThan($dateTime);
    }

    /**
     * Checks if the current PersianDate object is after the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if the current PersianDate object is after, false otherwise
     */
    public function isAfter(Carbon|PersianDate $dateTime): bool {
        return $this->greaterThan($dateTime);
    }

    /**
     * Checks if the current PersianDate object is less than the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if the current PersianDate object is less, false otherwise
     */
    public function lessThan(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) {
            $dateTime = $dateTime->toCarbon();
        }

        // Check if the current PersianDate object is less than the given one.
        return $this->toCarbon()->lessThan($dateTime);
    }

    /**
     * Checks if the current PersianDate object is before the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if the current PersianDate object is before, false otherwise
     */
    public function isBefore(Carbon|PersianDate $dateTime): bool {
        return $this->lessThan($dateTime);
    }

    /**
     * Checks if the current PersianDate object is greater than or equal to the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if the current PersianDate object is greater than or equal to, false otherwise
     */
    public function isAfterOrEqualsTo(Carbon|PersianDate $dateTime): bool {
        return $this->greaterThanOrEqualsTo($dateTime);
    }

    /**
     * Checks if the current PersianDate object is greater than or equal to the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if the current PersianDate object is greater than or equal to, false otherwise
     */
    public function greaterThanOrEqualsTo(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) {
            $dateTime = $dateTime->toCarbon();
        }

        // Check if the current PersianDate object is greater than or equal to the given one.
        return $this->toCarbon()->greaterThanOrEqualTo($dateTime);
    }

    /**
     * Checks if the current PersianDate object is less than or equal to the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if the current PersianDate object is less than or equal to, false otherwise
     */
    public function lessThanOrEqualsTo(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) { // Convert PersianDate to Carbon
            $dateTime = $dateTime->toCarbon();
        }

        return $this->toCarbon()->lessThanOrEqualTo($dateTime); // Compare with Carbon object
    }

    /**
     * Checks if the current PersianDate object is before or equals to the given one.
     *
     * @param Carbon|PersianDate $dateTime the date/time to compare with
     *
     * @return bool true if the current PersianDate object is before or equals to, false otherwise
     */
    public function isBeforenOrEqualsTo(Carbon|PersianDate $dateTime): bool {
        return $this->lessThanOrEqualsTo($dateTime); // Use the lessThanOrEqualsTo method
    }

    /**
     * Checks if the current PersianDate object is between the given start and end date/time.
     *
     * @param Carbon|PersianDate $start the start date/time to compare with
     * @param Carbon|PersianDate $end the end date/time to compare with
     * @param bool $equal whether to include the start and end date/time in the comparison or not
     *
     * @return bool true if the current PersianDate object is between the start and end date/time, false otherwise
     */
    public function isBetween(Carbon|PersianDate $start, Carbon|PersianDate $end, bool $equal = true): bool {
        if ($start instanceof PersianDate) { // Convert PersianDate to Carbon
            $start = $start->toCarbon();
        }
        if ($end instanceof PersianDate) { // Convert PersianDate to Carbon
            $end = $end->toCarbon();
        }

        return $this->toCarbon()->isBetween($start, $end, $equal); // Compare with Carbon object
    }

    /**
     * Get the day of the week.
     *
     * @return int The day of the week.
     *             0 for Saturday, 1 for Sunday, 2 for Monday, 3 for Tuesday, 4 for Wednesday, 5 for Thursday, 6 for Friday.
     */
    public function getDayOfWeek(): int {
        if ($this->isSaturday()) {
            return 0;
        }

        if ($this->isSunday()) {
            return 1;
        }

        if ($this->isMonday()) {
            return 2;
        }

        if ($this->isTuesday()) {
            return 3;
        }

        if ($this->isWednesday()) {
            return 4;
        }

        if ($this->isThursday()) {
            return 5;
        }

        return 6;
    }

    /**
     * Check if it's a specific day of week.
     *
     * @param int $dayOfWeek the day of week (0 for Saturday, 1 for Sunday, 2 for Monday, 3 for Tuesday, 4 for Wednesday, 5 for Thursday, 6 for Friday)
     *
     * @return bool true if it's a specific day of week, false otherwise
     */
    public function isDayOfWeek(int $dayOfWeek): bool {
        return $this->getDayOfWeek() === $dayOfWeek;
    }

    /**
     * Check if it's Saturday.
     *
     * @return bool true if it's Saturday, false otherwise
     */
    public function isSaturday(): bool {
        return $this->isDayOfWeek(Carbon::SATURDAY);
    }

    /**
     * Check if it's Sunday.
     *
     * @return bool true if it's Sunday, false otherwise
     */
    public function isSunday(): bool {
        return $this->isDayOfWeek(Carbon::SUNDAY);
    }

    /**
     * Check if it's Monday.
     *
     * @return bool true if it's Monday, false otherwise
     */
    public function isMonday(): bool {
        return $this->isDayOfWeek(Carbon::MONDAY);
    }

    /**
     * Check if it's Tuesday.
     *
     * @return bool true if it's Tuesday, false otherwise
     */
    public function isTuesday(): bool {
        return $this->isDayOfWeek(Carbon::TUESDAY);
    }

    /**
     * Check if it's Wednesday.
     *
     * @return bool true if it's Wednesday, false otherwise
     */
    public function isWednesday(): bool {
        return $this->isDayOfWeek(Carbon::WEDNESDAY);
    }

    /**
     * Check if it's Thursday.
     *
     * @return bool true if it's Thursday, false otherwise
     */
    public function isThursday(): bool {
        return $this->isDayOfWeek(Carbon::THURSDAY);
    }

    /**
     * Check if it's this year.
     *
     * @return bool true if it's this year, false otherwise
     */
    public function isThisYear(): bool {
        return $this->isBetween($this->startOfYear(), $this->endOfYear());
    }

    /**
     * Get the day of the year.
     *
     * @return int the day of the year
     */
    public function getDayOfYear(): int {
        $dayOfYear = 0;
        for ($m = 1; $this->getMonth() > $m; ++$m) {
            if (6 >= $m) {
                $dayOfYear += 31;

                continue;
            }

            if (12 > $m) {
                $dayOfYear += 30;

                continue;
            }
        }

        return $dayOfYear + $this->getDay();
    }

    /**
     * Convert the PersianDate object to a string.
     *
     * @return string the string representation of the PersianDate object in the format 'Y-m-d H:i:s'
     */
    public function toString(): string {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * Convert the PersianDate object to a string representation of a date.
     *
     * @return string the string representation of the PersianDate object in the format 'Y-m-d'
     */
    public function toDateString(): string {
        return $this->format('Y-m-d');
    }

    /**
     * An alias for toDateString().
     *
     * @return string the string representation of the PersianDate object in the format 'Y-m-d'
     */
    public function toDate(): string {
        return $this->toDateString();
    }

    /**
     * Convert the PersianDate object to a string representation of a time.
     *
     * @return string the string representation of the PersianDate object in the format 'H:i:s'
     */
    public function toTimeString(): string {
        return $this->format('H:i:s');
    }

    /**
     * An alias for toTimeString().
     *
     * @return string the string representation of the PersianDate object in the format 'H:i:s'
     */
    public function toTime(): string {
        return $this->toTimeString();
    }

    /**
     * Convert the PersianDate object to a string representation of a date and time.
     *
     * @return string the string representation of the PersianDate object in the format 'Y-m-d H:i:s'
     */
    public function toDateTimeString(): string {
        return $this->toString();
    }

    /**
     * An alias for toDateTimeString().
     *
     * @return string the string representation of the PersianDate object in the format 'Y-m-d H:i:s'
     */
    public function toDateTime(): string {
        return $this->toDateTimeString();
    }

    /**
     * Format the PersianDate object using strftime formatting.
     *
     * @param string $format the format string to use
     *
     * @return string the formatted string
     */
    public function format(string $format): string {
        return CalendarUtils::strftime($format, $this->toCarbon());
    }

    /**
     * Get a human-readable string representing how much time has passed since
     * this PersianDate object.
     *
     * @return string the string representation of the difference
     */
    public function ago(): string {
        $future = false;
        $now = time();
        $time = $this->getTimestamp();

        // catch error
        if (!$time) {
            return false;
        }

        // build period and length arrays
        $periods = ['ثانیه', 'دقیقه', 'ساعت', 'روز', 'هفته', 'ماه', 'سال', 'قرن'];
        $lengths = [60, 60, 24, 7, 4.35, 12, 10];

        // get difference
        $difference = $now - $time;

        // set descriptor
        if (0 > $difference) {
            $difference = abs($difference); // absolute value
            $future = true;
        }

        // do math
        for ($j = 0; $difference >= $lengths[$j] and count($lengths) - 1 > $j; ++$j) {
            $difference /= $lengths[$j];
        }

        // round difference
        $difference = intval(round($difference));

        // difference unit
        $unit = $periods[$j];

        // suffix
        $suffix = $future ? 'آینده' : 'پیش';

        if ($periods[3] == $unit) {
            if (1 === $difference) {
                return $future ? 'فردا' : 'دیروز';
            }
        } elseif ($periods[0] == $unit && 30 > $difference) {
            return 'لحظاتی پیش';
        }

        // return
        return trim(sprintf('%s %s %s', number_format($difference), $unit, $suffix));
    }

    /**
     * Get unix timestamp.
     */
    public function unix(): int {
        return $this->toCarbon()->unix();
    }

    /**
     * unix() method alias.
     */
    public function getTimestamp(): int {
        return $this->toCarbon()->getTimestamp();
    }

    /**
     * Get the next week PersianDate object.
     *
     * @return PersianDate the next week PersianDate object
     */
    public function getNextWeek(): PersianDate {
        return $this->addDays(7);
    }

    /**
     * Add days to the PersianDate object and return a new instance.
     *
     * @param int $days The number of days to add. Default is 1.
     *
     * @return PersianDate the new PersianDate object
     */
    public function addDays(int $days = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->addDays($days));
    }

    /**
     * Add 1 day to the PersianDate object and return a new instance.
     *
     * @return PersianDate the new PersianDate object
     */
    public function addDay(): PersianDate {
        return $this->addDays(1);
    }

    /**
     * Subtract 1 day from the PersianDate object and return a new instance.
     *
     * @return PersianDate the new PersianDate object
     */
    public function subDay(): PersianDate {
        return $this->subDays(1);
    }

    /**
     * Get the next month PersianDate object.
     *
     * @return PersianDate the next month PersianDate object
     */
    public function getNextMonth(): PersianDate {
        return $this->addMonths(1);
    }

    /**
     * Get the week number of the month.
     *
     * @return int the week number of the month
     */
    public function getWeekOfMonth(): int {
        return ceil(($this->getDayOfWeek() + $this->day) / 7);
    }

    /**
     * Get the week number of the year.
     *
     * @return int the week number of the year
     */
    public function getWeekOfYear(): int {
        return ceil($this->getDayOfYear() / 7);
    }

    /**
     * Set the PersianDate object to the start of the day.
     *
     * @return PersianDate the modified PersianDate object
     */
    public function startOfDay(): PersianDate {
        $this->hour = $this->minute = $this->second = 0;

        return $this;
    }

    /**
     * Set the PersianDate object to the end of the day.
     *
     * @return PersianDate the modified PersianDate object
     */
    public function endOfDay(): PersianDate {
        $this->hour = 23;
        $this->minute = $this->second = 59;

        return $this;
    }

    /**
     * Set the PersianDate object to the start of the week.
     *
     * @return PersianDate the modified PersianDate object
     */
    public function startOfWeek(): PersianDate {
        return $this->subDays($this->getDayOfWeek())->startOfDay();
    }

    /**
     * Set the PersianDate object to the end of the week.
     *
     * @return PersianDate the modified PersianDate object
     */
    public function endOfWeek(): PersianDate {
        return $this->addDays(6 - $this->getDayOfWeek())->endOfDay();
    }

    /**
     * Set the PersianDate object to the start of the month.
     *
     * @return PersianDate the modified PersianDate object
     */
    public function startOfMonth(): PersianDate {
        $this->day = 1;

        return $this;
    }

    /**
     * Set the PersianDate object to the end of the month.
     *
     * @return PersianDate the modified PersianDate object
     */
    public function endOfMonth(): PersianDate {
        $this->day = $this->getMonthDays();

        return $this;
    }

    /**
     * Set the PersianDate object to the start of the year.
     *
     * @return PersianDate the modified PersianDate object
     */
    public function startOfYear(): PersianDate {
        $this->month = $this->day = 1;
        $this->startOfDay();

        return $this;
    }

    /**
     * Set the PersianDate object to the end of the year.
     *
     * @return PersianDate the modified PersianDate object
     */
    public function endOfYear(): PersianDate {
        $this->month = 12;
        $this->day = $this->getMonthDays();
        $this->endOfDay();

        return $this;
    }

    /**
     * Get the difference in days between the PersianDate objects.
     *
     * @param mixed $date The date to compare against. Default is the current PersianDate object.
     * @param bool $abs whether to return the absolute value of the difference
     *
     * @return int the difference in days
     */
    public function diffInDays($date = null, $abs = true): int {
        if (null === $date) {
            $date = static::now();
        }

        if ($date instanceof PersianDate) {
            $date = $date->toCarbon();
        }

        return $this->toCarbon()->diffInDays($date, $abs);
    }
}
