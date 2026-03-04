<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $pages = [
        [
                'title' => ['ar' => 'الشروط والأحكام', 'en' => 'Terms and Conditions'],
                'slug' => 'terms-and-conditions',
            'page_layout_ar'=>'<section>Arabic content for company</section>',
            'page_layout_en'=>'<section>English content for company</section>',
        ],
        [
            'title' => ['ar' => 'الخصوصية', 'en' => 'Privacy Policy'],
            'slug' => 'privacy-policy',
            'page_layout_ar'=>'<section>Arabic content for privacy policy</section>',
            'page_layout_en'=>'<section>English content for privacy policy</section>',
        ],
        
       ];
       foreach ($pages as $page) {
        Page::create($page);
       }
    }
}
