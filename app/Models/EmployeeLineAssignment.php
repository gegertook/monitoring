<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EmployeeLineAssignment
 * @package App\Models
 */
class EmployeeLineAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'production_line_id',
        'shift',
        'assigned_date',
        'assigned_by',
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
