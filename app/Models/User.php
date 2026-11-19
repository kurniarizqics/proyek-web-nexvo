<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'password',
        'role',
        'division',
        'avatar',
        'is_active',
        'division_id',
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
        ];
    }

    public function getUserCodeAttribute(): string
    {
        return $this->employee_id ?: ('1125' . str_pad($this->id, 4, '0', STR_PAD_LEFT));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    public function divisionRelation()
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}