<?php
namespace App\Models;

use CodeIgniter\Model;

class EquipmentModel extends Model
{
    protected $table = 'tbequipment';
    protected $primaryKey = 'idequipment';

    protected $allowedFields = [
        'equipment_id',
        'name',
        'description',
        'category',
        'status',
        'location',
        'last_updated'
    ];

    // 👇 ADD THESE LINES 👇
    protected $validationRules = [
        'equipment_id' => 'permit_empty|is_unique[tbequipment.equipment_id,idequipment,{idequipment}]',
        'name' => 'required|min_length[3]|max_length[255]',
        'description' => 'required|min_length[3]',
        'category' => 'required|in_list[laptops,dlp,hdmi_cable,vga_cable,dlp_remote,keyboard_mouse,wacom,speaker_sets,webcams,extension_cords,cable_crimping_tools,cable_testers,lab_room_keys,other]',
        'status' => 'required|in_list[Available,Borrowed,Maintenance,Reserved]',
        'location' => 'required|max_length[255]',
    ];

    protected $validationMessages = [
        'equipment_id' => [
            'is_unique' => 'The Equipment ID is already in use.'
        ],
        'name' => [
            'required' => 'The Equipment Name is required.',
            'min_length' => 'The Equipment Name must be at least 3 characters long.'
        ],
        'description' => [
            'required' => 'The Description is required.',
            'min_length' => 'The Description must be at least 3 characters long.'
        ],
        'category' => [
            'required' => 'The Category is required.',
            'in_list' => 'The selected Category is invalid.'
        ],
        'status' => [
            'required' => 'The Status is required.',
            'in_list' => 'The selected Status is invalid.'
        ],
        'location' => [
            'required' => 'The Location is required.',
            'max_length' => 'The Location must not exceed 255 characters.'
        ],
    ];
    // 👆 END OF ADDITION 👆

    /**
     * Normalize an equipment row to ensure consistent keys for controller logic
     * and views.
     */
    public function normalize(array $row): array
    {
        return [
            'id' => $row['idequipment'] ?? null,
            'equipment_id' => $row['equipment_id'] ?? null,
            'name' => $row['name'] ?? null,
            'description' => $row['description'] ?? null,
            'category' => $row['category'] ?? null,
            'status' => $row['status'] ?? 'Available',
            'location' => $row['location'] ?? 'ITSO',

            // ⭐ FIX: Added 'last_updated' to the normalized array ⭐
            'last_updated' => $row['last_updated'] ?? 'N/A',
        ];
    }
}
