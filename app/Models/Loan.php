<?php

namespace App\Models;

use App\Enums\LoanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'desk_id',
        'document_path',
        'status',
        'start_time',
        'end_time',
        'check_in_time',
        'check_out_time',
        'approved_by',
        'admin_notes'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function desk(): BelongsTo
    {
        return $this->belongsTo(Desk::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopePending($query)
    {
        return $query->where('status', LoanStatus::PENDING->value);
    }

    public function scopeActive($query)
    {
        return $query->where('status', LoanStatus::APPROVED->value);
    }

    public function scopeHistory($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', [LoanStatus::COMPLETED->value, LoanStatus::REJECTED->value])
              ->orWhereNotNull('check_out_time');
        });
    }
}
