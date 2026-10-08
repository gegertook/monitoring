<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class QcDefect extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'style_order_id',
        'defect_date',
        'hour_number',
        'defect_type',
        'defect_qty',
        'action_taken',
        'notes',
        'recorded_by',
    ];

    protected $casts = [
        'defect_date' => 'date',
        'hour_number' => 'integer',
        'defect_qty' => 'integer',
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function styleOrder()
    {
        return $this->belongsTo(StyleOrder::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('defect_date', Carbon::today());
    }

    public function scopeByLine($query, $lineId)
    {
        return $query->where('production_line_id', $lineId);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('defect_date', [$start, $end]);
    }
}
