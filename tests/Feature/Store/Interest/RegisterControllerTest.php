<?php

namespace Tests\Feature\Store\Interest;

use App\Mail\RegisterInterest;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Class RegisterControllerTest
 * @package Tests\Feature\Store\Interest
 * @coversDefaultClass \App\Http\Controllers\Store\Interest\RegisterController
 * @group SignUp
 */
class RegisterControllerTest extends TestCase
{
    use WithFaker;

    /**
     * @var string
     */
    protected $route = '/register-interest';

    /**
     * method to make sure if someone sends the form through that all items are present
     * @test
     */
    public function submission_without_missing_parameters(): void
    {
        Mail::fake();

        Mail::assertNothingSent();

        $postData = [
            'business' => 'tester',
            'email' => 'test@test.com',
            'location' => 'tester',
            'business_type' => 'restaurant'
        ];

        $response = $this->post($this->route, $postData);

        Mail::assertQueued(RegisterInterest::class);

        $response->assertRedirect('/thanks');
    }

    /**
     * method to make sure if someone sends the form through that all items are present
     * @test
     */
    public function submission_with_missing_parameters(): void
    {
        $postData = [
            'business' => 'tester',
            'email' => 'test@test.com',
            'location' => 'tester',
        ];

        $response = $this->post($this->route, $postData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['business_type']);
    }

    /**
     * @test
     */
    public function submission_with_invalid_email(): void
    {
        $postData = [
            'business' => 'tester',
            'email' => 'asdasf',
            'location' => 'tester',
            'business_type' => 'restaurant'
        ];

        $response = $this->post($this->route, $postData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function submission_with_wrong_business_type(): void
    {
        $postData = [
            'business' => 'tester',
            'email' => $this->faker->email,
            'location' => 'tester',
            'business_type' => 'tester'
        ];

        $response = $this->post($this->route, $postData);

        $response->assertStatus(302);

        $response->assertSessionHasErrors(['business_type']);
    }
}
