<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** History of every withdraw-PIN event (who set / changed / unlocked / removed it, and when). */
class CreateAccountPinLogsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('account_pin_logs')) {
            Schema::create('account_pin_logs', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->index();
                $t->string('action', 40);              // created, changed, admin_set, admin_generated, admin_unlocked, admin_removed, locked
                $t->string('actor_type', 20)->default('member'); // member | admin | system
                $t->unsignedBigInteger('actor_id')->nullable();
                $t->string('note', 500)->nullable();
                $t->boolean('sms_sent')->nullable();
                $t->string('ip', 64)->nullable();
                $t->timestamp('created_at')->nullable()->index();
            });
        }

        // Menu: "Withdraw PINs" right after "Withdraw Requests"
        $menu = config('admin.database.menu_table', 'admin_menu');
        if (Schema::hasTable($menu) && !DB::table($menu)->where('uri', 'withdraw-pins')->exists()) {
            $ref = DB::table($menu)->where('uri', 'withdraw-requests')->first();
            $parent = $ref->parent_id ?? 0;
            $order = ($ref->order ?? 0) + 1;
            DB::table($menu)->where('parent_id', $parent)->where('order', '>=', $order)->increment('order');
            $row = ['parent_id' => $parent, 'order' => $order, 'title' => 'Withdraw PINs', 'icon' => 'fa-lock',
                'uri' => 'withdraw-pins', 'permission' => null, 'created_at' => now(), 'updated_at' => now()];
            $cols = Schema::getColumnListing($menu);
            if (in_array('roles', $cols)) $row['roles'] = json_encode([]);
            if (in_array('extension', $cols)) $row['extension'] = '';
            DB::table($menu)->insert(array_intersect_key($row, array_flip($cols)));
        }
    }

    public function down()
    {
        DB::table(config('admin.database.menu_table', 'admin_menu'))->where('uri', 'withdraw-pins')->delete();
        Schema::dropIfExists('account_pin_logs');
    }
}
