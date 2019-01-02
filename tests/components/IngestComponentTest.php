<?php

use App\Components\IngestComponent;
use App\Components\TaskValidationComponent;
use App\Models\TaskModel;
use Laravel\Lumen\Testing\DatabaseTransactions;
use twincitiespublictelevision\PBS_Media_Manager_Client\PBS_Media_Manager_API_Client;

class IngestComponentTest extends TestCase {
  use DatabaseTransactions;

  private $client;
  private $validator;

  /**
   * @before
   */
  public function setupClient() {
    $this->client = $this->getMockBuilder(PBS_Media_Manager_API_Client::class)->getMock();
    $this->validator = $this->getMockBuilder(TaskValidationComponent::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->validator->method('validate')->willReturn(true);
  }

  public function testIngestChecksAndCreatesSpecials() {
    $this->client->method('get_request')->willReturn(
      ['errors' => ['info' => [], 'response' => null]]
    );

    $task = factory(TaskModel::class)->make([
      'status' => TaskModel::PENDING,
      'episode_number' => null
    ]);

    $this->client->expects($this->once())
      ->method('get_special')
      ->with($task->parent_slug);

    $this->client->expects($this->once())
      ->method('create_child')
      ->with(
        $task->show_slug,
        'show',
        'special',
        [
          'title' => $task->parent_title,
          'description_short' => $task->parent_description_short,
          'description_long' => $task->parent_description_long,
          'slug' => $task->parent_slug
        ]
      );

    $ingestor = new IngestComponent($this->client, $this->validator);
    $this->assertEquals(
      TaskModel::SPECIAL_FAILED,
      $ingestor->ingest($task)
    );
  }

  public function testHandlesSpecialCreateError() {
    $this->client->method('get_request')->willReturn(
      ['errors' => ['info' => [], 'response' => null]]
    );

    $task = factory(TaskModel::class)->make([
      'status' => TaskModel::PENDING,
      'episode_number' => null
    ]);

    $this->client->expects($this->once())
      ->method('get_special')
      ->with($task->parent_slug);

    $this->client->expects($this->once())
      ->method('create_child')
      ->with(
        $task->show_slug,
        'show',
        'special',
        [
          'title' => $task->parent_title,
          'description_short' => $task->parent_description_short,
          'description_long' => $task->parent_description_long,
          'slug' => $task->parent_slug
        ]
      )
      ->willReturn(
        ['errors' => ['errors' => ['failure'], 'result' => []]]
      );

    $ingestor = new IngestComponent($this->client, $this->validator);
    $result = $ingestor->ingest($task);

    $this->assertEquals(
      TaskModel::SPECIAL_FAILED,
      $result
    );
    $this->assertEquals(
      json_encode(['errors' => ['errors' => ['failure'], 'result' => []]]),
      $task->failure_reason
    );
  }

  public function testIngestChecksAndCreatesSeasons() {
    $this->client->method('get_request')->willReturn(
      ['errors' => ['info' => [], 'response' => null]]
    );

    $task = factory(TaskModel::class)->make([
      'status' => TaskModel::PENDING
    ]);

    $this->client->expects($this->exactly(2))
      ->method('get_show')
      ->with($task->show_slug)
      ->willReturn([
        'data' => [
          'attributes' => [
            'ordinal_season' => true
          ]
        ]
      ]);

    $this->client->expects($this->once())
      ->method('get_show_seasons')
      ->with(
        $task->show_slug,
        ['ordinal' => $task->getSeasonNumber()]
      );

    $this->client->expects($this->once())
      ->method('create_child')
      ->with(
        $task->show_slug,
        'show',
        'season',
        [
          'ordinal' => $task->getSeasonNumber(),
        ]
      );

    $ingestor = new IngestComponent($this->client, $this->validator);
    $this->assertEquals(
      TaskModel::SEASON_FAILED,
      $ingestor->ingest($task)
    );
  }

  public function testIngestCreatesNonOrdinalSeason() {

    $this->client->method('get_request')->willReturn(
      ['errors' => ['info' => [], 'response' => null]]
    );

    /**
     * @var \App\Models\Task $task
     */
    $task = factory(TaskModel::class)->make([
      'status' => TaskModel::PENDING
    ]);

    $this->client->expects($this->exactly(2))
      ->method('get_show')
      ->with($task->show_slug)
      ->willReturn([
        'data' => [
          'attributes' => [
            'ordinal_season' => false
          ]
        ]
      ]);

    $this->client->expects($this->once())
      ->method('get_show_seasons')
      ->with(
        $task->show_slug,
        ['ordinal' => $task->getSeasonYear()]
      );

    $this->client->expects($this->once())
      ->method('create_child')
      ->with(
        $task->show_slug,
        'show',
        'season',
        [
          'ordinal' => $task->getSeasonYear(),
        ]
      );

    $ingestor = new IngestComponent($this->client, $this->validator);
    $this->assertEquals(
      TaskModel::SEASON_FAILED,
      $ingestor->ingest($task)
    );
  }

  public function testHandlesSeasonCreateError() {
    $this->client->method('get_request')->willReturn(
      ['errors' => ['info' => [], 'response' => null]]
    );

    $task = factory(TaskModel::class)->make([
      'status' => TaskModel::PENDING
    ]);

    $this->client->expects($this->exactly(2))
      ->method('get_show')
      ->with($task->show_slug)
      ->willReturn([
        'data' => [
          'attributes' => [
            'ordinal_season' => true
          ]
        ]
      ]);

    $this->client->expects($this->once())
      ->method('get_show_seasons')
      ->with(
        $task->show_slug,
        ['ordinal' => $task->getSeasonNumber()]
      );

    $this->client->expects($this->once())
      ->method('create_child')
      ->with(
        $task->show_slug,
        'show',
        'season',
        [
          'ordinal' => $task->getSeasonNumber(),
        ]
      )
      ->willReturn(
        ['errors' => ['errors' => ['failure'], 'result' => []]]
      );

    $ingestor = new IngestComponent($this->client, $this->validator);
    $result = $ingestor->ingest($task);

    $this->assertEquals(
      TaskModel::SEASON_FAILED,
      $result
    );
    $this->assertEquals(
      json_encode(['errors' => ['errors' => ['failure'], 'result' => []]]),
      $task->failure_reason
    );
  }

  public function testIngestChecksAndCreatesEpisodes() {
    $this->client->method('get_request')->willReturn(
      ['errors' => ['info' => [], 'response' => null]]
    );

    $this->client->method('get_show')->willReturn([
        'data' => [
          'attributes' => [
            'ordinal_season' => true
          ]
        ]
      ]);

    $this->client->method('get_show_seasons')->willReturn([
      ['id' => '12345']
    ]);

    $task = factory(TaskModel::class)->make([
      'status' => TaskModel::PENDING
    ]);

    $this->client->expects($this->once())
      ->method('get_episode')
      ->with($task->parent_slug);

    $this->client->expects($this->once())
      ->method('create_child')
      ->with(
        '12345',
        'season',
        'episode',
        [
          'title' => $task->parent_title,
          'description_short' => $task->parent_description_short,
          'description_long' => $task->parent_description_long,
          'slug' => $task->parent_slug,
          'ordinal' => $task->getEpisodeNumber(),
          'premiered_on' => date('Y-m-d', strtotime($task->parent_premiered_on)),
          'encored_on' => date('Y-m-d', strtotime($task->parent_encored_on))
        ]
      );

    $ingestor = new IngestComponent($this->client, $this->validator);
    $this->assertEquals(
      TaskModel::EPISODE_FAILED,
      $ingestor->ingest($task)
    );
  }


  public function testHandlesEpisodeCreateError() {
    $this->client->method('get_request')->willReturn(
      ['errors' => ['info' => [], 'response' => null]]
    );

    $this->client->method('get_show')->willReturn([
      'data' => [
        'attributes' => [
          'ordinal_season' => true
        ]
      ]
    ]);

    $this->client->method('get_show_seasons')->willReturn([
      ['id' => '12345']
    ]);

    $task = factory(TaskModel::class)->make([
      'status' => TaskModel::PENDING
    ]);

    $this->client->expects($this->once())
      ->method('get_episode')
      ->with($task->parent_slug);

    $this->client->expects($this->once())
      ->method('create_child')
      ->with(
        '12345',
        'season',
        'episode',
        [
          'title' => $task->parent_title,
          'description_short' => $task->parent_description_short,
          'description_long' => $task->parent_description_long,
          'slug' => $task->parent_slug,
          'ordinal' => $task->getEpisodeNumber(),
          'premiered_on' => date('Y-m-d', strtotime($task->parent_premiered_on)),
          'encored_on' => date('Y-m-d', strtotime($task->parent_encored_on))
        ]
      )
      ->willReturn(
        ['errors' => ['errors' => ['failure'], 'result' => []]]
      );

    $ingestor = new IngestComponent($this->client, $this->validator);
    $result = $ingestor->ingest($task);

    $this->assertEquals(
      TaskModel::EPISODE_FAILED,
      $result
    );
    $this->assertEquals(
      json_encode(['errors' => ['errors' => ['failure'], 'result' => []]]),
      $task->failure_reason
    );
  }

  public function testIngestChecksAndClearsAssets() {
    foreach (['clip', 'preview', 'full_length'] as $type) {
      $task = factory(TaskModel::class)->make([
        'object_type' => $type,
        'status' => TaskModel::PENDING
      ]);

      $this->client = $this->getMockBuilder(PBS_Media_Manager_API_Client::class)->getMock();

      $this->client->method('get_request')->willReturn(
        ['errors' => ['info' => [], 'response' => null]]
      );
      $this->client->method('get_show')->willReturn([
        'data' => [
          'attributes' => [
            'ordinal_season' => true
          ]
        ]
      ]);
      $this->client->method('get_show_seasons')->willReturn([
        ['id' => '12345']
      ]);
      $this->client->method('get_episode')->willReturn([
        'data' => ['id' => '67890']
      ]);
      $this->client->method('get_asset')->willReturn([
        'errors' => []
      ]);

      $params = [
        'encored_on' => date('Y-m-d', strtotime($task->encored_on)),
        'premiered_on' => date('Y-m-d', strtotime($task->premiered_on)),
        'object_type' => $task->object_type,
        'slug' => $task->slug
      ];

      if ($task->object_type !== 'full_length') {
        $params['description_short'] = $task->description_short;
        $params['description_long'] = $task->description_long;
        $params['title'] = $task->title;
      }

      $this->client->expects($this->once())
        ->method('create_child')
        ->with(
          '67890',
          'episode',
          'asset',
          $params
        )->willReturn('zyxwv_' . $task->object_type);

      $this->client->expects($this->exactly(2))
        ->method('update_object')
        ->withConsecutive(
          ['zyxwv_' . $task->object_type, 'asset', ['video' => null]],
          ['zyxwv_' . $task->object_type, 'asset', ['caption' => null]]
        );

      $ingestor = new IngestComponent($this->client, $this->validator);
      $this->assertEquals(
        TaskModel::STAGING,
        $ingestor->ingest($task),
        sprintf('Test [%s] asset type',$task->object_type)
      );
    }
  }

  public function testIngestUpdatesAssets() {
    foreach (['clip', 'preview', 'full_length'] as $type) {
      $task = factory(TaskModel::class)->make([
        'object_type' => $type,
        'status' => TaskModel::STAGED
      ]);
      $task->pbs_content_id = 'test-content-id';

      $this->client = $this->getMockBuilder(PBS_Media_Manager_API_Client::class)->getMock();

      $this->client->method('get_request')->willReturn(
        ['errors' => ['info' => [], 'response' => null]]
      );
      $this->client->method('get_show')->willReturn([
        'data' => [
          'attributes' => [
            'ordinal_season' => true
          ]
        ]
      ]);
      $this->client->method('get_show_seasons')->willReturn([
        ['id' => '12345']
      ]);
      $this->client->method('get_episode')->willReturn([
        'data' => ['id' => '67890']
      ]);
      $this->client->method('update_object')->willReturn(true);

      $params = [
        'encored_on' => date('Y-m-d', strtotime($task->encored_on)),
        'premiered_on' => date('Y-m-d', strtotime($task->premiered_on)),
        'tags' => explode(',', $task->tags) ?: [],
        'auto_publish' => true,
        'availabilities' => [
          'public' => [
            'start' => date('Y-m-d\TH:i:s\Z', strtotime($task->premiered_on)),
            'end' => null
          ],
          'all_members' => [
            'start' => date('Y-m-d\TH:i:s\Z', strtotime($task->premiered_on)),
            'end' => null
          ],
          'station_members' => [
            'start' => date('Y-m-d\TH:i:s\Z', strtotime($task->premiered_on)),
            'end' => null
          ]
        ],
        'images' => [
          ['profile' => 'asset-mezzanine-16x9', 'source' => $task->getImageUrl()]
        ],
        'video' => [
          'profile' => 'hd-16x9-mezzanine',
          'source' => $task->getVideoUrl()
        ],
        'caption' => $task->getCaptionUrl()
      ];

      if ($task->object_type !== 'full_length') {
        $params['description_short'] = $task->description_short;
        $params['description_long'] = $task->description_long;
        $params['title'] = $task->title;
      }

      $this->client->expects($this->once())
        ->method('update_object')
        ->with(
          'test-content-id',
          'asset',
          $params
        );

      $ingestor = new IngestComponent($this->client, $this->validator);
      $this->assertEquals(
        TaskModel::IN_PROGRESS,
        $ingestor->ingest($task),
        sprintf('Test [%s] asset type',$task->object_type)
      );
    }
  }

  public function testIngestCorrectlyParsesEmptyStatus() {

    $tests = [
      [TaskModel::IN_PROGRESS, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, TaskModel::STAGED]
    ];

    foreach ($tests as $i => $test) {
      $this->setupClient();
      $ingestor = new IngestComponent($this->client, $this->validator);

      $task = factory(TaskModel::class)->make([
        'pbs_content_id' => '12345',
        'status' => $test[0]
      ]);

      $resp = [
        'data' => [
          'id' => '12345',
          'attributes' => [
            'original_video' => [],
            'original_caption' => []
          ]
        ]
      ];

      $this->assertEquals(
        $test[1],
        $ingestor->getIngestTaskStatus($task, $resp),
        'Test [' . $i . '] ' . $test[0] . ' => ' . $test[1]
      );
    }
  }

  public function testIngestSendsClearRequestOnlyWhenTaskIsStillStaging() {

    // Test Definition:
    // [ video_state, caption_state, should_call_video_clear, should_call_caption_clear ]

    $tests = [
      [[], [], false, false],
      [[], IngestComponent::CAPTION_IN_PROGRESS, false, true],
      [[], IngestComponent::CAPTION_DONE, false, true],
      [[], IngestComponent::CAPTION_FAILED, false, false],

      [IngestComponent::VIDEO_IN_PROGRESS, [], true, false],
      [IngestComponent::VIDEO_IN_PROGRESS, IngestComponent::CAPTION_IN_PROGRESS, true, true],
      [IngestComponent::VIDEO_IN_PROGRESS, IngestComponent::CAPTION_DONE, true, true],
      [IngestComponent::VIDEO_IN_PROGRESS, IngestComponent::CAPTION_FAILED, false, false],

      [IngestComponent::VIDEO_DONE, [], true, false],
      [IngestComponent::VIDEO_DONE, IngestComponent::CAPTION_IN_PROGRESS, true, true],
      [IngestComponent::VIDEO_DONE, IngestComponent::CAPTION_DONE, true, true],
      [IngestComponent::VIDEO_DONE, IngestComponent::CAPTION_FAILED, false, false],

      [IngestComponent::VIDEO_FAILED, [], false, false],
      [IngestComponent::VIDEO_FAILED, IngestComponent::CAPTION_IN_PROGRESS, false, false],
      [IngestComponent::VIDEO_FAILED, IngestComponent::CAPTION_DONE, false, false],
      [IngestComponent::VIDEO_FAILED, IngestComponent::CAPTION_FAILED, false, false]
    ];

    foreach ($tests as $i => $test) {
      $this->setupClient();
      $ingestor = new IngestComponent($this->client, $this->validator);

      $task = factory(TaskModel::class)->make([
        'pbs_content_id' => '12345',
        'status' => TaskModel::STAGING
      ]);

      $resp = [
        'data' => [
          'id' => '12345',
          'attributes' => [
            'original_video' => is_array($test[0]) && empty($test[0]) ? $test[0] : ['ingestion_status' => $test[0]],
            'original_caption' => is_array($test[1]) && empty($test[1]) ? $test[1] : ['ingestion_status' => $test[1]],
          ]
        ]
      ];

      if ($test[2] && $test[3]) {
        $this->client->expects($this->exactly(2))
          ->method('update_object')
          ->withConsecutive(
            [$task->pbs_content_id, 'asset', ['video' => null]],
            [$task->pbs_content_id, 'asset', ['caption' => null]]
          );
      } elseif ($test[2]) {
        $this->client->expects($this->once())
          ->method('update_object')
          ->withConsecutive(
            [$task->pbs_content_id, 'asset', ['video' => null]]
          );
      } elseif ($test[3]) {
        $this->client->expects($this->once())
          ->method('update_object')
          ->withConsecutive(
            [$task->pbs_content_id, 'asset', ['caption' => null]]
          );
      } else {
        $this->client->expects($this->never())
          ->method('update_object');
      }

      try {
        $ingestor->getIngestTaskStatus($task, $resp);
      } catch (Exception $e) {
        $errMsg = 'Test failed while testing ' . json_encode($test[0]) . ' ' . json_encode($test[1]);
        echo $errMsg . "\n";
        echo $e->getMessage() . "\n";
      }
    }
  }

  public function testIngestCorrectlyParsesStatus() {

    $error = (new Faker\Provider\Lorem(new \Faker\Generator()))->sentence(10);

    // Test: [Starting Status, API Video Status, API Video Error Msg, API Caption Status, API Caption Error Msg, Resulting Status]

    $tests = [
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],

      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_DONE, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_DONE, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],

      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],

      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],

      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_DONE, '', TaskModel::IN_PROGRESS],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_DONE, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],

      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::IN_PROGRESS],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],

      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],

      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_DONE, '', TaskModel::DONE],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_DONE, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],

      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::IN_PROGRESS],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],
      [TaskModel::IN_PROGRESS, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],

      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],

      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_DONE, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_DONE, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],

      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, '', IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_FAILED, $error, IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],

      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],

      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_DONE, '', TaskModel::STAGING],
      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_DONE, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],

      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::STAGING],
      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, '', IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_IN_PROGRESS, $error, IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],

      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_FAILED, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_FAILED, $error, TaskModel::ASSET_FAILED],

      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_DONE, '', TaskModel::STAGING],
      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_DONE, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_DONE, $error, TaskModel::ASSET_FAILED],

      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::STAGING],
      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_IN_PROGRESS, '', TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, '', IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],
      [TaskModel::STAGING, IngestComponent::VIDEO_DONE, $error, IngestComponent::CAPTION_IN_PROGRESS, $error, TaskModel::ASSET_FAILED],
    ];

    foreach ($tests as $i => $test) {
      $this->setupClient();
      $ingestor = new IngestComponent($this->client, $this->validator);

      $task = factory(TaskModel::class)->make([
        'pbs_content_id' => '12345',
        'status' => $test[0]
      ]);

      $resp = [
        'data' => [
          'id' => '12345',
          'attributes' => [
            'original_video' => [
              'ingestion_status' => $test[1],
              'ingestion_error' => $test[2]
            ],
            'original_caption' => [
              'ingestion_status' => $test[3],
              'ingestion_error' => $test[4]
            ]
          ]
        ]
      ];

      $this->assertEquals(
        $test[5],
        $ingestor->getIngestTaskStatus($task, $resp),
        sprintf(
          'Test [%s] Update %s => %s Where Video: %s with %s x Caption: %s with %s',
          $i,
          $test[0],
          $test[5],
          $test[1],
          $test[2],
          $test[3],
          $test[4]
        )
      );
    }
  }
}
