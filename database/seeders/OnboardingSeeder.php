<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Onboarding;

class OnboardingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $onboardings = [
            [
                'title' => 'Welcome to Auto Brokers',
                'description' => 'Your trusted platform for finding the best automotive deals and connecting with reliable providers.',
                'order' => 1,
            ],
            [
                'title' => 'Browse Categories',
                'description' => 'Explore our wide range of automotive categories including cars, parts, and services.',
                'order' => 2,
            ],
            [
                'title' => 'Find Providers',
                'description' => 'Connect with verified automotive providers in your area for quality service and products.',
                'order' => 3,
            ],
            [
                'title' => 'Get Started',
                'description' => 'Create your account and start exploring the world of automotive excellence.',
                'order' => 4,
            ],
        ];

        foreach ($onboardings as $onboarding) {
            Onboarding::create($onboarding);
        }
    }
}
