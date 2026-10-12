<?php

namespace Tests\Feature\Insurance;

use App\Models\InsuranceSubscription;
use App\Models\InsuranceSubscriptionPayment;
use Carbon\Carbon;

class ScheduleAndSummaryTest extends InsuranceTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_twelve_months_from_today_are_this_month_to_eleven_months_ahead()
    {
        $program = $this->makeProgram(['duration_months' => 12]);
        $s = InsuranceSubscription::buildSchedule(Carbon::parse('2026-10-12'), $program);

        $this->assertCount(12, $s['periods']);
        $this->assertEquals('2026-10-12', $s['start_date']);
        $this->assertEquals('2027-10-11', $s['end_date'], 'cover ends the day before the 13th month');
        $this->assertEquals('OCTOBER-2026', $s['periods'][0]['name']);
        $this->assertEquals('SEPTEMBER-2027', $s['periods'][11]['name']);
        $this->assertEquals('2026-10-12', $s['periods'][0]['due'], 'first premium due on the start date');
        $this->assertEquals('2026-11-11', $s['periods'][0]['end']);
        $this->assertEquals('2027-10-11', $s['periods'][11]['end']);
    }

    public function test_month_end_start_never_skips_or_doubles_a_month()
    {
        $program = $this->makeProgram(['duration_months' => 12]);
        foreach (['2026-01-31', '2026-01-29', '2026-03-31', '2026-08-31', '2024-02-29'] as $start) {
            $s = InsuranceSubscription::buildSchedule(Carbon::parse($start), $program);
            $names = array_column($s['periods'], 'name');
            $this->assertCount(12, $names, "start {$start}");
            $this->assertCount(12, array_unique($names), "start {$start} produced a duplicate month");
            for ($i = 1; $i < 12; $i++) {
                $prevEnd = Carbon::parse($s['periods'][$i - 1]['end']);
                $this->assertEquals($prevEnd->addDay()->format('Y-m-d'), $s['periods'][$i]['start'], "gap/overlap after period {$i} for start {$start}");
            }
        }
        $jan31 = InsuranceSubscription::buildSchedule(Carbon::parse('2026-01-31'), $program);
        $this->assertEquals('FEBRUARY-2026', $jan31['periods'][1]['name']);
        $this->assertEquals('2026-02-28', $jan31['periods'][1]['due']);
        $this->assertEquals('2026-03-31', $jan31['periods'][2]['due'], 'back to the 31st, no drift');
    }

    public function test_other_frequencies_cover_the_whole_duration()
    {
        $q = InsuranceSubscription::buildSchedule(Carbon::parse('2026-02-15'), $this->makeProgram(['billing_frequency' => 'Quarterly', 'duration_months' => 12]));
        $this->assertCount(4, $q['periods']);
        $a = InsuranceSubscription::buildSchedule(Carbon::parse('2026-02-15'), $this->makeProgram(['billing_frequency' => 'Annually', 'duration_months' => 24]));
        $this->assertCount(2, $a['periods']);
        $w = InsuranceSubscription::buildSchedule(Carbon::parse('2026-10-12'), $this->makeProgram(['billing_frequency' => 'Weekly', 'billing_day' => 1, 'duration_months' => 1]));
        $this->assertEquals('2026-11-11', end($w['periods'])['end']);
        $this->assertCount(5, $w['periods']);
    }

    public function test_enrolling_today_creates_the_same_schedule_the_app_previewed()
    {
        $program = $this->makeProgram(['duration_months' => 12, 'premium_amount' => 16000]);
        $me = $this->makeUser();
        $h = $this->headersFor($me);

        $offer = collect($this->getJson('/api/insurance/my', $h)->assertStatus(200)->json('data.programs'))->firstWhere('id', $program->id);
        $this->assertTrue($offer['enrollment_open']);
        $this->assertEquals(12, $offer['preview']['payments']);
        $this->assertEquals(192000, $offer['preview']['total_amount']);
        $this->assertEquals(now()->format('M Y'), $offer['preview']['first_month']);
        $this->assertEquals(now()->addMonthsNoOverflow(11)->format('M Y'), $offer['preview']['last_month']);

        $id = $this->json('POST', '/api/insurance-subscriptions', ['insurance_program_id' => $program->id, 'start_date' => now()->toDateString()], $h)->assertStatus(201)->json('data.id');
        $policy = $this->getJson('/api/insurance/policies/' . $id, $h)->assertStatus(200)->json('data');

        $this->assertEquals($offer['preview']['first_month'], $policy['first_month']);
        $this->assertEquals($offer['preview']['last_month'], $policy['last_month']);
        $this->assertEquals($offer['preview']['end_date'], $policy['end_date']);
        $this->assertEquals(12, $policy['months_total']);
        $this->assertCount(12, $policy['months']);
        $this->assertEquals(192000, $policy['total_amount']);
        $this->assertEquals(16000, $policy['due_now_amount'], 'only the first month is due today');
        $this->assertEquals(1, $policy['due_now_months']);
        $this->assertEquals('due', $policy['state']);
        $this->assertEquals('due', $policy['months'][0]['status']);
        $this->assertEquals('upcoming', $policy['months'][1]['status']);

        // client-sent dates can never stretch or shrink the cover
        $sub = InsuranceSubscription::find($id);
        $this->assertEquals($offer['preview']['end_date'], $sub->end_date->format('Y-m-d'));
    }

    public function test_summary_due_now_is_overdue_plus_current_with_fees_not_the_whole_year()
    {
        $program = $this->makeProgram(['duration_months' => 12, 'premium_amount' => 10000, 'grace_period_days' => 0, 'late_payment_penalty' => 2000, 'penalty_type' => 'Fixed']);
        $me = $this->makeUser();
        $h = $this->headersFor($me);
        $id = $this->json('POST', '/api/insurance-subscriptions', ['insurance_program_id' => $program->id, 'start_date' => now()->toDateString()], $h)->json('data.id');

        $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $id)->orderBy('due_date')->get();
        $rows[0]->forceFill(['due_date' => now()->subDays(40)->toDateString()])->saveQuietly();
        $rows[1]->forceFill(['due_date' => now()->subDays(10)->toDateString()])->saveQuietly();
        $rows[2]->forceFill(['due_date' => now()->toDateString()])->saveQuietly();
        $this->artisan('insurance:maintain')->assertExitCode(0);

        $p = $this->getJson('/api/insurance/policies/' . $id, $h)->json('data');
        $this->assertEquals('overdue', $p['state']);
        $this->assertEquals('2 months overdue', $p['state_label']);
        $this->assertEquals(3, $p['due_now_months']);
        $this->assertEquals(34000, $p['due_now_amount'], '2 overdue months with fees + this month');
        $this->assertEquals(4000, $p['late_fees']);
        $this->assertEquals(124000, $p['balance']);

        $my = $this->getJson('/api/insurance/my', $h)->json('data');
        $this->assertEquals(34000, $my['totals']['due_now_amount']);

        // the old Insurance tab endpoint now reports the same "due now", not the whole year
        $ov = $this->getJson('/api/dashboard/insurance-overview', $h)->json('data');
        $this->assertEquals(34000, (float) $ov['pending_payments']);
    }

    public function test_closed_plan_explains_why_and_cannot_be_joined()
    {
        $program = $this->makeProgram(['start_date' => '2025-07-01', 'end_date' => now()->subMonth()->toDateString()]);
        $me = $this->makeUser();
        $h = $this->headersFor($me);
        $offer = collect($this->getJson('/api/insurance/my', $h)->json('data.programs'))->firstWhere('id', $program->id);
        $this->assertFalse($offer['enrollment_open']);
        $this->assertStringContainsString('closed', $offer['closed_reason']);
        $this->json('POST', '/api/insurance-subscriptions', ['insurance_program_id' => $program->id, 'start_date' => now()->toDateString()], $h)->assertStatus(400);
    }

    public function test_repair_moves_money_recorded_on_late_months_to_the_oldest_months()
    {
        $program = $this->makeProgram(['duration_months' => 12, 'premium_amount' => 16000, 'grace_period_days' => 7, 'late_payment_penalty' => 2000, 'penalty_type' => 'Fixed']);
        $me = $this->makeUser();
        $id = $this->json('POST', '/api/insurance-subscriptions', ['insurance_program_id' => $program->id, 'start_date' => now()->subDays(40)->toDateString()], $this->headersFor($this->makeAdmin()) + [])->assertStatus(422);

        // reproduce the live situation directly: policy started 40 days ago, LAST two months marked paid
        $sub = InsuranceSubscription::create(['user_id' => $me->id, 'insurance_program_id' => $program->id, 'start_date' => now()->subDays(40)->toDateString(), 'status' => 'Active', 'payment_status' => 'Current', 'coverage_status' => 'Active']);
        $rows = InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderBy('due_date')->get();
        InsuranceSubscriptionPayment::$allowOutOfOrder = true;
        try {
            foreach ([$rows[10], $rows[11]] as $r) {
                $r->update(['paid_amount' => 16000, 'payment_status' => 'Paid', 'payment_date' => $rows[0]->due_date->toDateString()]);
            }
        } finally {
            InsuranceSubscriptionPayment::$allowOutOfOrder = false;
        }
        $this->artisan('insurance:maintain')->assertExitCode(0);
        $this->assertEquals('Overdue', $rows[0]->fresh()->payment_status);

        $this->artisan('insurance:repair', ['--policy' => $sub->id])->assertExitCode(0);
        $this->assertEquals('Overdue', $rows[0]->fresh()->payment_status, 'dry run changes nothing');

        $this->artisan('insurance:repair', ['--policy' => $sub->id, '--apply' => true])->assertExitCode(0);
        $this->assertEquals('Paid', $rows[0]->fresh()->payment_status);
        $this->assertEquals(0, (float) $rows[0]->fresh()->penalty_amount, 'money paid on time: no late fee');
        $this->assertEquals('Paid', $rows[1]->fresh()->payment_status);
        $this->assertEquals('Pending', $rows[11]->fresh()->payment_status);
        $this->assertEquals(32000, (float) $sub->fresh()->total_paid);
        $this->assertEquals('Current', $sub->fresh()->payment_status);
    }

    public function test_repair_corrects_old_off_by_one_cover_end_without_shortening_a_policy_with_a_deleted_month()
    {
        $program = $this->makeProgram(['duration_months' => 12]);
        $me = $this->makeUser();
        $sub = InsuranceSubscription::create(['user_id' => $me->id, 'insurance_program_id' => $program->id, 'start_date' => '2026-09-03', 'status' => 'Active', 'payment_status' => 'Current', 'coverage_status' => 'Active']);
        // simulate a policy from the old code: end stored one day late, last month deleted by someone
        \DB::table('insurance_subscriptions')->where('id', $sub->id)->update(['end_date' => '2027-09-03', 'coverage_end_date' => '2027-09-03']);
        InsuranceSubscriptionPayment::where('insurance_subscription_id', $sub->id)->orderByDesc('due_date')->first()->delete();

        $this->artisan('insurance:repair', ['--policy' => $sub->id, '--apply' => true])->assertExitCode(0);
        $this->assertEquals('2027-09-02', $sub->fresh()->end_date->format('Y-m-d'));

        $p = $this->getJson('/api/insurance/policies/' . $sub->id, $this->headersFor($me))->json('data');
        $this->assertEquals(11, $p['months_total'], 'deleted month is reported, not silently restored');

        $this->artisan('insurance:repair', ['--policy' => $sub->id, '--apply' => true, '--restore-months' => true])->assertExitCode(0);
        $p = $this->getJson('/api/insurance/policies/' . $sub->id, $this->headersFor($me))->json('data');
        $this->assertEquals(12, $p['months_total']);
        $this->assertEquals('2026-09-03', $p['start_date']);
        $this->assertEquals('2027-09-02', $p['end_date']);
        $this->assertEquals('Sep 2026', $p['first_month']);
        $this->assertEquals('Aug 2027', $p['last_month']);
    }
}
