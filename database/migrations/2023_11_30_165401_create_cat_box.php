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
        Schema::create('iot_cat_box', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable()->comment('名稱');
            $table->string('location', 100)->nullable()->comment('放置');
            $table->string('iot_mac_id', 100)->comment('IOT 設備ID');
            $table->softDeletes();
            $table->timestamps();
            $table->comment('IOT資料');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_cat_box');
    }
};
