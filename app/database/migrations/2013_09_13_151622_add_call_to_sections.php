<?php

use Illuminate\Database\Migrations\Migration;

class AddCallToSections extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sections', function($table) {
            $table->integer('call_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sections', function($table) {
            $table->dropColumn('call_number');
        });
    }

}
