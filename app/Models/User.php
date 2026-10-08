<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class User
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nik',
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    public function styleOrders()
    {
        return $this->hasMany(StyleOrder::class, 'created_by');
    }

    public function evaluations()
    {
        return $this->hasMany(ManagerEvaluation::class, 'evaluated_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isRole($role)
    {
        return $this->role === $role;
    }

    public function isPpic()
    {
        return $this->isRole('ppic');
    }

    public function isIe()
    {
        return $this->isRole('ie');
    }

    public function isHrd()
    {
        return $this->isRole('hrd');
    }

    public function isSpv()
    {
        return $this->isRole('spv');
    }

    public function isAdminProduksi()
    {
        return $this->isRole('admin_produksi');
    }

    public function isManager()
    {
        return $this->isRole('manager');
    }

    public function isAdminQc()
    {
        return $this->isRole('admin_qc');
    }

    public function qcDefects()
    {
        return $this->hasMany(QcDefect::class, 'recorded_by');
    }
}
