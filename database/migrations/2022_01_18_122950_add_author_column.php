<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthorColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('files', function (Blueprint $table){
            $table->string('author')->nullable();
        });
        Schema::table('folders', function (Blueprint $table){
            $table->string('author')->nullable();
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
            $table->dropColumn('author');
        });
        Schema::table('folders', function (Blueprint $table){
            $table->dropColumn('author');
        });
    }
}
