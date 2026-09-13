<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('watch_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('description_bn')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('news_articles', function (Blueprint $table): void {
            $table->foreignId('news_category_id')->nullable()->after('created_by')->constrained()->nullOnDelete();
            $table->string('title_bn')->nullable()->after('title');
            $table->text('summary_bn')->nullable()->after('summary');
            $table->json('body_bn')->nullable()->after('body');
        });

        Schema::table('watch_shows', function (Blueprint $table): void {
            $table->foreignId('watch_category_id')->nullable()->after('created_by')->constrained()->nullOnDelete();
            $table->string('title_bn')->nullable()->after('title');
            $table->string('eyebrow_bn', 120)->nullable()->after('eyebrow');
            $table->text('description_bn')->nullable()->after('description');
        });

        Schema::table('watch_episodes', function (Blueprint $table): void {
            $table->string('title_bn')->nullable()->after('title');
            $table->text('description_bn')->nullable()->after('description');
        });

        $now = now();
        $news = [
            ['Bangladesh', 'বাংলাদেশ', 'News and public-interest reporting from across Bangladesh.', 'বাংলাদেশের সংবাদ ও জনস্বার্থমূলক প্রতিবেদন।'],
            ['Economy', 'অর্থনীতি', 'Economy, business and public finance.', 'অর্থনীতি, ব্যবসা ও সরকারি অর্থব্যবস্থা।'],
            ['Climate', 'জলবায়ু', 'Climate change and resilience.', 'জলবায়ু পরিবর্তন ও সহনশীলতা।'],
            ['Culture', 'সংস্কৃতি', 'Arts, heritage and cultural life.', 'শিল্প, ঐতিহ্য ও সাংস্কৃতিক জীবন।'],
            ['Science', 'বিজ্ঞান', 'Science, health and innovation.', 'বিজ্ঞান, স্বাস্থ্য ও উদ্ভাবন।'],
            ['Environment', 'পরিবেশ', 'Environment and nature reporting.', 'পরিবেশ ও প্রকৃতি বিষয়ক প্রতিবেদন।'],
            ['Media', 'গণমাধ্যম', 'Media, broadcasting and communication.', 'গণমাধ্যম, সম্প্রচার ও যোগাযোগ।'],
        ];
        $watch = [
            ['Live TV', 'সরাসরি টিভি', 'Live and scheduled public video broadcasts.', 'সরাসরি ও নির্ধারিত জনসেবা ভিডিও সম্প্রচার।'],
            ['Drama', 'নাটক', 'Original and classic drama.', 'মৌলিক ও ধ্রুপদি নাটক।'],
            ['Documentary', 'প্রামাণ্যচিত্র', 'Documentaries from Bangladesh and beyond.', 'বাংলাদেশ ও বিশ্বের প্রামাণ্যচিত্র।'],
            ['Culture', 'সংস্কৃতি ও সংগীত', 'Culture, music and performance.', 'সংস্কৃতি, সংগীত ও পরিবেশনা।'],
            ['Kids', 'শিশু', 'Safe programmes for young audiences.', 'শিশু-কিশোরদের নিরাপদ অনুষ্ঠান।'],
        ];

        foreach ($news as $position => [$name, $nameBn, $description, $descriptionBn]) {
            $id = DB::table('news_categories')->insertGetId([
                'name' => $name, 'name_bn' => $nameBn, 'slug' => Str::slug($name),
                'description' => $description, 'description_bn' => $descriptionBn,
                'position' => $position, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('news_articles')->where('category', $name)->update(['news_category_id' => $id]);
        }

        foreach ($watch as $position => [$name, $nameBn, $description, $descriptionBn]) {
            $id = DB::table('watch_categories')->insertGetId([
                'name' => $name, 'name_bn' => $nameBn, 'slug' => Str::slug($name),
                'description' => $description, 'description_bn' => $descriptionBn,
                'position' => $position, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('watch_shows')->where('category', $name)->update(['watch_category_id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::table('watch_episodes', function (Blueprint $table): void {
            $table->dropColumn(['title_bn', 'description_bn']);
        });
        Schema::table('watch_shows', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('watch_category_id');
            $table->dropColumn(['title_bn', 'eyebrow_bn', 'description_bn']);
        });
        Schema::table('news_articles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('news_category_id');
            $table->dropColumn(['title_bn', 'summary_bn', 'body_bn']);
        });
        Schema::dropIfExists('watch_categories');
        Schema::dropIfExists('news_categories');
    }
};
