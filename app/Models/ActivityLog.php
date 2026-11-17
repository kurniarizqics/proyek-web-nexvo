<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'description',
        'status',
    ];

    public static function record(string $description, string $status = 'success', ?User $user = null): void
    {
        $actor = $user ?? Auth::user();

        self::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name ?? 'Pengguna Tidak Dikenal',
            'user_role' => $actor ? ucfirst($actor->role) : 'Sistem',
            'description' => $description,
            'status' => $status,
        ]);
    }
}