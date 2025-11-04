<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInsuranceUserFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Add insurance user specific fields
            $table->string('tribe', 100)->nullable()->after('country');
            $table->string('father_name', 255)->nullable()->after('tribe');
            $table->string('mother_name', 255)->nullable()->after('father_name');
            $table->string('child_1', 255)->nullable()->after('mother_name')->comment('First biological child');
            $table->string('child_2', 255)->nullable()->after('child_1')->comment('Second biological child');
            $table->string('child_3', 255)->nullable()->after('child_2')->comment('Third biological child');
            $table->string('child_4', 255)->nullable()->after('child_3')->comment('Fourth biological child');
            $table->string('sponsor_id', 100)->nullable()->after('child_4')->comment('Sponsor ID number');
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
            // Drop the added columns
            $table->dropColumn([
                'tribe',
                'father_name',
                'mother_name',
                'child_1',
                'child_2',
                'child_3',
                'child_4',
                'sponsor_id'
            ]);
        });
    }
}
