<?php

namespace Database\Seeders\Auth;

use App\Models\Security\Permission;
use App\Models\Security\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SysadmiRolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reiniciar caché de roles y permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // roles básicos
        Role::create([
            'name' => __('Superuser'),
            'description' => __('has access to any system route and can perform any action that does not violate system stability'),
        ]);
        $sysadminRole = Role::create([
            'name' => __('Systems Administrator'),
            'description' => __('manages basic, security, and system monitoring data'),
        ]);

        // el rol Superusuario es inmutable
        DB::unprepared(
            "CREATE OR REPLACE FUNCTION protect_first_row()
            RETURNS TRIGGER AS $$
            BEGIN
                IF OLD.id = 1 THEN
                    RAISE EXCEPTION 'El registro con ID=1 está protegido y no puede ser modificado o eliminado.';
                END IF;

                RETURN OLD;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER protect_first_row
            BEFORE UPDATE OR DELETE ON roles
            FOR EACH ROW EXECUTE FUNCTION protect_first_row();"
        );

        // Permisos para gestionar los entes (organizaciones o compañías)
        Permission::create(['name' => 'create new organizations', 'description' => __('create new organizations')]);
        Permission::create(['name' => 'read any organization', 'description' => __('read any organization'), 'set_menu' => true,]);
        Permission::create(['name' => 'read organization', 'description' => __('read organization')]);
        Permission::create(['name' => 'update organizations', 'description' => __('update organizations')]);
        Permission::create(['name' => 'delete organizations', 'description' => __('delete organizations')]);
        Permission::create(['name' => 'export organizations', 'description' => __('export organizations')]);
        // Permisos para gestionar las unidades administrativas del ente
        Permission::create(['name' => 'create new organizational units', 'description' => __('create new organizational units')]);
        Permission::create(['name' => 'read any organizational unit', 'description' => __('read any organizational unit'), 'set_menu' => true]);
        Permission::create(['name' => 'read organizational unit', 'description' => __('read organizational unit')]);
        Permission::create(['name' => 'update organizational units', 'description' => __('update organizational units')]);
        Permission::create(['name' => 'delete organizational units', 'description' => __('delete organizational units')]);
        Permission::create(['name' => 'export organizational units', 'description' => __('export organizational units')]);
        // permisos para gestionar los roles
        Permission::create(['name' => 'create new roles', 'description' => __('create new roles')]);
        Permission::create(['name' => 'read any role', 'description' => __('read any role'), 'set_menu' => true]);
        Permission::create(['name' => 'read role', 'description' => __('read role')]);
        Permission::create(['name' => 'update roles', 'description' => __('update roles')]);
        Permission::create(['name' => 'delete roles', 'description' => __('delete roles')]);
        Permission::create(['name' => 'export roles', 'description' => __('export roles')]);
        // permisos para gestionar los permisos
        Permission::create(['name' => 'create new permissions', 'description' => __('create new permissions')]);
        Permission::create(['name' => 'read any permission', 'description' => __('read any permission'), 'set_menu' => true]);
        Permission::create(['name' => 'read permission', 'description' => __('read permission')]);
        Permission::create(['name' => 'update permissions', 'description' => __('update permissions')]);
        Permission::create(['name' => 'delete permissions', 'description' => __('delete permissions')]);
        Permission::create(['name' => 'export permissions', 'description' => __('export permissions')]);
        // permisos para gestionar los usuarios
        Permission::create(['name' => 'create new users', 'description' => __('create new users')]);
        Permission::create(['name' => 'read any user', 'description' => __('read any user'), 'set_menu' => true]);
        Permission::create(['name' => 'read user', 'description' => __('read user')]);
        Permission::create(['name' => 'update users', 'description' => __('update users')]);
        Permission::create(['name' => 'delete users', 'description' => __('delete users')]);
        Permission::create(['name' => 'force delete users', 'description' => __('force delete users')]);
        Permission::create(['name' => 'export users', 'description' => __('export users')]);
        Permission::create(['name' => 'restore users', 'description' => __('restore users')]);
        Permission::create(['name' => 'activate users', 'description' => __('activate users')]);
        Permission::create(['name' => 'deactivate users', 'description' => __('deactivate users')]);
        // permisos para gestionar los logs del sistema
        Permission::create(['name' => 'read any system log', 'description' => __('read any system log'), 'set_menu' => true]);
        Permission::create(['name' => 'read system log', 'description' => __('read system log')]);
        Permission::create(['name' => 'delete system logs', 'description' => __('delete system logs')]);
        Permission::create(['name' => 'export system logs', 'description' => __('export system logs')]);
        // permisos para gestionar las trazas de los usuarios
        Permission::create(['name' => 'read any activity trace', 'description' => __('read any activity trace'), 'set_menu' => true]);
        Permission::create(['name' => 'read activity trace', 'description' => __('read activity trace')]);
        Permission::create(['name' => 'export activity traces', 'description' => __('export activity traces')]);
        // permisos para gestionar los 'dashboards' del sistema
        Permission::create(['name' => 'read sysadmin dashboard', 'description' => __('read sysadmin dashboard')]);
        // permiso para gestionar el modo mantenimiento del sistema
        Permission::create(['name' => 'manage maintenance mode', 'description' => __('manage maintenance mode'), 'set_menu' => true]);


        Permission::all()->each(function (Permission $permission) use ($sysadminRole)
        {
            $permission->assignRole($sysadminRole);
        });
    }
}
