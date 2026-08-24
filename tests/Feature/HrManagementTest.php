<?php

namespace Tests\Feature;

use App\Livewire\Hr\Attendance;
use App\Livewire\Hr\DisciplineHistory;
use App\Livewire\Hr\MonthlyReport;
use App\Models\DisciplinarySanction;
use App\Models\HrSetting;
use App\Models\StaffAttendance;
use App\Models\StaffMember;
use App\Models\User;
use App\Services\HrDisciplineCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HrManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_cannot_access_hr_pages(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/hr/presence')->assertForbidden();
        $this->actingAs($cashier)->get('/hr/discipline')->assertForbidden();
        $this->actingAs($cashier)->get('/hr/rapport')->assertForbidden();
    }

    public function test_manager_can_record_attendance_and_late_minutes_are_calculated(): void
    {
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->cashier()->create(['monthly_salary' => 260000]);

        Livewire::actingAs($manager)
            ->test(Attendance::class)
            ->set('workDate', '2026-08-24')
            ->set('employeeKey', "user:{$employee->id}")
            ->set('scheduledStart', '08:00')
            ->set('actualStart', '08:25')
            ->set('scheduledEnd', '18:00')
            ->set('actualEnd', '18:00')
            ->set('status', 'present')
            ->call('saveAttendance')
            ->assertSet('notice', 'Présence enregistrée.');

        $attendance = StaffAttendance::firstOrFail();

        $this->assertSame('user', $attendance->employee_type);
        $this->assertSame($employee->id, $attendance->employee_id);
        $this->assertSame('2026-08-24', $attendance->work_date->toDateString());
        $this->assertSame('late', $attendance->status);
        $this->assertSame(25, $attendance->late_minutes);
        $this->assertSame($manager->id, $attendance->recorded_by);
    }

    public function test_absence_and_late_deductions_are_calculated_from_settings(): void
    {
        $settings = HrSetting::create([
            'planned_working_days_per_month' => 26,
            'planned_working_hours_per_day' => 8,
            'simple_late_threshold_minutes' => 15,
            'sanctionable_late_threshold_minutes' => 30,
        ]);

        $calculator = new HrDisciplineCalculator($settings);

        $this->assertSame(10000, $calculator->absenceDeduction(260000, false));
        $this->assertSame(0, $calculator->absenceDeduction(260000, true));
        $this->assertSame(625, $calculator->lateDeduction(260000, 30));
    }

    public function test_manager_can_create_and_validate_a_sanction(): void
    {
        $manager = User::factory()->manager()->create();
        $staff = StaffMember::create([
            'name' => 'Serveuse Test',
            'job_title' => 'Serveuse',
            'monthly_salary' => 130000,
            'is_active' => true,
        ]);

        Livewire::actingAs($manager)
            ->test(DisciplineHistory::class)
            ->set('employeeKey', "staff:{$staff->id}")
            ->set('faultType', 'absence')
            ->set('description', 'Absence non justifiée')
            ->set('faultDate', '2026-08-24')
            ->set('sanctionType', 'salary_deduction')
            ->set('deductionAmount', '5000')
            ->call('createSanction')
            ->assertSet('notice', 'Sanction créée en brouillon.');

        $sanction = DisciplinarySanction::firstOrFail();
        $this->assertSame('draft', $sanction->status);

        Livewire::actingAs($manager)
            ->test(DisciplineHistory::class)
            ->call('validateSanction', $sanction->id)
            ->assertSet('notice', 'Sanction validée.');

        $this->assertDatabaseHas('disciplinary_sanctions', [
            'id' => $sanction->id,
            'status' => 'validated',
            'responsible_id' => $manager->id,
            'deduction_amount' => 5000,
        ]);
    }

    public function test_monthly_report_uses_validated_deductions_for_net_salary(): void
    {
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->cashier()->create([
            'name' => 'Caissier Rapport',
            'monthly_salary' => 260000,
        ]);

        StaffAttendance::create([
            'employee_type' => 'user',
            'employee_id' => $employee->id,
            'work_date' => '2026-08-24',
            'scheduled_start' => '08:00',
            'actual_start' => '08:30',
            'scheduled_end' => '18:00',
            'actual_end' => '18:00',
            'status' => 'late',
            'late_minutes' => 30,
            'recorded_by' => $manager->id,
        ]);

        DisciplinarySanction::create([
            'employee_type' => 'user',
            'employee_id' => $employee->id,
            'fault_type' => 'late',
            'description' => 'Retard sanctionné',
            'fault_date' => '2026-08-24',
            'sanction_type' => 'salary_deduction',
            'deduction_amount' => 10000,
            'responsible_id' => $manager->id,
            'status' => 'validated',
            'validated_at' => now(),
        ]);

        DisciplinarySanction::create([
            'employee_type' => 'user',
            'employee_id' => $employee->id,
            'fault_type' => 'late',
            'description' => 'Brouillon ignoré',
            'fault_date' => '2026-08-24',
            'sanction_type' => 'salary_deduction',
            'deduction_amount' => 5000,
            'responsible_id' => $manager->id,
            'status' => 'draft',
        ]);

        $rows = Livewire::actingAs($manager)
            ->test(MonthlyReport::class)
            ->set('month', '2026-08')
            ->instance()
            ->reportRows();

        $row = collect($rows)->firstWhere('name', 'Caissier Rapport');

        $this->assertSame(1, $row['late_count']);
        $this->assertSame(30, $row['late_minutes']);
        $this->assertSame(10000, $row['deductions']);
        $this->assertSame(250000, $row['net_salary']);
    }
}
