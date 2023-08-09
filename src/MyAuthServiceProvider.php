<?php

namespace My\Auth;

use Illuminate\Support\ServiceProvider;

class MyAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Paketni registrlash va konfiguratsiya
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
    }
}
