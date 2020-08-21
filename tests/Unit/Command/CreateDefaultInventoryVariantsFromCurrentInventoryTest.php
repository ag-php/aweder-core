<?php

namespace Tests\Unit\Command;

use App\Contract\Repositories\InventoryVariantContract;
use App\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Mockery;
use Tests\TestCase;

/**
 * Class CreateInventoryVariantsFromCurrentInventoryTest
 * @package Tests\Unit\Command
 * @group Inventory
 */
class CreateDefaultInventoryVariantsFromCurrentInventoryTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function command_outputs_nothing_found_when_no_inventory_items_present(): void
    {
        $this->artisan('inventory:create_default_inventory_variants_from_current_inventory')
            ->expectsOutput('No items to update')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function command_outputs_total_count_of_found_inventory_items_needed_converting(): void
    {
        factory(Inventory::class, 10)->create();

        $this->artisan('inventory:create_default_inventory_variants_from_current_inventory')
            ->expectsOutput('10 Found and will have singular variants created for them.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function command_creates_individual_inventory_variant_for_inventory_item(): void
    {
        $inventory = factory(Inventory::class)->create();

        $this->assertCount(0, $inventory->variants);

        $this->artisan('inventory:create_default_inventory_variants_from_current_inventory')
            ->expectsOutput('1 Found and will have singular variants created for them.')
            ->assertExitCode(0);

        $this->assertDatabaseHas(
            'inventory_variants',
            [
                'inventory_id' => $inventory->id,
            ]
        );

        $this->assertDatabaseHas(
            'inventories',
            [
                'price' => null,
                'id' => $inventory->id,
            ]
        );
    }

    /**
     * @test
     */
    public function command_does_not_run_in_production(): void
    {
        app()->detectEnvironment(function () {
            return 'production';
        });

        $inventory = factory(Inventory::class)->create();

        $this->assertCount(0, $inventory->variants);

        $this->app->environment();

        $this->artisan('inventory:create_default_inventory_variants_from_current_inventory')
            ->expectsConfirmation(
                'WARNING! This app is in production. This command manipulates inventory items in the db.'
                . ' Are you sure you want to run it? [Y/N]',
                'no'
            )->expectsOutput('Command Not Run')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function command_does_run_in_production(): void
    {
        app()->detectEnvironment(function () {
            return 'production';
        });

        $inventory = factory(Inventory::class)->create();

        $this->assertCount(0, $inventory->variants);

        $this->app->environment();

        $this->artisan('inventory:create_default_inventory_variants_from_current_inventory')
            ->expectsConfirmation(
                'WARNING! This app is in production. This command manipulates inventory items in the db.'
                . ' Are you sure you want to run it? [Y/N]',
                'yes'
            )->expectsOutput('1 Found and will have singular variants created for them.')
            ->assertExitCode(0);

        $this->assertDatabaseHas(
            'inventory_variants',
            [
                'inventory_id' => $inventory->id,
            ]
        );

        $this->assertDatabaseHas(
            'inventories',
            [
                'price' => null,
                'id' => $inventory->id,
            ]
        );
    }
}
