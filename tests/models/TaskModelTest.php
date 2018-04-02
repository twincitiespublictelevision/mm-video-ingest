<?php

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\content\LargeFileContent;

class TaskModelTest extends TestCase {

  public function testExtractsSeasonNumber() {
    $task = factory('App\Models\TaskModel')->make(['episode_number' => 5501]);

    $this->assertEquals(
      55,
      $task->getSeasonNumber()
    );
  }

  public function testExtractsEpisodeNumber() {
    $task1 = factory('App\Models\TaskModel')->make(['episode_number' => 5507]);

    $this->assertEquals(
      7,
      $task1->getEpisodeNumber()
    );

    $task1 = factory('App\Models\TaskModel')->make(['episode_number' => 5523]);

    $this->assertEquals(
      23,
      $task1->getEpisodeNumber()
    );
  }

  public function testGeneratesVideoUrl() {
    $task = factory('App\Models\TaskModel')->make([
      'base_url' => 'http://0.0.0.0',
      'video_file' => 'video.mp4'
    ]);

    $this->assertEquals(
      'http://0.0.0.0/video.mp4',
      $task->getVideoUrl()
    );
  }

  public function testGeneratesCaptionUrl() {
    $task = factory('App\Models\TaskModel')->make([
      'base_url' => 'http://0.0.0.0',
      'caption_file' => 'caption.srt'
    ]);

    $this->assertEquals(
      'http://0.0.0.0/caption.srt',
      $task->getCaptionUrl()
    );
  }

  public function testGeneratesImageUrl() {
    $task = factory('App\Models\TaskModel')->make([
      'base_url' => 'http://0.0.0.0',
      'image_file' => 'image.jpg'
    ]);

    $this->assertEquals(
      'http://0.0.0.0/image.jpg',
      $task->getImageUrl()
    );
  }

  public function testFetchesImageData() {
    $root = vfsStream::setup();
    $imageFile = vfsStream::newFile('image.jpg')
      ->withContent(LargeFileContent::withKilobytes(500))
      ->at($root);

    $task = factory('App\Models\TaskModel')->make([
      'base_url' => $root->url(),
      'image_file' => $imageFile->getName()
    ]);

    $this->assertEquals(
      base64_encode($imageFile->getContent()),
      $task->getImageData()
    );
  }

  public function testChecksIfFailedDueToAsset() {

    // Test the non-failure status
    $status = [
      \App\Models\TaskModel::PENDING,
      \App\Models\TaskModel::STAGING,
      \App\Models\TaskModel::STAGED,
      \App\Models\TaskModel::IN_PROGRESS,
      \App\Models\TaskModel::CANCELLED,
      \App\Models\TaskModel::SEASON_FAILED,
      \App\Models\TaskModel::SPECIAL_FAILED,
      \App\Models\TaskModel::EPISODE_FAILED,
      \App\Models\TaskModel::DONE,
    ];

    for ($i = 0; $i < count($status); $i++) {
      $task = factory('App\Models\TaskModel')->make([
        'status' => $status[$i]
      ]);

      $this->assertFalse(
        $task->hasAssetFailed()
      );
    }

    $task = factory('App\Models\TaskModel')->make([
      'status' => \App\Models\TaskModel::ASSET_FAILED
    ]);

    $this->assertTrue(
      $task->hasAssetFailed()
    );
  }

  public function testResetSetsFieldsToBaseValues() {
    $task = factory('App\Models\TaskModel')->make([
      'pbs_content_id' => 'abc',
      'tp_media_id' => 15,
      'failure_reason' => 'Something happened',
      'status' => \App\Models\TaskModel::ASSET_FAILED,
      'retries' => 3
    ]);

    $task->reset();

    $this->assertNull($task->pbs_content_id);
    $this->assertNull($task->tp_media_id);
    $this->assertNull($task->failure_reason);

    $this->assertEquals(
      \App\Models\TaskModel::PENDING,
      $task->status
    );
  }

  public function testRetrySetsFieldsToBaseValuesOfAvailable() {
    $task = factory('App\Models\TaskModel')->make([
      'pbs_content_id' => 'abc',
      'tp_media_id' => 15,
      'failure_reason' => 'Something happened',
      'status' => \App\Models\TaskModel::ASSET_FAILED,
      'retries' => 3
    ]);

    $result = $task->retry();

    $this->assertTrue($result);
    $this->assertNull($task->pbs_content_id);
    $this->assertNull($task->tp_media_id);
    $this->assertNull($task->failure_reason);

    $this->assertEquals(
      \App\Models\TaskModel::PENDING,
      $task->status
    );

    $this->assertEquals(
      2,
      $task->retries
    );
  }

  public function testRetryDoesNotChangeModelWhenOutOfRetries() {
    $task = factory('App\Models\TaskModel')->make([
      'pbs_content_id' => 'abc',
      'tp_media_id' => 15,
      'failure_reason' => 'Something happened',
      'status' => \App\Models\TaskModel::ASSET_FAILED,
      'retries' => 0
    ]);

    $result = $task->retry();

    $this->assertFalse($result);
    $this->assertEquals('abc', $task->pbs_content_id);
    $this->assertEquals(15, $task->tp_media_id);
    $this->assertEquals('Something happened', $task->failure_reason);

    $this->assertEquals(
      \App\Models\TaskModel::ASSET_FAILED,
      $task->status
    );

    $this->assertEquals(
      0,
      $task->retries
    );
  }
}
