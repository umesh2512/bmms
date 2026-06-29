<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['board', 'agm', 'egm', 'committee', 'department', 'other'])->default('board');
            $table->enum('status', [
                'draft',
                'scheduled',
                'agenda_prepared',
                'board_pack_generated',
                'rsvp_active',
                'in_progress',
                'minutes_drafted',
                'minutes_under_approval',
                'closed',
                'archived',
            ])->default('draft');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('committee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('chairperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('secretary_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location')->nullable();
            $table->string('online_link')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('notice_days')->default(21);
            $table->boolean('quorum_required')->default(true);
            $table->unsignedSmallInteger('quorum_count')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('notice_sent_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
