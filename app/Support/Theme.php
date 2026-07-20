<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * Resolves the active theme (config/theme.php + settings-table overrides)
 * into CSS custom properties consumed by resources/css/app.css.
 */
class Theme
{
    /** Runtime overrides stored in the settings table (Admin → Settings → Theme). */
    private static function overrides(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            return [
                'primary' => Setting::get('theme_primary'),
                'accent' => Setting::get('theme_accent'),
                'default_mode' => Setting::get('theme_default_mode'),
                'sidebar' => Setting::get('theme_sidebar_style'),
                'radius' => Setting::get('theme_radius'),
            ];
        } catch (\Throwable) {
            return []; // database unavailable (e.g. during build) — fall back to config
        }
    }

    public static function mode(): string
    {
        return self::overrides()['default_mode'] ?? config('theme.default_mode', 'system');
    }

    public static function sidebar(): string
    {
        return self::overrides()['sidebar'] ?? config('theme.sidebar', 'dark');
    }

    public static function brand(string $key): string
    {
        return (string) config("theme.brand.$key", '');
    }

    /** Full CSS variable block for the <head>. */
    public static function cssVariables(): string
    {
        $overrides = self::overrides();

        $primaryBase = $overrides['primary'] ?? null;
        $accentBase = $overrides['accent'] ?? null;

        $primary = $primaryBase
            ? self::scaleFromBase($primaryBase)
            : (config('theme.palettes.primary') ?? self::scaleFromBase((string) config('theme.primary')));

        $accent = $accentBase
            ? self::scaleFromBase($accentBase)
            : (config('theme.palettes.accent') ?? self::scaleFromBase((string) config('theme.accent')));

        $radius = $overrides['radius'] ?? config('theme.radius', '0.625rem');

        $lines = [];
        foreach ($primary as $shade => $hex) {
            $lines[] = "--primary-{$shade}: {$hex};";
        }
        foreach ($accent as $shade => $hex) {
            $lines[] = "--accent-{$shade}: {$hex};";
        }
        $lines[] = "--app-radius: {$radius};";
        $lines[] = '--font-sans: '.config('theme.fonts.sans').';';

        return implode(' ', $lines);
    }

    /**
     * Generate a Tailwind-like 50–950 scale from one base colour by
     * mixing towards white (light shades) and black (dark shades).
     */
    public static function scaleFromBase(string $hex): array
    {
        [$r, $g, $b] = self::hexToRgb($hex);

        $mix = static function (float $ratio, int $to) use ($r, $g, $b): string {
            $mixChannel = static fn (int $channel): int => (int) round($channel + ($to - $channel) * $ratio);

            return sprintf('#%02x%02x%02x', $mixChannel($r), $mixChannel($g), $mixChannel($b));
        };

        return [
            50 => $mix(0.95, 255), 100 => $mix(0.88, 255), 200 => $mix(0.72, 255),
            300 => $mix(0.52, 255), 400 => $mix(0.28, 255), 500 => $mix(0.10, 255),
            600 => $hex, 700 => $mix(0.18, 0), 800 => $mix(0.34, 0),
            900 => $mix(0.48, 0), 950 => $mix(0.68, 0),
        ];
    }

    /** @return array{0:int,1:int,2:int} */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
