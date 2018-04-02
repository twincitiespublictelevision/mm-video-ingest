<?php

use App\Components\TaskValidationComponent;
use App\Models\TaskModel;
use App\Models\ValidationResult;
use Laravel\Lumen\Testing\DatabaseTransactions;
use twincitiespublictelevision\PBS_Media_Manager_Client\PBS_Media_Manager_API_Client;

class TaskValidationComponentTest extends TestCase {
  use DatabaseTransactions;

  private $client;

  /**
   * @before
   */
  public function setupClient() {
    $this->client = $this->getMockBuilder(PBS_Media_Manager_API_Client::class)->getMock();
  }

  private function _testAttributePopulated($attributeName) {
    $task = factory(TaskModel::class)->make([$attributeName => '']);

    $this->client->method('get_show')
                 ->willReturn(['data' => ['id' => '123', 'attributes' => []]]);
    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertFalse(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testHasTitle() {
    $this->_testAttributePopulated('title');
  }

  public function testHasSlug() {
    $this->_testAttributePopulated('slug');
  }

  public function testHasBaseURL() {
    $this->_testAttributePopulated('base_url');
  }

  public function testHasVideoFile() {
    $this->_testAttributePopulated('video_file');
  }

  public function testHasImageFile() {
    $this->_testAttributePopulated('image_file');
  }

  public function testHasParentSlug() {
    $this->_testAttributePopulated('parent_slug');
  }

  public function testObjectTypeValid() {
    $task = factory(TaskModel::class)->make(['object_type' => 'faketype']);

    $this->client->method('get_show')
                 ->willReturn(['data' => ['id' => '123', 'attributes' => []]]);
    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertFalse(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testValidEpisodeNumber() {
    $task = factory(TaskModel::class)->make(['episode_number' => 10]);

    $this->client->method('get_show')
                 ->willReturn(['data' => ['id' => '123', 'attributes' => []]]);
    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertFalse(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testValidEpisodeAssetUpdateTask() {
    $task = factory(TaskModel::class)->make([
      'show_slug' => 'showA',
      'parent_slug' => 'parentA',
      'status' => TaskModel::PENDING
    ]);

    $this->client->method('get_asset')
      ->willReturn([
        'data' => [
          'id' => '123',
          'attributes' => [
            'parent_tree' => [
              'type' => 'episode',
              'attributes' => [
                'slug' => 'parentA',
                'season' => [
                  'type' => 'season',
                  'attributes' => [
                    'show' => [
                      'type' => 'show',
                      'attributes' => [
                        'slug' => 'showA'
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]
      ]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertTrue(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testValidEpisodeAssetCreateTask() {
    $task = factory(TaskModel::class)->make([
      'show_slug' => 'showA',
      'parent_slug' => 'parentA',
      'status' => TaskModel::PENDING
    ]);

    $this->client->method('get_episode')
      ->willReturn([
        'data' => [
          'id' => '123',
          'attributes' => [
            'show' => [
              'type' => 'show',
              'attributes' => [
                'slug' => 'showA'
              ]
            ]
          ]
        ]
      ]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertTrue(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testInvalidEpisodeAssetUpdateTask() {
    $task = factory(TaskModel::class)->make([
      'show_slug' => 'showA',
      'parent_slug' => 'parentA',
      'status' => TaskModel::PENDING
    ]);

    $this->client->method('get_asset')
      ->willReturn([
        'data' => [
          'id' => '123',
          'attributes' => [
            'parent_tree' => [
              'type' => 'episode',
              'attributes' => [
                'slug' => 'parentB'
              ]
            ]
          ]
        ]
      ]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertFalse(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testInvalidEpisodeAssetCreateTask() {
    $task = factory(TaskModel::class)->make([
      'show_slug' => 'showA',
      'parent_slug' => 'parentA',
      'status' => TaskModel::PENDING
    ]);

    $this->client->method('get_episode')
      ->willReturn([
        'data' => [
          'id' => '123',
          'attributes' => [
            'show' => [
              'type' => 'show',
              'attributes' => [
                'slug' => 'showB'
              ]
            ]
          ]
        ]
      ]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertFalse(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testValidSpecialAssetUpdateTask() {
    $task = factory(TaskModel::class)->make([
      'show_slug' => 'showA',
      'parent_slug' => 'parentA',
      'episode_number' => null,
      'status' => TaskModel::PENDING
    ]);

    $this->client->method('get_asset')
      ->willReturn([
        'data' => [
          'id' => '123',
          'attributes' => [
            'parent_tree' => [
              'type' => 'special',
              'attributes' => [
                'slug' => 'parentA',
                'show' => [
                  'type' => 'show',
                  'attributes' => [
                    'slug' => 'showA'
                  ]
                ]
              ]
            ]
          ]
        ]
      ]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertTrue(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testValidSpecialAssetCreateTask() {
    $task = factory(TaskModel::class)->make([
      'show_slug' => 'showA',
      'parent_slug' => 'parentA',
      'episode_number' => null,
      'status' => TaskModel::PENDING
    ]);

    $this->client->method('get_special')
      ->willReturn([
        'data' => [
          'id' => '123',
          'attributes' => [
            'show' => [
              'type' => 'show',
              'attributes' => [
                'slug' => 'showA'
              ]
            ]
          ]
        ]
      ]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertTrue(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testInvalidSpecialAssetUpdateTask() {
    $task = factory(TaskModel::class)->make([
      'show_slug' => 'showA',
      'parent_slug' => 'parentA',
      'episode_number' => null,
      'status' => TaskModel::PENDING
    ]);

    $this->client->method('get_asset')
      ->willReturn([
        'data' => [
          'id' => '123',
          'attributes' => [
            'parent_tree' => [
              'type' => 'special',
              'attributes' => [
                'slug' => 'parentB',
                'show' => [
                  'type' => 'show',
                  'attributes' => [
                    'slug' => 'showA'
                  ]
                ]
              ]
            ]
          ]
        ]
      ]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertFalse(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testInvalidSpecialAssetCreateTask() {
    $task = factory(TaskModel::class)->make([
      'show_slug' => 'showA',
      'parent_slug' => 'parentA',
      'episode_number' => null,
      'status' => TaskModel::PENDING
    ]);

    $this->client->method('get_special')
      ->willReturn([
        'data' => [
          'id' => '123',
          'attributes' => [
            'show' => [
              'type' => 'show',
              'attributes' => [
                'slug' => 'showB'
              ]
            ]
          ]
        ]
      ]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertFalse(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testValidShow() {
    $task = factory(TaskModel::class)->make();
    $this->client->method('get_show')
      ->willReturn(['data' => ['id' => '123', 'attributes' => []]]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertTrue(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }

  public function testInvalidShow() {
    $task = factory(TaskModel::class)->make();

    $this->client->method('get_show')
      ->willReturn(['errors' => ['info' => [], 'response' => null]]);

    $validator = new TaskValidationComponent($this->client);
    $result = $validator->validate($task);
    $this->assertFalse(
      $result->getValidationResult(), implode("\n", $result->getMessages())
    );
  }
}