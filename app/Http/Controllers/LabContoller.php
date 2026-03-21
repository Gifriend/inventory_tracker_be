<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\Table;
use Illuminate\Http\Request;

class LabController extends Controller
{
      public function store(Request $request)
      {
            $request->validate(['name' => 'required|string|unique:labs,name']);
            $lab = Lab::create(['name' => $request->name]);
            return $this->successResponse($lab, 'Lab berhasil ditambahkan', 201);
      }

      public function storeTables(Request $request, $id)
      {
            $request->validate([
                  'start_number' => 'required|integer|min:1',
                  'end_number' => 'required|integer|gte:start_number',
            ]);

            $lab = Lab::findOrFail($id);
            $tables = [];

            for ($i = $request->start_number; $i <= $request->end_number; $i++) {
                  $tables[] = [
                        'lab_id' => $lab->id,
                        'table_number' => 'Meja ' . $i,
                        'status' => 'available',
                        'created_at' => now(),
                        'updated_at' => now(),
                  ];
            }

            Table::insert($tables);

            return $this->successResponse(null, 'Range meja berhasil ditambahkan');
      }

      public function availableTables($id)
      {
            $tables = Table::where('lab_id', $id)->where('status', 'available')->get();

            if ($tables->isEmpty()) {
                  return $this->successResponse([], 'Belum ada data');
            }

            return $this->successResponse($tables, 'Berhasil mengambil data meja tersedia');
      }
}
