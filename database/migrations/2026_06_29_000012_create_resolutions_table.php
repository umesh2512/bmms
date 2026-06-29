<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('meeting_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agenda_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->enum('type', ['ordinary', 'special', 'circular'])->default('ordinary');
            $table->enum('status', ['proposed', 'voting', 'passed', 'failed', 'withdrawn', 'deferred'])->default('proposed');
            $table->boolean('is_secret_ballot')->default(false);
            $table->enum('required_majority', ['simple', 'two_thirds', 'unanimous'])->default('simple');
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('seconded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('votes_yes')->default(0);
            $table->unsignedSmallInteger('votes_no')->default(0);
            $table->unsignedSmallInteger('votes_abstain')->default(0);
            $table->timestamp('voting_opens_at')->nullable();
            $table->timestamp('voting_closes_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('result_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('resolution_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resolution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('vote', ['yes', 'no', 'abstain']);
            $table->timestamp('voted_at')->useCurrent();

            $table->unique(['resolution_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resolution_votes');
        Schema::dropIfExists('resolutions');
    }
};
