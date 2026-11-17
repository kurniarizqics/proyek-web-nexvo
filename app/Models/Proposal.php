<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'amount',
        'reason',
        'document_path',
        'review_note',
        'rejection_reason',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}