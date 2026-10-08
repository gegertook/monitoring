<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ManagerEvaluation
 * @package App\Models
 */
class ManagerEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_line_id',
        'style_order_id',
        'evaluation_date',
        'result',
        'notes',
        'evaluated_by',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
    ];

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function styleOrder()
    {
        return $this->belongsTo(StyleOrder::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
