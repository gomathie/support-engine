<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Renames manager → trainer and employee → trainee.
 *
 * Renamed in place rather than created-and-migrated: role ids are referenced by
 * the model_has_roles and role_has_permissions pivots, so creating new rows and
 * moving assignments across would need a data migration on both, and would lose
 * the assignment history. An UPDATE on roles.name keeps every existing pivot row
 * pointing at the right role.
 *
 * The old names described an org chart; the new ones describe what somebody does
 * on the training portal, which is what the policies actually branch on.
 */
return new class extends Migration
{
    /** @var array<string, string> old name => new name */
    private array $renames = [
        'manager' => 'trainer',
        'employee' => 'trainee',
    ];

    public function up(): void
    {
        foreach ($this->renames as $from => $to) {
            // Guard against a half-applied state: if the target already exists
            // (a fresh install seeded with the new names), there is nothing to
            // rename and renaming would collide with the unique index.
            $targetExists = DB::table('roles')->where('name', $to)->exists();
            $sourceExists = DB::table('roles')->where('name', $from)->exists();

            if ($sourceExists && ! $targetExists) {
                DB::table('roles')->where('name', $from)->update(['name' => $to]);
            }
        }

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        foreach (array_flip($this->renames) as $from => $to) {
            $targetExists = DB::table('roles')->where('name', $to)->exists();
            $sourceExists = DB::table('roles')->where('name', $from)->exists();

            if ($sourceExists && ! $targetExists) {
                DB::table('roles')->where('name', $from)->update(['name' => $to]);
            }
        }

        $this->forgetPermissionCache();
    }

    /**
     * Spatie caches the role and permission map. Without this the application
     * keeps answering hasRole('manager') from a cache that no longer matches
     * the database, and every policy silently denies.
     */
    private function forgetPermissionCache(): void
    {
        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
