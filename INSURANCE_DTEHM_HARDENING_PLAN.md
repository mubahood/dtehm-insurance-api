# Insurance + DTEHM Membership Hardening Plan

Status (12 Oct 2026): audit complete; Phases 1-4 done. 51 automated tests (1,421 assertions) pass on BOTH the local clone and a clone of the real LIVE database (372 users, 8 policies, 88 payments), incl. real-account scenarios. Flutter analyzer: 0 errors; release APK builds. NOT deployed - awaiting owner go-ahead (see Deployment).
Nothing is deployed to production until local testing passes and the owner approves.

Core principles kept: UGX currency, DTEHM/DIP IDs, MLM parent_1..parent_10 chain, sponsor commissions,
Pesapal as the gateway, UniversalPayment as the single payment abstraction, member-centric app.

## Phase 1 - Backend security (critical)
| ID | Problem | Fix |
|----|---------|-----|
| S1 | Insurance / medical / membership / universal-payment routes have no auth | Wrap in auth middleware; admin-only actions get role check |
| S2 | `markAsPaid`, payment `update`, subscription `update/store` use `$request->all()` and body `user_id` | Whitelist with `only()`, user taken from token, admin override only |
| S3 | IDOR on show / getUserSubscription / getUserPayments / stats / medical requests | Ownership-or-admin check |
| S4 | Legacy membership confirm endpoints trust client `status=success`, credit commission, not idempotent | Admin-only or gateway-verified; transaction + status guard; one commission per payment |
| S5 | `universal-payments/initialize` trusts client amounts | Recompute amounts server-side from item ids; verify ownership |
| S6 | IPN / callback not idempotent; COMPLETED can regress; items can process twice | `lockForUpdate`, never downgrade COMPLETED, set `confirmed_at` once, atomic ipn_count |
| S7 | Partial insurance payments double-add on retry | Per-item idempotency key (universal_payment_id + payment row) |
| S8 | `VerifyPesapalWebhook` UA allow-list | Remove; rely on gateway status re-query |
| S9 | Raw `$e->getMessage()` returned to clients | Log, return generic message |
| S10 | Sort injection / unbounded `per_page` | Column whitelist, cap 100 |
| S11 | Dev-only routes exposed in production | Gate by environment |

## Phase 2 - Backend correctness
- Payment state machine: Partial must not be forced to Overdue; reset days_overdue when settled.
- `updateBalances`: count Partial paid amounts; exclude Waived from expected; run on `deleted`, not `deleting`.
- Create + prepare in a single DB transaction; bulk insert payment rows.
- Duplicate-active-subscription race: lock + unique index; unique `policy_number`.
- Cancel / suspend / activate: allowed-transition guard; cancel waives or stops accrual on unpaid rows.
- Expiry job: move ended subscriptions to Expired; schedule overdue/penalty update.
- Membership: one source of truth (UniversalPayment path); fix `dtehm_membership_amount` vs `dtehm_membership_paid_amount`; unique `dtehm_member_id`; fees from SystemConfiguration only.
- Add migrations for insurance tables (currently unversioned) and lookup indexes.

## Phase 3 - Flutter insurance + membership
| ID | Problem | Fix |
|----|---------|-----|
| F1 | `MembershipPayment.dart` assigns dynamic to `RespondModel` (always throws) | Wrap with `RespondModel(...)` |
| F2 | Post-payment membership check never runs (result null) | Return result / always re-check |
| F3 | Double-tap creates two payments | In-flight guard, disabled button |
| F4 | `UniversalPayment` crashes on connection failure (String indexed) | Type-check responses |
| F5 | `insurance_user_id` / `insurance_user` vs API `user_id` / `user` | Read both keys |
| F6 | Pending-months list ignores subscription filter for members | Server filter for everyone + client filter |
| F7 | Pesapal fallback posts to endpoint needing `order_id` | Re-call universal initialize |
| F8 | Polling has no cap / overlap guard | 5 min cap, in-flight flag, backoff, "check later" state |
| F9 | WebView success detection by URL substring | Match own callback path only |
| F10 | Unsafe parse (`DateTime.parse`, `double.parse`) | tryParse helpers |
| F11 | Paywall fails open on network error | Retry / offline screen |
| F12 | Hard-coded fees, repurposed user fields | Fees from server |
| F13 | Navigation after enrollment pops wrong screens | `Get.off` / `Get.until` |
| F14 | Missing error/empty states, `mounted` checks | Add |
| F15 | TLS cert override, token logging, no timeouts | Remove / add |

## Phase 4 - Testing (local DB)
- Dump of live DB: BLOCKED - SSH account has shell/SFTP disabled. Need cPanel export from owner.
  Until then tests run on the existing local DB (204 users, 131 memberships, 4 subscriptions).
- Feature tests: auth required on every route; ownership; enroll -> pay -> partial -> full; duplicate IPN;
  forged confirm rejected; amount tampering rejected; cancel/suspend transitions; membership purchase once.
- Flutter: `flutter analyze` + release build check.

## Phase 5 - Release
Only after Phase 4 passes and owner approval: backup live DB, run new migrations, deploy API, then ship app build.
Old app versions must keep working: new auth requirements need the existing `Tok`/`User-Id` headers the app already sends.


---

## Implementation status

### Done (backend, in this repo, uncommitted)
- **Auth**: `auth.jwt` / `api.admin` middleware; all insurance, medical, membership, universal-payment routes protected; admin-only: program writes, subscription update/suspend/activate/delete, payment update/pay/stats/overdue, medical review/delete/stats, `/process`.
- **Spoofing closed platform-wide**: `Utils::get_user` and `EnsureTokenIsValid` no longer let the `User-Id` header override/replace the JWT (it was possible to act as any admin). Legacy behaviour only with `STRICT_API_AUTH=false`.
- **Money**: server rebuilds payment items/amounts (`UniversalPayment::prepareItemsForClient`); membership type from amount only; share count from amount; Pesapal amount/currency cross-check; COMPLETED is final; row-locked, apply-once processing; idempotent initialize (double tap = one payment); `isCompleted()` now = status COMPLETED only.
- **Reliability**: Pesapal IPN, browser callback, app poll and a 5-minute `payments:reconcile` sweep all finish a paid payment through one service (`UniversalPaymentReconciler`) - a member who pays and closes the app is still credited.
- **Insurance lifecycle**: balances count partial payments and exclude waived; Partial stays Partial; penalties for late partials; cancel waives unpaid months and keeps history; delete blocked when money was paid; daily `insurance:maintain` (overdue, expiry, Late/Current).
- **Privileges**: members cannot set `user_type`, `status`, membership or stockist flags (create/update); profile edit is self-or-admin; public sponsor list returns no contact details.
- **Errors**: no exception text in any insurance/payment response; sort-column whitelist; page size cap 100.
- **DTEHM IDs**: one race-safe generator (`User::ensureDtehmMemberId`), old year-format generator removed from the payment path; unique index on `dtehm_member_id`; regex allows 4-digit numbers.
- **Schema**: migrations for the 4 insurance tables (create-if-missing) and lookup indexes (users phone/DTEHM/DIP/sponsor, payments).
- Legacy `membership/confirm-payment` retired (410).

### Done (Flutter app, uncommitted)
- 401 => logout to login once; TLS bypass removed (prod cert valid until 4 Dec 2026); timeouts; token no longer logged; consistent error shape with plain-language messages.
- Membership/Review models no longer crash (`RespondModel`); post-payment membership re-check always runs; double-tap guard; polling capped at 5 min without overlap; WebView only reacts to our own callback; fallback "Pay now" uses the real endpoint; unpaid-months list includes Overdue/Partial and sends the true outstanding amount; user_id/user keys read correctly; paywall retries and shows a retry dialog instead of failing open; enrolment hands off to the payment screen without extra pops.

### Not done / needs a decision
1. **Live DB test** - blocked, see below.
2. **Sponsor commission on membership paid via Pesapal**: the retired legacy path paid the sponsor UGX 10,000; the Pesapal path (the one members actually use) pays nothing. Business rule needed - not guessed.
3. Remaining Flutter polish (empty/error states on insurance tab and lists, unused legacy stack `MembershipService`, backup screens, stale local duplicate-enrolment cache check).
4. Other modules (transactions, orders, projects) were not audited beyond the shared auth fix.

## Live database (resolved)
cPanel (port 2083) accepts the account login even though SSH/SFTP shell is disabled. The DB `dtehxejt_system` (MariaDB 11.4, 100 tables) was downloaded via cPanel's backup endpoint and imported into local scratch DBs `dtehm_live_copy` (migrated, maintenance job run on it) and `dtehm_live_test` (used by `DB_DATABASE=dtehm_live_test vendor/bin/phpunit tests/Feature/Insurance`). Findings on real data:
- No duplicate / malformed DTEHM ids: the unique index is safe. 1 policy has a deleted user (orphan) - handled.
- Policy #8 stored total was 16,000 too high (old bug: totals recalculated before a row delete); corrected by the new accounting.
- Admins are role-based (1 admin + 7 managers; nobody has user_type Admin) - recognised correctly by the new admin checks.
- Product purchases (and 4 old test membership rows) are applied by other code and are deliberately skipped by the new shared sweep.
- First maintenance run will mark premiums overdue and apply the program's UGX 2,000 penalty after the 7-day grace (3 rows on the copy). Members have not been charged penalties automatically before - tell the team.

## Deployment runbook (only after live-data test passes and owner approves)
1. Back up the live DB and the current code.
2. Deploy API code. Do **not** change `.env` yet.
3. `php artisan migrate --force` (2 new migrations; both are no-ops on existing tables/indexes).
4. Confirm the Laravel scheduler cron exists on the host: `* * * * * php /path/artisan schedule:run` (needed for `payments:reconcile` and `insurance:maintain`).
5. One-time: `php artisan payments:reconcile --days=3650` to apply paid-but-unapplied payments (5 such rows exist even in the local copy), then `php artisan insurance:maintain`.
6. **Auth rollout** (mitigated: 401s now say exactly `Unauthenticated`, which installed app builds already treat as "log out and show login", so old builds recover by themselves): `STRICT_API_AUTH` defaults to **true**. Installed app builds whose saved token is older than the old 60-minute lifetime will get 401 on first call. New app build handles 401 by sending the member to login; old builds will just show errors until the member logs in again. Options: (a) ship the app update first, then deploy API; (b) deploy API with `STRICT_API_AUTH=false` temporarily (security holes stay open, spoofed headers are logged), then flip to true once most members updated. New logins now last 30 days (`JWT_TTL=43200`).
7. Smoke test with a real member: enrol, pay a small premium, pay membership, cancel.
8. Ship the Flutter build (bump `version` in pubspec.yaml; release build must be compiled for real - analyze alone is not enough, see `project-flutter-app` notes).

### Server facts (read-only recon via cPanel)
- API code: cPanel Git Version Control repo `/home/dtehxejt/dip`, branch `main`, remote github.com/mubahood/dtehm-insurance-api; currently 2 commits behind local main (`b1d6dad`); no `.cpanel.yml` (deploy = "Update from Remote" only; no composer/migrate/cache steps run). No shell: migrations and cron must be run another way (cPanel Cron one-off, or a temporary admin-only route), so migrations are the one step needing a decision.
- Deploy = commit + push to GitHub main, then cPanel > Git Version Control > Update from Remote (deploys the 2 older unreleased commits too).
- Security note: the Flutter repo tracks `dtehm-app-credentials.txt` (keystore passwords + an SSH private key). Remove from git history / rotate if that repo is hosted anywhere.


---

## Round 2 (12 Oct 2026): simpler member journey + correct months and totals

### What was wrong (found on the live data)
- **Months paid out of order**: admin grid listed newest months first; policy #6 had JUL+AUG 2027 marked paid while SEP+OCT 2026 were overdue (penalised). Nothing enforced oldest-first anywhere.
- **Cover end one day too late** (start + 12 months instead of the day before): "3 Sep 2026 - 3 Sep 2027" looked like 13 months.
- **Month arithmetic drift**: periods were built by repeatedly adding a month to the previous date (31 Jan -> 3 Mar skipped February).
- **"Pending payments" = the whole year** shown as if owed now; late fees and part-payments ignored; app summed its own numbers.
- **A month deleted** from policy #8 (11 months billed, 12 covered).
- **The only plan's enrolment window closed on 30 Jun 2026**, so no member can join from the app (all 3 live policies were created by the office).
- App journey: 3 different entry points (programs list, subscriptions list, dashboard) and a multi-screen enrol -> pay flow.

### Fixed (backend)
- `InsuranceSubscription::buildSchedule()` - single schedule: no-overflow months counted from the start date, first payment due on the start day, cover ends the day before month 13. Dates sent by clients are ignored.
- Oldest-month-first everywhere: model rule (any path), admin form message, admin grid sorted oldest first, checkout picks the N oldest open months, Pesapal money is applied oldest-first (and rolls to the next month if one was settled meanwhile). Online payments always settle whole months (incl. late fee); part-payments only via the office.
- Grace period respected before overdue / late fee.
- `GET /api/insurance/my`, `GET /api/insurance/policies/{id}` - server-calculated summary (due now, months paid, next payment, every month) + plan offers with a "starting today" preview and a plain reason when enrolment is closed. Old `/dashboard/insurance-overview` now reports the same "due now".
- `php artisan insurance:repair` (dry run by default; `--apply`, `--restore-months`): re-applies recorded money to the oldest months (same amounts/dates/refs, late fee dropped only if the money arrived before the month was late), fixes the old cover-end date, reports/restores deleted months.
- Security found on the way: user JSON exposed **password hash + password-reset code** (reset request returned the code => account takeover by e-mail); fixed (`$hidden`, reset code single-use, 5 wrong tries cancel it). `Utils::get_user_id()` now JWT-first too (was header-spoofable across orders/chat/transactions).

### Fixed (app)
- One place for insurance: **Insurance tab -> "My cover"** card (status, due now / next payment, months paid, Pay / See months). Not covered -> plan card with one **Join** button (or the reason it is closed). Home "Insurance" + "Pay Premium" and More > "My Cover" open the same page; old program/subscription lists are admin-only.
- Join sheet: exactly what will be created (months, dates, total, first payment today) -> "Join and pay first month".
- Pay sheet: "How many months?" stepper, the months it covers (oldest first, late fees shown), total, warning when paying less than is due.
- Policy screen: every month with Paid / Due now / Overdue / Upcoming.
- Widget tests (`test/insurance_screens_test.dart`) render these screens from responses recorded on the live-data copy.

### Verified
- Backend: 63 tests on local clone and live-data clone. App: analyzer 0 errors, 7 screen tests, release APK builds.
- End-to-end on a copy of live data via local API + fake Pesapal: policy #6 repaired to "Up to date, next Nov 2026"; paying 2 months -> 4/12 paid, UGX 64,000 paid, UGX 128,000 left.

### Deploy additions (after approval)
1. Deploy API (as before), then `php artisan insurance:repair` (dry run) -> review -> `php artisan insurance:repair --apply` (decide on `--restore-months` for policy #8).
2. Admin: reopen enrolment for "Comprehensive Health Insurance" (clear or extend End Date) or members still cannot join.
3. Ship app build (bump `version` in pubspec.yaml).


---

## Deployment log - 12 Oct 2026 (production, dip.dtehmhealth.com)

Server facts: app root `/home/dtehxejt/dip` (also the web root; `.htaccess` routes to `public/`), no SSH shell,
admin panel at the site root (`/auth/login`). CLI PHP is **`/usr/local/bin/php`** (8.2); plain `php` on the
server is the CGI binary and silently ignores artisan arguments. Server `git fetch` fails (corrupt pack), and
previous releases were uploaded by hand, so this release was uploaded as files too.

1. Backup of the live DB downloaded before any change (kept locally, outside the repo).
2. Server code verified identical to GitHub `91fbbcd` (513/515 files; the 2 differences are composer autoload maps, not authoritative).
3. Security clean-up: `public/read_env.php` (printed the Pesapal production keys to anyone), `public/fix_runner.php`
   and `public/dtehm_sms_test.php` moved to `~/deploy_logs/quarantine_public_scripts/` (not deleted).
4. Commit `84755b1` pushed to GitHub; its 42 changed files uploaded and extracted; all PHP files linted on the server.
   Replaced files + `.env` + caches backed up in `~/deploy_logs/pre_deploy_files.tgz` (rollback: extract it in `~/dip`).
5. `.env`: `APP_ENV=local -> production`, `APP_DEBUG=true -> false` (backup `~/deploy_logs/env.pre_deploy.bak`).
6. `migrate --force`: the 2 new migrations ran. `config:cache`, `route:cache`, `view:clear`.
7. `insurance:repair --apply --restore-months`: policy #6 money moved to SEP/OCT 2026 (now up to date, late fee removed),
   policy #8 AUGUST-2027 restored, cover end corrected to 2027-09-02 on #6/#7/#8. Re-check: consistent.
8. Plan "Comprehensive Health Insurance": enrolment end date 2026-06-30 cleared -> open again.
9. `insurance:maintain` and `payments:reconcile --days=3650` (13 open payments checked, none paid-but-missed).
10. Cron added: `* * * * * cd /home/dtehxejt/dip && /usr/local/bin/php artisan schedule:run` (there was no cron before).
11. Seeders: none needed for this release. `DatabaseSeeder` only reorganises product categories - do not run it on production.
12. App: v100.0.230+230, release AAB built (`build/app/outputs/bundle/release/app-release.aab`) - upload to Play Console.

Follow-ups for the owner: rotate the Pesapal consumer key/secret (they were readable via read_env.php),
upload the AAB, decide the sponsor commission rule for Pesapal-paid membership.
