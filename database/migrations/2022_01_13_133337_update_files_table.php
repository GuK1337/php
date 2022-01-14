<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('files', function (Blueprint $table){
            $table->string('name')->nullable(false);
            $table->bigInteger('parent')->nullable();
            $table->json('users')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('files', function (Blueprint $table){
            $table->dropColumn('name');
            $table->dropColumn('parent');
            $table->dropColumn('users');
            $table->dropColumn('data');
        });
    }
}
