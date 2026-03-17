<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Table extends Model
{
      protected $fillable = ['lab_id', 'table_number', 'status'];

      public function lab()
      {
            return $this->belongsTo(Lab::class);
      }
}
