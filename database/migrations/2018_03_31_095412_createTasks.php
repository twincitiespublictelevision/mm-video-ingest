<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTasks extends Migration {

  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up() {
    Schema::create('task', function (Blueprint $table) {
      $table->increments('id');
      $table->string('title');
      $table->string('description_short');
      $table->string('description_long');
      $table->string('object_type');
      $table->dateTime('premiered_on');
      $table->dateTime('encored_on');
      $table->string('slug');
      $table->string('tags')->nullable();
      $table->string('topics')->nullable();
      $table->string('base_url');
      $table->string('video_file');
      $table->string('image_file');
      $table->string('caption_file');
      $table->string('status')->nullable(false)->default('pending');
      $table->text('failure_reason')->nullable();
      $table->string('show_slug');
      $table->string('parent_title');
      $table->string('parent_slug');
      $table->string('parent_description_short');
      $table->string('parent_description_long');
      $table->integer('episode_number')->nullable();
      $table->dateTime('parent_premiered_on');
      $table->dateTime('parent_encored_on');
      $table->string('pbs_content_id')->nullable();
      $table->bigInteger('tp_media_id')->nullable();
      $table->integer('retries')->default(3);
      $table->timestamps();
    });

    \DB::statement('ALTER TABLE task CHANGE premiered_on premiered_on DATETIME NOT NULL');
    \DB::statement('ALTER TABLE task CHANGE encored_on encored_on DATETIME DEFAULT "1000-01-01 00:00:00"');
    \DB::statement('ALTER TABLE task CHANGE parent_premiered_on parent_premiered_on DATETIME NOT NULL');
    \DB::statement('ALTER TABLE task CHANGE parent_encored_on parent_encored_on DATETIME DEFAULT "1000-01-01 00:00:00"');
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down() {
    Schema::drop('task');
  }
}
