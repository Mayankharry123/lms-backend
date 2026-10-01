<?php

namespace App\Support;

use DateTimeInterface;

class DateTimeFormatter
{
    private const TIMESTAMP_FORMAT = 'Y-m-d H:i:s A';

    public static function format(?DateTimeInterface $dateTime): ?string
    {
        return $dateTime?->format(self::TIMESTAMP_FORMAT);
    }
}