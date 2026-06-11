<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Daily Duas', 'slug' => 'daily-duas', 'sort_order' => 1],
            ['name' => 'Health', 'slug' => 'health', 'sort_order' => 2],
            ['name' => 'Family', 'slug' => 'family', 'sort_order' => 3],
            ['name' => 'Umrah', 'slug' => 'umrah', 'sort_order' => 4],
            ['name' => 'Essentials', 'slug' => 'essentials', 'sort_order' => 5],
            ['name' => 'Ramadan', 'slug' => 'ramadan', 'sort_order' => 6],
        ];

        foreach ($categories as $category) {
            BlogCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }

        $posts = [
            ['daily-duas', 'Morning and Evening Adhkar Every Muslim Should Know', 'Start and end your day with these powerful remembrances that protect the heart and strengthen faith.', 5, 'images/blog/prayer.jpg'],
            ['daily-duas', 'Duas to Recite Before Sleeping and After Waking Up', 'Simple, authentic supplications that bring peace at night and gratitude in the morning.', 4, 'images/blog/hands-prayer.jpg'],
            ['health', 'Powerful Duas for Healing and Recovery', 'Turn to Allah with hope when you or a loved one are unwell, trusting in His mercy.', 6, 'images/blog/hands-prayer.jpg'],
            ['health', 'Duas for Mental Peace and Emotional Strength', 'When anxiety feels heavy, these duas remind us that calm comes from remembering Allah.', 5, 'images/blog/prayer.jpg'],
            ['family', 'Duas for Parents, Spouse, and Children', 'Ask Allah to bless your family with guidance, love, and protection in this life and the next.', 5, 'images/blog/family.jpg'],
            ['family', 'How to Make Dua for a Righteous Family', 'Build a habit of praying together and making sincere dua for one another.', 4, 'images/blog/family.jpg'],
            ['umrah', 'Essential Duas to Learn Before Your Umrah Journey', 'Prepare spiritually with the key supplications for ihram, tawaf, and sa’i.', 7, 'images/blog/kaaba.jpg'],
            ['umrah', 'What to Pray at the Kaaba and Between Safa and Marwa', 'A practical guide to meaningful dua during the most sacred moments of Umrah.', 6, 'images/blog/pilgrims.jpg'],
            ['essentials', 'The Etiquette of Making Dua: What Every Muslim Should Know', 'Learn how to ask Allah with humility, certainty, and good manners.', 5, 'images/blog/quran.jpg'],
            ['essentials', 'Best Times When Duas Are Most Likely to Be Accepted', 'From the last third of the night to Fridays after Asr — moments worth remembering.', 6, 'images/blog/mosque.jpg'],
            ['ramadan', 'Duas for the First, Middle, and Last Ten Days of Ramadan', 'Align your heart with the unique spiritual themes of each part of the blessed month.', 5, 'images/blog/ramadan.jpg'],
            ['ramadan', 'What to Pray on Laylatul Qadr', 'Capture the night that is better than a thousand months with sincere, focused dua.', 4, 'images/blog/mosque.jpg'],
        ];

        foreach ($posts as [$categorySlug, $title, $excerpt, $readTime, $image]) {
            $category = BlogCategory::query()->where('slug', $categorySlug)->firstOrFail();
            $slug = Str::slug($title);

            BlogPost::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'blog_category_id' => $category->id,
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => "<p>{$excerpt}</p><p>My Dua List helps Muslims collect, organize, and share dua requests for every occasion — from Ramadan and Umrah to daily family life. Keep your supplications close, stay consistent, and share the blessings with those you love.</p><p>May Allah accept your duas and grant you ease in every step of your journey.</p>",
                    'featured_image' => $image,
                    'read_time_minutes' => $readTime,
                    'is_published' => true,
                    'published_at' => now()->subDays($readTime),
                ],
            );
        }
    }
}
