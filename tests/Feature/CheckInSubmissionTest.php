<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Pet;
use App\Models\CheckIn;

class CheckInSubmissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_submit_complete_checkin_data()
    {
        $checkinData = [
            'user' => [
                'info' => [
                    'phone' => '1234567890',
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'address' => '123 Main St',
                    'city' => 'Anytown',
                    'zip' => '12345'
                ],
                'emergencyContact' => [
                    'name' => 'Jane Doe',
                    'phone' => '0987654321'
                ]
            ],
            'pets' => [
                [
                    'info' => [
                        'petName' => 'Fluffy',
                        'petColor' => 'White',
                        'petType' => 'dog',
                        'petBreed' => 'Golden Retriever',
                        'petAge' => '2020-01-01',
                        'petWeight' => '25',
                        'petGender' => 'male',
                        'petSpayed' => 'yes'
                    ],
                    'health' => [
                        'unusualHealthBehavior' => 'None',
                        'warnings' => 'None'
                    ],
                    'feeding' => [
                        [
                            'day_time' => 'morning',
                            'feeding_med_details' => '1 cup dry food'
                        ]
                    ],
                    'medication' => []
                ]
            ],
            'grooming' => [
                'bath' => true,
                'nails' => false
            ],
            'inventory' => [
                [
                    'name' => 'Collar',
                    'description' => 'Red collar'
                ]
            ]
        ];

        $response = $this->postJson('/api/checkin/submit', [
            'checkin_data' => $checkinData
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Check-in submitted successfully'
                ]);

        // Verify data was created
        $this->assertDatabaseHas('users', [
            'phone' => '1234567890',
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $this->assertDatabaseHas('pets', [
            'race' => 'Golden Retriever',
            'color' => 'White'
        ]);

        $this->assertDatabaseHas('check_ins', [
            'check_out' => null
        ]);
    }

    /** @test */
    public function it_validates_required_checkin_data()
    {
        $response = $this->postJson('/api/checkin/submit', [
            'checkin_data' => []
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_updates_existing_user_and_creates_checkin()
    {
        // Create existing user
        $user = User::factory()->create([
            'phone' => '1234567890',
            'email' => 'existing@example.com'
        ]);

        $checkinData = [
            'user' => [
                'info' => [
                    'phone' => '1234567890',
                    'name' => 'Updated Name',
                    'email' => 'existing@example.com',
                    'address' => 'Updated Address',
                    'city' => 'Updated City',
                    'zip' => '54321'
                ]
            ],
            'pets' => [
                [
                    'info' => [
                        'petName' => 'Fluffy',
                        'petColor' => 'White',
                        'petType' => 'dog',
                        'petBreed' => 'Golden Retriever',
                        'petAge' => '2020-01-01',
                        'petWeight' => '25',
                        'petGender' => 'male',
                        'petSpayed' => 'yes'
                    ],
                    'health' => [],
                    'feeding' => [],
                    'medication' => []
                ]
            ]
        ];

        $response = $this->postJson('/api/checkin/submit', [
            'checkin_data' => $checkinData
        ]);

        $response->assertStatus(200);

        // Verify user was updated
        $this->assertDatabaseHas('users', [
            'phone' => '1234567890',
            'name' => 'Updated Name',
            'address' => 'Updated Address'
        ]);

        // Verify check-in was created
        $this->assertDatabaseHas('check_ins', [
            'user_id' => $user->id
        ]);
    }
}