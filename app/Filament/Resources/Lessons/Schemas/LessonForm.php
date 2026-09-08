<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Enums\CompletionRequirement;
use App\Enums\LessonType;
use App\Models\Module;
use App\Models\Lesson;
use App\Support\Video\VideoEmbed;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Placement')
                    ->columns(2)
                    ->schema([
                        Select::make('module_id')
                            ->label('Module')
                            ->options(fn () => Module::query()
                                ->with('course:id,title')
                                ->orderBy('course_id')
                                ->orderBy('position')
                                ->get()
                                ->mapWithKeys(fn (Module $m) => [
                                    $m->id => $m->course->title.' — '.$m->title,
                                ])
                                ->all())
                            ->searchable()
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug(Str::limit($state, 60, '')));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('position')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Blank appends to the end.'),

                        Toggle::make('is_published')->default(true),
                    ]),

                Section::make('Content')
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->options(LessonType::options())
                            ->default(LessonType::RichText->value)
                            ->required()
                            ->live()
                            ->helperText('Adding a new type is an enum case plus a branch in the viewer.'),

                        Select::make('completion_requirement')
                            ->options(CompletionRequirement::options())
                            ->default(CompletionRequirement::View->value)
                            ->required(),

                        Textarea::make('summary')
                            ->label('Short summary')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->helperText('One or two sentences. Shown under the title in the course outline and again at the top of the lesson. What will they be able to do afterwards?'),

                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),

                        FileUpload::make('cover_image_path')
                            ->label('Cover image')
                            ->image()
                            ->disk('public')
                            ->directory('lesson-covers')
                            ->maxSize(2048)
                            ->columnSpanFull()
                            ->helperText('Optional. A banner at the top of the lesson, shown when the lesson has no video.'),

                        // Everything authored here is run through HTMLPurifier's
                        // `lesson` allowlist before it is sent to a browser, so
                        // a compromised author account cannot become stored XSS
                        // against every employee.
                        RichEditor::make('content')
                            ->columnSpanFull()
                            ->visible(fn ($get) => in_array(
                                $get('type'),
                                [LessonType::RichText->value, LessonType::Download->value],
                                true,
                            ))
                            ->helperText('Sanitised on save and again on display.'),

                        TextInput::make('external_url')
                            ->url()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->visible(fn ($get) => $get('type') === LessonType::ExternalLink->value),

                        TextInput::make('estimated_minutes')
                            ->label('Estimated duration (minutes)')
                            ->numeric()
                            ->minValue(0),
                    ]),

                /*
                 * Video (PA-9).
                 *
                 * The author pastes whatever URL the browser gave them. It is
                 * parsed here into a provider and an id, and only those are
                 * stored — the embed URL is rebuilt from a fixed template at
                 * render time, so a pasted string never reaches an iframe src.
                 *
                 * Gated on videos.manage, which is grantable per Trainer.
                 */
                Section::make('Video file')
                    ->description('Optional, and available on any lesson — the video sits above the lesson text rather than replacing it. Uploaded to the private disk: there is no public URL, and playback goes through a route that checks the lesson policy first.')
                    ->collapsed(fn (?Lesson $record) => ! $record?->hasUploadedVideo())
                    ->collapsible()
                    ->visible(fn () => Filament::auth()->user()?->can('videos.manage') ?? false)
                    ->columns(2)
                    ->schema([
                        FileUpload::make('video_path')
                            ->label('Video')
                            ->disk('private')
                            ->directory('lesson-videos')
                            ->visibility('private')
                            ->columnSpanFull()

                            // §5.1: MP4 (H.264/AAC), MOV, WEBM. The browser
                            // filter is a convenience; the server-side mime
                            // check below is what actually holds.
                            ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])

                            // 500 MB, in kilobytes. PHP's upload_max_filesize
                            // and post_max_size have to allow this too.
                            ->maxSize(512000)

                            ->helperText('MP4 (H.264/AAC), MOV or WEBM · up to 500 MB. Over the limit, trim to 5–7 minutes or export at 1080p.')

                            // The row carries what the file was and where it
                            // went, so a later move to S3 does not strand it.
                            ->afterStateUpdated(function ($state, $set): void {
                                if (! $state instanceof TemporaryUploadedFile) {
                                    return;
                                }

                                $set('video_disk', 'private');
                                $set('video_original_name', $state->getClientOriginalName());
                                $set('video_mime_type', $state->getMimeType());
                                $set('video_size_bytes', $state->getSize());
                                $set('video_status', 'ready');
                            }),

                        TextInput::make('video_original_name')
                            ->label('Original filename')
                            ->readOnly()
                            ->dehydrated(),

                        TextInput::make('video_mime_type')
                            ->label('Format')
                            ->readOnly()
                            ->dehydrated(),

                        Hidden::make('video_disk'),
                        Hidden::make('video_size_bytes'),

                        // Transcoding is not built (see PA-10 in the delivery
                        // log), so an upload is ready as soon as it lands. The
                        // field exists so the queued pipeline has somewhere to
                        // report without another migration.
                        Hidden::make('video_status'),
                    ]),

                /*
                 * Where the lesson came from.
                 *
                 * Shown to the trainee after the lesson text, as source material
                 * rather than as part of the teaching. A lesson is usually drawn
                 * from more than one page, so this is a list — naming a chapter
                 * and leaving somebody to search for it is not a reference.
                 */
                Section::make('Documentation links')
                    ->description('Shown after the lesson text. Link to the pages on docs.pilot-gps.com this lesson was written from.')
                    ->collapsed(fn (?Lesson $record) => blank($record?->documentationLinks()))
                    ->collapsible()
                    ->schema([
                        Repeater::make('doc_links')
                            ->hiddenLabel()
                            ->columns(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Title')
                                    ->required()
                                    ->maxLength(160)
                                    ->placeholder('Sensors → Calibration tables'),

                                TextInput::make('url')
                                    ->label('URL')
                                    ->required()
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('https://docs.pilot-gps.com/sensors_1.html')

                                    // An href is a script-capable sink: a
                                    // `javascript:` URL in an anchor runs on
                                    // click. Only http and https are rendered,
                                    // and this refuses the rest at the form.
                                    ->rule(fn () => function (string $attribute, $value, callable $fail): void {
                                        $scheme = strtolower((string) parse_url((string) $value, PHP_URL_SCHEME));

                                        if (filled($value) && ! in_array($scheme, ['http', 'https'], true)) {
                                            $fail('Only http and https links are allowed.');
                                        }
                                    }),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->addActionLabel('Add a link')
                            ->defaultItems(0)
                            ->reorderable(),
                    ]),

                Section::make('Video link')
                    ->description('Optional, and available on any lesson. Paste a YouTube or Vimeo URL and the player appears above the lesson text. If a file is uploaded above, it is used instead of this link.')
                    ->collapsed(fn (?Lesson $record) => $record?->videoEmbed() === null)
                    ->collapsible()
                    ->visible(fn () => Filament::auth()->user()?->can('videos.manage') ?? false)
                    ->columns(2)
                    ->schema([
                        TextInput::make('video_url')
                            ->label('Video URL')
                            ->placeholder('https://www.youtube.com/watch?v=… or https://vimeo.com/…')
                            ->columnSpanFull()
                            ->required()
                            ->live(onBlur: true)

                            // Not a column. The provider and id below are.
                            ->dehydrated(false)

                            ->afterStateHydrated(function ($state, $set, ?Lesson $record): void {
                                $set('video_url', $record?->videoEmbed()?->canonicalUrl());
                            })
                            ->afterStateUpdated(function ($state, $set): void {
                                $embed = VideoEmbed::parse($state);

                                $set('video_provider', $embed?->provider);
                                $set('video_id', $embed?->id);
                            })
                            ->rule(fn () => function (string $attribute, $value, callable $fail): void {
                                if (filled($value) && VideoEmbed::parse($value) === null) {
                                    $fail('That is not a YouTube or Vimeo URL we recognise. Paste the address from the browser bar.');
                                }
                            })
                            ->helperText('YouTube or Vimeo. Embedded privately — youtube-nocookie, no suggested videos at the end.'),

                        // Written by the parser above, shown so the author can
                        // see what was actually understood.
                        TextInput::make('video_provider')
                            ->label('Provider')
                            ->readOnly()
                            ->dehydrated()
                            ->placeholder('detected from the URL'),

                        TextInput::make('video_id')
                            ->label('Video ID')
                            ->readOnly()
                            ->dehydrated()
                            ->placeholder('detected from the URL'),

                        TextInput::make('video_duration_seconds')
                            ->label('Duration (seconds)')
                            ->numeric()
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->helperText(fn ($state) => (int) $state > 420
                                ? 'Over the 5–7 minute ceiling. Consider splitting it — one objective per video.'
                                : 'Neither provider reports this without an API key, so enter it by hand.')
                            ->hintColor(fn ($state) => (int) $state > 420 ? 'warning' : null),

                        Textarea::make('video_transcript')
                            ->label('Transcript')
                            ->rows(8)
                            ->columnSpanFull()
                            ->helperText('Adults scan before they watch, and auto-captioning mangles PILOT terminology. Searchable alongside the video.'),
                    ]),
            ]);
    }
}
