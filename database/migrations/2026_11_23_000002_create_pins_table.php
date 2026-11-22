<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 70);
            $table->string('place');
            $table->text('body');
            $table->string('theme')->default('Campus Life');
            $table->enum('visibility', ['Public', 'Friends', 'Private'])->default('Public');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->index(['lat', 'lng']);
            $table->index('visibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pins');
    }
};