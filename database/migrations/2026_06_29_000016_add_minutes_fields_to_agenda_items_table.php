<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->string('naac_criteria_no')->nullable()->after('title');
            $table->string('hod_resolution_no')->nullable()->after('naac_criteria_no');
            $table->date('hod_resolution_date')->nullable()->after('hod_resolution_no');
            $table->longText('points_discussed')->nullable()->after('hod_resolution_date');
            $table->text('decisions')->nullable()->after('points_discussed');
            $table->string('action_by')->nullable()->after('decisions');
            $table->date('action_date')->nullable()->after('action_by');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropColumn([
                'naac_criteria_no', 'hod_resolution_no', 'hod_resolution_date',
                'points_discussed', 'decisions', 'action_by', 'action_date',
            ]);
        });
    }
};
