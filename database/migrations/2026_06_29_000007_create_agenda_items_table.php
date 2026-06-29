<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('agenda_items')->nullOnDelete();
            $table->unsignedSmallInteger('order_column')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('presenter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('time_allocated')->nullable();
            $table->boolean('resolution_required')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_items');
    }
};
