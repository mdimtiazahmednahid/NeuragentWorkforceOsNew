<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $guarded = ['id'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'joining_date' => 'datetime',
            'password_changed_at' => 'datetime',
            'suspended_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_default_password' => 'boolean',
            'must_change_password' => 'boolean',
            'is_active' => 'boolean',
            'bank_account_details' => 'array',
            'mobile_banking_details' => 'array',
        ];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function roleModel()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class);
    }

    public function whatsappNotifications(): HasMany
    {
        return $this->hasMany(WhatsAppNotification::class);
    }

    public function salary()
    {
        return $this->hasOne(Salary::class);
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function contributionPoints()
    {
        return $this->hasMany(ContributionPoint::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function projectMemberships()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    public function hasRole($roleName)
    {
        return $this->role === $roleName || ($this->roleModel && $this->roleModel->name === $roleName);
    }

    public function hasPermission($permission)
    {
        // SUPER_ADMIN overrides all
        if ($this->hasRole('SUPER_ADMIN')) {
            return true;
        }

        if ($this->roleModel && is_array($this->roleModel->permissions)) {
            return in_array($permission, $this->roleModel->permissions);
        }

        return false;
    }

    public function getRoleNameAttribute()
    {
        return $this->roleModel?->name ?? $this->role;
    }

    public function getIsAdminAttribute()
    {
        return in_array($this->roleName, ['SUPER_ADMIN', 'ADMIN', 'CEO', 'COO']);
    }

    public function getIsManagementAttribute()
    {
        return in_array($this->roleName, ['SUPER_ADMIN', 'ADMIN', 'CEO', 'COO', 'HR']);
    }

    public function getIsProjectManagerAttribute()
    {
        return in_array($this->roleName, ['SUPER_ADMIN', 'ADMIN', 'CEO', 'COO', 'PROJECT_MANAGER', 'MANAGER']);
    }

    public function getIsLeadAttribute()
    {
        return in_array($this->roleName, ['SUPER_ADMIN', 'ADMIN', 'CEO', 'COO', 'PROJECT_MANAGER', 'MANAGER', 'TEAM_LEAD', 'LEAD']);
    }
}
