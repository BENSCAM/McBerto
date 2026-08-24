<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class StaffMember extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'job_title',
        'monthly_salary',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function activityLabel(): string
    {
        return "Personnel {$this->name}";
    }
}
