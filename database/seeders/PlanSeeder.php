<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PromoCode;
use Illuminate\Database\Seeder;

/**
 * M18 — Free & Premium plans with configurable limits (FR-SUB-01)
 * mirroring the FRS §4.18.4 plan comparison.
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::query()->updateOrCreate(
            ['code' => 'free'],
            [
                'name' => 'Free',
                'name_bn' => 'ফ্রি',
                'description' => 'Listen free with advertisements and limitations.',
                'price_monthly' => 0,
                'price_annual' => 0,
                'currency' => 'BDT',
                'trial_days' => 0,
                'features' => [
                    'ads' => true,
                    'skips_per_hour' => 6,
                    'max_quality_kbps' => 128,
                    'offline_downloads' => false,
                    'equalizer' => false,
                    'premium_content' => 'preview',
                    'preview_seconds' => 90,
                ],
                'is_active' => true,
            ],
        );

        Plan::query()->updateOrCreate(
            ['code' => 'premium'],
            [
                'name' => 'Premium',
                'name_bn' => 'প্রিমিয়াম',
                'description' => 'Ad-free, full-length, high-quality and offline listening.',
                'price_monthly' => 199,
                'price_annual' => 1999,
                'currency' => 'BDT',
                'trial_days' => 7,
                'features' => [
                    'ads' => false,
                    'skips_per_hour' => null,
                    'max_quality_kbps' => 320,
                    'offline_downloads' => true,
                    'equalizer' => true,
                    'premium_content' => 'full',
                    'preview_seconds' => null,
                ],
                'is_active' => true,
            ],
        );

        PromoCode::query()->updateOrCreate(
            ['code' => 'BETAR50'],
            [
                'discount_percent' => 50,
                'plan_id' => Plan::query()->where('code', 'premium')->value('id'),
                'valid_from' => now()->subMonth()->toDateString(),
                'valid_until' => now()->addMonths(2)->toDateString(),
                'max_uses' => 1000,
                'is_active' => true,
            ],
        );

        PromoCode::query()->updateOrCreate(
            ['code' => 'EKUSHEY21'],
            [
                'discount_percent' => 21,
                'plan_id' => Plan::query()->where('code', 'premium')->value('id'),
                'valid_from' => now()->subMonths(5)->toDateString(),
                'valid_until' => now()->subMonths(4)->toDateString(), // expired
                'max_uses' => 500,
                'used_count' => 431,
                'is_active' => false,
            ],
        );

        $this->command?->info('Plans: Free + Premium, 2 promo codes seeded');
    }
}
