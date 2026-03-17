<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lab extends Model
{
      protected $fillable = ['name'];

      public function tables()
      {
            return $this->hasMany(Table::class);
      }
}
