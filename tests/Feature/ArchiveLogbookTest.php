<?php

namespace Tests\Feature;

use App\Models\ArchiveLog;
use App\Models\Doctrack;
use App\Models\Log;
use App\Models\RoutingSlip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArchiveLogbookTest extends TestCase
{
    use DatabaseTransactions;

    private const ARCHIVE_YEAR = 2020;

    private function recordsOfficer(): User
    {
        return User::findOrFail(56);
    }

    private function otherRecordsOfficer(): User
    {
        return User::where('role', 'records_officer')
            ->where('id', '!=', 56)
            ->firstOrFail();
    }

    private function administrator(): User
    {
        return User::where('role', 'Administrator')->firstOrFail();
    }

    private function createRoutingTransaction(string $rslipId, int $year = self::ARCHIVE_YEAR, array $overrides = []): void
    {
        DB::table('routing_slip')->insert(array_merge([
            'rslip_id' => $rslipId,
            'op_ctrl' => $rslipId,
            'user_id' => 56,
            'pres_dept' => 'RECORDS OFFICE',
            'source' => 'TEST SOURCE',
            'subject' => 'Archived routing transaction ' . $rslipId,
            'trans_remarks' => 'test remark',
            'other_remarks' => null,
            'r_destination' => 'PRESIDENT',
            'routed_users' => null,
            'assigned_to' => null,
            'assign_com' => null,
            'document' => null,
            'esig' => null,
            'received_name' => 'Test Receiver',
            'route_status' => 3,
            'date_received' => $year . '-06-15',
            'created_at' => $year . '-06-15 09:00:00',
            'updated_at' => $year . '-06-16 10:00:00',
            'is_archived' => 0,
        ], $overrides));
    }

    private function createDocumentTransaction(string $docslipId, int $year = self::ARCHIVE_YEAR, array $overrides = []): void
    {
        DB::table('doctrack_slip')->insert(array_merge([
            'docslip_id' => $docslipId,
            'ctrl_no' => 9000,
            'user_id' => 56,
            'update_by' => 56,
            'doc_type' => 'Correspondence',
            'doc_title' => 'Archived document transaction ' . $docslipId,
            'user_name' => 'RECORDS OFFICE',
            'doctrack_stat' => 6,
            'comments' => 'test comment',
            'created_at' => $year . '-07-10 08:00:00',
            'updated_at' => $year . '-07-11 09:00:00',
            'is_archived' => 0,
        ], $overrides));
    }

    public function test_archive_pages_require_the_authorized_account()
    {
        $authorized = $this->recordsOfficer();
        $otherOfficer = $this->otherRecordsOfficer();
        $administrator = $this->administrator();

        $this->get(route('archive.index'))->assertRedirect(route('getLogin'));

        $this->actingAs($authorized)->get(route('archive.index'))->assertStatus(200);
        $this->actingAs($authorized)->get(route('archive.history'))->assertStatus(200);

        foreach ([$otherOfficer, $administrator] as $user) {
            $this->actingAs($user)->get(route('archive.index'))->assertStatus(403);
            $this->actingAs($user)->get(route('archive.history'))->assertStatus(403);
            $this->actingAs($user)->get(route('archive.preview'))->assertStatus(403);
            $this->actingAs($user)->post(route('archive.store'))->assertStatus(403);
            $this->actingAs($user)->get(route('archive.history.routingData'))->assertStatus(403);
            $this->actingAs($user)->get(route('archive.history.documentData'))->assertStatus(403);
        }
    }

    public function test_archive_marks_records_without_deleting_or_rewriting_them()
    {
        $rslipId = '900101';
        $docslipId = 'CPSU900101';

        $this->createRoutingTransaction($rslipId);
        $this->createDocumentTransaction($docslipId);

        DB::table('logs')->insert([
            'user_id' => 56,
            'doc_id' => 1,
            'route_id' => $rslipId,
            'action' => 'routed',
            'status_update' => 2,
            'prev_file' => null,
            'new_file' => 'test.pdf',
            'new_destination' => 'PRESIDENT',
            'comments' => 'log comment',
            'assigned_to' => null,
            'created_at' => self::ARCHIVE_YEAR . '-06-15 09:05:00',
            'updated_at' => self::ARCHIVE_YEAR . '-06-15 09:05:00',
        ]);

        $routingBefore = (array) DB::table('routing_slip')->where('rslip_id', $rslipId)->first();
        $documentBefore = (array) DB::table('doctrack_slip')->where('docslip_id', $docslipId)->first();

        $response = $this->actingAs($this->recordsOfficer())->post(route('archive.store'), [
            'year' => self::ARCHIVE_YEAR,
            'type' => 'both',
        ]);

        $response->assertStatus(200)->assertJson(['status' => 'completed']);

        $routingAfter = (array) DB::table('routing_slip')->where('rslip_id', $rslipId)->first();
        $documentAfter = (array) DB::table('doctrack_slip')->where('docslip_id', $docslipId)->first();

        $this->assertEquals(1, $routingAfter['is_archived']);
        $this->assertEquals(1, $documentAfter['is_archived']);
        $this->assertEquals(56, $routingAfter['archived_by']);
        $this->assertEquals(56, $documentAfter['archived_by']);
        $this->assertNotNull($routingAfter['archived_at']);
        $this->assertNotNull($documentAfter['archived_at']);

        // The business data is preserved exactly as it was.
        foreach (['subject', 'source', 'pres_dept', 'trans_remarks', 'document', 'created_at'] as $column) {
            $this->assertSame($routingBefore[$column], $routingAfter[$column], "routing_slip.{$column} changed");
        }

        foreach (['doc_title', 'doc_type', 'user_name', 'comments', 'created_at'] as $column) {
            $this->assertSame($documentBefore[$column], $documentAfter[$column], "doctrack_slip.{$column} changed");
        }

        $this->assertSame(
            2,
            ArchiveLog::where('archive_year', self::ARCHIVE_YEAR)->count(),
            'One audit row per archived transaction type is expected.'
        );
    }

    public function test_archived_transactions_disappear_from_normal_queries_but_stay_in_the_database()
    {
        $rslipId = '900102';
        $docslipId = 'CPSU900102';

        $this->createRoutingTransaction($rslipId);
        $this->createDocumentTransaction($docslipId);

        DB::table('logs')->insert([
            'user_id' => 56,
            'doc_id' => 1,
            'route_id' => $rslipId,
            'action' => 'routed',
            'status_update' => 2,
            'prev_file' => null,
            'new_file' => 'test.pdf',
            'new_destination' => 'PRESIDENT',
            'comments' => null,
            'assigned_to' => null,
            'created_at' => self::ARCHIVE_YEAR . '-06-15 09:05:00',
            'updated_at' => self::ARCHIVE_YEAR . '-06-15 09:05:00',
        ]);

        $this->actingAs($this->recordsOfficer())->post(route('archive.store'), [
            'year' => self::ARCHIVE_YEAR,
            'type' => 'both',
        ])->assertStatus(200);

        $this->assertNull(RoutingSlip::where('rslip_id', $rslipId)->first());
        $this->assertNull(Doctrack::where('docslip_id', $docslipId)->first());
        $this->assertSame(0, Log::where('route_id', $rslipId)->count());

        // Still in the database and visible to the archive features.
        $this->assertEquals(1, DB::table('routing_slip')->where('rslip_id', $rslipId)->count());
        $this->assertEquals(1, DB::table('doctrack_slip')->where('docslip_id', $docslipId)->count());
        $this->assertEquals(1, DB::table('logs')->where('route_id', $rslipId)->count());
        $this->assertNotNull(RoutingSlip::archived()->where('rslip_id', $rslipId)->first());
        $this->assertNotNull(Doctrack::archived()->where('docslip_id', $docslipId)->first());
    }

    public function test_repeating_the_archive_does_not_archive_the_same_records_twice()
    {
        $rslipId = '900103';
        $this->createRoutingTransaction($rslipId);

        $officer = $this->recordsOfficer();
        $payload = ['year' => self::ARCHIVE_YEAR, 'type' => 'routing_slip'];

        $this->actingAs($officer)->post(route('archive.store'), $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'completed']);

        $firstArchivedAt = DB::table('routing_slip')->where('rslip_id', $rslipId)->value('archived_at');

        $second = $this->actingAs($officer)->post(route('archive.store'), $payload);

        $second->assertStatus(200);
        $this->assertSame('skipped', $second->json('status'));
        $this->assertSame(0, $second->json('records'));
        $this->assertSame(
            $firstArchivedAt,
            DB::table('routing_slip')->where('rslip_id', $rslipId)->value('archived_at')
        );

        $this->assertSame(
            1,
            DB::table('routing_slip')->where('rslip_id', $rslipId)->where('is_archived', 1)->count()
        );
    }

    public function test_archiving_one_type_does_not_archive_the_other_type()
    {
        $rslipId = '900104';
        $docslipId = 'CPSU900104';

        $this->createRoutingTransaction($rslipId);
        $this->createDocumentTransaction($docslipId);

        $this->actingAs($this->recordsOfficer())->post(route('archive.store'), [
            'year' => self::ARCHIVE_YEAR,
            'type' => 'routing_slip',
        ])->assertStatus(200);

        $this->assertEquals(1, DB::table('routing_slip')->where('rslip_id', $rslipId)->value('is_archived'));
        $this->assertEquals(0, DB::table('doctrack_slip')->where('docslip_id', $docslipId)->value('is_archived'));
    }

    public function test_archive_history_paginates_at_the_requested_length()
    {
        for ($index = 1; $index <= 30; $index++) {
            $this->createRoutingTransaction('91' . str_pad((string) $index, 4, '0', STR_PAD_LEFT), self::ARCHIVE_YEAR, [
                'is_archived' => 1,
                'archived_at' => now(),
                'archived_by' => 56,
            ]);
        }

        $officer = $this->recordsOfficer();

        $page = $this->actingAs($officer)->get(route('archive.history.routingData') . '?' . http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'columns' => [
                ['data' => 'ctrl_no'],
            ],
        ]));

        $page->assertStatus(200);
        $this->assertCount(25, $page->json('data'));
        $this->assertGreaterThanOrEqual(30, $page->json('recordsTotal'));
        $this->assertGreaterThanOrEqual(30, $page->json('recordsFiltered'));

        $secondPage = $this->actingAs($officer)->get(route('archive.history.routingData') . '?' . http_build_query([
            'draw' => 2,
            'start' => 25,
            'length' => 25,
            'columns' => [
                ['data' => 'ctrl_no'],
            ],
        ]));

        $secondPage->assertStatus(200);
        $this->assertGreaterThanOrEqual(5, count($secondPage->json('data')));

        $largePage = $this->actingAs($officer)->get(route('archive.history.routingData') . '?' . http_build_query([
            'draw' => 3,
            'start' => 0,
            'length' => 100,
            'columns' => [
                ['data' => 'ctrl_no'],
            ],
        ]));

        $largePage->assertStatus(200);
        $this->assertGreaterThanOrEqual(30, count($largePage->json('data')));
    }

    public function test_archive_history_search_and_filters_are_applied_server_side()
    {
        $this->createRoutingTransaction('920001', self::ARCHIVE_YEAR, [
            'is_archived' => 1,
            'archived_at' => now(),
            'archived_by' => 56,
        ]);
        $this->createRoutingTransaction('920002', self::ARCHIVE_YEAR, [
            'subject' => 'A completely different subject',
            'is_archived' => 1,
            'archived_at' => now(),
            'archived_by' => 56,
        ]);

        $response = $this->actingAs($this->recordsOfficer())
            ->get(route('archive.history.routingData') . '?' . http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 100,
                'ctrl_no' => '920001',
                'year' => self::ARCHIVE_YEAR,
                'columns' => [
                    ['data' => 'ctrl_no'],
                ],
            ]));

        $response->assertStatus(200);

        $rows = $response->json('data');
        $this->assertCount(1, $rows);
        $this->assertStringContainsString('920001', $rows[0]['ctrl_no']);

        $documentResponse = $this->actingAs($this->recordsOfficer())
            ->get(route('archive.history.documentData') . '?' . http_build_query([
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]));

        $documentResponse->assertStatus(200);
        $this->assertArrayHasKey('data', $documentResponse->json());
    }

    public function test_archived_detail_and_file_endpoints_are_read_only_and_protected()
    {
        $rslipId = '900105';
        $this->createRoutingTransaction($rslipId, self::ARCHIVE_YEAR, [
            'is_archived' => 1,
            'archived_at' => now(),
            'archived_by' => 56,
        ]);

        $officer = $this->recordsOfficer();

        $this->actingAs($officer)
            ->get(route('archive.history.routingShow', $rslipId))
            ->assertStatus(200)
            ->assertSee($rslipId);

        $this->actingAs($officer)
            ->get(route('archive.history.routingShow', '900999'))
            ->assertStatus(404);

        $this->actingAs($this->otherRecordsOfficer())
            ->get(route('archive.history.routingShow', $rslipId))
            ->assertStatus(403);
    }

    public function test_normal_transaction_pages_block_direct_access_to_archived_records()
    {
        $rslipId = '900106';
        $docslipId = 'CPSU900106';

        $this->createRoutingTransaction($rslipId, self::ARCHIVE_YEAR, [
            'is_archived' => 1,
            'archived_at' => now(),
            'archived_by' => 56,
        ]);
        $this->createDocumentTransaction($docslipId, self::ARCHIVE_YEAR, [
            'is_archived' => 1,
            'archived_at' => now(),
            'archived_by' => 56,
        ]);

        $officer = $this->recordsOfficer();

        $this->actingAs($officer)->get(route('slipForm', $rslipId))->assertStatus(404);
        $this->actingAs($officer)->get(route('pdfSlip', $rslipId))->assertStatus(404);
        $this->actingAs($officer)->get(route('slipMonitoring', $docslipId))->assertStatus(404);
        $this->actingAs($officer)->get(route('documents.tracking', ['route_id' => $rslipId]))->assertStatus(404);
    }

    public function test_archive_history_endpoints_handle_existing_transactions_with_history_and_files()
    {
        $officer = $this->recordsOfficer();
        $year = (int) now()->year;

        $routingGroup = DB::table('routing_slip')
            ->select('rslip_id')
            ->whereNotNull('created_at')
            ->groupBy('rslip_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('rslip_id')
            ->first();

        $documentGroup = DB::table('doctrack_slip')
            ->select('docslip_id')
            ->whereNotNull('created_at')
            ->groupBy('docslip_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('docslip_id')
            ->first();

        $this->assertNotNull($routingGroup, 'The database needs at least one routing transaction with multiple rows.');
        $this->assertNotNull($documentGroup, 'The database needs at least one document transaction with multiple rows.');

        DB::table('routing_slip')
            ->where('rslip_id', $routingGroup->rslip_id)
            ->update(['is_archived' => 1, 'archived_at' => now(), 'archived_by' => 56]);

        DB::table('doctrack_slip')
            ->where('docslip_id', $documentGroup->docslip_id)
            ->update(['is_archived' => 1, 'archived_at' => now(), 'archived_by' => 56]);

        $routing = $this->actingAs($officer)->get(route('archive.history.routingData') . '?' . http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'year' => $year,
            'ctrl_no' => $routingGroup->rslip_id,
            'columns' => [
                ['data' => 'ctrl_no'],
            ],
        ]));

        $routing->assertStatus(200);
        $this->assertNotEmpty($routing->json('data'));
        $this->assertArrayHasKey('validity', $routing->json('data.0'));
        $this->assertArrayHasKey('logbook_status', $routing->json('data.0'));

        $this->actingAs($officer)
            ->get(route('archive.history.routingShow', $routingGroup->rslip_id))
            ->assertStatus(200);

        $document = $this->actingAs($officer)->get(route('archive.history.documentData') . '?' . http_build_query([
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'year' => $year,
            'ctrl_no' => $documentGroup->docslip_id,
            'columns' => [
                ['data' => 'ctrl_no'],
            ],
        ]));

        $document->assertStatus(200);
        $this->assertNotEmpty($document->json('data'));
        $this->assertArrayHasKey('validity', $document->json('data.0'));
        $this->assertArrayHasKey('logbook_status', $document->json('data.0'));

        $this->actingAs($officer)
            ->get(route('archive.history.documentShow', $documentGroup->docslip_id))
            ->assertStatus(200);
    }
}
