<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_returns_ok(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_cgu_page_returns_ok(): void
    {
        $this->get(route('cgu'))->assertOk();
    }

    public function test_privacy_page_returns_ok(): void
    {
        $this->get(route('privacy'))->assertOk();
    }

    public function test_faq_page_returns_ok(): void
    {
        $this->get(route('faq.index'))->assertOk();
    }

    public function test_login_page_returns_ok(): void
    {
        $this->get(route('auth.login'))->assertOk();
    }

    public function test_register_page_returns_ok(): void
    {
        $this->get(route('auth.register'))->assertOk();
    }

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('auth.login'));
    }

    public function test_guest_is_redirected_from_tontines(): void
    {
        $this->get(route('tontines.index'))->assertRedirect(route('auth.login'));
    }
}
