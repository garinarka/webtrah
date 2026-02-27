<?php

namespace Database\Seeders;

use App\Models\Approval;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\FamilyUnit;
use App\Models\Person;
use App\Models\User;
use App\Models\Relationship;

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

        $pendingPerson = Person::create([
            'family_unit_id' => $family->id,
            'created_by' => $admin->id,
            'status' => 'draft',
            'gender' => 'female',
            'birth_date' => '1995-08-25',
            'display_name' => 'Siti Aminah (Pending Approval)',
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

        Approval::create([
            'approvable_type' => Person::class,
            'approvable_id' => $pendingPerson->id,
            'action' => 'create',
            'changes' => [
                'display_name' => ['old' => null, 'new' => 'Siti Aminah'],
                'birth_date' => ['old' => null, 'new' => '1995-08-25'],
                'gender' => ['old' => null, 'new' => 'female'],
            ],
            'status' => 'pending',
            'requested_by' => $admin->id,
        ]);

        // kakek -> bapak
        Relationship::create([
            'subject_id' => $kakek->id,
            'object_id' => $bapak->id,
            'type' => 'parent',
            'status' => 'approved',
            'is_biological' => true,
            'created_by' => $admin->id,
        ]);

        // kakek -> paman
        Relationship::create([
            'subject_id' => $kakek->id,
            'object_id' => $paman->id,
            'type' => 'parent',
            'status' => 'approved',
            'is_biological' => true,
            'created_by' => $admin->id,
        ]);

        // bapak -> andi
        Relationship::create([
            'subject_id' => $bapak->id,
            'object_id' => Person::where('display_name', 'like', '%Andi%')->first()->id,
            'type' => 'parent',
            'status' => 'approved',
            'is_biological' => true,
            'created_by' => $admin->id,
        ]);

        // bapak -> ani
        Relationship::create([
            'subject_id' => $bapak->id,
            'object_id' => Person::where('display_name', 'like', '%Ani%')->first()->id,
            'type' => 'parent',
            'status' => 'approved',
            'is_biological' => true,
            'created_by' => $admin->id,
        ]);
    }
}
