<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Desk;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    // Fungsi untuk mengambil semua daftar lab/ruangan
    public function index()
    {
        $rooms = Room::all();
        
        return response()->json([
            'message' => 'Berhasil mengambil daftar ruangan',
            'data' => $rooms
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:rooms,name']);
        $room = Room::create(['name' => $request->name]);
        return response()->json(['message' => 'Ruangan berhasil ditambahkan', 'data' => $room], 201);
    }

    public function storeDesks(Request $request, $id)
    {
        $request->validate([
            'start_number' => 'required|integer|min:1',
            'end_number' => 'required|integer|gte:start_number',
        ]);

        $room = Room::findOrFail($id);
        $desks = [];

        for ($i = $request->start_number; $i <= $request->end_number; $i++) {
            $desks[] = [
                'room_id' => $room->id,
                'desk_number' => 'Meja ' . $i,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Desk::insert($desks);

        return response()->json(['message' => 'Range meja berhasil ditambahkan ke ruangan ' . $room->name]);
    }

    public function availableDesks($id)
    {
        $desks = Desk::where('room_id', $id)->where('status', 'available')->get();
        return response()->json(['data' => $desks]);
    }

    public function indexDesks($id)
    {
        $room = Room::with(['desks' => function($query) {
            $query->orderBy('id', 'asc'); // short table from 1
        }])->findOrFail($id);

        // QR code for table
        $desks = $room->desks->map(function ($desk) use ($room) {
            return [
                'id' => $desk->id,
                'desk_number' => $desk->desk_number,
                'status' => $desk->status,
                //text fot QR code
                'qr_payload' => json_encode([
                    'room_id' => $room->id,
                    'desk_id' => $desk->id
                ])
            ];
        });

        return response()->json([
            'message' => 'Berhasil mengambil data meja',
            'room_name' => $room->name,
            'data' => $desks
        ]);
    }

}