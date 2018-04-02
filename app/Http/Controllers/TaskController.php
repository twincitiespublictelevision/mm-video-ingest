<?php

namespace App\Http\Controllers;

use App\Components\DataTablesServerComponent;
use App\Models\TaskModel;
use Illuminate\Http\Request;

class TaskController extends Controller {

  /**
   * Gets data for all the tasks in the system.
   */
  public function tasks() {
    
    /*
     * DataTables example server-side processing script.
     *
     * Please note that this script is intentionally extremely simply to show how
     * server-side processing can be implemented, and probably shouldn't be used as
     * the basis for a large complex system. It is suitable for simple use cases as
     * for learning.
     *
     * See http://datatables.net/usage/server-side for full details on the server-
     * side processing requirements of DataTables.
     *
     * @license MIT - http://datatables.net/license_mit
     */

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Easy set variables
     */

    // DB table to use
    $table = 'task';

    // Table's primary key
    $primaryKey = 'id';

    // Array of database columns which should be read and sent back to DataTables.
    // The `db` parameter represents the column name in the database, while the `dt`
    // parameter represents the DataTables column identifier. In this case simple
    // indexes
    $columns = array(
      array('db' => 'id', 'dt' => 0),
      array('db' => 'show_slug',  'dt' => 1),
      array('db' => 'title',  'dt' => 2),
      array('db' => 'pbs_content_id', 'dt' => 3),
      array('db' => 'updated_at', 'dt' => 4),
      array('db' => 'status', 'dt' => 5),
      array('db' => 'failure_reason', 'dt' => 6)
    );

    // SQL server connection information
    $sql_details = array(
      'user' => env('DB_USERNAME'),
      'pass' => env('DB_PASSWORD'),
      'db'   => env('DB_DATABASE'),
      'host' => env('DB_HOST')
    );
    
    echo json_encode(
      DataTablesServerComponent::simple($_GET, $sql_details, $table, $primaryKey, $columns)
    );
  }

  /**
   * Given a POST payload, attempts to generate a new task
   */
  public function create(TaskModel $repo, Request $request) {

    // Get the request data
    $data = json_decode($request->getContent(), true);

    // Generate a new task from the request
    $task = $repo->newInstance($data);
    $task->status = TaskModel::PENDING;

    if ($task->save()) {
      return response()->json($task);
    }

    return response()->json('Invalid task payload', 400);
  }

  /**
   * Cancels processing jobs
   */
  public function cancel() {
    $processingTasks = TaskModel::whereNotIn('status', TaskModel::OUT_OF_INGEST_TASKS)->get();

    foreach ($processingTasks as $processingTask) {
      $processingTask->status = TaskModel::CANCELLED;
      $processingTask->save();
    }

    return redirect()->route('index');
  }
}