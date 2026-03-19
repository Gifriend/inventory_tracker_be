<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE loans ALTER COLUMN document_path DROP NOT NULL;');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE loans MODIFY document_path VARCHAR(255) NULL;');
        } else {
            // Fallback: try Schema change (may require doctrine/dbal)
            Schema::table('loans', function (Blueprint $table) {
                $table->string('document_path')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE loans ALTER COLUMN document_path SET NOT NULL;');
        } elseif ($driver === 'mysql') {
            DB::statement('ALTER TABLE loans MODIFY document_path VARCHAR(255) NOT NULL;');
        } else {
            Schema::table('loans', function (Blueprint $table) {
                $table->string('document_path')->nullable(false)->change();
            });
        }
    }
};
