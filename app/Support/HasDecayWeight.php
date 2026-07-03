<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Trait dùng chung cho công thức suy giảm trọng số theo thời gian
 * (exponential decay), dùng ở cả RecommendationService (rule-based cải
 * tiến) lẫn ItemBasedRecommendationService (Item-based CF) để đảm bảo
 * 2 phương pháp dùng cùng 1 định nghĩa "gần đây quan trọng hơn".
 */
trait HasDecayWeight
{
    /**
     * Half-life 30 ngày: 30 ngày trước -> còn 50% trọng số,
     * 60 ngày trước -> còn 25%, v.v.
     */
    protected function decayWeight(Carbon $timestamp, int $halfLifeDays = 30): float
    {
        $daysAgo = max(0, $timestamp->diffInDays(now()));
        return 0.5 ** ($daysAgo / $halfLifeDays);
    }
}