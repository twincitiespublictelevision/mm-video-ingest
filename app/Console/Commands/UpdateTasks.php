<?php

namespace App\Console\Commands;

use App\Components\IngestComponent;
use App\Models\TaskModel;
use Illuminate\Console\Command;

class UpdateTasks extends Command {

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'ingest:update';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Updates the status of all tasks in the system that are not complete';

  /**
   * Fires the command
   */
  public function fire(IngestComponent $ingester) {
    $updatableTasks = TaskModel::whereIn('status', [TaskModel::STAGING, TaskModel::IN_PROGRESS])
      ->get();

    foreach ($updatableTasks as $updatableTask) {
      $ingester->updateIngestTask($updatableTask);
    }
  }
}