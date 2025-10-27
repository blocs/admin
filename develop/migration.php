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
        Schema::dropIfExists('LOOP_ITEM');

        Schema::create('LOOP_ITEM', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            /* ITEM_LIST */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('LOOP_ITEM');
    }
};
