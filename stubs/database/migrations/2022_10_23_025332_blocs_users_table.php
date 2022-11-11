<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BlocsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('group')->nullable();
            $table->softDeletes();
            $table->timestamp('disabled_at', 0)->nullable();
            $table->string('file')->nullable();
            $table->string('filename')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('group');
            $table->dropSoftDeletes();
            $table->dropColumn('disabled_at');
            $table->dropColumn('file');
            $table->dropColumn('filename');
        });
    }
}
