<?php

namespace Tests\Feature\Store\Menu;

use App\Merchant;
use App\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;


/**
 * Class ViewControllerTest
 * @package Tests\Feature\Store\Menu
 * @group Store
 */
class ViewControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     * @test
     * @return void
     */
    public function valid_store(): void
    {
        $merchant = factory(Merchant::class)->create(['logo' => null]);

        $response = $this->call('GET', $merchant->url_slug);

        $response->assertStatus(SymfonyResponse::HTTP_FOUND);

        $response->assertRedirect($merchant->url_slug . '/take-away');
    }

    /**
     * A basic feature test example.
     * @test
     * @return void
     */
    public function invalid_store(): void
    {
        $response = $this->call('GET', '/invalid-store-of-my-making');

        $response->assertStatus(SymfonyResponse::HTTP_NOT_FOUND);
    }

    /**
     * tests that the cart can accept orders
     * @test
     */
    public function can_add_item_to_cart_for_a_new_order(): void
    {
        $merchant = $this->createAndReturnMerchant();

        $inventory = $this->createAndReturnInventoryItem(['merchant_id' => $merchant->id]);

        $storeRoute = route('store.menu.view', $merchant->url_slug);

        $postRoute = route('store.order.add', $merchant->url_slug);

        $postData = [
            'item' => $inventory->id
        ];

        $response = $this->from($storeRoute)->post($postRoute, $postData);

        $response->assertSessionHas('success', 'The item has been added to your order');

        $response->assertStatus(SymfonyResponse::HTTP_FOUND);
    }

    /**
     * @test
     * @group Test
     * makes sure that the order detail page is visible and that the correct database items are setp
     */
    public function can_place_order_and_be_directed_to_order_details_page(): void
    {
        $merchant = $this->createAndReturnMerchant(
            [
                'allow_collection' => 1,
                'allow_delivery' => 1,
                'delivery_radius' => 6,
                'delivery_cost' => 599,
            ]
        );

        $inventory = $this->createAndReturnInventoryItem(['merchant_id' => $merchant->id]);

        $order = $this->createAndReturnOrderForStatus('Incomplete Order', ['merchant_id' => $merchant->id]);

        $orderItem = $this->createAndReturnOrderItem(
            [
                'order_id' => $order->id,
                'inventory_id' => $inventory->id,
                'price' => $inventory->price
            ]
        );

        $storeRoute = route('store.menu.view', ['merchant' => $merchant->url_slug, 'order' => $order->url_slug]);

        $postRoute = route('store.order.submit', ['merchant' => $merchant->url_slug, 'order' => $order->url_slug]);

        $note = $this->faker->realText(100);

        $postData = [
            'customer_note' => $note,
            'order_no' => $order->url_slug,
            'collection_type' => 'delivery',
        ];

        $response = $this->from($storeRoute)->post($postRoute, $postData);

        $response->assertRedirect(
            route(
                'store.menu.order-details',
                [
                    'merchant' => $merchant->url_slug,
                    'order' => $order->url_slug
                ]
            )
        );

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
                'customer_note' => $note,
            ]
        );
    }

    /**
     * once a customer has moved the order past the purchased stage there should be now way to change order details on
     * that page.
     * @test
     */
    public function cannot_view_order_details_page_once_order_has_gone_past_a_customer_submission_stage(): void
    {
        $merchant = $this->createAndReturnMerchant();

        $order = $this->createAndReturnOrderForStatus('Acknowledged Order', ['merchant_id' => $merchant->id]);

        $storeRoute = route('store.menu.view', ['merchant' => $merchant->url_slug, 'order' => $order->url_slug]);

        $storeRouteNoOrder = route(
            'store.menu.view',
            [
                'merchant' => $merchant->url_slug,
            ]
        );

        $orderDetailsRoute = route(
            'store.menu.order-details',
            [
                'merchant' => $merchant->url_slug,
                'order' => $order->url_slug
            ]
        );

        $response = $this->from($storeRoute)->get($orderDetailsRoute);

        $response->assertRedirect($storeRouteNoOrder);

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
                'status' => 'acknowledged'
            ]
        );
    }

    /**
     * @test
     */
    public function check_that_disabled_inventory_items_are_not_visible(): void
    {
        $merchant = $this->createAndReturnMerchant(['logo' => null]);

        $category = $this->createAndReturnCategory(['merchant_id' => $merchant->id]);

        $inventory = $this->createAndReturnInventoryItem(
            [
                'merchant_id' => $merchant->id,
                'available' => 0,
                'category_id' => $category->id,
                'title' => 'This Wonderful Product',
            ]
        );

        $storeRoute = route('store.menu.view', ['merchant' => $merchant->url_slug]);

        $response = $this->get($storeRoute);

        $response->assertDontSeeText('This Wonderful Product');
    }

    /**
     * @test
     */
    public function check_that_inventory_items_are_visible(): void
    {
        $merchant = $this->createAndReturnMerchant(['logo' => null]);

        $category = $this->createAndReturnCategory(['merchant_id' => $merchant->id]);

        $inventory = $this->createAndReturnInventoryItem(
            [
                'merchant_id' => $merchant->id,
                'available' => 1,
                'category_id' => $category->id,
                'title' => 'This Wonderful Product',
            ]
        );

        $storeRoute = route('store.menu.view', ['merchant' => $merchant->url_slug]);

        $response = $this->get($storeRoute);

        $response->assertSee('This Wonderful Product');
    }
}
