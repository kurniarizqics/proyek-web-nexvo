<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'category_id',
        'user_id',
        'amount',
        'transaction_date',
        'description',
        'proposal_id',
        'proof_path',
        'voided_at',
        'void_reason',
    ];
    
    protected $casts = [
        'transaction_date' => 'date',
        'voided_at' => 'datetime',
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proposal()
    {
    return $this->belongsTo(Proposal::class);
    }
}