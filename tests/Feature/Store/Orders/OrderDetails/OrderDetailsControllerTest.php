<?php

namespace Tests\Feature\Store\Orders\OrderDetails;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Tests\TestCase;

/**
 * Class OrderDetailsControllerTest
 * @package Tests\Feature\Store\Orders
 * @coversDefaultClass \App\Http\Controllers\Store\Orders\OrderDetails\OrderDetailsController
 * @group Orders
 */
class OrderDetailsControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * once a customer has moved the order past the purchased stage there should be no
     * way to change order details on that page.
     * @test
     */
    public function cannot_view_order_details_page_once_order_has_gone_past_a_customer_submission_stage(): void
    {
        Mail::fake();

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
     * this stops a user from viewig a order details page for a different merchant to stop guess attacks
     * @test
     */
    public function cannot_view_order_details_page_of_order_that_is_with_a_different_merchant(): void
    {
        Mail::fake();
        $merchant = $this->createAndReturnMerchant();

        $merchantTwo = $this->createAndReturnMerchant();

        $order = $this->createAndReturnOrderForStatus('Incomplete Order', ['merchant_id' => $merchant->id]);

        $storeRoute = route('store.menu.view', ['merchant' => $merchant->url_slug, 'order' => $order->url_slug]);

        $storeRouteNoOrder = route(
            'store.menu.view',
            [
                'merchant' => $merchant->url_slug,
            ]
        );

        $homeRoute = route('home');

        $orderDetailsRoute = route(
            'store.menu.order-details',
            [
                'merchant' => $merchantTwo->url_slug,
                'order' => $order->url_slug
            ]
        );

        $response = $this->from($storeRoute)->get($orderDetailsRoute);

        $response->assertRedirect($homeRoute);

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
                'status' => 'incomplete'
            ]
        );
    }

    /**
     * @test
     */
    public function can_view_order_details_for_order_that_belongs_to_merchant_in_the_domain(): void
    {
        Mail::fake();
        $merchant = $this->createAndReturnMerchant();

        $merchantTwo = $this->createAndReturnMerchant();

        $order = $this->createAndReturnOrderForStatus('Incomplete Order', ['merchant_id' => $merchant->id]);

        $storeRoute = route('store.menu.view', ['merchant' => $merchant->url_slug, 'order' => $order->url_slug]);

        $homeRoute = route('home');

        $orderDetailsRoute = route(
            'store.menu.order-details',
            [
                'merchant' => $merchant->url_slug,
                'order' => $order->url_slug
            ]
        );

        $response = $this->from($storeRoute)->get($orderDetailsRoute);

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
                'status' => 'ready-to-buy'
            ]
        );
    }

    /**
     * @test
     * @group ValidationOrder
     */
    public function validation_correct_when_order_details_are_not_filled_in(): void
    {
        Mail::fake();

        $merchant = $this->createAndReturnMerchant();

        $order = $this->createAndReturnOrderForStatus('Incomplete Order', ['merchant_id' => $merchant->id]);

        $postOrderDetailsRoute = route(
            'store.menu.order-details.post',
            [
                'merchant' => $merchant->url_slug,
                'order' => $order->url_slug
            ]
        );

        $orderDetailsRoute = route(
            'store.menu.order-details',
            [
                'merchant' => $merchant->url_slug,
                'order' => $order->url_slug
            ]
        );

        $response = $this->from($orderDetailsRoute)->post($postOrderDetailsRoute, []);

        $response->assertRedirect($orderDetailsRoute);

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
                'status' => 'incomplete'
            ]
        );
    }

    /**
     * @test
     * @group OrderDelivery
     */
    public function setting_incorrect_delivery_type_for_merchant_to_make_sure_a_response_is_returned(): void
    {
        $this->markTestSkipped('This test will need to change now when the new flow is in place.');
        Mail::fake();

        $merchant = $this->createAndReturnMerchant(['allow_delivery' => 0]);

        $order = $this->createAndReturnOrderForStatus('Incomplete Order', ['merchant_id' => $merchant->id]);

        $postOrderDetailsRoute = route(
            'store.menu.order-details.post',
            [
                'merchant' => $merchant->url_slug,
                'order' => $order->url_slug
            ]
        );

        $orderDetailsRoute = route(
            'store.menu.order-details',
            [
                'merchant' => $merchant->url_slug,
                'order' => $order->url_slug
            ]
        );

        $postDetails = [
            'customer_name' => $this->faker->name,
            'customer_email' => $this->faker->safeEmail,
            'customer_address' => $this->faker->address,
            'customer_phone' => $this->faker->phoneNumber,
            'collection_type' => 'delivery',
            'order_no' => $order->url_slug,
            'order_time' => [
                'hour' =>  Carbon::now()->subHour()->hour,
                'minute' => Carbon::now()->subMinutes(10)->minute,
            ]
        ];

        $response = $this->from($orderDetailsRoute)->post($postOrderDetailsRoute, $postDetails);

        $response->assertRedirect($orderDetailsRoute);

        $response->assertSessionHasErrors('collection_type');

        $this->assertDatabaseHas(
            'orders',
            [
                'id' => $order->id,
                'status' => 'incomplete'
            ]
        );
    }
}
