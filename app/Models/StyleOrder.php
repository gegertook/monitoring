<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class StyleOrder
 * @package App\Models
 */
class StyleOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'code_style',
        'buyer',
        'total_qty_target',
        'deadline',
        'status',
        'assigned_line_id',
        'created_by',
    ];

    protected $casts = [
        'deadline' => 'date',
        'total_qty_target' => 'integer',
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class, 'assigned_line_id');
    }

    public function breakdownProcesses()
    {
        return $this->hasMany(BreakdownProcess::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hourlyOutputs()
    {
        return $this->hasMany(HourlyOutput::class);
    }

    public function operatorOutputs()
    {
        return $this->hasMany(OperatorOutput::class);
    }

    public function evaluations()
    {
        return $this->hasMany(ManagerEvaluation::class);
    }

    public function qcDefects()
    {
        return $this->hasMany(QcDefect::class);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function getProgressPercentageAttribute()
    {
        if (!$this->total_qty_target || $this->total_qty_target <= 0) {
            return 0;
        }

        $totalOutput = $this->hourlyOutputs()->sum('output_qty');
        return min(100, round(($totalOutput / $this->total_qty_target) * 100, 2));
    }
}
