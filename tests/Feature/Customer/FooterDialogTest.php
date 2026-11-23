<?php

namespace Tests\Feature\Customer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_renders_dialog_popups_with_callout_and_numbered_badges(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('footer-dialog');
        $response->assertSee('footer-dialog__callout');
        $response->assertSee('footer-dialog__badge');
        $response->assertSee('footer-dialog__heading');

        // Check documents title and section badges
        $response->assertSee('Cara Penyewaan');
        $response->assertSee('01');
        $response->assertSee('02');
        $response->assertSee('Pertanyaan Umum (FAQ)');
        $response->assertSee('Syarat &amp; Ketentuan', false);
        $response->assertSee('Kebijakan Privasi');
        $response->assertSee('Jaminan KTP &amp; Pengembalian', false);
        $response->assertSee('Kebijakan Pembatalan');
    }

    public function test_footer_has_no_empty_hash_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $this->assertStringNotContainsString('href="#"', $response->getContent());
    }
}
