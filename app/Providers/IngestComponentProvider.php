<?php

namespace App\Providers;

use App\Components\IngestComponent;
use App\Components\TaskValidationComponent;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use twincitiespublictelevision\PBS_Media_Manager_Client\PBS_Media_Manager_API_Client;

class IngestComponentProvider extends ServiceProvider {
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register() {

    $this->app->singleton(IngestComponent::class, function(Container $app) {
      return new IngestComponent(
        $app->make(PBS_Media_Manager_API_Client::class),
        $app->make(TaskValidationComponent::class)
      );
    });
  }
}
