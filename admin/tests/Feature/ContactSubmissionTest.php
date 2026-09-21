<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactSubmissionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_web_contact_is_saved_once_with_created_timestamp(): void
    {
        Mail::fake();
        $before = DB::table('contacts')->count();

        $this->withoutMiddleware(VerifyCsrfToken::class)->post('/contact', [
            'name' => 'Contact Timestamp Test',
            'email' => 'contact-timestamp@example.test',
            'mobile' => '9876543210',
            'message' => 'Please share today\'s flower prices.',
        ])->assertOk();

        $this->assertSame($before + 1, DB::table('contacts')->count());
        $this->assertDatabaseHas('contacts', [
            'name' => 'Contact Timestamp Test',
            'email' => 'contact-timestamp@example.test',
        ]);
        $this->assertNotNull(DB::table('contacts')->where('email', 'contact-timestamp@example.test')->value('created_at'));
    }

    public function test_api_contact_is_saved_with_created_timestamp(): void
    {
        $this->postJson('/api/contact-us', [
            'name' => 'API Contact Timestamp Test',
            'email' => 'api-contact-timestamp@example.test',
            'mobile' => '9876543211',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertNotNull(DB::table('contacts')->where('email', 'api-contact-timestamp@example.test')->value('created_at'));
    }
}
