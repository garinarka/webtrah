<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\FamilyUnit;
use App\Models\Person;
use App\Models\User;

class PersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $family = FamilyUnit::firstOrCreate([
            'name' => 'Keluarga Wijaya'
        ]);
        $admin = User::where('email', 'admin@family.test')->first();

        // root person (kakek)
        $kakek = Person::create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'status' => 'active',
            'gender' => 'male',
            'birth_date' => '1940-05-15',
            'birth_accuracy' => 'exact',
            'display_name' => 'Surya Wijaya (Kakek)',
        ]);

        // anak-anak
        $bapak = Person::create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'status' => 'active',
            'gender' => 'male',
            'birth_date' => '1965-03-20',
            'display_name' => 'Budi Wijaya (Bapak)',
        ]);

        $paman = Person::create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'status' => 'active',
            'gender' => 'male',
            'birth_date' => '1968-07-10',
            'display_name' => 'Ahmad Wijaya (Paman)',
        ]);

        // cucu
        Person::create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'status' => 'active',
            'gender' => 'male',
            'birth_date' => '1990-01-15',
            'display_name' => 'Andi Wijaya (Saya)',
        ]);

        Person::create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'status' => 'active',
            'gender' => 'female',
            'birth_date' => '1992-06-22',
            'display_name' => 'Ani Wijaya (Adik)',
        ]);

        // pending person (untuk test approval)
        Person::create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'status' => 'pending',
            'gender' => 'male',
            'birth_date' => '2020-03-10',
            'display_name' => 'Bayi Baru (Pending)',
        ]);
    }
}
