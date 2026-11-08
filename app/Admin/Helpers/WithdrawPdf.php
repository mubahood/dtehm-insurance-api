<?php

namespace App\Admin\Helpers;

use App\Models\WithdrawRequest;
use Encore\Admin\Facades\Admin;
use Illuminate\Contracts\Support\Renderable;

/**
 * The "Download withdraw list (PDF)" button, used everywhere in the admin panel
 * (top bar, dashboard, withdraw requests, withdraw PINs, quick search).
 *
 * The link carries `download` + target=_blank so laravel-admin's pjax never intercepts it and the
 * browser saves the file; the server also answers with Content-Disposition: attachment.
 */
class WithdrawPdf
{
    public static function url(): string
    {
        return admin_url('withdraw-requests/pdf-pending');
    }

    /** Only admins (the route is in the admin.only group) see the button. */
    public static function allowed(): bool
    {
        $u = Admin::user();
        return $u && method_exists($u, 'isRole') && $u->isRole('admin');
    }

    /** @return array{count:int, amount:float} pending requests, always current (one small query) */
    public static function pending(): array
    {
        $row = WithdrawRequest::where('status', 'pending')->selectRaw('COUNT(*) c, COALESCE(SUM(amount),0) a')->first();
        return ['count' => (int) ($row->c ?? 0), 'amount' => (float) ($row->a ?? 0)];
    }

    public static function filename(): string
    {
        return 'DTEHM-pending-withdrawals-' . now()->format('Y-m-d-Hi') . '.pdf';
    }

    /**
     * @param string $size 'lg' (big call-to-action), 'md' (toolbar), 'nav' (top bar)
     */
    public static function button(string $size = 'md', string $label = 'Download withdraw list (PDF)'): string
    {
        if (!self::allowed()) {
            return '';
        }
        $p = self::pending();
        $badge = '<span class="wr-pdf-count" title="Pending requests">' . $p['count'] . '</span>';
        $attrs = 'href="' . e(self::url()) . '" download="' . e(self::filename()) . '" target="_blank" rel="noopener"'
            . ' data-wr-pdf="1" title="Download the list of pending withdraw requests (' . $p['count'] . ', UGX ' . number_format($p['amount'], 0) . ') as a PDF"';

        if ($size === 'nav') {
            return '<li class="wr-pdf-nav"><a ' . $attrs . ' class="wr-pdf-btn"><i class="fa fa-file-pdf-o"></i> <span class="hidden-xs">Withdraw list PDF</span> ' . $badge . '</a></li>';
        }
        $cls = $size === 'lg' ? 'btn btn-lg btn-danger wr-pdf-btn wr-pdf-lg' : 'btn btn-sm btn-danger wr-pdf-btn';
        return '<a ' . $attrs . ' class="' . $cls . '"><i class="fa fa-download"></i> ' . e($label) . ' ' . $badge . '</a>';
    }

    /** Top-bar item, rendered when the page is drawn so the count is current. */
    public static function navItem(): Renderable
    {
        return new class implements Renderable {
            public function render()
            {
                return WithdrawPdf::button('nav');
            }
        };
    }

    /** Dashboard card: what is waiting, and the two actions. */
    public static function dashboardCard(): string
    {
        if (!self::allowed()) {
            return '';
        }
        $p = self::pending();
        $oldest = WithdrawRequest::where('status', 'pending')->min('created_at');
        $wait = $oldest ? ' · oldest waiting ' . \Carbon\Carbon::parse($oldest)->diffForHumans(null, true) : '';
        return '<div class="wr-dash ' . ($p['count'] ? 'wr-dash-busy' : 'wr-dash-clear') . '">'
            . '<div class="wr-dash-text"><span>Withdraw requests waiting</span>'
            . '<b>' . $p['count'] . ' request' . ($p['count'] == 1 ? '' : 's') . ' · UGX ' . number_format($p['amount'], 0) . '</b>'
            . '<small>' . ($p['count'] ? 'Download the list to pay members, then approve them' . $wait : 'Nothing waiting right now') . '</small></div>'
            . '<div class="wr-dash-actions">' . self::button('lg')
            . ' <a href="' . e(admin_url('withdraw-requests?tab=pending')) . '" class="btn btn-lg btn-default"><i class="fa fa-check-square-o"></i> Review &amp; approve</a></div>'
            . '</div>';
    }
}
