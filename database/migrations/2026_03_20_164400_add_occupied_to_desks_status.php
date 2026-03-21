<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Postgres created a check constraint named `desks_status_check` for the enum.
        // Update it to include the `occupied` value.
        DB::statement("ALTER TABLE desks DROP CONSTRAINT IF EXISTS desks_status_check;");
        DB::statement("ALTER TABLE desks ADD CONSTRAINT desks_status_check CHECK (status IN ('available','maintenance','occupied')); ");
    }

    public function down(): void    
    {
        // Revert to the previous allowed values (remove 'occupied')
        DB::statement("ALTER TABLE desks DROP CONSTRAINT IF EXISTS desks_status_check;");
        DB::statement("ALTER TABLE desks ADD CONSTRAINT desks_status_check CHECK (status IN ('available','maintenance')); ");
    }
};
