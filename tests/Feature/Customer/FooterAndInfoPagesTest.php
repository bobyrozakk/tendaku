<?php

namespace Tests\Feature\Customer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FooterAndInfoPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_replaces_testimonials_with_cara_penyewaan_section(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('id="cara-penyewaan"', false);
        $response->assertSee('Cara Penyewaan pada Tendaku');
        $response->assertSee('Pilih Alat &amp; Tentukan Jadwal', false);
        $response->assertSee('Serah Terima &amp; Jaminan KTP Asli', false);
        $response->assertSee('Kembalikan Alat &amp; Ambil KTP', false);

        // Testimonial lama dan rating bintang dihapus
        $response->assertDontSee('Dari Para Camper');
        $response->assertDontSee('Dipercaya Komunitas Kami');

        // Tombol Back-to-Top di kanan bawah beranda
        $response->assertSee('id="back-to-top-btn"', false);
        $response->assertSee('Kembali ke atas');
    }

    public function test_footer_displays_real_contact_data_for_malang(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Malang, Jawa Timur, Indonesia');
        $response->assertSee('admintendaku@gmail.com');
        $response->assertSee('0812-3456-7890');

        // Pastikan data lama tidak muncul lagi
        $response->assertDontSee('Jawa Tengah, Indonesia');
        $response->assertDontSee('support@tendaku.id');
    }

    public function test_footer_has_only_whatsapp_and_no_instagram_or_tiktok(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('WhatsApp');
        $response->assertDontSee('Instagram');
        $response->assertDontSee('TikTok');
    }

    public function test_footer_bottom_has_no_duplicate_legal_links(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Seluruh hak cipta dilindungi');
        $response->assertDontSee('Syarat Penggunaan');
    }

    public function test_layanan_page_renders_successfully_with_all_five_sections(): void
    {
        $response = $this->get(route('layanan'));

        $response->assertOk();
        $response->assertSee('id="sewa-tenda"', false);
        $response->assertSee('id="sewa-matras"', false);
        $response->assertSee('id="perlengkapan-camping"', false);
        $response->assertSee('id="panduan-penyewaan"', false);
        $response->assertSee('id="faq"', false);

        $response->assertSee('Sewa Tenda Camping');
        $response->assertSee('Sewa Matras');
        $response->assertSee('Perlengkapan Camping Lengkap');
        $response->assertSee('Panduan Langkah Penyewaan');
        $response->assertSee('Pertanyaan yang Sering Diajukan (FAQ)');
    }

    public function test_bantuan_page_renders_successfully_with_all_five_sections(): void
    {
        $response = $this->get(route('bantuan'));

        $response->assertOk();
        $response->assertSee('id="syarat-ketentuan"', false);
        $response->assertSee('id="kebijakan-privasi"', false);
        $response->assertSee('id="deposit-pengembalian"', false);
        $response->assertSee('id="kebijakan-pembatalan"', false);
        $response->assertSee('id="panduan-penggunaan"', false);

        $response->assertSee('Syarat &amp; Ketentuan Sewa', false);
        $response->assertSee('Kebijakan Privasi');
        $response->assertSee('Jaminan KTP &amp; Prosedur Pengembalian', false);
        $response->assertSee('Kebijakan Pembatalan &amp; Force Majeure', false);
        $response->assertSee('Panduan Penggunaan &amp; Etika Outdoor', false);
    }

    public function test_hubungi_kami_page_renders_successfully_with_malang_contact_details(): void
    {
        $response = $this->get(route('hubungi-kami'));

        $response->assertOk();
        $response->assertSee('Hubungi Tim Tendaku.id');
        $response->assertSee('Malang, Jawa Timur, Indonesia');
        $response->assertSee('admintendaku@gmail.com');
        $response->assertSee('0812-3456-7890');
        $response->assertSee('Kirim Pesan atau Pertanyaan');
    }
}
