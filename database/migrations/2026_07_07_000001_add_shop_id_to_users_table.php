<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShopIdToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'shop_id')) {
                // FK to users.id (self-reference) — the stockist this user belongs to
                $table->unsignedBigInteger('shop_id')->nullable()->after('stockist_area');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'shop_id')) {
                $table->dropColumn('shop_id');
            }
        });
    }
}
