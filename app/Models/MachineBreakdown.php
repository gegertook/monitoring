<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Class MachineBreakdown
 * @package App\Models
 */
class MachineBreakdown extends Model
{
    use HasFactory;

    public const STATUS_REPORTED = 'reported';
    public const STATUS_MECHANIC_SENT = 'mechanic_sent';
    public const STATUS_REPLACEMENT_REQUESTED = 'replacement_requested';
    public const STATUS_REPAIRED = 'repaired';
    public const STATUS_REPLACED = 'replaced';

    public const OPEN_STATUSES = [
        self::STATUS_REPORTED,
        self::STATUS_MECHANIC_SENT,
        self::STATUS_REPLACEMENT_REQUESTED,
    ];

    public const REPLACEMENT_THRESHOLD_MINUTES = 60;

    protected $fillable = [
        'machine_id',
        'production_line_id',
        'style_order_id',
        'notes',
        'status',
        'resolution',
        'reported_by',
        'reported_at',
        'mechanic_user_id',
        'mechanic_sent_at',
        'replacement_requested_by',
        'replacement_requested_at',
        'replacement_machine_id',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'mechanic_sent_at' => 'datetime',
        'replacement_requested_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function productionLine()
    {
        return $this->belongsTo(ProductionLine::class);
    }

    public function styleOrder()
    {
        return $this->belongsTo(StyleOrder::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function mechanic()
    {
        return $this->belongsTo(User::class, 'mechanic_user_id');
    }

    public function replacementRequester()
    {
        return $this->belongsTo(User::class, 'replacement_requested_by');
    }

    public function replacementMachine()
    {
        return $this->belongsTo(Machine::class, 'replacement_machine_id');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeForLine($query, $lineId)
    {
        return $query->where('production_line_id', $lineId);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isResolved(): bool
    {
        return !$this->isOpen();
    }

    /**
     * Menit sejak mesin dilaporkan rusak (dipakai untuk aturan "lebih dari 1 jam").
     */
    public function minutesSinceReported(): int
    {
        return Carbon::parse($this->reported_at)->diffInMinutes(now());
    }

    public function canRequestReplacement(): bool
    {
        return $this->isOpen()
            && $this->minutesSinceReported() >= self::REPLACEMENT_THRESHOLD_MINUTES;
    }
}
