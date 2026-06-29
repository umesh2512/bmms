<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_packs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('board_pack_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order_column')->default(0);
            $table->unsignedSmallInteger('page_number')->nullable();
            $table->unsignedSmallInteger('page_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_pack_items');
        Schema::dropIfExists('board_packs');
    }
};
