<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lab rooms table
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Cyber, Multimedia, TBD, RPL
            $table->timestamps();
        });

        // Computer desks table
        Schema::create('desks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('desk_number'); // Desk 1, Desk 2, etc.
            $table->enum('status', ['available', 'maintenance'])->default('available');
            $table->timestamps();
        });

        // Loans table
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Borrowing user
            
            // Nullable because it is set by Admin/Aslab during approval
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete(); 
            $table->foreignId('desk_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('document_path'); // Request letter PDF file path
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->dateTime('start_time'); // Borrow start time
            $table->dateTime('end_time');   // Borrow end time
            
            // Store Admin/Aslab ID who approved (optional but recommended for audit)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); 
            $table->text('admin_notes')->nullable(); // Rejection reason or additional notes
            
            $table->timestamps();
        });
        
        // Note: Ensure your 'users' table already has a 'role' column
        // (for example: enum('user', 'admin', 'aslab')) before running this migration.
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
        Schema::dropIfExists('desks');
        Schema::dropIfExists('rooms');
    }
};