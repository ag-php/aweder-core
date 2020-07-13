<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Inventory;
use App\Order;
use App\OrderItem;
use Faker\Generator as Faker;

$factory->define(OrderItem::class, function (Faker $faker) {
    return [
        'order_id' => function () {
            return factory(Order::class)->create()->id;
        },
        'inventory_id' => function () {
            return factory(Inventory::class)->create()->id;
        },
        'title' => $faker->word,
        'quantity' => 1,
        'price' => 500,
    ];
});


$factory->state(OrderItem::class, 'No Variant', function (Faker $faker) {
    return [
        'order_id' => function () {
            return factory(Order::class)->create()->id;
        },
        'inventory_id' => null,
        'title' => null,
        'quantity' => 1,
        'price' => 500,
    ];
});
