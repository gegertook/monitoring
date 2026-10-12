<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Machine
 * @package App\Models
 */
class Machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'machine_type',
        'description',
        'is_active',
        'current_line_id',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function currentLine()
    {
        return $this->belongsTo(ProductionLine::class, 'current_line_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function breakdowns()
    {
        return $this->hasMany(MachineBreakdown::class);
    }

    /**
     * Laporan downtime yang masih terbuka (belum diperbaiki / diganti).
     */
    public function openBreakdown()
    {
        return $this->hasOne(MachineBreakdown::class)->whereIn('status', ['reported', 'mechanic_sent', 'replacement_requested']);
    }

    public function tasks()
    {
        return $this->hasMany(OperatorTask::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnLine($query, $lineId)
    {
        return $query->where('current_line_id', $lineId);
    }

    /**
     * Status runtime mesin, tanpa kolom tersimpan agar tidak pernah sinkron:
     * rusak > dipakai (di line) > ready.
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'nonaktif';
        }

        if ($this->relationLoaded('openBreakdown') ? $this->openBreakdown : $this->openBreakdown()->exists()) {
            return 'rusak';
        }

        return $this->current_line_id ? 'dipakai' : 'ready';
    }

    public function isBroken(): bool
    {
        return $this->openBreakdown()->exists();
    }
}
