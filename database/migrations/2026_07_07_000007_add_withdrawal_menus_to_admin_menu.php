<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddWithdrawalMenusToAdminMenu extends Migration
{
    public function up()
    {
        $menuTable = config('admin.database.menu_table', 'admin_menu');
        $columns   = DB::getSchemaBuilder()->getColumnListing($menuTable);
        $hasRoles  = in_array('roles', $columns);
        $hasExt    = in_array('extension', $columns);

        $maxOrder = DB::table($menuTable)->where('parent_id', 28)->max('order') ?? 13;

        $items = [
            ['title' => 'Shareholder Withdrawals', 'icon' => 'fa-money',    'uri' => 'shareholder-withdrawals', 'offset' => 1],
            ['title' => 'Award Withdrawals',        'icon' => 'fa-gift',     'uri' => 'award-withdrawals',       'offset' => 2],
        ];

        foreach ($items as $item) {
            if (DB::table($menuTable)->where('uri', $item['uri'])->exists()) continue;
            $row = [
                'parent_id'  => 28,
                'order'      => $maxOrder + $item['offset'],
                'title'      => $item['title'],
                'icon'       => $item['icon'],
                'uri'        => $item['uri'],
                'permission' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasRoles) $row['roles']     = json_encode([]);
            if ($hasExt)   $row['extension'] = '';
            DB::table($menuTable)->insert($row);
        }
    }

    public function down()
    {
        $menuTable = config('admin.database.menu_table', 'admin_menu');
        DB::table($menuTable)->whereIn('uri', ['shareholder-withdrawals', 'award-withdrawals'])->delete();
    }
}
