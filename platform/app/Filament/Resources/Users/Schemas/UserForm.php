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
                        Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->options(Role::options())
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
