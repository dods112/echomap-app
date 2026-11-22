<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pin_title');
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason');
            $table->enum('status', ['Pending', 'Approved', 'Removed'])->default('Pending');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};