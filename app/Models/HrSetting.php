<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrSetting extends Model
{
    protected $fillable = [
        'planned_working_days_per_month',
        'planned_working_hours_per_day',
        'simple_late_threshold_minutes',
        'sanctionable_late_threshold_minutes',
    ];

    protected function casts(): array
    {
        return [
            'planned_working_days_per_month' => 'integer',
            'planned_working_hours_per_day' => 'integer',
            'simple_late_threshold_minutes' => 'integer',
            'sanctionable_late_threshold_minutes' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'planned_working_days_per_month' => 26,
            'planned_working_hours_per_day' => 8,
            'simple_late_threshold_minutes' => 15,
            'sanctionable_late_threshold_minutes' => 30,
        ]);
    }
}
