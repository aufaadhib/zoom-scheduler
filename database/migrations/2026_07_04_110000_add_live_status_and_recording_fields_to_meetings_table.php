<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('meeting_status')->default('scheduled')->after('type');
            $table->timestamp('started_at')->nullable()->after('meeting_status');
            $table->timestamp('ended_at')->nullable()->after('started_at');
            $table->text('recording_share_url')->nullable()->after('password');
            $table->string('recording_passcode')->nullable()->after('recording_share_url');
            $table->timestamp('recording_completed_at')->nullable()->after('recording_passcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_status',
                'started_at',
                'ended_at',
                'recording_share_url',
                'recording_passcode',
                'recording_completed_at',
            ]);
        });
    }
};
