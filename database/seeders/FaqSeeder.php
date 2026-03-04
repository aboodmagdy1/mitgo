<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            [
                'title' => [
                    'en' => 'What is the best way to contact you?',
                    'ar' => 'كيف يمكنني التواصل معك؟',
                ],
                'description' => [
                    'en' => 'You can contact us via email or phone.',
                    'ar' => 'يمكنك التواصل معنا عبر البريد الإلكتروني أو الهاتف.',
                ],
            ],
            [
                'title' => [
                    'en' => 'What is the best way to contact you?',
                    'ar' => 'كيف يمكنني التواصل معك؟',
                ],
                'description' => [
                    'en' => 'You can contact us via email or phone.',
                    'ar' => 'يمكنك التواصل معنا عبر البريد الإلكتروني أو الهاتف.',
                ],
            ],
        ];

        foreach($questions as $question){
            Faq::create($question);
        }
    }
}
