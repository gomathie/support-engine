<?php

namespace App\Filament\Resources\Certificates;

use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Resources\Certificates\Tables\CertificatesTable;
use App\Models\Certificate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Read-only by design.
 *
 * A certificate is a record of something that happened: this person completed
 * this course on this date. It is issued by the system when a course completes
 * and is never typed in by hand, because a hand-written one would be
 * indistinguishable from an earned one — and the whole point of the
 * verification link is that it can be trusted.
 *
 * The PDF can be re-rendered from the table; the record behind it cannot be
 * edited.
 */
class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Reporting';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'certificate_number';

    public static function table(Table $table): Table
    {
        return CertificatesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** Managers see certificates for their own departments only. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('user.department');

        $user = auth()->user();

        if (! $user || $user->isAdmin()) {
            return $query;
        }

        return $query->whereHas(
            'user',
            fn ($q) => $q->whereIn('department_id', $user->visibleDepartmentIds()),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
        ];
    }
}
