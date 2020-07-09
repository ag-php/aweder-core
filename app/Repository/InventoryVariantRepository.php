<?php

namespace App\Repository;

use App\Contract\Repositories\InventoryVariantContract;
use App\InventoryVariant;
use App\Traits\HelperTrait;
use Psr\Log\LoggerInterface;

class InventoryVariantRepository implements InventoryVariantContract
{
    use HelperTrait;

    /**
     * @var InventoryVariant
     */
    protected $model;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(InventoryVariant $model, LoggerInterface $logger)
    {
        $this->model = $model;

        $this->logger = $logger;
    }

    protected function getModel(): InventoryVariant
    {
        return $this->model;
    }
}
