<?php

namespace App\Filament\Resources\Certificates\Tables;

use App\Jobs\RenderCertificatePdf;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Department;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('certificate_number')
                    ->label('Number')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('recipient_name')
                    ->label('Recipient')
                    ->searchable()
                    ->description(fn (Certificate $record) => $record->user?->department?->name),

                TextColumn::make('course_title')
                    ->label('Course')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('score')
                    ->numeric(decimalPlaces: 0)
                    ->suffix('%')
                    ->placeholder('—')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->date('j M Y')
                    ->sortable(),

                TextColumn::make('issued_at')
                    ->label('Issued')
                    ->date('j M Y')
                    ->sortable(),

                IconColumn::make('pdf')
                    ->label('PDF')
                    ->state(fn (Certificate $record) => $record->isRendered())
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('course_id')
                    ->label('Course')
                    ->options(fn () => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                    ->searchable(),

                SelectFilter::make('department')
                    ->label('Department')
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'] ?? null,
                        fn ($q, $id) => $q->whereHas('user', fn ($u) => $u->where('department_id', $id)),
                    )),
            ])
            ->recordActions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (Certificate $record) => $record->isRendered())
                    ->action(fn (Certificate $record) => Storage::disk($record->disk)->download(
                        $record->path,
                        $record->certificate_number.'.pdf',
                    )),

                // For a certificate whose queued render failed, or one issued
                // before a change to the template.
                Action::make('regenerate')
                    ->label('Regenerate PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('The certificate number, recipient and dates are unchanged — only the file is re-rendered.')
                    ->action(function (Certificate $record): void {
                        if ($record->path) {
                            Storage::disk($record->disk)->delete($record->path);
                            $record->forceFill(['path' => null])->save();
                        }

                        RenderCertificatePdf::dispatch($record);

                        Notification::make()
                            ->success()
                            ->title('Queued for re-rendering')
                            ->send();
                    }),
            ])
            ->defaultSort('issued_at', 'desc');
    }
}
