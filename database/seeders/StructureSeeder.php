<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Service;

class StructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create a Pole (Department)
        $pole = Department::firstOrCreate(
            ['name' => 'Pole Technique'],
            ['description' => 'Pole chargé des aspects techniques et informatiques.']
        );

        // 2. Create a Sub-Department
        $subDepartment = SubDepartment::firstOrCreate(
            ['name' => 'Département IT', 'department_id' => $pole->id]
        );

        // 3. Create a Service
        Service::firstOrCreate(
            ['name' => 'Service Développement', 'sub_department_id' => $subDepartment->id]
        );
        
        $this->command->info('Structure seeded successfully: Pole Technique > Département IT > Service Développement');
    }
}
