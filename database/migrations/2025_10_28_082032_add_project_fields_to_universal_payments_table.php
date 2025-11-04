<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectFieldsToUniversalPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('universal_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('user_id');
            $table->integer('number_of_shares')->nullable()->after('project_id')->comment('For share purchases');
            
            // Add index
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('universal_payments', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropColumn(['project_id', 'number_of_shares']);
        });
    }
}
