<?php

namespace App\Providers;

use App\FormFields\TestCheckBoxFormField;
use App\FormFields\TestTextField;
use App\FormFields\TestTextFormField;
use Illuminate\Support\ServiceProvider;
use TCG\Voyager\Facades\Voyager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Voyager::addFormField(TestTextFormField::class);
        Voyager::addFormField(TestCheckBoxFormField::class);
        Voyager::addFormField(TestTextField::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
