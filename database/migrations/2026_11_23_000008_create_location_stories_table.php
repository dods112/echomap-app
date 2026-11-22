<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_stories', function (Blueprint $table) {
            $table->id();
            $table->string('place');
            $table->string('title');
            $table->text('summary');
            $table->text('quote')->nullable();
            $table->string('quote_author')->nullable();
            $table->unsignedSmallInteger('pin_count');
            $table->string('pins_hash', 64)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_stories');
    }
};