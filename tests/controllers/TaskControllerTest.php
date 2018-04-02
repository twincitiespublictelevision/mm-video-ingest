<?php

use Laravel\Lumen\Testing\DatabaseTransactions;

class TaskControllerTest extends TestCase {
  use DatabaseTransactions;

  public function testCanCreateTasksFromJSON() {
    $payload = [
      'title' => 'Test Auto Ingest',
      'description_short' => 'test short auto',
      'description_long' => 'test long auto',
      'object_type' => 'clip',
      'premiered_on' => '2013-11-05',
      'encored_on' => '2013-11-23',
      'slug' => 'the-clip-slug',
      'tags' => '',
      'topics' => '',
      'base_url' => 'http =>//0.0.0.0',
      'video_file' => 'video.mp4',
      'image_file' => 'image.jpg',
      'caption_file' => 'caption.srt',
      'show_slug' => 'almanac',
      'parent_title' => 'Test Eps Container',
      'parent_slug' => 'test-eps-contained',
      'parent_description_short' => 'Test Eps Short Desc',
      'parent_description_long' => 'Test Eps Long Desc',
      'episode_number' => 3305
    ];

    $jsonResponse = $payload;
    $jsonResponse['premiered_on'] = '2013-11-05T06:00:00Z';
    $jsonResponse['encored_on'] = '2013-11-23T06:00:00Z';

    $this->json('POST', '/tasks/', $payload)->seeJson($jsonResponse);

    // Silence warnings
    $this->assertEquals(true, true);
  }
}
