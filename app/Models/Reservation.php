<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 1;
    public const STATUS_CANCEL = 2;

    public $casts = [
        'price' => 'integer',
        'status' => 'integer',
        'start_date' => 'immutable_date',
        'end_date' => 'immutable_date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function scopeBetweenDates($query, $from, $to): void
    {
         $query->where(function($query) use ($from, $to) {
             $query->whereBetween('start_date', [$from, $to])
                ->orWhereBetween('end_date', [$from, $to])
                ->orWhere(function ($query) use ($from, $to) {
                    $query->where('start_date', '<', $from)
                        ->where('end_date', '>', $to);
                });
        });
    }
}
