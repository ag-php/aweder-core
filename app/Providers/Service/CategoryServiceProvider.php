<?php

namespace App\Providers\Service;

use App\Contract\Service\CategoryContract;
use App\Service\CategoryService;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CategoryServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->bind(CategoryContract::class, function (Application $app) {
            $categoryRepository = $app->make(\App\Contract\Repositories\CategoryContract::class);

            return new CategoryService(
                $categoryRepository,
            );
        });
    }
}
