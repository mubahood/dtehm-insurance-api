<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddNewDashboardsToAdminMenu extends Migration
{
    public function up()
    {
        $menuTable = config('admin.database.menu_table', 'admin_menu');

        // Get current max order under parent 28 (DTEHM)
        $maxOrder = DB::table($menuTable)->where('parent_id', 28)->max('order') ?? 12;

        // Build insert data — only include columns that actually exist in the table
        $columns   = DB::getSchemaBuilder()->getColumnListing($menuTable);
        $hasRoles  = in_array('roles', $columns);
        $hasExtension = in_array('extension', $columns);

        $baseItems = [
            ['title' => 'Shops Dashboard',        'icon' => 'fa-store',     'uri' => 'shops-dashboard',        'offset' => 1],
            ['title' => 'Awards Dashboard',        'icon' => 'fa-trophy',    'uri' => 'awards-dashboard',       'offset' => 2],
            ['title' => 'Shareholders Dashboard',  'icon' => 'fa-pie-chart', 'uri' => 'shareholders-dashboard', 'offset' => 3],
        ];

        $newItems = [];
        foreach ($baseItems as $base) {
            $row = [
                'parent_id'  => 28,
                'order'      => $maxOrder + $base['offset'],
                'title'      => $base['title'],
                'icon'       => $base['icon'],
                'uri'        => $base['uri'],
                'permission' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasRoles)     $row['roles']     = json_encode([]);
            if ($hasExtension) $row['extension'] = '';
            $newItems[] = $row;
        }

        foreach ($newItems as $item) {
            $exists = DB::table($menuTable)->where('uri', $item['uri'])->exists();
            if (!$exists) {
                DB::table($menuTable)->insert($item);
            }
        }
    }

    public function down()
    {
        $menuTable = config('admin.database.menu_table', 'admin_menu');
        DB::table($menuTable)->whereIn('uri', [
            'shops-dashboard',
            'awards-dashboard',
            'shareholders-dashboard',
        ])->delete();
    }
}
