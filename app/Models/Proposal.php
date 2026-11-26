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
        'account_number',
        'bank_name',
        'account_holder_name',
        'document_path',
        'review_note',
        'rejection_reason',
        'status',
        'realization_status',
        'realization_items',
        'realization_proofs',
        'realization_submitted_at',
        'realization_rejection_reason',
    ];

    protected $casts = [
        'realization_items' => 'array',
        'realization_proofs' => 'array',
        'realization_submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
}