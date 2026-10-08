<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductionLine
 * @package App\Models
 */
class ProductionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function styleOrders()
    {
        return $this->hasMany(StyleOrder::class, 'assigned_line_id');
    }

    public function employeeAssignments()
    {
        return $this->hasMany(EmployeeLineAssignment::class);
    }

    public function hourlyOutputs()
    {
        return $this->hasMany(HourlyOutput::class);
    }

    public function evaluations()
    {
        return $this->hasMany(ManagerEvaluation::class);
    }

    public function qcDefects()
    {
        return $this->hasMany(QcDefect::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
