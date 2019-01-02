<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskModel extends Model implements Task {
  protected $table = 'task';

  protected $fillable = [
    'title',
    'description_short',
    'description_long',
    'object_type',
    'premiered_on',
    'encored_on',
    'slug',
    'tags',
    'topics',
    'base_url',
    'video_file',
    'image_file',
    'caption_file',
    'show_slug',
    'parent_title',
    'parent_slug',
    'parent_description_short',
    'parent_description_long',
    'episode_number',
    'parent_premiered_on',
    'parent_encored_on'
  ];

  /**
   * Tests if the task is targeting an episode
   *
   * @return bool
   */
  public function isEpisodeTask() {
    return $this->episode_number !== null;
  }

  /**
   * Tests if the task is targeting a special
   *
   * @return bool
   */
  public function isSpecialTask() {
    return !$this->isEpisodeTask();
  }

  /**
   * Extracts the four digit year of the premiere date of the parent container
   *
   * @return string
   */
  public function getParentPremiereYear() {
    return substr($this->parent_premiered_on, 0, 4);
  }

  /**
   * Extracts the four digit year that indicates the season of the parent
   *
   * @return string
   */
  public function getSeasonYear() {
    return $this->getParentPremiereYear();
  }

  /**
   * Attempts to extract the season number from an episode number
   *
   * @return int
   */
  public function getSeasonNumber() {
    return (int)floor(((int) $this->episode_number) / 100);
  }

  /**
   * Attempts to extact the episode number in relation to the season
   * from the full episode number
   *
   * @return int
   */
  public function getEpisodeNumber() {
    return ((int) $this->episode_number) % 100;
  }

  /**
   * Generates the full url to the video file for the task
   *
   * @return string
   */
  public function getVideoUrl() {
    return $this->base_url.'/'.$this->video_file;
  }

  /**
   * Generates the full url to the caption file for the task
   *
   * @return string
   */
  public function getCaptionUrl() {
    return !empty($this->caption_file) ? $this->base_url.'/'.$this->caption_file : '';
  }

  /**
   * Generates the full url to the image file for the task
   *
   * @return string
   */
  public function getImageUrl() {
    return $this->base_url.'/'.$this->image_file;
  }

  /**
   * Translates encore date strings from the database (stored as Central time)
   * to UTC time as required by the Media Manager API
   *
   * @param $value
   * @return string
   */
  public function getEncoredOnAttribute($value) {
    return $this->_toISO8601($value);
  }

  /**
   * Translates premiere date strings from the database (stored as Central time)
   * to UTC time as required by the Media Manager API
   *
   * @param $value
   * @return string
   */
  public function getPremieredOnAttribute($value) {
    return $this->_toISO8601($value);
  }

  /**
   * Translates parent encore date strings from the database
   * (stored as Central time) to UTC time as required by the Media Manager API
   *
   * @param $value
   * @return string
   */
  public function getParentEncoredOnAttribute($value) {
    return $this->_toISO8601($value);
  }

  /**
   * Translates parent premiere date strings from the database
   * (stored as Central time) to UTC time as required by the Media Manager API
   *
   * @param $value
   * @return string
   */
  public function getParentPremieredOnAttribute($value) {
    return $this->_toISO8601($value);
  }

  private function _toISO8601($value) {
    $date = new \DateTime(null, new \DateTimeZone('UTC'));
    $date->setTimestamp(strtotime($value));

    return $date->format('Y-m-d\TH:i:s\Z');
  }

  public function getImageData() {
    $file = @file_get_contents($this->getImageUrl());

    return $file !== false ? base64_encode($file) : null;
  }

  /**
   * Returns true if this task failed due to asset processing
   *
   * @return bool
   */
  public function hasAssetFailed() {
    return $this->status === self::ASSET_FAILED;
  }

  /**
   * Resets the progress of the task to the base state
   */
  public function reset() {
    $this->pbs_content_id = null;
    $this->tp_media_id = null;
    $this->failure_reason = null;
    $this->status = self::PENDING;
  }

  /**
   * Attempts to retry the task. If there are reties left, reset the task
   * and return true. If there are no retries left return false
   */
  public function retry() {
    if ($this->retries > 0) {
      $this->reset();
      $this->retries = $this->retries - 1;

      return true;
    }

    return false;
  }
}