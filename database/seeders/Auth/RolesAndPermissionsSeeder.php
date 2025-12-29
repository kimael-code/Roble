<?php

namespace Database\Seeders\Auth;

use App\Models\Security\Permission;
use App\Models\Security\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
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
            'name' => 'Superusuario',
            'description' => 'tiene acceso a cualquier ruta del sistema y puede ejecutar cualquier acción que no viole la estabilidad del sistema',
        ]);
        $sysadminRole = Role::create([
            'name' => 'Administrador de Sistemas',
            'description' => 'gestiona los datos básicos, de seguridad y de monitoreo del sistema',
        ]);

        // el rol Superusuario debe ser inmutable
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
        Permission::create(['name' => 'create new organizations', 'description' => 'crear nuevos entes']);
        Permission::create(['name' => 'read any organization', 'description' => 'ver listado de entes', 'set_menu' => true,]);
        Permission::create(['name' => 'read organization', 'description' => 'ver detalles de un ente']);
        Permission::create(['name' => 'update organizations', 'description' => 'editar cualquier ente']);
        Permission::create(['name' => 'delete organizations', 'description' => 'eliminar cualquier ente']);
        Permission::create(['name' => 'export organizations', 'description' => 'exportar datos de entes']);
        // Permisos para gestionar las unidades administrativas del ente
        Permission::create(['name' => 'create new organizational units', 'description' => 'crear nuevas unidades administrativas']);
        Permission::create(['name' => 'read any organizational unit', 'description' => 'ver listado de unidades administrativas', 'set_menu' => true]);
        Permission::create(['name' => 'read organizational unit', 'description' => 'ver detalles de una unidad administrativa']);
        Permission::create(['name' => 'update organizational units', 'description' => 'editar cualquier unidad administrativa']);
        Permission::create(['name' => 'delete organizational units', 'description' => 'eliminar cualquier unidad administrativa']);
        Permission::create(['name' => 'export organizational units', 'description' => 'exportar datos de unidades administrativas']);
        // permisos para gestionar los roles
        Permission::create(['name' => 'create new roles', 'description' => 'crear nuevos roles']);
        Permission::create(['name' => 'read any role', 'description' => 'ver listado de roles', 'set_menu' => true]);
        Permission::create(['name' => 'read role', 'description' => 'ver detalles de un rol']);
        Permission::create(['name' => 'update roles', 'description' => 'editar cualquier rol']);
        Permission::create(['name' => 'delete roles', 'description' => 'eliminar cualquier rol']);
        Permission::create(['name' => 'export roles', 'description' => 'exportar datos de roles']);
        // permisos para gestionar los permisos
        Permission::create(['name' => 'create new permissions', 'description' => 'crear nuevos permisos']);
        Permission::create(['name' => 'read any permission', 'description' => 'ver listado de permisos', 'set_menu' => true]);
        Permission::create(['name' => 'read permission', 'description' => 'ver detalles de un permiso']);
        Permission::create(['name' => 'update permissions', 'description' => 'editar cualquier permiso']);
        Permission::create(['name' => 'delete permissions', 'description' => 'eliminar cualquier permiso']);
        Permission::create(['name' => 'export permissions', 'description' => 'exportar datos de permisos']);
        // permisos para gestionar los usuarios
        Permission::create(['name' => 'create new users', 'description' => 'crear nuevos usuarios']);
        Permission::create(['name' => 'read any user', 'description' => 'ver listado de usuarios', 'set_menu' => true]);
        Permission::create(['name' => 'read user', 'description' => 'ver detalles de un usuario']);
        Permission::create(['name' => 'update users', 'description' => 'editar cualquier usuario']);
        Permission::create(['name' => 'delete users', 'description' => 'eliminar cualquier usuario']);
        Permission::create(['name' => 'force delete users', 'description' => 'eliminar permanentemente cualquier usuario']);
        Permission::create(['name' => 'export users', 'description' => 'exportar datos de usuarios']);
        Permission::create(['name' => 'restore users', 'description' => 'restaurar cualquier usuario']);
        Permission::create(['name' => 'enable users', 'description' => 'activar cualquier usuario']);
        Permission::create(['name' => 'disable users', 'description' => 'desactivar cualquier usuario']);
        Permission::create(['name' => 'reset user passwords', 'description' => 'restablecer contraseñas de cualquier usuario']);
        // permisos para gestionar los logs del sistema
        Permission::create(['name' => 'read any system log', 'description' => 'ver listado de registros de depuración', 'set_menu' => true]);
        Permission::create(['name' => 'read system log', 'description' => 'ver detalles de un registro de depuración']);
        Permission::create(['name' => 'delete system logs', 'description' => 'eliminar registros de depuración']);
        Permission::create(['name' => 'export system logs', 'description' => 'exportar registros de depuración']);
        // permisos para gestionar las trazas de los usuarios
        Permission::create(['name' => 'read any activity trace', 'description' => 'ver listado de trazas de usuarios', 'set_menu' => true]);
        Permission::create(['name' => 'read activity trace', 'description' => 'ver detalles de un traza de usuario']);
        Permission::create(['name' => 'export activity traces', 'description' => 'exportar trazas de usuarios']);
        // permisos para gestionar los 'dashboards' del sistema
        Permission::create(['name' => 'read sysadmin dashboard', 'description' => 'ver tablero de administrador de sistema']);
        // permiso para gestionar el modo mantenimiento del sistema
        Permission::create(['name' => 'manage maintenance mode', 'description' => 'gestionar modo de mantenimiento del sistema', 'set_menu' => true]);

        // Asignar todos los permisos al Administrador de Sistemas excepto los no implementados
        $excludedPermissions = [
            'export organizations',
            'export organizational units',
        ];

        Permission::all()->each(function (Permission $permission) use ($sysadminRole, $excludedPermissions)
        {
            if (!\in_array($permission->name, $excludedPermissions))
            {
                $permission->assignRole($sysadminRole);
            }
        });
    }
}
