<?php

namespace Tests\Feature;

use App\Models\RoutingSlip;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoutingSlipUploadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_routing_slip_accepts_multiple_pdf_files()
    {
        Storage::fake('local');

        $user = User::where('role', 'super_user')->first() ?? User::first();

        $response = $this->actingAs($user)->post(route('storeSlip'), [
            'ctrl_no'       => 987654,
            'user_id'       => $user->id,
            'source'        => 'Test source',
            'subject'       => 'Multi file routing slip',
            'trans_remarks' => 'For testing',
            'r_destination' => 'Test Destination',
            'route_status'  => '1',
            'received_name' => 'Test Receiver',
            'date_received' => now()->toDateString(),
            'document'      => [
                UploadedFile::fake()->create('one.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('two.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('viewSlip'));

        $slip = RoutingSlip::where('subject', 'Multi file routing slip')->latest('id')->first();

        $this->assertNotNull($slip);
        $this->assertSame('one.pdf', $slip->document);
        $this->assertCount(2, $slip->files);
        Storage::disk('local')->assertExists('documents/one.pdf');
        Storage::disk('local')->assertExists('documents/two.pdf');
    }

    public function test_update_routing_slip_adds_files_without_replacing_existing_ones()
    {
        Storage::fake('local');

        $user = User::where('role', 'super_user')->first() ?? User::first();

        $this->actingAs($user)->post(route('storeSlip'), [
            'ctrl_no'       => 987655,
            'user_id'       => $user->id,
            'source'        => 'Test source',
            'subject'       => 'Routing slip update attachments',
            'trans_remarks' => 'For testing',
            'r_destination' => 'Test Destination',
            'route_status'  => '1',
            'received_name' => 'Test Receiver',
            'date_received' => now()->toDateString(),
            'document'      => [
                UploadedFile::fake()->create('primary.pdf', 100, 'application/pdf'),
            ],
        ]);

        $slip = RoutingSlip::where('subject', 'Routing slip update attachments')->latest('id')->first();
        $this->assertNotNull($slip);
        $this->assertCount(1, $slip->files);

        $response = $this->actingAs($user)->put(route('updateSlip', $slip->id), [
            'op_ctrl'       => 987655,
            'user_id'       => $user->id,
            'pres_dept'     => 'Records',
            'subject'       => 'Routing slip update attachments',
            'trans_remarks' => 'Updated for testing',
            'route_status'  => '1',
            'received_name' => ['Test Receiver'],
            'document'      => [
                UploadedFile::fake()->create('extra-one.pdf', 100, 'application/pdf'),
                UploadedFile::fake()->create('extra-two.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('viewSlip'));

        $slip->refresh();

        $this->assertSame('extra-one.pdf', $slip->document);
        $this->assertCount(2, $slip->files);
        Storage::disk('local')->assertExists('documents/extra-one.pdf');
        Storage::disk('local')->assertExists('documents/extra-two.pdf');
    }
}
