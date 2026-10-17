<?php

namespace App\Models;

use App\Services\AffiliateNotificationClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Password;
use Throwable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function sendPasswordResetNotification($token): void
    {
        // Build the link from the trusted portal origin, never the incoming Host header.
        $url = 'https://stellarafi.com'.route('affiliate.password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false);

        try {
            app(AffiliateNotificationClient::class)->sendPasswordReset(
                $this->getEmailForPasswordReset(),
                $url,
                (int) config('auth.passwords.users.expire', 60),
                'password-reset-'.hash('sha256', $token),
                (string) $this->getKey(),
            );
        } catch (Throwable $exception) {
            // A failed delivery must not leave the user waiting on an unusable token.
            Password::broker('users')->deleteToken($this);
            throw $exception;
        }
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'affiliate_admin_role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function affiliate()
    {
        return $this->hasOne(Affiliate::class, 'external_user_id', 'id');
    }

    public function affiliateAdminRole(): ?string
    {
        $email = strtolower((string) $this->email);
        $environmentOwners = (array) config('affiliate.admin_emails', []);

        if (in_array($email, $environmentOwners, true)) {
            return 'super_admin';
        }

        $role = trim((string) $this->affiliate_admin_role);

        return in_array($role, ['super_admin', 'admin', 'finance', 'analyst'], true) ? $role : null;
    }

    public function hasAffiliateAdminAccess(): bool
    {
        return $this->affiliateAdminRole() !== null;
    }

    public function canManageAffiliateProgram(): bool
    {
        return in_array($this->affiliateAdminRole(), ['super_admin', 'admin'], true);
    }

    public function canManageAffiliateCommissions(): bool
    {
        return in_array($this->affiliateAdminRole(), ['super_admin', 'admin', 'finance'], true);
    }

    public function canManageAffiliateRoles(): bool
    {
        return $this->affiliateAdminRole() === 'super_admin';
    }

    public function isEnvironmentAffiliateOwner(): bool
    {
        return in_array(strtolower((string) $this->email), (array) config('affiliate.admin_emails', []), true);
    }
}
