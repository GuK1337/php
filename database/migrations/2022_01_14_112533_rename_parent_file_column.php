<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameParentFileColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('files', function(Blueprint $table) {
            $table->renameColumn('parent', 'folder_id');
        });
    }


    public function down()
    {
        Schema::table('files', function(Blueprint $table) {
            $table->renameColumn('folder_id', 'parent');
        });
    }
}
