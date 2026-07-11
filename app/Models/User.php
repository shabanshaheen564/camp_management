<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'camp_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function camp()
    {
        return $this->belongsTo(Camp::class);
    }

    public function createdCamps()
    {
        return $this->hasMany(Camp::class, 'created_by');
    }

    public function hasRole($role)
    {
        return $this->role && ($this->role->name === $role || $this->role->slug === $role);
    }

    public function hasPermission($permission)
    {
        return $this->role && $this->role->permissions()->where('name', $permission)->exists();
    }

    public function hasAnyPermission(array $permissions)
    {
        return $this->role && $this->role->permissions()->whereIn('name', $permissions)->exists();
    }

    public function hasAllPermissions(array $permissions)
    {
        if (!$this->role) return false;
        
        $userPermissions = $this->role->permissions()->whereIn('name', $permissions)->count();
        return $userPermissions === count($permissions);
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isSupervisor()
    {
        return $this->hasRole('supervisor');
    }

    public function canAccessCamp($campId)
    {
        if ($this->isAdmin()) {
            return true;
        }
        
        if ($this->isSupervisor()) {
            return $this->camp_id == $campId;
        }
        
        return false;
    }
}