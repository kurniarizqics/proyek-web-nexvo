<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $fillable = [
        'name',
        'description',
        'coordinator_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function members()
    {
        return $this->hasMany(User::class, 'division_id');
    }
}