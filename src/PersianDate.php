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
            return static::fromCarbon(now());
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

    public function getMonth(): int {
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

    public function getHour(): int {
        return $this->hour;
    }

    public function getMinute(): int {
        return $this->minute;
    }

    public function getSecond(): int {
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
	 * @return PersianDate The new PersianDate object after adding the weeks.
	 */
	public function addWeeks(int $days = 1): PersianDate {
		return static::fromCarbon($this->toCarbon()->addWeeks($days));
	}

	/**
	 * Subtracts a specified number of weeks from the current PersianDate object.
	 *
	 * @param int $days The number of weeks to subtract. Default is 1.
	 * @return PersianDate The new PersianDate object after subtracting the weeks.
	 */
	public function subWeek(int $days = 1): PersianDate {
		return static::fromCarbon($this->toCarbon()->subWeek($days));
	}

	/**
	 * Subtracts a specified number of weeks from the current PersianDate object.
	 *
	 * @param int $days The number of weeks to subtract. Default is 1.
	 * @return PersianDate The new PersianDate object after subtracting the weeks.
	 */
	public function subWeeks(int $days = 1): PersianDate {
		return static::fromCarbon($this->toCarbon()->subWeeks($days));
	}

	/**
	 * Get a Carbon instance with PersianDate date and time as gregorian.
	 *
	 * @return Carbon
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

    public function subHour(): PersianDate {
        return $this->subHours(1);
    }

    public function addMinutes(int $minutes = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->addMinutes($minutes));
    }

    public function addMinute(): PersianDate {
        return $this->addMinutes(1);
    }

    public function subMinutes(int $minutes = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->subMinutes($minutes));
    }

    public function subMinute(): PersianDate {
        return $this->subMinutes(1);
    }

    public function addSeconds(int $secs = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->addSeconds($secs));
    }

    public function addSecond(): PersianDate {
        return $this->addSeconds(1);
    }

    public function subSeconds(int $secs = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->subSeconds($secs));
    }

    public function subSecond(): PersianDate {
        return $this->subSeconds(1);
    }

    public function equalsTo(Carbon|PersianDate $dateTime): bool {
        return $this->equalTo($dateTime);
    }

    public function equalTo(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) {
            $dateTime = $dateTime->toCarbon();
        }

        return $this->toCarbon()->equalTo($dateTime);
    }

    public function greaterThan(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) {
            $dateTime = $dateTime->toCarbon();
        }

        return $this->toCarbon()->greaterThan($dateTime);
    }

    public function isAfter(Carbon|PersianDate $dateTime): bool {
        return $this->greaterThan($dateTime);
    }

    public function lessThan(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) {
            $dateTime = $dateTime->toCarbon();
        }

        return $this->toCarbon()->lessThan($dateTime);
    }

    public function isBefore(Carbon|PersianDate $dateTime): bool {
        return $this->lessThan($dateTime);
    }

    public function greaterThanOrEqualsTo(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) {
            $dateTime = $dateTime->toCarbon();
        }

        return $this->toCarbon()->greaterThanOrEqualTo($dateTime);
    }

    public function isAfterOrEqualsTo(Carbon|PersianDate $dateTime): bool {
        return $this->greaterThanOrEqualsTo($dateTime);
    }

    public function lessThanOrEqualsTo(Carbon|PersianDate $dateTime): bool {
        if ($dateTime instanceof PersianDate) {
            $dateTime = $dateTime->toCarbon();
        }

        return $this->toCarbon()->lessThanOrEqualTo($dateTime);
    }

    public function isBeforenOrEqualsTo(Carbon|PersianDate $dateTime): bool {
        return $this->lessThanOrEqualsTo($dateTime);
    }

    public function isBetween(Carbon|PersianDate $start, Carbon|PersianDate $end, bool $equal = true): bool {
        if ($start instanceof PersianDate) {
            $start = $start->toCarbon();
        }
        if ($end instanceof PersianDate) {
            $end = $end->toCarbon();
        }

        return $this->toCarbon()->isBetween($start, $end, $equal);
    }

    public function isWeekend(): bool {
        return $this->isSaturday() || $this->isFriday();
    }

    public function isStartOfWeek(): bool {
        return $this->isSaturday();
    }

    public function isSaturday(): bool {
        return $this->isDayOfWeek(Carbon::SATURDAY);
    }

    public function isDayOfWeek(int $day): bool {
        Assertion::between($day, 0, 6);

        return $this->toCarbon()->isDayOfWeek($day);
    }

    public function isEndOfWeek(): bool {
        return $this->isFriday();
    }

    public function isFriday(): bool {
        return $this->isDayOfWeek(Carbon::FRIDAY);
    }

    public function isToday(): bool {
        return $this->toCarbon()->isToday();
    }

    public function isTomorrow(): bool {
        return $this->toCarbon()->isTomorrow();
    }

    public function isYesterday(): bool {
        return $this->toCarbon()->isYesterday();
    }

    public function isFuture(): bool {
        return $this->toCarbon()->isFuture();
    }

    public function isPast(): bool {
        return $this->toCarbon()->isPast();
    }

    public function toArray(): array {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'dayOfWeek' => $this->getDayOfWeek(),
            'dayOfYear' => $this->getDayOfYear(),
            'hour' => $this->hour,
            'minute' => $this->minute,
            'second' => $this->second,
            'micro' => $this->toCarbon()->micro,
            'timestamp' => $this->toCarbon()->timestamp,
            'formatted' => $this->toString(),
            'timezone' => $this->timezone,
        ];
    }

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

    public function isSunday(): bool {
        return $this->isDayOfWeek(Carbon::SUNDAY);
    }

    public function isMonday(): bool {
        return $this->isDayOfWeek(Carbon::MONDAY);
    }

    public function isTuesday(): bool {
        return $this->isDayOfWeek(Carbon::TUESDAY);
    }

    public function isWednesday(): bool {
        return $this->isDayOfWeek(Carbon::WEDNESDAY);
    }

    public function isThursday(): bool {
        return $this->isDayOfWeek(Carbon::THURSDAY);
    }

    public function isThisYear(): bool {
        return $this->isBetween($this->startOfYear(), $this->endOfYear());
    }

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

    public function toString(): string {
        return $this->format('Y-m-d H:i:s');
    }

    public function toDateString() {
        return $this->format('Y-m-d');
    }

    public function toDate() {
        return $this->toDateString();
    }

    public function toTimeString() {
        return $this->format('H:i:s');
    }

    public function toTime() {
        return $this->toTimeString();
    }

    public function toDateTimeString() {
        return $this->toString();
    }

    public function toDateTime() {
        return $this->toDateTimeString();
    }

    public function format(string $format): string {
        return CalendarUtils::strftime($format, $this->toCarbon());
    }

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

    public function getTimestamp(): int {
        return $this->toCarbon()->getTimestamp();
    }

    public function getNextWeek(): PersianDate {
        return $this->addDays(7);
    }

    public function addDays(int $days = 1): PersianDate {
        return static::fromCarbon($this->toCarbon()->addDays($days));
    }

    public function addDay(): PersianDate {
        return $this->addDays(1);
    }

    public function subDay(): PersianDate {
        return $this->subDays(1);
    }

    public function getNextMonth(): PersianDate {
        return $this->addMonths(1);
    }

    public function getWeekOfMonth(): int {
        return ceil(($this->getDayOfWeek() + $this->day) / 7);
    }

    public function getWeekOfYear(): int {
        return ceil($this->getDayOfYear() / 7);
    }

    public function startOfDay(): persianDate {
        $this->hour = $this->minute = $this->second = 0;

        return $this;
    }

    public function endOfDay(): persianDate {
        $this->hour = 23;
        $this->minute = $this->second = 59;

        return $this;
    }

    public function startOfWeek(): persianDate {
        return $this->subDays($this->getDayOfWeek())->startOfDay();
    }

    public function endOfWeek(): PersianDate {
        return $this->addDays(6 - $this->getDayOfWeek())->endOfDay();
    }

    public function startOfMonth(): persianDate {
        $this->day = 1;

        return $this;
    }

    public function endOfMonth(): persianDate {
        $this->day = $this->getMonthDays();

        return $this;
    }

    public function startOfYear(): persianDate {
        $this->month = $this->day = 1;
        $this->startOfDay();

        return $this;
    }

    public function endOfYear(): PersianDate {
        $this->month = 12;
        $this->day = $this->getMonthDays();
        $this->endOfDay();

        return $this;
    }
}