<?php

namespace App\Models;

use App\Enums\LabRequestStatus;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class LabRequest extends Model
{
    protected $fillable = ['user_id', 'lab_id', 'table_id', 'pdf_path', 'message', 'status'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function lab() {
        return $this->belongsTo(Lab::class);
    }

    public function table() {
        return $this->belongsTo(Table::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeHistory($query)
    {
        return $query->whereIn('status', [
            LabRequestStatus::APPROVED->value,
            LabRequestStatus::COMPLETED->value,
            LabRequestStatus::REJECTED->value,
        ]);
    }
}
