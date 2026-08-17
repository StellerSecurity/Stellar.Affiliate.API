<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AffiliatePasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_affiliate_can_change_own_password(): void
    {
        $user = User::create([
            'name' => 'Affiliate User',
            'email' => 'affiliate@example.com',
            'password' => Hash::make('old-password'),
        ]);

        Affiliate::create([
            'external_user_id' => $user->id,
            'name' => 'Affiliate User',
            'email' => 'affiliate@example.com',
            'public_code' => 'SELFTEST',
            'status' => 'active',
            'payout_currency' => 'EUR',
        ]);

        $response = $this->actingAs($user)->patch(route('affiliate.settings.password'), [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Your password has been updated.');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertFalse(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_affiliate_must_supply_current_password(): void
    {
        $user = User::create([
            'name' => 'Affiliate User',
            'email' => 'affiliate@example.com',
            'password' => Hash::make('old-password'),
        ]);

        Affiliate::create([
            'external_user_id' => $user->id,
            'name' => 'Affiliate User',
            'email' => 'affiliate@example.com',
            'public_code' => 'WRONGPASS',
            'status' => 'active',
            'payout_currency' => 'EUR',
        ]);

        $response = $this->actingAs($user)->from(route('affiliate.settings'))->patch(route('affiliate.settings.password'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertRedirect(route('affiliate.settings'));
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_admin_can_reset_linked_affiliate_password(): void
    {
        $admin = User::create([
            'name' => 'Program Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin-password'),
            'affiliate_admin_role' => 'admin',
        ]);

        $affiliateUser = User::create([
            'name' => 'Affiliate User',
            'email' => 'affiliate@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $affiliate = Affiliate::create([
            'external_user_id' => $affiliateUser->id,
            'name' => 'Affiliate User',
            'email' => 'affiliate@example.com',
            'public_code' => 'ADMINSET',
            'status' => 'active',
            'payout_currency' => 'EUR',
        ]);

        $response = $this->actingAs($admin)->patch(route('affiliate.admin.affiliates.password.update', $affiliate), [
            'password' => 'admin-set-password-123',
            'password_confirmation' => 'admin-set-password-123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Affiliate portal password updated.');
        $this->assertTrue(Hash::check('admin-set-password-123', $affiliateUser->fresh()->password));
    }

    public function test_admin_can_create_and_link_portal_login_when_affiliate_has_email(): void
    {
        $admin = User::create([
            'name' => 'Program Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin-password'),
            'affiliate_admin_role' => 'admin',
        ]);

        $affiliate = Affiliate::create([
            'name' => 'New Affiliate',
            'email' => 'new-affiliate@example.com',
            'public_code' => 'NEWLOGIN',
            'status' => 'active',
            'payout_currency' => 'EUR',
        ]);

        $response = $this->actingAs($admin)->patch(route('affiliate.admin.affiliates.password.update', $affiliate), [
            'password' => 'first-password-123',
            'password_confirmation' => 'first-password-123',
        ]);

        $response->assertRedirect();
        $portalUser = User::where('email', 'new-affiliate@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('first-password-123', $portalUser->password));
        $this->assertSame($portalUser->id, $affiliate->fresh()->external_user_id);
    }

    public function test_affiliate_password_endpoint_cannot_reset_admin_accounts(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => Hash::make('super-password'),
            'affiliate_admin_role' => 'super_admin',
        ]);

        $admin = User::create([
            'name' => 'Program Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin-password'),
            'affiliate_admin_role' => 'admin',
        ]);

        $affiliate = Affiliate::create([
            'external_user_id' => $superAdmin->id,
            'name' => 'Protected',
            'email' => 'super@example.com',
            'public_code' => 'PROTECTED',
            'status' => 'active',
            'payout_currency' => 'EUR',
        ]);

        $response = $this->actingAs($admin)->patch(route('affiliate.admin.affiliates.password.update', $affiliate), [
            'password' => 'should-not-work-123',
            'password_confirmation' => 'should-not-work-123',
        ]);

        $response->assertForbidden();
        $this->assertTrue(Hash::check('super-password', $superAdmin->fresh()->password));
    }
}
