<?php

namespace App\Filament\Resources\Lessons\RelationManagers;

use App\Models\LessonResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Files attached to a lesson: PDFs, documents, images, downloadables.
 *
 * Everything lands on the `private` disk, which has no public URL. Employees
 * reach these bytes only through ResourceDownloadController, which runs
 * LessonResourcePolicy first — so an internal document cannot leak by URL the
 * way it would from storage/app/public.
 */
class ResourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'resources';

    protected static ?string $title = 'Files & resources';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                FileUpload::make('path')
                    ->label('File')
                    ->disk('private')
                    ->directory('lesson-resources')
                    ->visibility('private')
                    ->required(fn (string $operation) => $operation === 'create')
                    ->maxSize(20480)

                    // An allowlist, not a denylist. Anything not named here is
                    // rejected regardless of what the browser claims the type is.
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'text/plain',
                        'text/csv',
                        'image/png',
                        'image/jpeg',
                        'image/gif',
                        'image/webp',
                        'image/svg+xml',
                    ])

                    // Uploads are stored under a generated name. Using the
                    // original would let an author choose the path, and two
                    // lessons with a "notes.pdf" would collide.
                    ->storeFileNamesIn('original_filename')

                    ->columnSpanFull()
                    ->helperText('Stored privately. Employees can only reach it through a permission-checked route.')

                    // Metadata is captured on the way in so the table and the
                    // download headers do not have to stat the file later.
                    ->afterStateUpdated(function ($state, $set): void {
                        if ($state instanceof TemporaryUploadedFile) {
                            $set('mime_type', $state->getMimeType());
                            $set('size_bytes', $state->getSize());
                        }
                    }),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->helperText('What the employee sees.'),

                Toggle::make('is_downloadable')
                    ->default(true)
                    ->helperText('Off for files that are only rendered inline, such as a lesson image.'),

                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),

                TextInput::make('mime_type')->hidden()->dehydrated(),
                TextInput::make('size_bytes')->hidden()->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (LessonResource $record) => $record->description),

                TextColumn::make('original_filename')
                    ->label('File')
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('mime_type')
                    ->label('Type')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('size')
                    ->label('Size')
                    ->state(fn (LessonResource $record) => $record->humanSize())
                    ->alignEnd(),

                IconColumn::make('is_downloadable')
                    ->label('Download')
                    ->boolean(),

                // A row whose file has gone missing is worth seeing at a glance
                // rather than discovering when an employee reports a 404.
                IconColumn::make('present')
                    ->label('On disk')
                    ->state(fn (LessonResource $record) => $record->exists())
                    ->boolean(),
            ])
            ->reorderable('position')
            ->defaultSort('position')
            ->headerActions([
                CreateAction::make()
                    ->label('Upload file')
                    ->mutateDataUsing(function (array $data): array {
                        $data['disk'] = 'private';
                        $data['uploaded_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (LessonResource $record) => $record->exists())
                    ->action(fn (LessonResource $record) => Storage::disk($record->disk)->download(
                        $record->path,
                        $record->original_filename ?: $record->name,
                    )),

                EditAction::make(),

                // The model's deleted() hook removes the file too, so this does
                // not leave an unreachable orphan on the disk.
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
