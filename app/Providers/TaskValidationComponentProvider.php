<?php

namespace App\Providers;

use App\Components\TaskValidationComponent;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use twincitiespublictelevision\PBS_Media_Manager_Client\PBS_Media_Manager_API_Client;

class TaskValidationComponentProvider extends ServiceProvider {
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register() {

    $this->app->singleton(TaskValidationComponent::class, function(Container $app) {
      return new TaskValidationComponent(
        $app->make(PBS_Media_Manager_API_Client::class)
      );
    });
  }
}
