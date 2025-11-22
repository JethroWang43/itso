<?php
namespace App\Libraries;

use App\Models\EquipmentModel;
use App\Models\Users_model;

class AppValidation
{
    /**
     * Checks if the equipment exists and is 'Available'.
     * Signature updated to accept 5 arguments to force the correct CI validation call.
     */
    public function is_available(string $str, string $param, array $data, ?string &$error = null, ?string $originalField = null): bool
    {
        // $str is the value of the 'equipment_id' field being validated
        $equipmentId = $str;
        if (empty($equipmentId)) {
            // Let the 'required' rule handle this.
            return true;
        }

        $model = new EquipmentModel();
        $equipment = $model->find($equipmentId);

        // Check if equipment exists AND its status is 'Available' (case-insensitive)
        if ($equipment !== null && strtolower($equipment['status'] ?? '') === 'available') {
            return true;
        }

        // Set error message
        if ($error === null) {
            $error = 'The selected equipment is not currently available for borrowing.';
        }
        return false;
    }

    /**
     * Checks if the equipment ID exists in the database.
     * Signature updated to accept 5 arguments.
     */
    public function is_existing_equipment(string $str, string $param, array $data, ?string &$error = null, ?string $originalField = null): bool
    {
        $equipmentId = $str; // Use $str directly

        if (empty($equipmentId)) {
            return true;
        }

        $model = new EquipmentModel();
        if ($model->find($equipmentId) !== null) {
            return true;
        }

        // Set error message
        if ($error === null) {
            $error = 'The selected equipment ID does not exist.';
        }
        return false;
    }

    /**
     * Checks if the user ID exists in the database.
     * Signature updated to accept 5 arguments.
     */
    public function is_existing_user(string $str, string $param, array $data, ?string &$error = null, ?string $originalField = null): bool
    {
        $userId = $str;
        if (empty($userId)) {
            return true;
        }

        $model = model('Users_model');
        if ($model->find($userId) !== null) {
            return true;
        }

        // Set error message
        if ($error === null) {
            $error = 'The selected user ID does not exist.';
        }
        return false;
    }

    /**
     * Checks if a date is today or in the future. (Requires only 2 arguments)
     */
    public function after_today(string $str, ?string &$error = null): bool
    {
        $date = strtotime($str);
        $today = strtotime('today');

        if ($date < $today) {
            if ($error !== null) {
                $error = 'The {field} must be today or a future date.';
            }
            return false;
        }
        return true;
    }
}
