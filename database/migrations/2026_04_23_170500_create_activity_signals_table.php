<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_entry_id')->constrained()->cascadeOnDelete();
            $table->string('source');
            $table->string('label');
            $table->unsignedSmallInteger('minutes')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_signals');
    }
};
