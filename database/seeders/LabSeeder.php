<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Desk;

class LabSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = ['Lab Cyber', 'Lab Multimedia', 'Lab TBD', 'Lab RPL'];

        foreach ($rooms as $roomName) {
            $room = Room::create(['name' => $roomName]);

            $desks = [];
            for ($i = 1; $i <= 15; $i++) {
                $desks[] = [
                    'room_id' => $room->id,
                    'desk_number' => 'Meja ' . $i,
                    'status' => 'available',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            Desk::insert($desks);
        }
    }
}