<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class HourlyOutput
 * @package App\Models
 */
class HourlyOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'style_order_id',
        'hour_number',
        'output_qty',
        'defect_qty',
        'production_date',
        'recorded_by',
        'is_validated',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'production_date' => 'date',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
        'output_qty' => 'integer',
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

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function getDefectRateAttribute()
    {
        $total = $this->output_qty + $this->defect_qty;
        if ($total <= 0) {
            return 0;
        }

        return round(($this->defect_qty / $total) * 100, 2);
    }

    public function getEfficiencyAttribute()
    {
        // Example logic, assumes target per hour is accessible via styleOrder and its active process or production line capacity.
        // Assuming capacity represents hourly target for the line.
        if (!$this->productionLine || $this->productionLine->capacity <= 0) {
            return 0;
        }

        return round(($this->output_qty / $this->productionLine->capacity) * 100, 2);
    }
}
