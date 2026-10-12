<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lookup indexes for the columns the app searches on constantly (login by phone / DTEHM id / DIP id,
 * sponsor lookup, id generation, payment reconciliation). Each one is added only if it is missing,
 * and the unique DTEHM id index is added only when the data has no duplicates, so this is safe on live data.
 */
class AddMemberAndPaymentLookupIndexes extends Migration
{
    private function hasIndex(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function addIndex(string $table, array $columns, string $name, bool $unique = false): void
    {
        if (!Schema::hasTable($table) || $this->hasIndex($table, $name)) {
            return;
        }
        foreach ($columns as $c) {
            if (!Schema::hasColumn($table, $c)) {
                return;
            }
        }
        // TEXT / BLOB columns can only be indexed with a prefix length
        $parts = [];
        foreach ($columns as $c) {
            $type = DB::table('information_schema.columns')
                ->where('table_schema', DB::getDatabaseName())->where('table_name', $table)->where('column_name', $c)
                ->value('data_type');
            $parts[] = in_array(strtolower((string) $type), ['text', 'tinytext', 'mediumtext', 'longtext', 'blob'], true)
                ? "`{$c}`(50)" : "`{$c}`";
        }
        DB::statement('ALTER TABLE `' . $table . '` ADD ' . ($unique ? 'UNIQUE ' : '') . 'INDEX `' . $name . '` (' . implode(', ', $parts) . ')');
    }

    public function up()
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        // users
        $dupes = DB::table('users')->whereNotNull('dtehm_member_id')->where('dtehm_member_id', '!=', '')
            ->select('dtehm_member_id')->groupBy('dtehm_member_id')->havingRaw('COUNT(*) > 1')->count();
        if ($dupes === 0) {
            // empty strings would collide on a unique index: turn them into NULL first
            DB::table('users')->where('dtehm_member_id', '')->update(['dtehm_member_id' => null]);
            $this->addIndex('users', ['dtehm_member_id'], 'users_dtehm_member_id_unique', true);
        } else {
            $this->addIndex('users', ['dtehm_member_id'], 'users_dtehm_member_id_index');
            \Log::warning("users.dtehm_member_id has {$dupes} duplicated id(s): added a plain index, fix the duplicates then add the unique index.");
        }
        $this->addIndex('users', ['phone_number'], 'users_phone_number_index');
        $this->addIndex('users', ['business_name'], 'users_business_name_index');
        $this->addIndex('users', ['sponsor_id'], 'users_sponsor_id_index');

        // memberships
        $this->addIndex('dtehm_memberships', ['user_id', 'status'], 'dtehm_memberships_user_status_index');
        $this->addIndex('membership_payments', ['user_id', 'status'], 'membership_payments_user_status_index');

        // payments reconciliation sweep
        $this->addIndex('universal_payments', ['items_processed', 'status', 'created_at'], 'universal_payments_reconcile_index');
        $this->addIndex('insurance_subscription_payments', ['user_id', 'payment_status', 'due_date'], 'isp_user_status_due_index');
    }

    public function down()
    {
        // Indexes are harmless to keep; nothing to undo.
    }
}
