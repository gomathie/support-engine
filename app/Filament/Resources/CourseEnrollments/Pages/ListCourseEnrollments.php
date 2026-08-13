<?php

namespace App\Filament\Resources\CourseEnrollments\Pages;

use App\Actions\Enrollment\EnrollEmployee;
use App\Enums\EnrollmentSource;
use App\Enums\Role;
use App\Filament\Resources\CourseEnrollments\CourseEnrollmentResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

class ListCourseEnrollments extends ListRecords
{
    protected static string $resource = CourseEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->bulkAssignAction(),
            CreateAction::make()->label('Assign one'),
        ];
    }

    /**
     * Assign a course to individuals, a whole department, or everyone holding a
     * role — the four targets the brief asks for, in one dialog.
     *
     * This is the one-off version. For "everybody in Operations should always
     * have this", an assignment rule is the right tool, because it keeps
     * applying to people who join later.
     */
    private function bulkAssignAction(): Action
    {
        return Action::make('bulkAssign')
            ->label('Assign to many')
            ->icon('heroicon-o-user-plus')
            ->modalWidth('lg')
            ->schema([
                Select::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()
                        ->visible()
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->all())
                    ->searchable()
                    ->required(),

                Select::make('target')
                    ->label('Assign to')
                    ->options([
                        'department' => 'Everyone in a department',
                        'role' => 'Everyone with a role',
                        'users' => 'Specific employees',
                        'all' => 'Every active employee',
                    ])
                    ->default('department')
                    ->live()
                    ->required(),

                Select::make('department_id')
                    ->label('Department')
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get) => $get('target') === 'department'),

                Select::make('role')
                    ->label('Role')
                    ->options(Role::options())
                    ->required()
                    ->visible(fn (Get $get) => $get('target') === 'role'),

                Select::make('user_ids')
                    ->label('Employees')
                    ->options(fn () => User::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->multiple()
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get) => $get('target') === 'users'),

                DatePicker::make('due_at')
                    ->label('Due date')
                    ->helperText('Blank uses the course default.'),
            ])
            ->action(function (array $data, EnrollEmployee $enroll): void {
                $course = Course::query()->findOrFail($data['course_id']);

                $users = $this->resolveTargets($data);

                $assigned = 0;
                $alreadyHad = 0;

                foreach ($users as $user) {
                    $existed = $user->enrollments()->where('course_id', $course->id)->exists();

                    $enrollment = $enroll->handle(
                        user: $user,
                        course: $course,
                        source: match ($data['target']) {
                            'department' => EnrollmentSource::Department,
                            'role' => EnrollmentSource::RoleBased,
                            default => EnrollmentSource::Manual,
                        },
                        assignedBy: auth()->user(),
                    );

                    if (! empty($data['due_at'])) {
                        $enrollment->update(['due_at' => $data['due_at']]);
                    }

                    $existed ? $alreadyHad++ : $assigned++;
                }

                Notification::make()
                    ->success()
                    ->title($assigned.' employee'.($assigned === 1 ? '' : 's').' assigned')
                    ->body($alreadyHad > 0
                        ? $alreadyHad.' already had this course and were left alone.'
                        : null)
                    ->send();
            });
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function resolveTargets(array $data): \Illuminate\Support\Collection
    {
        $query = User::query()->where('is_active', true);

        return match ($data['target']) {
            'department' => $query->where('department_id', $data['department_id'])->get(),
            'role' => $query->role($data['role'])->get(),
            'users' => $query->whereIn('id', $data['user_ids'])->get(),
            default => $query->get(),
        };
    }
}
