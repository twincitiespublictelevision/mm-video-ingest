<?php

namespace App\Console\Commands;

use App\Components\IngestComponent;
use App\Components\TaskMapper;
use App\Models\Task;
use App\Models\TaskModel;
use Illuminate\Console\Command;

class IngestNext extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ingest:next';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Ingests the next task available';

  /**
   * Fires the command
   */
  public function fire(IngestComponent $ingester) {

    // Get the list of currently processing tasks
    $processingTasks = TaskModel::where('status', Task::IN_PROGRESS)
      ->count();

    // Check to make sure we are below the maximum of allowed concurrent tasks
    if ($processingTasks < (int) env('CONCURRENT_TASKS')) {

      // Grab the next available tasks
      $availableTasks = TaskModel::whereIn('status', [Task::PENDING, Task::STAGED])
        ->orderBy('id', 'ASC')
        ->get();

      // Loop through the available tasks and start them up to the
      // concurrency limit
      foreach ($availableTasks as $task) {

        // Make sure the concurrency limit is respected
        if ($processingTasks < (int) env('CONCURRENT_TASKS')) {

          // Before processing a task, make sure there is not another task with
          // the same slug already processing
          $inProcessCheck = TaskModel::where('slug', $task->slug)
              ->where('id', '!=', $task->id)
              ->where('status', [Task::STAGING, Task::STAGED, Task::IN_PROGRESS])
              ->count() > 0;

          // If it isn't already processing then start it
          if (!$inProcessCheck) {
            if ($ingester->ingest(TaskMapper::map($task)) === Task::IN_PROGRESS) {
              $processingTasks++;
            }
          }
        }
      }
    }
  }
}