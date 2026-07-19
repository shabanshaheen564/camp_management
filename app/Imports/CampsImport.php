<?php

namespace App\Imports;

use App\Models\Camp;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use App\Models\User;

class CampsImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $name = trim($row['name'] ?? '');
        if (!$name) {
            return null;
        }

        $admin = User::where('email', 'admin@camp.org')->first();

        $data = [
            'name' => $name,
            'created_by' => $admin?->id,
        ];

        $map = [
            'location' => 'location',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'capacity' => 'capacity',
            'current_occupancy' => 'current_occupancy',
            'manager' => 'manager',
            'phone' => 'phone',
            'description' => 'description',
            'status' => 'status',
            'is_active' => 'is_active',
        ];

        foreach ($map as $dbField => $excelField) {
            if (isset($row[$excelField])) {
                $value = trim($row[$excelField]);
                if ($value === '') continue;

                switch ($dbField) {
                    case 'capacity':
                    case 'current_occupancy':
                        $data[$dbField] = (int) $value;
                        break;
                    case 'latitude':
                    case 'longitude':
                        $data[$dbField] = (float) $value;
                        break;
                    case 'is_active':
                        $data[$dbField] = in_array(strtolower($value), ['1', 'نعم', 'yes', 'true', 'active']);
                        break;
                    case 'status':
                        $data[$dbField] = in_array(strtolower($value), ['inactive', 'full']) ? strtolower($value) : 'active';
                        break;
                    default:
                        $data[$dbField] = $value;
                }
            }
        }

        return Camp::updateOrCreate(['name' => $name], $data);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'current_occupancy' => 'nullable|integer|min:0',
        ];
    }
}
