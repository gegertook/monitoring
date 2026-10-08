<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OperatorOutput
 * @package App\Models
 */
class OperatorOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'style_order_id',
        'breakdown_process_id',
        'production_date',
        'output_qty',
        'defect_qty',
        'recorded_by',
    ];

    protected $casts = [
        'production_date' => 'date',
        'output_qty' => 'integer',
        'defect_qty' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function styleOrder()
    {
        return $this->belongsTo(StyleOrder::class);
    }

    public function breakdownProcess()
    {
        return $this->belongsTo(BreakdownProcess::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
