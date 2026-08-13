<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\Department;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Employee')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('employee_number')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('job_title')
                            ->maxLength(255),

                        Select::make('department_id')
                            ->label('Department')
                            ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->helperText('Assignment rules targeting this department apply immediately.'),

                        TextInput::make('certificate_name')
                            ->label('Name on certificates')
                            ->maxLength(255)
                            ->helperText('Leave blank to use the name above.'),
                    ]),

                Section::make('Access')
                    ->columns(2)
                    ->schema([
                        /*
                         * No ->options() override here, deliberately.
                         *
                         * ->relationship() makes the value the role's id, which
                         * is what the model_has_roles pivot stores. Overriding
                         * the options with an enum-keyed list made the widget
                         * offer role *names* instead, so an edit produced a
                         * mixed array — the hydrated id plus the newly picked
                         * name — and syncing it asked Postgres for
                         * `where roles.id in (1, admin)`, which is a type error,
                         * not a missing record.
                         *
                         * The labels are prettified from the enum instead.
                         */
                        Select::make('roles')
                            ->relationship(name: 'roles', titleAttribute: 'name')
                            ->getOptionLabelFromRecordUsing(
                                fn (SpatieRole $record): string => Role::tryFrom($record->name)?->label()
                                    ?? Str::headline($record->name),
                            )
                            ->multiple()
                            ->preload()
                            ->required()
                            ->helperText('Admins and managers can reach /admin; employees cannot.'),

                        Toggle::make('is_active')
                            ->default(true)
                            ->helperText('Deactivating ends any open session on the next request.'),

                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            // Blank on edit means "leave the password alone" rather
                            // than "set it to empty".
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                            ->helperText(fn (string $operation) => $operation === 'edit'
                                ? 'Leave blank to keep the current password.'
                                : null)
                            ->columnSpanFull(),
                    ]),

                Section::make('Managed departments')
                    ->description('Only relevant for managers. Scopes which employees and reports they can see.')
                    ->schema([
                        Select::make('managedDepartments')
                            ->label('Departments this person manages')
                            ->relationship('managedDepartments', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }
}
