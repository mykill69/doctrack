<?php

namespace Tests\Feature;

use App\Models\Doctrack;
use App\Models\DoctrackFile;
use App\Models\LogsTracking;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DoctrackTransactionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_doctrack_with_multiple_files_and_ctrl_no()
    {
        Storage::fake('local');

        $creator = User::where('role', 'super_user')->first() ?? User::first();
        $recipient = User::where('id', '!=', $creator->id)->first();

        $response = $this->actingAs($creator)->post(route('storeDoctrack'), [
            'user_id'   => $creator->id,
            'doc_type'  => 'Correspondence',
            'doc_title' => 'Multi upload test document',
            'ctrl_no'   => 12345,
            'update_by' => [(string) $recipient->id],
            'file'      => [
                UploadedFile::fake()->create('alpha.pdf', 100),
                UploadedFile::fake()->create('beta.docx', 100),
                UploadedFile::fake()->create('gamma.png', 100),
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $slip = Doctrack::where('doc_title', 'Multi upload test document')->first();
        $this->assertNotNull($slip, 'No slip was created');
        $this->assertEquals(12345, $slip->ctrl_no, 'ctrl_no was not saved');

        // creator row + 1 recipient row
        $this->assertEquals(2, Doctrack::where('docslip_id', $slip->docslip_id)->count());
        $this->assertEquals(12345, Doctrack::where('docslip_id', $slip->docslip_id)->first()->ctrl_no);

        // ✅ one file record per uploaded file
        $files = DoctrackFile::where('docslip_id', $slip->docslip_id)->get();
        $this->assertCount(3, $files);

        // ✅ logs tracking keeps the file names
        $log = LogsTracking::where('docslip_id', $slip->docslip_id)->first();
        $this->assertNotNull($log->file_logs);
        $this->assertStringContainsString('alpha.pdf', $log->file_logs);
    }

    public function test_creator_can_delete_a_single_row_only()
    {
        $creator = User::where('role', 'super_user')->first() ?? User::first();
        $recipient = User::where('id', '!=', $creator->id)->first();

        // build a transaction with 2 rows
        $this->actingAs($creator)->post(route('storeDoctrack'), [
            'user_id'   => $creator->id,
            'doc_type'  => 'Correspondence',
            'doc_title' => 'Row delete test document',
            'update_by' => [(string) $recipient->id],
        ])->assertStatus(200);

        $slip = Doctrack::where('doc_title', 'Row delete test document')->first();
        $this->assertNotNull($slip);
        $this->assertEquals(2, Doctrack::where('docslip_id', $slip->docslip_id)->count());

        $recipientRow = Doctrack::where('docslip_id', $slip->docslip_id)
            ->whereNotNull('update_by')
            ->first();

        $this->actingAs($creator)
            ->delete(route('deleteSlipRow', ['id' => $recipientRow->id]))
            ->assertRedirect();

        // ✅ only the single row was removed, the transaction is still there
        $this->assertEquals(1, Doctrack::where('docslip_id', $slip->docslip_id)->count());
        $this->assertNotNull(Doctrack::where('docslip_id', $slip->docslip_id)->first());

        // ✅ no comment was added by the row deletion
        $this->assertEquals(0, LogsTracking::where('docslip_id', $slip->docslip_id)
            ->whereNotNull('comments')
            ->count());
    }

    public function test_non_creator_cannot_delete_a_row()
    {
        $creator = User::where('role', 'super_user')->first() ?? User::first();
        $recipient = User::where('id', '!=', $creator->id)->first();

        $this->actingAs($creator)->post(route('storeDoctrack'), [
            'user_id'   => $creator->id,
            'doc_type'  => 'Correspondence',
            'doc_title' => 'Row guard test document',
            'update_by' => [(string) $recipient->id],
        ])->assertStatus(200);

        $slip = Doctrack::where('doc_title', 'Row guard test document')->first();
        $row = Doctrack::where('docslip_id', $slip->docslip_id)->first();

        $this->actingAs($recipient)
            ->delete(route('deleteSlipRow', ['id' => $row->id]));

        $this->assertEquals(2, Doctrack::where('docslip_id', $slip->docslip_id)->count());
    }

    public function test_store_doctrack_update_with_multiple_files()
    {
        Storage::fake('local');

        $creator = User::where('role', 'super_user')->first() ?? User::first();
        $recipient = User::where('id', '!=', $creator->id)->first();

        $this->actingAs($creator)->post(route('storeDoctrack'), [
            'user_id'   => $creator->id,
            'doc_type'  => 'Correspondence',
            'doc_title' => 'Update multi upload document',
            'update_by' => [(string) $recipient->id],
        ])->assertStatus(200);

        $slip = Doctrack::where('doc_title', 'Update multi upload document')->first();

        $this->actingAs($recipient)->post(route('storeDoctrackUpdate'), [
            'user_id'    => $creator->id,
            'update_by'  => $recipient->id,
            'docslip_id' => $slip->docslip_id,
            'doc_type'   => 'Correspondence',
            'doc_title'  => 'Update multi upload document',
            'user_name'  => $creator->fname . ' ' . $creator->lname,
            'file'       => [
                UploadedFile::fake()->create('one.pdf', 100),
                UploadedFile::fake()->create('two.pdf', 100),
            ],
        ])->assertStatus(302);

        $files = DoctrackFile::where('docslip_id', $slip->docslip_id)->get();
        $this->assertCount(2, $files);
    }

    public function test_upload_file_endpoint_accepts_multiple_files()
    {
        Storage::fake('local');

        $creator = User::where('role', 'super_user')->first() ?? User::first();

        $this->actingAs($creator)->post(route('uploadDoctrackFile'), [
            'docslip_id' => 'CPSU99999999',
            'file'       => [
                UploadedFile::fake()->create('x.pdf', 50),
                UploadedFile::fake()->create('y.pdf', 50),
            ],
        ])->assertStatus(200);

        $this->assertCount(2, DoctrackFile::where('docslip_id', 'CPSU99999999')->get());
    }

    public function test_ctrl_no_column_is_returned_for_users_1235_and_56()
    {
        foreach ([1235, 56] as $allowedId) {
            $user = User::find($allowedId);
            if (!$user) {
                $this->markTestSkipped("User {$allowedId} does not exist.");
            }

            $response = $this->actingAs($user)->get(route('doctrackSlip.data'));
            $response->assertStatus(200);

            $rows = $response->json('data');
            $this->assertNotEmpty($rows, 'No rows returned for user ' . $allowedId);

            foreach ($rows as $row) {
                $this->assertStringContainsString('data-field="ctrl_no"', $row['ctrl_input'],
                    'User ' . $allowedId . ' is missing the CTRL # input');
            }
        }
    }

    public function test_dashboard_search_and_sorting_are_fast_and_correct()
    {
        $user = User::where('role', 'super_user')->first() ?? User::first();

        $columns = [
            ['data' => 'route_display', 'name' => 'route_id'],
            ['data' => 'date_received', 'name' => 'date_received'],
            ['data' => 'source', 'name' => 'source'],
            ['data' => 'subject', 'name' => 'subject'],
            ['data' => 'file_name', 'name' => 'file_name'],
            ['data' => 'updated_by', 'name' => 'updated_by'],
            ['data' => 'duration', 'name' => 'duration'],
        ];

        // ✅ The search must answer fast (it used to take 40+ seconds)
        $start = microtime(true);
        $response = $this->actingAs($user)->get(route('dashboard.data') . '?' . http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'search'  => ['value' => 'Payroll'],
            'columns' => $columns,
        ]));
        $response->assertStatus(200);
        $elapsed = microtime(true) - $start;

        $this->assertLessThan(5, $elapsed, 'The dashboard search is too slow (' . round($elapsed, 2) . 's)');

        $payload = $response->json();
        $this->assertLessThanOrEqual(
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            'recordsFiltered cannot be bigger than recordsTotal'
        );
        $this->assertLessThanOrEqual(25, count($payload['data']));
    }

    public function test_dashboard_sorting_applies_the_requested_column()
    {
        $user = User::where('role', 'super_user')->first() ?? User::first();

        $columns = [
            ['data' => 'route_display', 'name' => 'route_id'],
            ['data' => 'date_received', 'name' => 'date_received'],
            ['data' => 'source', 'name' => 'source'],
            ['data' => 'subject', 'name' => 'subject'],
        ];

        $response = $this->actingAs($user)->get(route('dashboard.data') . '?' . http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 25,
            'order'   => [['column' => 0, 'dir' => 'desc']],
            'columns' => $columns,
        ]));

        $response->assertStatus(200);

        $routeIds = array_map(fn($row) => (int) $row['route_id'], $response->json('data'));
        $sorted  = $routeIds;
        rsort($sorted);

        $this->assertSame($sorted, $routeIds, 'The dashboard is not sorted by the requested column');
    }

    public function test_doctrack_table_sorting_applies_the_requested_column()
    {
        $user = User::where('role', 'super_user')->first() ?? User::first();

        // The columns exactly as the blade sends them
        $columns = [
            ['data' => 'date_received', 'name' => 'created_at'],
            ['data' => 'source', 'name' => 'user_name'],
            ['data' => 'subject', 'name' => 'doc_title'],
            ['data' => 'status', 'name' => 'doctrack_stat'],
        ];

        $response = $this->actingAs($user)->get(route('doctrackSlip.data') . '?' . http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 50,
            'order'   => [['column' => 2, 'dir' => 'asc']],
            'columns' => $columns,
        ]));

        $response->assertStatus(200);

        $titles = array_map(fn($row) => $row['subject'], $response->json('data'));
        $sorted = $titles;
        usort($sorted, 'strcasecmp');

        $this->assertSame($sorted, $titles, 'The document slip table is not sorted by the requested column');
    }

    public function test_doctrack_table_action_taken_column_is_sortable()
    {
        $user = User::where('role', 'super_user')->first() ?? User::first();

        // The columns exactly as the blade sends them (ACTION TAKEN is index 5)
        $columns = [
            ['data' => 'date_received', 'name' => 'created_at'],
            ['data' => 'source', 'name' => 'user_name'],
            ['data' => 'subject', 'name' => 'doc_title'],
            ['data' => 'action_unit'],
            ['data' => 'received_by_date'],
            ['data' => 'action_taken', 'name' => 'action_taken'],
            ['data' => 'date_released', 'name' => 'updated_at'],
            ['data' => 'remarks'],
            ['data' => 'status', 'name' => 'doctrack_stat'],
        ];

        $response = $this->actingAs($user)->get(route('doctrackSlip.data') . '?' . http_build_query([
            'draw'    => 1,
            'start'   => 0,
            'length'  => 100,
            'order'   => [['column' => 5, 'dir' => 'asc']],
            'columns' => $columns,
        ]));

        $response->assertStatus(200);

        $names = array_map(fn($row) => strip_tags($row['action_taken']), $response->json('data'));

        $this->assertNotEmpty($names);

        $sorted = $names;
        usort($sorted, 'strcasecmp');

        $this->assertSame($sorted, $names, 'The ACTION TAKEN column is not sorted');
    }
}
