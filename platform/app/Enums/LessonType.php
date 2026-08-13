<?php

namespace App\Enums;

/**
 * Content types a lesson can carry. Adding one means adding a case here and a
 * matching branch in resources/js/Pages/Lessons/Show.vue — no migration, because
 * lessons.type is a plain string column.
 *
 * Deliberately excludes video: the platform is text, document and quiz based,
 * and there is no streaming infrastructure behind it.
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
            self::RichText => 'Rich text',
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
