<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Employee
 * @package App\Models
 */
class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik',
        'name',
        'skill_level',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function lineAssignments()
    {
        return $this->hasMany(EmployeeLineAssignment::class);
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySkillLevel($query, $level)
    {
        return $query->where('skill_level', $level);
    }
}
