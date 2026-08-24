<?php

namespace App\Services;

use App\Models\HrSetting;

class HrDisciplineCalculator
{
    public function __construct(protected ?HrSetting $settings = null)
    {
        $this->settings ??= HrSetting::current();
    }

    public function dailyRate(int $monthlySalary): int
    {
        return (int) round($monthlySalary / max(1, $this->settings->planned_working_days_per_month));
    }

    public function hourlyRate(int $monthlySalary): float
    {
        return $this->dailyRate($monthlySalary) / max(1, $this->settings->planned_working_hours_per_day);
    }

    public function absenceDeduction(int $monthlySalary, bool $justified): int
    {
        return $justified ? 0 : $this->dailyRate($monthlySalary);
    }

    public function lateDeduction(int $monthlySalary, int $lateMinutes): int
    {
        return (int) round($this->hourlyRate($monthlySalary) * ($lateMinutes / 60));
    }

    public function abandonmentDeduction(int $monthlySalary, int $remainingMinutes): int
    {
        return (int) round($this->hourlyRate($monthlySalary) * ($remainingMinutes / 60));
    }
}
