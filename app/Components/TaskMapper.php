<?php

namespace App\Components;

use App\Models\AlmanacTask;
use App\Models\Task;
use App\Models\TaskModel;

/**
 * Class TaskMapper
 * @package App\Components
 */
class TaskMapper {
  const MAP = [
    'almanac' => AlmanacTask::class
  ];

  /**
   * @param TaskModel $model
   * @return Task
   */
  public static function map(TaskModel $model): Task {
    if (isset(self::MAP[$model->show_slug])) {
      $wrapper = self::MAP[$model->show_slug];
      return new $wrapper($model);
    }

    return $model;
  }
}