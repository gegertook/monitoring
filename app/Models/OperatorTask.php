<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OperatorTask
 * @package App\Models
 */
class OperatorTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'breakdown_process_id',
        'employee_id',
        'style_order_id',
        'assigned_by',
        'assigned_date',
    ];

    protected $casts = [
        'assigned_date' => 'date',
    ];

    public function breakdownProcess()
    {
        return $this->belongsTo(BreakdownProcess::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function styleOrder()
    {
        return $this->belongsTo(StyleOrder::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
