<?php

namespace App\Enums;

/**
 * Content types a topic can carry. Adding one means adding a case here and a
 * matching branch in resources/js/Pages/Topics/Show.vue — no migration, because
 * topics.type is a plain string column.
 *
 * Video was excluded under the original brief. The competency plan reverses
 * that: §4.1 makes short video the default delivery format for re-aligned
 * content. VideoEmbed (PA-9) covers hosted video; native upload follows in PA-10.
 */
enum TopicType: string
{
    case RichText = 'rich_text';
    case VideoEmbed = 'video_embed';
    case VideoUpload = 'video_upload';
    case Pdf = 'pdf';
    case Image = 'image';
    case Document = 'document';
    case ExternalLink = 'external_link';
    case Download = 'download';

    public function label(): string
    {
        return match ($this) {
            self::RichText => 'Rich text',
            self::VideoEmbed => 'Video (YouTube / Vimeo)',
            self::VideoUpload => 'Video (uploaded file)',
            self::Pdf => 'PDF',
            self::Image => 'Image',
            self::Document => 'Document',
            self::ExternalLink => 'External link',
            self::Download => 'Downloadable resource',
        };
    }

    /** Types whose payload lives in topic_resources rather than topics.content. */
    public function requiresResource(): bool
    {
        return in_array($this, [self::Pdf, self::Image, self::Document, self::Download], true);
    }

    /** Types whose payload is a video, hosted or uploaded. */
    public function isVideo(): bool
    {
        return in_array($this, [self::VideoEmbed, self::VideoUpload], true);
    }

    /** An uploaded file on the private disk, rather than a third-party embed. */
    public function isUploadedVideo(): bool
    {
        return $this === self::VideoUpload;
    }

    public function icon(): string
    {
        return match ($this) {
            self::RichText => 'heroicon-o-document-text',
            self::VideoEmbed => 'heroicon-o-play-circle',
            self::VideoUpload => 'heroicon-o-film',
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
