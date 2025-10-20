<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmergencyContact;

/**
 * Service class for handling user and emergency contact operations during check-in
 */
class CheckInUserService
{
    /**
     * Process or create user from check-in data
     *
     * @param array $userData The user data from the check-in form
     * @return User The processed user instance
     * @throws \Exception If user data is invalid
     */
    public function processUser(array $userData): User
    {
        if (!isset($userData['info'])) {
            throw new \Exception('User info is required');
        }

        $userInfo = $userData['info'];

        // Validate required fields
        if (empty($userInfo['phone'])) {
            throw new \Exception('User phone is required');
        }

        // For sequential submission, we want to ALWAYS create a new user
        // to avoid conflicts with existing records
        $user = User::create([
            'name' => $userInfo['name'] ?? '',
            'email' => $userInfo['email'] ?? '',
            'phone' => $userInfo['phone'],
            'address' => $userInfo['address'] ?? '',
            'password' => bcrypt('default_password'), // Should be changed later
            'email_verified_at' => null,
            'remember_token' => null,
        ]);

        return $user;
    }

    /**
     * Process user info for sequential submission (always creates new user)
     *
     * @param array $userInfo The user information array
     * @return User The created user instance
     * @throws \Exception If user data is invalid
     */
    public function processUserInfo(array $userInfo): User
    {
        // Validate required fields
        if (empty($userInfo['phone']) || empty($userInfo['name']) || empty($userInfo['email'])) {
            throw new \Exception('User phone, name, and email are required');
        }

        // Always create a new user for sequential submissions
        $user = User::create([
            'name' => $userInfo['name'],
            'email' => $userInfo['email'],
            'phone' => $userInfo['phone'],
            'address' => $userInfo['address'] ?? '',
            'password' => bcrypt('default_password'), // Should be changed later
            'email_verified_at' => null,
            'remember_token' => null,
        ]);

        return $user;
    }

    /**
     * Process emergency contact for a user
     *
     * @param User $user The user instance
     * @param array $emergencyData The emergency contact data
     * @return void
     */
    public function processEmergencyContact(User $user, array $emergencyData): void
    {
        if (empty($emergencyData['name']) || empty($emergencyData['phone'])) {
            return; // Skip if emergency contact data is incomplete
        }

        EmergencyContact::updateOrCreate(
            ['user_id' => $user->id],
            [
                'name' => $emergencyData['name'],
                'phone' => $emergencyData['phone']
            ]
        );
    }
}