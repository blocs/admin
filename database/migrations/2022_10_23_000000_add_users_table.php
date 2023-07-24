<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
            $table->timestamp('disabled_at')->nullable();
            $table->text('role')->nullable();
            $table->text('file')->nullable();
            $table->string('filename', 32)->nullable();
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
            $table->dropSoftDeletes();
            $table->dropColumn('disabled_at');
            $table->dropColumn('role');
            $table->dropColumn('file');
            $table->dropColumn('filename');
        });
    }
}
