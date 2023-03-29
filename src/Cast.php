<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;

class Cast implements CastsAttributes {
    public function __construct(?string $format = null) {
        $this->format = $format;
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

        return persianDate($value)->toCarbon();
    }
}
