<?php

namespace App\Components;

use App\Models\TaskModel;
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
   * @param TaskModel $task
   * @return ValidationResult
   */
  public function validate(TaskModel $task) {

    $validationResult = new ValidationResult(true);
    $validationMessages = [];

    if (($task->getAttribute('title') === null) || empty($task->getAttribute('title'))) {
      $validationMessages[] = 'Expected a title.';
    }

    if (($task->getAttribute('slug') === null) || empty($task->getAttribute('slug'))) {
      $validationMessages[] = 'Expected a slug.';
    }

    if (($task->getAttribute('base_url') === null) || empty($task->getAttribute('base_url'))) {
      $validationMessages[] = 'Expected a base_url.';
    }

    if (($task->getAttribute('video_file') === null) || empty($task->getAttribute('video_file'))) {
      $validationMessages[] = 'Expected a video_file.';
    }

    if (($task->getAttribute('image_file') === null) || empty($task->getAttribute('image_file'))) {
      $validationMessages[] = 'Expected a image_file.';
    }

    if (($task->getAttribute('parent_title') === null) || empty($task->getAttribute('parent_title'))) {
      $validationMessages[] = 'Expected a parent title.';
    }

    if (($task->getAttribute('parent_slug') === null) || empty($task->getAttribute('parent_slug'))) {
      $validationMessages[] = 'Expected a parent_slug.';
    }

    if (($task->getAttribute('status') === null) || ($task->getAttribute('status') !== TaskModel::PENDING)) {
      $validationMessages[] = 'Expected task status to be pending.';
    }

    if (($task->getAttribute('episode_number') !== null) &&
        ($task->getAttribute('episode_number') <= 100)) {
        $validationMessages[] = 'Expected episode number to be >100.';
    }

    if (($task->getAttribute('object_type') === null) ||
        (!in_array($task->getAttribute('object_type'), ['clip', 'preview', 'full_length']))) {
      $validationMessages[] = 'Expected object type to be clip, preview, or full_length.';
    }

    if (!empty($validationMessages)) {
      $validationResult->setValidationResult(false);
      $validationResult->addMessage($validationMessages);
    }

    return $validationResult;
  }
}