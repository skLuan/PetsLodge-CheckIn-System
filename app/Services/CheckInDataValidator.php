<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;

/**
 * Service class for validating check-in data
 *
 * PHASE 2.5: New service for comprehensive check-in data validation
 * Validates data from both cookie format and database format
 */
class CheckInDataValidator
{
    /**
     * Validate check-in data in cookie format
     *
     * @param array $cookieData The data in cookie format
     * @return \Illuminate\Validation\Validator
     */
    public function validateCookieFormat(array $cookieData)
    {
        $rules = [
            'user.info.name' => 'required|string|max:255',
            'user.info.phone' => 'required|string|max:20',
            'user.info.email' => 'required|email|max:255',
            'user.info.address' => 'required|string|max:255',
            'user.info.city' => 'required|string|max:100',
            'user.info.zip' => 'required|string|max:20',
            'user.emergencyContact.name' => 'nullable|string|max:255',
            'user.emergencyContact.phone' => 'nullable|string|max:20',
            'pets' => 'required|array|min:1',
            'pets.*.info.petName' => 'required|string|max:255',
            'pets.*.info.petType' => 'required|string|max:100',
            'pets.*.info.petBreed' => 'nullable|string|max:100',
            'pets.*.info.petColor' => 'nullable|string|max:100',
            'pets.*.info.petAge' => 'nullable|date_format:Y-m-d',
            'pets.*.info.petWeight' => 'nullable|numeric|min:0',
            'pets.*.info.petGender' => 'nullable|string|max:50',
            'pets.*.info.petSpayed' => 'nullable|string|max:50',
            'pets.*.health.unusualHealthBehavior' => 'boolean',
            'pets.*.health.healthBehaviors' => 'nullable|string',
            'pets.*.health.warnings' => 'nullable|string',
            'pets.*.feeding' => 'array',
            'pets.*.feeding.*.day_time' => 'required|string|in:morning,noon,afternoon,evening,night',
            'pets.*.feeding.*.feeding_med_details' => 'required|string',
            'pets.*.medication' => 'array',
            'pets.*.medication.*.day_time' => 'required|string|in:morning,noon,afternoon,evening,night',
            'pets.*.medication.*.feeding_med_details' => 'required|string',
            'inventory' => 'array',
            'inventory.*' => 'string|max:255',
            'grooming' => 'array',
            'groomingDetails' => 'nullable|string',
            'inventoryComplete' => 'boolean',
            'termsAccepted' => 'boolean',
        ];

        return Validator::make($cookieData, $rules);
    }

    /**
     * Validate check-in data in database format
     *
     * @param array $checkInData The data in database format
     * @return \Illuminate\Validation\Validator
     */
    public function validateDatabaseFormat(array $checkInData)
    {
        $rules = [
            'user.name' => 'required|string|max:255',
            'user.phone' => 'required|string|max:20',
            'user.email' => 'required|email|max:255',
            'user.address' => 'required|string|max:255',
            'user.city' => 'required|string|max:100',
            'user.zip' => 'required|string|max:20',
            'user.emergencyContact.name' => 'nullable|string|max:255',
            'user.emergencyContact.phone' => 'nullable|string|max:20',
            'pet.name' => 'required|string|max:255',
            'pet.kindOfPet' => 'required|string|max:100',
            'pet.race' => 'nullable|string|max:100',
            'pet.color' => 'nullable|string|max:100',
            'pet.birth_date' => 'nullable|date_format:Y-m-d',
            'pet.weight' => 'nullable|numeric|min:0',
            'pet.gender' => 'nullable|string|max:50',
            'pet.castrated' => 'nullable|string|max:50',
            'pet.health_conditions' => 'nullable|string',
            'pet.warnings' => 'nullable|string',
            'feeding' => 'array',
            'feeding.*.day_time' => 'required|string|in:morning,noon,afternoon,evening,night',
            'feeding.*.feeding_med_details' => 'required|string',
            'medication' => 'array',
            'medication.*.day_time' => 'required|string|in:morning,noon,afternoon,evening,night',
            'medication.*.feeding_med_details' => 'required|string',
            'inventory' => 'array',
            'inventory.*' => 'string|max:255',
            'grooming' => 'array',
            'groomingDetails' => 'nullable|string',
        ];

        return Validator::make($checkInData, $rules);
    }

    /**
     * Validate that required fields are present and non-empty
     *
     * @param array $data The data to validate
     * @return bool True if all required fields are present
     */
    public function hasRequiredFields(array $data): bool
    {
        $required = [
            'user.info.name',
            'user.info.phone',
            'user.info.email',
            'user.info.address',
            'user.info.city',
            'user.info.zip',
            'pets',
        ];

        foreach ($required as $field) {
            $value = $this->getNestedValue($data, $field);
            if (empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a nested value from an array using dot notation
     *
     * @param array $array The array to search
     * @param string $key The key in dot notation (e.g., 'user.info.name')
     * @return mixed The value or null if not found
     */
    private function getNestedValue(array $array, string $key)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }

        return $value;
    }

    /**
     * Validate that data has changed from original
     *
     * @param array $original The original data
     * @param array $current The current data
     * @return bool True if data has changed
     */
    public function hasChanged(array $original, array $current): bool
    {
        return json_encode($original) !== json_encode($current);
    }

    /**
     * Get a summary of which sections have changed
     *
     * @param array $original The original data
     * @param array $current The current data
     * @return array Array with keys indicating which sections changed
     */
    public function getChangeSummary(array $original, array $current): array
    {
        return [
            'userInfo' => ($original['user'] ?? []) !== ($current['user'] ?? []),
            'pets' => ($original['pets'] ?? []) !== ($current['pets'] ?? []),
            'grooming' => ($original['grooming'] ?? []) !== ($current['grooming'] ?? []),
            'inventory' => ($original['inventory'] ?? []) !== ($current['inventory'] ?? []),
            'groomingDetails' => ($original['groomingDetails'] ?? '') !== ($current['groomingDetails'] ?? ''),
        ];
    }
}
