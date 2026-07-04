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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zoom_account_id')->constrained()->cascadeOnDelete();
            $table->string('zoom_meeting_id');
            $table->string('topic');
            $table->text('agenda')->nullable();
            $table->integer('type'); // 1 = instant, 2 = scheduled
            $table->timestamp('start_time')->nullable();
            $table->integer('duration'); // in minutes
            $table->string('timezone');
            $table->text('join_url');
            $table->text('start_url');
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
