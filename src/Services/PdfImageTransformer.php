<?php

declare(strict_types=1);

namespace HeritageApps\Help\Services;

class PdfImageTransformer
{
    /**
     * Replace <img> src attributes with base64 data URIs so DomPDF can embed them.
     *
     * @param  array<string, int>  $stats  Passed by reference: converted_to_data_uri, conversion_failed
     */
    public function transform(string $html, array &$stats): string
    {
        return preg_replace_callback(
            '/<img([^>]*)\ssrc=["\']([^"\']+)["\']([^>]*)>/i',
            function (array $matches) use (&$stats): string {
                $before = $matches[1];
                $src = $matches[2];
                $after = $matches[3];

                $dataUri = $this->toDataUri($src);

                if ($dataUri === null) {
                    $stats['conversion_failed']++;

                    return $matches[0];
                }

                $stats['converted_to_data_uri']++;

                return "<img{$before} src=\"{$dataUri}\"{$after}>";
            },
            $html,
        ) ?? $html;
    }

    private function toDataUri(string $src): ?string
    {
        if (str_starts_with($src, 'data:')) {
            return $src;
        }

        $absoluteUrl = str_starts_with($src, 'http')
            ? $src
            : $this->resolveRelativeUrl($src);

        $content = @file_get_contents($absoluteUrl);

        if ($content === false || $content === '') {
            return null;
        }

        $mimeType = $this->detectMimeType($absoluteUrl, $content);

        return 'data:' . $mimeType . ';base64,' . base64_encode($content);
    }

    private function resolveRelativeUrl(string $src): string
    {
        $appUrl = rtrim(config('app.url', ''), '/');

        return $appUrl . '/' . ltrim($src, '/');
    }

    private function detectMimeType(string $url, string $content): string
    {
        $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'svg'         => 'image/svg+xml',
            default       => $this->sniffMimeType($content),
        };
    }

    private function sniffMimeType(string $content): string
    {
        $header = substr($content, 0, 12);

        if (str_starts_with($header, "\x89PNG")) {
            return 'image/png';
        }

        if (str_starts_with($header, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($header, 'GIF8')) {
            return 'image/gif';
        }

        if (str_starts_with($header, 'RIFF') && str_contains(substr($content, 0, 16), 'WEBP')) {
            return 'image/webp';
        }

        return 'image/png';
    }
}
