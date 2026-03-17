<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function desks(): HasMany
    {
        return $this->hasMany(Desk::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }
}