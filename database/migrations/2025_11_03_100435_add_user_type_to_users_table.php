<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserTypeToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if user_type column doesn't exist, then add it
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type', 50)->default('Customer')->after('username')
                    ->comment('User type: Admin or Customer');
            } else {
                // If column exists, modify it to ensure proper default
                $table->string('user_type', 50)->default('Customer')->change();
            }
        });

        // Update existing records that have 'insurance_user' to 'Customer'
        DB::table('users')
            ->where('user_type', 'insurance_user')
            ->update(['user_type' => 'Customer']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Don't drop the column as it might be used elsewhere
            // Just revert default if needed
        });
    }
}
