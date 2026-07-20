<?php

/*
|--------------------------------------------------------------------------
| Central Theme & Colour Configuration
|--------------------------------------------------------------------------
|
| Single source of truth for the Admin Portal look & feel. Change the
| palette or UI preferences here (or override the base colours at runtime
| from Admin → Settings → Theme) and the whole application re-skins —
| every component consumes these values through CSS variables.
|
| Each palette is a full 50–950 shade scale. To swap the brand colour,
| replace the palette values (any Tailwind-style scale works), or set
| only 'primary'/'accent' base hex values in the settings table and the
| shade scale is generated automatically.
|
*/

return [

    // Base brand colours. Used to auto-generate a shade scale when no
    // explicit palette override exists for a shade.
    'primary' => env('THEME_PRIMARY', '#0f766e'),   // deep teal — Betar broadcast heritage
    'accent' => env('THEME_ACCENT', '#d97706'),     // warm amber — dial-light glow

    // Explicit shade palettes (leave null to derive from the base colour).
    'palettes' => [
        'primary' => [
            50 => '#f0fdfa', 100 => '#ccfbf1', 200 => '#99f6e4', 300 => '#5eead4',
            400 => '#2dd4bf', 500 => '#14b8a6', 600 => '#0d9488', 700 => '#0f766e',
            800 => '#115e59', 900 => '#134e4a', 950 => '#042f2e',
        ],
        'accent' => [
            50 => '#fffbeb', 100 => '#fef3c7', 200 => '#fde68a', 300 => '#fcd34d',
            400 => '#fbbf24', 500 => '#f59e0b', 600 => '#d97706', 700 => '#b45309',
            800 => '#92400e', 900 => '#78350f', 950 => '#451a03',
        ],
    ],

    // Neutral surface tuning: 'slate' | 'gray' | 'zinc' | 'stone'
    'neutral' => 'slate',

    // Default colour mode: 'light' | 'dark' | 'system'
    'default_mode' => 'system',

    // Sidebar flavour: 'dark' (always dark, brand look) | 'surface' (follows mode)
    'sidebar' => 'dark',

    // Global corner radius applied to cards, inputs, buttons.
    'radius' => '0.625rem',

    // Font stacks — Bangla-aware.
    'fonts' => [
        'sans' => "'Inter', 'Noto Sans Bengali', ui-sans-serif, system-ui, sans-serif",
        'display' => "'Inter', 'Noto Sans Bengali', ui-sans-serif, system-ui, sans-serif",
    ],

    // Brand identity strings used across the UI shell.
    'brand' => [
        'name' => 'Betar Archive',
        'full_name' => 'Bangladesh Betar Audio Archive',
        'short' => 'BB',
    ],
];
