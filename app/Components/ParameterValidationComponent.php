<?php

namespace App\Components;

use App\Models\Task;
use App\Models\ValidationResult;

/**
 * Class ParameterValidationComponent
 * @package App\Components
 */
class ParameterValidationComponent {

  /**
   * Validates the data inside of a task to make sure that parameters are populated
   * and of the correct types.
   *
   * @param Task $task
   * @return ValidationResult
   */
  public function validate(Task $task) {

    $validationResult = new ValidationResult(true);
    $validationMessages = [];

    if (($task->title === null) || empty($task->title)) {
      $validationMessages[] = 'Expected a title.';
    }

    if (($task->slug === null) || empty($task->slug)) {
      $validationMessages[] = 'Expected a slug.';
    }

    if (($task->base_url === null) || empty($task->base_url)) {
      $validationMessages[] = 'Expected a base_url.';
    }

    if (($task->video_file === null) || empty($task->video_file)) {
      $validationMessages[] = 'Expected a video_file.';
    }

    if (($task->image_file === null) || empty($task->image_file)) {
      $validationMessages[] = 'Expected a image_file.';
    }

    if (($task->parent_title === null) || empty($task->parent_title)) {
      $validationMessages[] = 'Expected a parent title.';
    }

    if (($task->parent_slug === null) || empty($task->parent_slug)) {
      $validationMessages[] = 'Expected a parent_slug.';
    }

    if (($task->status === null) || ($task->status !== Task::PENDING)) {
      $validationMessages[] = 'Expected task status to be pending.';
    }

    if (($task->episode_number !== null) &&
        ($task->episode_number <= 100)) {
        $validationMessages[] = 'Expected episode number to be >100.';
    }

    if (($task->object_type === null) ||
        (!in_array($task->object_type, ['clip', 'preview', 'full_length']))) {
      $validationMessages[] = 'Expected object type to be clip, preview, or full_length.';
    }

    if (!empty($validationMessages)) {
      $validationResult->setValidationResult(false);
      $validationResult->addMessage($validationMessages);
    }

    return $validationResult;
  }
}