<?php

namespace App\Models;

interface Task {
  const PENDING = 'pending';
  const STAGING = 'staging';
  const STAGED = 'staged';
  const IN_PROGRESS = 'in_progress';
  const CANCELLED = 'cancelled';
  const SEASON_FAILED = 'season_failed';
  const SPECIAL_FAILED = 'special_failed';
  const EPISODE_FAILED = 'episode_failed';
  const ASSET_FAILED = 'asset_failed';
  const DONE = 'done';

  const OUT_OF_INGEST_TASKS = [
    Task::PENDING,
    Task::SEASON_FAILED,
    Task::SPECIAL_FAILED,
    Task::EPISODE_FAILED,
    Task::ASSET_FAILED,
    Task::DONE
  ];

  /**
   * Tests if the task is targeting an episode
   *
   * @return bool
   */
  public function isEpisodeTask();

  /**
   * Tests if the task is targeting a special
   *
   * @return bool
   */
  public function isSpecialTask();

  /**
   * Extracts the four digit year of the premiere date of the parent container
   *
   * @return string
   */
  public function getParentPremiereYear();

  /**
   * Extracts the four digit year that indicates the season of the parent
   *
   * @return string
   */
  public function getSeasonYear();

  /**
   * Attempts to extract the season number from an episode number
   *
   * @return int
   */
  public function getSeasonNumber();

  /**
   * Attempts to extact the episode number in relation to the season
   * from the full episode number
   *
   * @return int
   */
  public function getEpisodeNumber();

  /**
   * Generates the full url to the video file for the task
   *
   * @return string
   */
  public function getVideoUrl();

  /**
   * Generates the full url to the caption file for the task
   *
   * @return string
   */
  public function getCaptionUrl();

  /**
   * Generates the full url to the image file for the task
   *
   * @return string
   */
  public function getImageUrl();

  /**
   * Translates encore date strings from the database (stored as Central time)
   * to UTC time as required by the Media Manager API
   *
   * @param $value
   * @return string
   */
  public function getEncoredOnAttribute($value);

  /**
   * Translates premiere date strings from the database (stored as Central time)
   * to UTC time as required by the Media Manager API
   *
   * @param $value
   * @return string
   */
  public function getPremieredOnAttribute($value);

  /**
   * Translates parent encore date strings from the database
   * (stored as Central time) to UTC time as required by the Media Manager API
   *
   * @param $value
   * @return string
   */
  public function getParentEncoredOnAttribute($value);

  /**
   * Translates parent premiere date strings from the database
   * (stored as Central time) to UTC time as required by the Media Manager API
   *
   * @param $value
   * @return string
   */
  public function getParentPremieredOnAttribute($value);

  /**
   * @return string
   */
  public function getImageData();

  /**
   * Returns true if this task failed due to asset processing
   *
   * @return bool
   */
  public function hasAssetFailed();

  /**
   * Resets the progress of the task to the base state
   */
  public function reset();

  /**
   * Attempts to retry the task. If there are reties left, reset the task
   * and return true. If there are no retries left return false
   *
   * @return bool
   */
  public function retry();

  /**
   * @return bool
   */
  public function save();

  public function touch();

  public function __get($name);

  public function __set($name, $value);

  public function __isset($name);
}