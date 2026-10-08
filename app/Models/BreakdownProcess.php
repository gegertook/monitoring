<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BreakdownProcess
 * @package App\Models
 */
class BreakdownProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'style_order_id',
        'sequence_number',
        'process_name',
        'smv',
        'target_per_hour',
        'created_by',
    ];

    protected $casts = [
        'smv' => 'decimal:4',
        'target_per_hour' => 'integer',
    ];

    public function styleOrder()
    {
        return $this->belongsTo(StyleOrder::class);
    }

    public function operatorTasks()
    {
        return $this->hasMany(OperatorTask::class);
    }

    public function operatorOutputs()
    {
        return $this->hasMany(OperatorOutput::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
