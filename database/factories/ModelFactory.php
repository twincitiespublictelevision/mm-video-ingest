<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(App\User::class, function ($faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
    ];
});

$factory->define(App\Models\TaskModel::class, function(Faker\Generator $faker) {
  return [
    'id' => $faker->randomNumber(),
    'title' => $faker->sentence(),
    'description_short' => $faker->sentence(10),
    'description_long' => $faker->sentence(20),
    'object_type' => $faker->randomElement(['full_length', 'clip', 'preview']),
    'premiered_on' => $faker->unique()->dateTime->format('Y-m-d'),
    'encored_on' => $faker->unique()->dateTime->format('Y-m-d'),
    'slug' => $faker->slug(4),
    'tags' => '',
    'topics' => '',
    'base_url' => 'http://0.0.0.0',
    'video_file' => 'video.mp4',
    'image_file' => 'image.jpg',
    'caption_file' => 'caption.srt',
    'status' => \App\Models\TaskModel::PENDING,
    'created_at' => $faker->unique()->dateTime->format('Y-m-d'),
    'updated_at' => $faker->unique()->dateTime->format('Y-m-d'),
    'failure_reason' => '',
    'show_slug' => $faker->slug(2),
    'parent_title' => $faker->sentence(),
    'parent_slug' => $faker->slug(4),
    'parent_description_short' => $faker->sentence(10),
    'parent_description_long' => $faker->sentence(20),
    'episode_number' => $faker->numberBetween(101,PHP_INT_MAX),
    'parent_premiered_on' => $faker->unique()->dateTime->format('Y-m-d'),
    'parent_encored_on' => $faker->unique()->dateTime->format('Y-m-d'),
    'pbs_content_id' => null,
    'retries' => 3
  ];
});