<?php

namespace App\Models;

use App\Enums\ProgressStatus;
use App\Enums\Role;
use Database\Factories\UserFactory;
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
        return $this->is_active && $this->hasAnyRole([Role::Admin->value, Role::Trainer->value]);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::Admin->value);
    }

    public function isTrainer(): bool
    {
        return $this->hasRole(Role::Trainer->value);
    }

    public function isTrainee(): bool
    {
        return $this->hasRole(Role::Trainee->value);
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

        if ($this->isTrainer()) {
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

    // ------------------------------------------------------------- cohorts

    /**
     * The trainees this trainer is currently responsible for.
     *
     * `ended_at` null is the whole definition of "current" — closed assignments
     * stay in the table so a historical grade remains attributable to whoever
     * held the cohort at the time.
     */
    public function trainees(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'trainer_trainee', 'trainer_id', 'trainee_id')
            ->wherePivotNull('ended_at')
            ->withPivot(['assigned_at', 'assigned_by', 'ended_at'])
            ->withTimestamps();
    }

    /** Every trainee this trainer has ever held, including handed-over ones. */
    public function allTrainees(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'trainer_trainee', 'trainer_id', 'trainee_id')
            ->withPivot(['assigned_at', 'assigned_by', 'ended_at'])
            ->withTimestamps();
    }

    /** The trainer currently responsible for this trainee, if any. */
    public function trainers(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'trainer_trainee', 'trainee_id', 'trainer_id')
            ->wherePivotNull('ended_at')
            ->withPivot(['assigned_at', 'assigned_by', 'ended_at'])
            ->withTimestamps();
    }

    public function currentTrainer(): ?self
    {
        return $this->trainers()->first();
    }

    /**
     * Trainee ids this user may grade.
     *
     * Deliberately distinct from visibleDepartmentIds(): a trainer may READ
     * every trainee's transcript (transcripts.view-all) but may only GRADE
     * their own cohort. Those were one department-shaped rule before Phase 0
     * and are now two, because they answer different questions.
     *
     * @return array<int, int>
     */
    public function gradableTraineeIds(): array
    {
        if ($this->isAdmin()) {
            return self::query()->pluck('id')->all();
        }

        if ($this->isTrainer()) {
            return $this->trainees()->pluck('users.id')->all();
        }

        return [];
    }

    public function canGrade(self $trainee): bool
    {
        // Nobody marks their own paper, whatever their role.
        if ($this->is($trainee)) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $this->isTrainer()
            && $this->trainees()->whereKey($trainee->getKey())->exists();
    }

    // ---------------------------------------------------------- competency

    /** Every rung this person has been awarded, revoked ones included. */
    public function competencyLevels(): HasMany
    {
        return $this->hasMany(TraineeLevel::class);
    }

    /**
     * The highest rung held in one area, or null.
     *
     * This is the answer to "who is Level 1 in Sensors" from the person's side;
     * the reverse lookup goes through TraineeLevel directly.
     */
    public function levelIn(CompetencyArea|int $area): ?Level
    {
        $areaId = $area instanceof CompetencyArea ? $area->getKey() : $area;

        return Level::query()
            ->join('trainee_levels', 'trainee_levels.level_id', '=', 'levels.id')
            ->where('trainee_levels.user_id', $this->getKey())
            ->where('trainee_levels.competency_area_id', $areaId)
            ->whereNull('trainee_levels.revoked_at')
            ->orderByDesc('levels.position')
            ->select('levels.*')
            ->first();
    }

    public function holdsLevel(Level|int $level, CompetencyArea|int $area): bool
    {
        return $this->competencyLevels()
            ->active()
            ->where('level_id', $level instanceof Level ? $level->getKey() : $level)
            ->where('competency_area_id', $area instanceof CompetencyArea ? $area->getKey() : $area)
            ->exists();
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

    public function topicProgress(): HasMany
    {
        return $this->hasMany(TopicProgress::class);
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
