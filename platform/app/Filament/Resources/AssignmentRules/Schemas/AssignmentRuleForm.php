<?php

namespace App\Filament\Resources\AssignmentRules\Schemas;

use App\Enums\Role;
use App\Models\AssignmentRule;
use App\Models\Course;
use App\Models\Department;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AssignmentRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('course_id')
                    ->label('Assign this course')
                    ->options(fn () => Course::query()->visible()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),

                Select::make('target_type')
                    ->label('To')
                    ->options([
                        AssignmentRule::TARGET_DEPARTMENT => 'Everyone in a department',
                        AssignmentRule::TARGET_ROLE => 'Everyone with a role',
                        AssignmentRule::TARGET_USER => 'One specific employee',
                        AssignmentRule::TARGET_ALL => 'Every active employee',
                    ])
                    ->default(AssignmentRule::TARGET_DEPARTMENT)
                    ->live()
                    ->required(),

                Select::make('target_id')
                    ->label('Department')
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get) => $get('target_type') === AssignmentRule::TARGET_DEPARTMENT),

                Select::make('target_value')
                    ->label('Role')
                    ->options(Role::options())
                    ->required()
                    ->visible(fn (Get $get) => $get('target_type') === AssignmentRule::TARGET_ROLE),

                Select::make('target_id')
                    ->label('Employee')
                    ->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required()
                    ->visible(fn (Get $get) => $get('target_type') === AssignmentRule::TARGET_USER),

                TextInput::make('due_days')
                    ->label('Due within (days)')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Overrides the course default for people enrolled by this rule.'),

                Toggle::make('is_active')
                    ->default(true)
                    ->helperText('Inactive rules assign nobody, but existing enrollments are kept.'),

                Toggle::make('applies_retroactively')
                    ->label('Apply to existing employees')
                    ->default(true)
                    ->helperText('Off means only people who join the target from now on.')
                    ->columnSpanFull(),
            ]);
    }
}
