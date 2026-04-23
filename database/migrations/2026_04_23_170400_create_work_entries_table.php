<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->decimal('hours', 4, 2);
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('confidence_score')->default(70);
            $table->string('source')->default('integration-draft');
            $table->string('note')->nullable();
            $table->text('explanation')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_entries');
    }
};
