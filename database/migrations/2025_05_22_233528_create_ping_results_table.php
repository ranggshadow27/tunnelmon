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
        Schema::create('ping_results', function (Blueprint $table) {
            $table->id(); // PK auto_increment
            $table->string('ip_address', 50); // FK dari device_details
            $table->tinyInteger('status')->unsigned(); // tinyint, 0 atau 1
            $table->tinyInteger('packet_loss')->unsigned(); // tinyint, 0-100%
            $table->text('message')->nullable(); // text, boleh null
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('ip_address')->references('ip_address')->on('device_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ping_results');
    }
};
