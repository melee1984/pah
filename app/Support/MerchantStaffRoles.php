<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MerchantStaffRoles
{
    public const LABELS = ['kitchen' => 'Kitchen', 'cashier' => 'Cashier'];

    public const PERMISSIONS = [
        'kitchen' => ['branch.orders.view', 'branch.orders.prepare'],
        'cashier' => ['branch.orders.view', 'branch.tables.manage', 'branch.bills.view', 'branch.payments.record'],
    ];

    public static function seed(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name => $permissions) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'web');
            }
            Role::findOrCreate($name, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
