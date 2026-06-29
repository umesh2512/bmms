<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agenda_item_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('stage', ['draft', 'staged', 'published'])->default('draft');
            $table->unsignedSmallInteger('order_column')->default(0);
            $table->timestamps();

            $table->unique(['meeting_id', 'document_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_documents');
    }
};
