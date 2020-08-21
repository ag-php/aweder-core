<?php

namespace Tests\Unit\Repository;

use App\Contract\Repositories\InventoryOptionGroupContract;
use App\Contract\Repositories\InventoryOptionGroupItemContract;
use App\Contract\Repositories\InventoryVariantContract;
use App\Contract\Repositories\OrderContract;
use App\Contract\Repositories\OrderItemContract;
use App\Inventory;
use App\Merchant;
use App\Order;
use App\OrderItem;
use App\OrderReminder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Class OrderRepositoryTest
 * @package Tests\Unit\Repository
 * @coversDefaultClass \App\Repository\OrderRepository;
 * @group Order
 */
class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @var OrderContract
     */
    protected $repository;

    /**
     * @var OrderItemContract
     */
    protected $orderItemRepository;

    /**
     * @var InventoryOptionGroupContract
     */
    protected $inventoryOptionGroupRepository;

    /**
     * @var InventoryOptionGroupItemContract
     */
    protected $inventoryOptionGroupItemRepository;

    /**
     * @var InventoryVariantContract
     */
    protected $inventoryVariantRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->app->make(OrderContract::class);
        $this->inventoryOptionGroupRepository = $this->app->make(InventoryOptionGroupContract::class);
        $this->inventoryOptionGroupItemRepository = $this->app->make(InventoryOptionGroupItemContract::class);
        $this->inventoryVariantRepository = $this->app->make(InventoryVariantContract::class);
        $this->orderItemRepository = $this->app->make(OrderItemContract::class);
    }

    /**
     * @test
     */
    public function only_returns_unprocessed_orders_in_created_order_for_specified_time_period(): void
    {
        $unprocessedOrder = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes(30),
            'order_submitted' => Carbon::parse()->subMinutes(30),
        ]);

        $secondUnProcessedOrder = factory(Order::class)->state('Unprocessed Order')->create([
            'order_submitted' => Carbon::parse()->subMinutes(40),
            'created_at' => Carbon::parse()->subMinutes(40),
        ]);

        factory(Order::class)->state('Incomplete Order')->create();

        factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes(10),
            'order_submitted' => Carbon::parse()->subMinutes(10),
        ]);

        $unprocessedOrders = $this->repository->getUnprocessedOrdersBetweenPeriod(
            Carbon::parse()->subMinutes(60),
            Carbon::parse()->subMinutes(30)
        );

        $this->assertCount(2, $unprocessedOrders);

        $this->assertEquals($secondUnProcessedOrder->id, $unprocessedOrders->first()->id);

        $this->assertEquals($unprocessedOrder->id, $unprocessedOrders->last()->id);
    }

    /**
     * @test
     * checks that a blank order is saved on creation
     */
    public function saves_incomplete_status_for_new_order(): void
    {
        $status = 'incomplete';

        $merchant = $this->createAndReturnMerchant();

        $order = $this->repository->createEmptyOrderWithStatus($merchant->id, $status);

        $this->assertSame($status, $order->status);
    }

    /**
     * @test
     */
    public function make_sure_item_quantity_is_updated(): void
    {
        $order = factory(Order::class)->create();

        $inventory = factory(Inventory::class)->create();

        $orderItem = new OrderItem(
            [
                'inventory_id' => $inventory->id,
                'quantity' => 1,
            ]
        );

        $order->items()->save($orderItem);

        $this->repository->updateQuantityOnItemInOrder($order, $inventory->id);

        $this->assertDatabaseHas(
            'order_items',
            [
                'inventory_id' => $inventory->id,
                'order_id' => $order->id,
                'quantity' => 2,
            ]
        );
    }

    /**
     * checks that various order submission states are ok
     * @test
     * @dataProvider submittedOrders
     * @param string $scope
     * @param bool $expectedStatus
     * @group OrderStatus
     */
    public function order_previously_submitted_check(string $scope, bool $expectedStatus): void
    {
        $order = factory(Order::class)->state($scope)->create();

        $this->assertSame($expectedStatus, $this->repository->hasOrderBeenPreviouslySubmitted($order));
    }

    /**
     * @test
     * @dataProvider statusDataProvider
     * @param $status
     */
    public function get_orders_by_merchant_and_status_returns_orders_by_merchant_using_status($status): void
    {
        $merchant = factory(Merchant::class)->create();

        factory(Order::class, 5)->create([
            'merchant_id' => $merchant->id,
            'status' => $status
        ]);

        $orders = $this->repository->getOrdersByMerchantAndStatus($merchant->id, $status, 'DESC');

        foreach ($orders as $order) {
            $this->assertEquals($order->status, $status);
        }
    }

    /**
     * @test
     * @group UnprocessedOrders
     */
    public function get_unprocessed_orders_between_period_where_no_reminders_have_been_sent_for_time_with_no_order_reminders_set(): void
    {
        $merchant = factory(Merchant::class)->create();

        $minutes = 20;

        $start = Carbon::now()->subMinutes($minutes + 1)->addSecond();
        $end = Carbon::now()->subMinutes($minutes);

        $order = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes),
            'order_submitted' => Carbon::parse()->subMinutes($minutes),
            'merchant_id' => $merchant->id,
        ]);

        $orders = $this->repository->getUnprocessedOrdersBetweenPeriodWhereNoRemindersHaveBeenSentForTime(
            $start,
            $end,
            $minutes
        );

        $this->assertCount(1, $orders);
    }

    /**
     * @test
     * @group UnprocessedOrders
     */
    public function get_unprocessed_orders_between_period_where_no_reminders_have_been_sent_for_time_with_order_reminders_set(): void
    {
        $merchant = factory(Merchant::class)->create();

        $minutes = 20;

        $start = Carbon::now()->subMinutes($minutes + 1)->addSecond();
        $end = Carbon::now()->subMinutes($minutes);

        $order = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes),
            'order_submitted' => Carbon::parse()->subMinutes($minutes),
            'merchant_id' => $merchant->id,
        ]);

        factory(OrderReminder::class)->create(
            [
                'order_id' => $order->id,
                'reminder_time' => $minutes,
                'sent' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        );

        $orders = $this->repository->getUnprocessedOrdersBetweenPeriodWhereNoRemindersHaveBeenSentForTime(
            $start,
            $end,
            $minutes
        );

        $this->assertCount(0, $orders);
    }

    /**
     * @test
     * @group UnprocessedOrders
     */
    public function get_unprocessed_orders_between_period_where_no_reminders_have_been_sent_for_time_with_mixed_order_reminders_set(): void
    {
        $merchant = factory(Merchant::class)->create();

        $minutes = 20;

        $start = Carbon::now()->subMinutes($minutes + 1)->addSecond();
        $end = Carbon::now()->subMinutes($minutes);

        $order = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes),
            'order_submitted' => Carbon::parse()->subMinutes($minutes),
            'merchant_id' => $merchant->id,
        ]);

        factory(OrderReminder::class)->create(
            [
                'order_id' => $order->id,
                'reminder_time' => $minutes,
                'sent' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        );

        $order = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes),
            'order_submitted' => Carbon::parse()->subMinutes($minutes),
            'merchant_id' => $merchant->id,
        ]);

        $orders = $this->repository->getUnprocessedOrdersBetweenPeriodWhereNoRemindersHaveBeenSentForTime(
            $start,
            $end,
            $minutes
        );

        $this->assertCount(1, $orders);
    }

    /**
     * @test
     * @group UnprocessedOrders
     */
    public function get_unprocessed_orders_between_period_where_no_reminders_have_been_sent_for_time_with_various_orders_time_slots(): void
    {
        $merchant = factory(Merchant::class)->create();

        $minutes = 20;

        $start = Carbon::now()->subMinutes($minutes + 1)->addSecond();
        $end = Carbon::now()->subMinutes($minutes);

        $order = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes),
            'order_submitted' => Carbon::parse()->subMinutes($minutes),
            'merchant_id' => $merchant->id,
        ]);

        factory(OrderReminder::class)->create(
            [
                'order_id' => $order->id,
                'reminder_time' => $minutes,
                'sent' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        );

        $orderWayBefore = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes - 50),
            'order_submitted' => Carbon::parse()->subMinutes($minutes - 50),
            'merchant_id' => $merchant->id,
        ]);

        $orderNewer = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes + 10),
            'order_submitted' => Carbon::parse()->subMinutes($minutes + 10),
            'merchant_id' => $merchant->id,
        ]);

        $orders = $this->repository->getUnprocessedOrdersBetweenPeriodWhereNoRemindersHaveBeenSentForTime(
            $start,
            $end,
            $minutes
        );

        $this->assertCount(0, $orders);
    }

    /**
     * @test
     * @group UnprocessedOrders
     */
    public function get_unprocessed_orders_between_period_where_no_reminders_have_been_sent_for_time_with_various_orders_time_slots_one(): void
    {
        $merchant = factory(Merchant::class)->create();

        $minutes = 19;

        $start = Carbon::now()->subMinutes($minutes + 1)->addSecond();
        $end = Carbon::now()->subMinutes($minutes);

        $orderWithReminder = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes),
            'order_submitted' => Carbon::parse()->subMinutes($minutes),
            'merchant_id' => $merchant->id,
        ]);

        factory(OrderReminder::class)->create(
            [
                'order_id' => $orderWithReminder->id,
                'reminder_time' => $minutes,
                'sent' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        );

        $orderWayBefore = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes - 50),
            'order_submitted' => Carbon::parse()->subMinutes($minutes - 50),
            'merchant_id' => $merchant->id,
        ]);

        $orderNewer = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes + 10),
            'order_submitted' => Carbon::parse()->subMinutes($minutes + 10),
            'merchant_id' => $merchant->id,
        ]);

        $order = factory(Order::class)->state('Unprocessed Order')->create([
            'created_at' => Carbon::parse()->subMinutes($minutes),
            'order_submitted' => Carbon::parse()->subMinutes($minutes),
            'merchant_id' => $merchant->id,
        ]);

        $orders = $this->repository->getUnprocessedOrdersBetweenPeriodWhereNoRemindersHaveBeenSentForTime(
            $start,
            $end,
            $minutes
        );

        $this->assertCount(1, $orders);
    }

    /**
     * @test
     */
    public function only_returns_valid_orders_in_status_checks(): void
    {
        $merchant = factory(Merchant::class)->create();

        $order = $this->createAndReturnOrderForStatus('Rejected Order', ['merchant_id' => $merchant->id]);

        $result = $this->repository->getOrdersByMerchantAndStatuses(
            $merchant->id,
            ['purchased'],
            'ASC',
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear()
        );

        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function no_orders_returned_for_different_merchant(): void
    {
        $merchant = factory(Merchant::class)->create();

        $merchantTwo = factory(Merchant::class)->create();

        $order = $this->createAndReturnOrderForStatus('Rejected Order', ['merchant_id' => $merchant->id]);

        $result = $this->repository->getOrdersByMerchantAndStatuses(
            $merchantTwo->id,
            ['purchased'],
            'ASC',
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear()
        );

        $this->assertTrue($result->isEmpty());
    }

    /**
     * @test
     */
    public function get_dashboard_metrics_for_merchant_with_orders(): void
    {
        $merchant = factory(Merchant::class)->create();
        $fulfilledOrders = random_int(1, 15);
        $processingOrders = random_int(1, 20);

        factory(Order::class, $fulfilledOrders)->create([
            'merchant_id' => $merchant->id,
            'status' => 'fulfilled'
        ]);

        factory(Order::class, $processingOrders)->create([
            'merchant_id' => $merchant->id,
            'status' => 'processing'
        ]);

        $orderRepository = $this->app->make(OrderContract::class);
        $dashboardMetrics = $orderRepository->getDashboardStatisticsForMerchantWithDateRange(
            $merchant->id,
            'this-month'
        );
        $this->assertArrayHasKey('fulfilled', $dashboardMetrics);
        $this->assertEquals($fulfilledOrders, $dashboardMetrics['fulfilled']);
    }

    /**
     * @test
     */
    public function get_transformed_dashboard_metrics_for_merchant(): void
    {
        $merchant = factory(Merchant::class)->create();
        $acknowledgedOrders = random_int(1, 15);
        $processingOrders = random_int(1, 20);

        factory(Order::class, $acknowledgedOrders)->create([
            'merchant_id' => $merchant->id,
            'status' => 'processing'
        ]);

        factory(Order::class, $processingOrders)->create([
            'merchant_id' => $merchant->id,
            'status' => 'acknowledged'
        ]);

        $orderRepository = $this->app->make(OrderContract::class);
        $dashboardMetrics = $orderRepository->getFrontendStatisticsForMerchantWithDateRange(
            $merchant->id,
            'this-month'
        );
        $this->assertArrayHasKey('Processing', $dashboardMetrics);
        $this->assertEquals($acknowledgedOrders + $processingOrders, $dashboardMetrics['Processing']);
    }

    /**
     * @test
     */
    public function get_transformed_dashboard_metrics_filtered_by_week(): void
    {
        $merchant = factory(Merchant::class)->create();
        $acknowledgedOrders = random_int(1, 15);
        $processingOrders = random_int(1, 20);

        factory(Order::class, $acknowledgedOrders)->create([
            'merchant_id' => $merchant->id,
            'status' => 'processing'
        ]);

        factory(Order::class, $processingOrders)->create([
            'merchant_id' => $merchant->id,
            'status' => 'acknowledged'
        ]);

        factory(Order::class)->create([
            'merchant_id' => $merchant->id,
            'status' => 'processing',
            'created_at' => Carbon::now()->subDays(8)
        ]);

        $orderRepository = $this->app->make(OrderContract::class);
        $dashboardMetrics = $orderRepository->getFrontendStatisticsForMerchantWithDateRange($merchant->id, 'this-week');
        $this->assertArrayHasKey('Processing', $dashboardMetrics);
        $this->assertEquals($acknowledgedOrders + $processingOrders, $dashboardMetrics['Processing']);
    }

    /**
     * @test
     */
    public function get_dashboard_metrics_for_merchant_without_orders(): void
    {
        $merchant = factory(Merchant::class)->create();
        $orderRepository = $this->app->make(OrderContract::class);
        $dashboardMetrics = $orderRepository->getDashboardStatisticsForMerchantWithDateRange(
            $merchant->id,
            'this-month'
        );

        $this->assertArrayNotHasKey('fulfilled', $dashboardMetrics);
    }

    /**
     * @test
     */
    public function check_unknown_status_returned(): void
    {
        $merchantOne = $this->createAndReturnMerchant(['registration_stage' => 0]);

        $order = $this->createAndReturnOrderForStatus('Purchased Order', ['merchant_id' => $merchantOne->id]);
        $order->status = 'nonsense';
        $order->save();

        $this->assertEquals($order->getNiceFrontendStatus(), $order->getUnknownStatus());
    }

    /*
     * @test
     */
    public function retrieve_only_status_orders_requested(): void
    {
        $merchant = factory(Merchant::class)->create();

        $merchantTwo = factory(Merchant::class)->create();

        $this->createAndReturnOrderForStatus('Rejected Order', ['merchant_id' => $merchant->id]);

        $this->createAndReturnOrderForStatus('Purchased Order', ['merchant_id' => $merchant->id]);

        $this->createAndReturnOrderForStatus('Payment Rejected', ['merchant_id' => $merchant->id]);

        $this->createAndReturnOrderForStatus('Acknowledged Order', ['merchant_id' => $merchant->id]);

        $this->createAndReturnOrderForStatus('Rejected Order', ['merchant_id' => $merchantTwo->id]);

        $this->createAndReturnOrderForStatus('Purchased Order', ['merchant_id' => $merchantTwo->id]);

        $this->createAndReturnOrderForStatus('Payment Rejected', ['merchant_id' => $merchantTwo->id]);

        $this->createAndReturnOrderForStatus('Acknowledged Order', ['merchant_id' => $merchantTwo->id]);

        $result = $this->repository->getOrdersByMerchantAndStatuses(
            $merchant->id,
            [
                'purchased',
                'acknowledged',
            ],
            'ASC',
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear()
        );

        $this->assertCount(2, $result);
    }

    /**
     * @test
     * @group VariantId
     */
    public function returns_orders_with_order_items_that_require_variant_id_adding_to_them(): void
    {
        factory(Order::class, 4)->state('With Variant Id Missing')->create();

        $result = $this->repository->getOrdersWithOrderItemsThatNeedDefaultVariantId();

        $this->assertCount(4, $result);
    }

    /**
     * @test
     * @group VariantId
     */
    public function return_no_orders_when_none_exist(): void
    {
        $result = $this->repository->getOrdersWithOrderItemsThatNeedDefaultVariantId();

        $this->assertCount(0, $result);
    }

    /**
     * @test
     * @group VariantId
     */
    public function returns_orders_with_order_items_tha_have_variant_id(): void
    {
        factory(Order::class, 1)->state('With Variant Id')->create();

        $result = $this->repository->getOrdersWithOrderItemsThatNeedDefaultVariantId();

        $this->assertCount(0, $result);
    }

    /**
     * @test
     */
    public function add_order_item_with_variant_and_options_to_order(): void
    {
        $merchant = $this->createAndReturnMerchant(['registration_stage' => 0]);
        $order = $this->createAndReturnOrderForStatus('Purchased Order', ['merchant_id' => $merchant->id]);
        $inventory = factory(Inventory::class)->create(['merchant_id' => $merchant->id]);
        $inventoryOptionGroup = $this->createAndReturnInventoryOptionGroup(
            ['name' => 'Extras']
        );

        $inventoryOptionGroupItem = $this->createAndReturnInventoryOptionGroupItem(
            ['name' => 'Go Faster Stripes']
        );

        $this->inventoryOptionGroupItemRepository->addItemToOptionGroup(
            $inventoryOptionGroup, $inventoryOptionGroupItem
        );

        $this->inventoryOptionGroupRepository->addOptionGroupToInventoryItem($inventory, $inventoryOptionGroup);
        $inventoryVariantName = 'Electric Blue Keyboard';

        $inventoryVariant = $this->createAndReturnInventoryVariant(
            ['name' => $inventoryVariantName]
        );

        $this->inventoryVariantRepository->addVariantToInventoryItem($inventory, $inventoryVariant);

        $randomPrice = $this->faker->numberBetween(1, 5000);

        $orderItem = $this->createAndReturnOrderItem([
            'variant_id' => $inventoryVariant->id,
            'inventory_id' => $inventory->id,
            'price' => $randomPrice
        ]);

        $this->orderItemRepository->addOptionToOrderItem($orderItem, $inventoryOptionGroupItem);
        $this->assertEquals('Go Faster Stripes', $orderItem->inventoryOptions()->first()->name);
        $this->assertEquals('Electric Blue Keyboard', $orderItem->inventoryVariant()->first()->name);

        $this->repository->addOrderItemToOrder($order, $orderItem);
        $this->assertEquals($randomPrice, $order->items()->first()->price);
    }

    public function statusDataProvider(): array
    {
        return [
            ['processing'],
            ['incomplete'],
            ['purchased'],
            ['ready-to-buy'],
            ['payment-rejected'],
            ['acknowledged'],
            ['rejected'],
            ['unacknowledged'],
        ];
    }

    public function submittedOrders(): array
    {
        return  [
            'incomplete' =>[
                'Incomplete Order',
                false,
            ],
            'purchased' =>[
                'Purchased Order',
                true,
            ],
            'rejected' =>[
                'Payment Rejected',
                true,
            ],
            'acknowledged' =>[
                'Acknowledged Order',
                true,
            ],
            'unacknowledged' =>[
                'Unacknowledged Order',
                true,
            ],
        ];
    }
}
