<?php

namespace App\Models;

use App\Enums\ProgressStatus;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\Contracts\HasAppAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'department_id',
    'employee_number',
    'job_title',
    'certificate_name',
    'theme_preference',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ---------------------------------------------------------------- access

    /**
     * Gate for the Filament panel. Employees have no business in /admin, and
     * this is enforced here rather than by hiding the link — see also the
     * `admin` middleware on the panel itself.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && $this->hasAnyRole([Role::Admin->value, Role::Manager->value]);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin->value);
    }

    public function isManager(): bool
    {
        return $this->hasRole(Role::Manager->value);
    }

    public function isEmployee(): bool
    {
        return $this->hasRole(Role::Employee->value);
    }

    /**
     * Department ids this user is allowed to see other people's data for.
     * Admins get everything; managers get the departments they run; everyone
     * else gets nothing. Reporting queries scope on this rather than trusting
     * a request parameter.
     *
     * @return array<int, int>
     */
    public function visibleDepartmentIds(): array
    {
        if ($this->isAdmin()) {
            return Department::query()->pluck('id')->all();
        }

        if ($this->isManager()) {
            return $this->managedDepartments()->pluck('departments.id')->all();
        }

        return [];
    }

    // --------------------------------------------------------- relationships

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function managedDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_manager')->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_enrollments')
            ->withPivot(['source', 'due_at', 'enrolled_at', 'deleted_at'])
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

    public function courseProgress(): HasMany
    {
        return $this->hasMany(CourseProgress::class);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function supportCases(): HasMany
    {
        return $this->hasMany(SupportCase::class);
    }

    // ------------------------------------------------------------ accessors

    /**
     * The name that goes on a certificate. People often go by a short name day
     * to day and a full one on paper.
     */
    public function certificateName(): string
    {
        return $this->certificate_name ?: $this->name;
    }

    public function completedCoursesCount(): int
    {
        return $this->courseProgress()
            ->where('status', ProgressStatus::Completed->value)
            ->count();
    }
}
