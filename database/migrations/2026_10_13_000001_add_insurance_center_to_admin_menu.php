<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Adds "Insurance Control Center" at the top of the insurance (DIP) menu. Safe to run twice. */
class AddInsuranceCenterToAdminMenu extends Migration
{
    public function up()
    {
        $table = config('admin.database.menu_table', 'admin_menu');
        if (!Schema::hasTable($table) || DB::table($table)->where('uri', 'insurance-center')->exists()) {
            return;
        }
        $parent = DB::table($table)->where('uri', 'insurance-programs')->value('parent_id') ?? 0;
        $order = (int) (DB::table($table)->where('parent_id', $parent)->min('order') ?? 1);
        DB::table($table)->where('parent_id', $parent)->increment('order');

        $row = ['parent_id' => $parent, 'order' => $order, 'title' => 'Insurance Control Center', 'icon' => 'fa-heartbeat',
            'uri' => 'insurance-center', 'permission' => null, 'created_at' => now(), 'updated_at' => now()];
        $cols = Schema::getColumnListing($table);
        if (in_array('roles', $cols)) $row['roles'] = json_encode([]);
        if (in_array('extension', $cols)) $row['extension'] = '';
        DB::table($table)->insert(array_intersect_key($row, array_flip($cols)));
    }

    public function down()
    {
        DB::table(config('admin.database.menu_table', 'admin_menu'))->where('uri', 'insurance-center')->delete();
    }
}
