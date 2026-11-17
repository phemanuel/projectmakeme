<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class MakeMeDeliverySchedule
{
    public const TIMEZONE = 'Africa/Lagos';

    public static function deliveryDate(
        CarbonInterface $receivedAt
    ): string {
        $local = Carbon::parse($receivedAt->toIso8601String())
            ->timezone(self::TIMEZONE);

        $cutoff = $local->copy()->setTime(10, 0, 0);

        if ($local->greaterThan($cutoff)) {
            $local->addDay();
        }

        return $local->toDateString();
    }
}