<?php

namespace App\Providers;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use twincitiespublictelevision\PBS_Media_Manager_Client\PBS_Media_Manager_API_Client;

class PBSMediaManagerClientProvider extends ServiceProvider {
  /**
   * Register any application services.
   *
   * @return void
   */
  public function register() {

    $this->app->singleton(PBS_Media_Manager_API_Client::class, function(Container $app) {

      return new PBS_Media_Manager_API_Client(
        env('MM_KEY'),
        env('MM_SECRET'),
        env('MM_ENDPOINT') . env('MM_API')
      );
    });
  }
}
