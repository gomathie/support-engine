<?php

namespace App\Enums;

/**
 * What form a lesson's *body* takes. Adding one means adding a case here and a
 * matching branch in resources/js/Pages/Lessons/Show.vue — no migration, because
 * lessons.type is a plain string column.
 *
 * **Video is not a type.** It was, briefly, and that was wrong: a lesson whose
 * type was "video" rendered the player *instead of* its text, so the video stood
 * alone with nothing explaining it. Video is now a property any lesson may have —
 * a YouTube/Vimeo link or an uploaded file — shown above the body it belongs
 * with. See Lesson::hasVideo().
 *
 * That is what §4.1 actually asks for: video as the delivery format for the
 * material, not as a substitute for it.
 */
enum LessonType: string
{
    case RichText = 'rich_text';
    case Pdf = 'pdf';
    case Image = 'image';
    case Document = 'document';
    case ExternalLink = 'external_link';
    case Download = 'download';

    public function label(): string
    {
        return match ($this) {
            self::RichText => 'Text (with optional video)',
            self::Pdf => 'PDF',
            self::Image => 'Image',
            self::Document => 'Document',
            self::ExternalLink => 'External link',
            self::Download => 'Downloadable resource',
        };
    }

    /** Types whose payload lives in lesson_resources rather than lessons.content. */
    public function requiresResource(): bool
    {
        return in_array($this, [self::Pdf, self::Image, self::Document, self::Download], true);
    }

    public function icon(): string
    {
        return match ($this) {
            self::RichText => 'heroicon-o-document-text',
            self::Pdf => 'heroicon-o-document',
            self::Image => 'heroicon-o-photo',
            self::Document => 'heroicon-o-paper-clip',
            self::ExternalLink => 'heroicon-o-link',
            self::Download => 'heroicon-o-arrow-down-tray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
