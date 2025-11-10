<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMembershipFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_membership_paid')->default(false)->after('user_type');
            $table->timestamp('membership_paid_at')->nullable()->after('is_membership_paid');
            $table->decimal('membership_amount', 10, 2)->nullable()->after('membership_paid_at');
            $table->bigInteger('membership_payment_id')->nullable()->after('membership_amount');
            $table->enum('membership_type', ['LIFE', 'ANNUAL', 'MONTHLY'])->nullable()->after('membership_payment_id');
            $table->date('membership_expiry_date')->nullable()->after('membership_type');
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
            $table->dropColumn([
                'is_membership_paid',
                'membership_paid_at',
                'membership_amount',
                'membership_payment_id',
                'membership_type',
                'membership_expiry_date'
            ]);
        });
    }
}
