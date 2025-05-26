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
        Schema::create('request_logs', function (Blueprint $table) {
            $table->string('request_id')->primary();

            $table->string('subdomain')->nullable();

            $table->longText('raw_request');

            $table->integer('start_time');
            $table->integer('stop_time')->nullable();

            $table->dateTime('performed_at');
            $table->float('duration');

            $table->json('plugin_data')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
