<?php

namespace Penobit\PersianDate;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class Cast implements CastsAttributes {
    public $format = 'Y-m-d H:i:s';

    public function __construct(?string $format = null) {
        if(!empty($format)) {
            $this->format = $format;
        }
    }

    /**
     * Cast the given value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function get($model, string $key, $value, array $attributes) {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \Penobit\PersianDate\PersianDate) {
            return $value->toCarbon();
        }

        $date = persianDate(new Carbon($value));

        if($this->format){

            if($this->format === 'auto'){
                if($date->isToday()){
                    return 'امروز';
                }elseif($date->isTomorrow()){
                    return 'فردا';
                }elseif($date->isYesterday()){
                    return 'دیروز';
                }elseif($date->isBetween(
                    persianDate()->startOfWeek(),
                    persianDate()->endOfWeek()
                )){
                    return $date->format('l');
                }elseif($date->isThisYear()){
                    return $date->format('l d F');
                }

                return $date->format('l d F Y');
            }

            return $date->format($this->format);
        }

        return $date;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function set($model, string $key, $value, array $attributes) {
        if (empty($value)) {
            return null;
        }

        $carbon = persianDate($value)->toCarbon();

        return !empty($this->format) ? $carbon->format($this->format) : $value->toDateTimeString();
    }
}
