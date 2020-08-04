<?php

namespace App\Providers\Service;

use App\Contract\Service\InventoryOptionGroupItemContract;
use App\Service\InventoryOptionGroupItemService;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class InventoryOptionGroupItemServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->app->bind(InventoryOptionGroupItemContract::class, function (Application $app) {
            $logger = $app->make(LoggerInterface::class);

            $inventoryOptionGroupItemRepository = $app->make(
                \App\Contract\Repositories\InventoryOptionGroupItemContract::class
            );

            return new InventoryOptionGroupItemService(
                $inventoryOptionGroupItemRepository,
                $logger
            );
        });
    }
}
