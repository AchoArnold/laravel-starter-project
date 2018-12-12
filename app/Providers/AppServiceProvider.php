<?php

namespace App\Providers;

use App\Weather\Configurations\DarkSkyApiConfiguration;
use App\Weather\Services\DarkSkyWeatherDataService;
use App\Weather\Services\WeatherDataService;
use DavidePastore\Ipinfo\Ipinfo;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
    }

    /**
     * Register any application services.
     */
    public function register()
    {
    }
}
