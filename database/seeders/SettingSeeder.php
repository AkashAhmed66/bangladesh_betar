<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Central application + theme configuration.
 * Theme values here override config/theme.php defaults at runtime.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // group, key, value, type, label
            ['general', 'app_name', 'Bangladesh Betar Audio Archive', 'string', 'Application name'],
            ['general', 'app_tagline', 'The sound heritage of the nation', 'string', 'Tagline'],
            ['general', 'default_locale', 'en', 'string', 'Default locale'],
            ['general', 'support_email', 'support@betar.gov.bd', 'string', 'Support e-mail'],

            ['theme', 'theme_primary', '#0f766e', 'color', 'Primary colour'],
            ['theme', 'theme_accent', '#d97706', 'color', 'Accent colour'],
            ['theme', 'theme_default_mode', 'system', 'string', 'Default mode (light | dark | system)'],
            ['theme', 'theme_sidebar_style', 'dark', 'string', 'Sidebar style (dark | light)'],
            ['theme', 'theme_radius', '0.625rem', 'string', 'Corner radius'],

            ['streaming', 'free_tier_quality_kbps', '128', 'int', 'Free tier max quality (kbps)'],
            ['streaming', 'premium_quality_kbps', '320', 'int', 'Premium max quality (kbps)'],
            ['streaming', 'preview_seconds', '90', 'int', 'Premium-content preview length (seconds)'],
            ['streaming', 'free_skips_per_hour', '6', 'int', 'Free tier skips per hour'],
            ['streaming', 'free_daily_picks', '10', 'int', 'Free tier: tracks a user can actively choose or queue per day, 0 = unlimited (after the limit, autoplay switches to a random radio mix)'],
            ['streaming', 'stream_url_ttl_minutes', '30', 'int', 'Signed streaming URL validity (minutes)'],

            ['moderation', 'moderation_mode', 'post', 'string', 'Comment moderation (pre | post | disabled)'],
            ['moderation', 'profanity_filter_enabled', 'true', 'bool', 'Enable profanity filter'],

            ['rights', 'rights_expiry_alert_days', '[90,30,7]', 'json', 'Rights expiry alert intervals (days)'],

            ['workflow', 'approval_escalation_hours', '72', 'int', 'Escalate approvals pending longer than (hours)'],

            ['ads', 'ad_every_n_songs', '2', 'int', 'Play an ad after every N songs (1 = every song)'],
            ['ads', 'ad_frequency_cap_per_hour', '4', 'int', 'Max ads per listener per hour'],
            ['ads', 'preroll_enabled', 'true', 'bool', 'Enable pre-roll ads'],

            ['backup', 'backup_schedule_cron', '0 2 * * *', 'string', 'Backup schedule (cron)'],
            ['backup', 'integrity_check_interval_days', '30', 'int', 'Fixity check interval (days)'],
        ];

        foreach ($settings as [$group, $key, $value, $type, $label]) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['group' => $group, 'value' => $value, 'type' => $type, 'label' => $label],
            );
        }

        $this->command?->info('Settings: '.count($settings).' configuration keys seeded');
    }
}
