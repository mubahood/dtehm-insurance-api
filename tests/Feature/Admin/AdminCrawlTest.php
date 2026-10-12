<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Opens every admin page (lists, create forms, and the detail/edit pages linked from each list)
 * as a real admin and fails on any server error. Run on a *_test copy of live data:
 *   DB_DATABASE=dtehm_live_test vendor/bin/phpunit tests/Feature/Admin/AdminCrawlTest.php
 */
class AdminCrawlTest extends TestCase
{
    use DatabaseTransactions;

    public function test_every_admin_page_opens_without_errors()
    {
        $db = DB::connection()->getDatabaseName();
        if (!str_ends_with($db, '_test')) {
            $this->fail("Refusing to crawl database {$db}");
        }

        $adminId = DB::table('admin_role_users')->join('admin_roles', 'admin_roles.id', '=', 'admin_role_users.role_id')
            ->where('admin_roles.slug', 'admin')->value('admin_role_users.user_id');
        if (!$adminId) {
            $this->markTestSkipped('No admin user in this database');
        }
        $this->actingAs(User::find($adminId), 'admin');

        $skip = '#(logout|auth/|_handle_|api/|export|download|import|callback|ipn|pesapal/|test|run|process|send|generate|delete|destroy|sms)#i';
        $start = [];
        foreach (Route::getRoutes() as $r) {
            if (!in_array('GET', $r->methods())) continue;
            $mw = implode(',', $r->gatherMiddleware());
            if (!str_contains($mw, 'admin')) continue;
            $uri = $r->uri();
            if (preg_match($skip, $uri)) continue;
            if (str_contains($uri, '{')) continue;
            $start[] = '/' . ltrim($uri, '/');
        }
        $start = array_values(array_unique($start));

        $failures = [];
        $seen = [];
        $queue = $start;
        $deep = 0;
        while ($queue) {
            $url = array_shift($queue);
            if (isset($seen[$url])) continue;
            $seen[$url] = true;
            try {
                $res = $this->get($url);
                $status = $res->getStatusCode();
                $body = (string) $res->getContent();
            } catch (\Throwable $e) {
                $failures[] = "$url  EXCEPTION " . get_class($e) . ': ' . substr($e->getMessage(), 0, 160);
                continue;
            }
            if ($status >= 500) {
                $msg = $res->exception ? substr($res->exception->getMessage(), 0, 160) . ' @ ' . basename($res->exception->getFile()) . ':' . $res->exception->getLine() : '';
                $failures[] = "$url  HTTP $status  $msg";
                continue;
            }
            // follow one detail + one edit link from each list page
            if (in_array($url, $start, true) && $deep < 600) {
                $base = preg_quote(rtrim($url, '/'), '#');
                if (preg_match('#href="(?:https?://[^/"]+)?(' . $base . '/\d+)"#', $body, $m)) { $queue[] = $m[1]; $deep++; }
                if (preg_match('#href="(?:https?://[^/"]+)?(' . $base . '/\d+/edit)"#', $body, $m)) { $queue[] = $m[1]; $deep++; }
            }
        }

        fwrite(STDERR, "\nCrawled " . count($seen) . " admin pages, " . count($failures) . " failing\n" . implode("\n", $failures) . "\n");
        file_put_contents(storage_path('logs/admin_crawl_report.txt'), implode("\n", $failures));
        $this->assertSame([], $failures);
    }
}
