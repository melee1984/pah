<?php

namespace Database\Seeders;

use App\Support\MerchantStaffRoles;
use Illuminate\Database\Seeder;

class MerchantStaffRoleSeeder extends Seeder
{
    public function run(): void
    {
        MerchantStaffRoles::seed();
    }
}
