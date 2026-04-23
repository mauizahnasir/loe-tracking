<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_name');
            $table->string('health');
            $table->unsignedTinyInteger('health_score');
            $table->unsignedTinyInteger('utilization_percent');
            $table->decimal('confirmed_hours', 6, 2)->default(0);
            $table->decimal('draft_hours', 6, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
