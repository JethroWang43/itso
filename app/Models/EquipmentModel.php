<?php namespace App\Models;

use CodeIgniter\Model;

class EquipmentModel extends Model
{
    protected $table = 'tbequipment';
    protected $primaryKey = 'idequipment';

    protected $allowedFields = [
        'equipment_id', 'name', 'description', 'category', 'status', 'location', 'last_updated'
    ];
    
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
