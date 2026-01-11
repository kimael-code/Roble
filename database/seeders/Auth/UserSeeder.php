<?php

namespace Database\Seeders\Auth;

use App\Models\Organization\OrganizationalUnit;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder de usuarios para entorno de desarrollo local.
 *
 * Este seeder crea 6 usuarios de prueba:
 * - 2 Superusuarios (asignados a Gerencia de Informática)
 * - 2 Administradores de Sistemas (asignados a Gerencias/Unidades aleatorias)
 * - 1 Usuario sin rol asignado (asignado a Gerencia/Unidad aleatoria)
 * - 1 Usuario externo (no activado, sin unidad asignada)
 *
 * NOTA: Este seeder NO debe ejecutarse en producción.
 * En producción solo se ejecutan RolesAndPermissionsSeeder y OrganizationSeeder.
 */
class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Contraseña por defecto para todos los usuarios de prueba.
     */
    private const DEFAULT_PASSWORD = '12345678';

    /**
     * Patrones excluidos para asignación de unidades organizacionales.
     * Se excluyen: Departamento, Presidencia, Vicepresidencia, Sección.
     */
    private const EXCLUDED_OU_PATTERNS = [
        'DEPARTAMENTO%',
        'PRESIDENCIA%',
        'VICEPRESIDENCIA%',
        'SECCIÓN%',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = $this->getDevUsers();

        // Mezclar usuarios (excepto el primero que debe ser Superusuario)
        $firstUser = array_shift($users);
        shuffle($users);
        array_unshift($users, $firstUser);

        // Obtener unidades administrativas válidas para asignación
        $gerenciaInformatica = $this->getGerenciaInformatica();
        $otrasUnidades = $this->getValidOrganizationalUnits();

        foreach ($users as $userData)
        {
            $this->createUserWithPerson($userData, $gerenciaInformatica, $otrasUnidades);
        }
    }

    /**
     * Obtiene la Gerencia de Informática para los Superusuarios.
     */
    protected function getGerenciaInformatica(): ?OrganizationalUnit
    {
        return OrganizationalUnit::where('acronym', 'GI')
            ->orWhere('name', 'like', 'GERENCIA DE INFORMÁTICA%')
            ->orWhere('name', 'like', '%Informática%')
            ->first();
    }

    /**
     * Obtiene las unidades organizacionales válidas (Gerencias y Unidades).
     * Excluye: Departamentos, Presidencia, Vicepresidencia, Secciones.
     *
     * @return \Illuminate\Support\Collection<int, OrganizationalUnit>
     */
    protected function getValidOrganizationalUnits(): \Illuminate\Support\Collection
    {
        $query = OrganizationalUnit::whereNull('disabled_at');

        foreach (self::EXCLUDED_OU_PATTERNS as $pattern)
        {
            $query->where('name', 'not like', $pattern);
        }

        // Excluir también Junta Directiva
        $query->where('name', 'not like', 'JUNTA DIRECTIVA%');

        return $query->get();
    }

    /**
     * Obtiene el listado de usuarios de desarrollo.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getDevUsers(): array
    {
        $password = Hash::make(self::DEFAULT_PASSWORD);

        return [
            // Primer usuario: Superusuario (siempre se crea primero)
            [
                'user' => [
                    'name' => 'root.dev',
                    'email' => 'root.dev@example.com',
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
                'person' => [
                    'id_card' => '12345678',
                    'names' => 'Usuario',
                    'surnames' => 'Root',
                    'phones' => ['ext' => '1001', 'principal' => '0212-555-0001'],
                    'emails' => ['principal' => 'root.dev@example.com', 'empresarial' => 'root@empresa.gob.ve'],
                    'position' => 'Administrador Principal',
                    'staff_type' => 'interno',
                ],
                'role' => 'Superusuario',
                'ou_type' => 'informatica', // Gerencia de Informática
            ],
            // Segundo Superusuario
            [
                'user' => [
                    'name' => 'super.dev',
                    'email' => 'super.dev@example.com',
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
                'person' => [
                    'id_card' => '23456789',
                    'names' => 'Super',
                    'surnames' => 'Usuario',
                    'phones' => ['ext' => '1002', 'principal' => '0212-555-0002'],
                    'emails' => ['principal' => 'super.dev@example.com', 'empresarial' => 'super@empresa.gob.ve'],
                    'position' => 'Administrador de Respaldo',
                    'staff_type' => 'interno',
                ],
                'role' => 'Superusuario',
                'ou_type' => 'informatica', // Gerencia de Informática
            ],
            // Primer Administrador de Sistemas
            [
                'user' => [
                    'name' => 'admin.dev',
                    'email' => 'admin.dev@example.com',
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
                'person' => [
                    'id_card' => '34567890',
                    'names' => 'Administrador',
                    'surnames' => 'Primero',
                    'phones' => ['ext' => '2001', 'principal' => '0212-555-0003'],
                    'emails' => ['principal' => 'admin.dev@example.com', 'empresarial' => 'admin1@empresa.gob.ve'],
                    'position' => 'Administrador de Sistemas',
                    'staff_type' => 'interno',
                ],
                'role' => 'Administrador de Sistemas',
                'ou_type' => 'random', // Gerencia/Unidad aleatoria
            ],
            // Segundo Administrador de Sistemas
            [
                'user' => [
                    'name' => 'sysadmin.dev',
                    'email' => 'sysadmin.dev@example.com',
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
                'person' => [
                    'id_card' => '45678901',
                    'names' => 'Sistema',
                    'surnames' => 'Administrador',
                    'phones' => ['ext' => '2002', 'principal' => '0212-555-0004'],
                    'emails' => ['principal' => 'sysadmin.dev@example.com', 'empresarial' => 'sysadmin@empresa.gob.ve'],
                    'position' => 'Administrador de Sistemas',
                    'staff_type' => 'interno',
                ],
                'role' => 'Administrador de Sistemas',
                'ou_type' => 'random', // Gerencia/Unidad aleatoria
            ],
            // Usuario sin rol asignado (pero activado y verificado)
            [
                'user' => [
                    'name' => 'sinrol.dev',
                    'email' => 'sinrol.dev@example.com',
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ],
                'person' => [
                    'id_card' => '56789012',
                    'names' => 'Sin',
                    'surnames' => 'Rol',
                    'phones' => ['ext' => '3001', 'principal' => '0212-555-0005'],
                    'emails' => ['principal' => 'sinrol.dev@example.com', 'empresarial' => 'sinrol@empresa.gob.ve'],
                    'position' => 'Empleado General',
                    'staff_type' => 'interno',
                ],
                'role' => null,
                'ou_type' => 'random', // Gerencia/Unidad aleatoria
            ],
            // Usuario externo (no activado, sin correo verificado, sin unidad)
            [
                'user' => [
                    'name' => 'externo.dev',
                    'email' => 'externo.dev@example.com',
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'is_active' => false,
                    'email_verified_at' => null,
                ],
                'person' => [
                    'id_card' => '67890123',
                    'names' => 'Usuario',
                    'surnames' => 'Externo',
                    'phones' => ['celular' => '0414-123-4567'],
                    'emails' => ['principal' => 'externo.dev@example.com', 'personal' => 'externo.personal@gmail.com'],
                    'position' => 'Consultor Externo',
                    'staff_type' => 'externo',
                ],
                'role' => null,
                'ou_type' => 'none', // Sin unidad asignada
            ],
        ];
    }

    /**
     * Crea un usuario junto con sus datos personales y asigna unidad organizacional.
     *
     * @param array<string, mixed> $userData
     * @param OrganizationalUnit|null $gerenciaInformatica
     * @param \Illuminate\Support\Collection<int, OrganizationalUnit> $otrasUnidades
     */
    protected function createUserWithPerson(
        array $userData,
        ?OrganizationalUnit $gerenciaInformatica,
        \Illuminate\Support\Collection $otrasUnidades
    ): void {
        $user = User::create($userData['user']);

        // Crear los datos personales asociados
        $personData = $userData['person'];
        $personData['user_id'] = $user->id;
        Person::create($personData);

        // Asignar rol si está definido
        if ($userData['role'])
        {
            $user->assignRole($userData['role']);
        }

        // Asignar unidad organizacional según el tipo
        $this->assignOrganizationalUnit($user, $userData['ou_type'], $gerenciaInformatica, $otrasUnidades);
    }

    /**
     * Asigna una unidad organizacional al usuario.
     *
     * @param User $user
     * @param string $ouType
     * @param OrganizationalUnit|null $gerenciaInformatica
     * @param \Illuminate\Support\Collection<int, OrganizationalUnit> $otrasUnidades
     */
    protected function assignOrganizationalUnit(
        User $user,
        string $ouType,
        ?OrganizationalUnit $gerenciaInformatica,
        \Illuminate\Support\Collection $otrasUnidades
    ): void {
        switch ($ouType)
        {
            case 'informatica':
                if ($gerenciaInformatica)
                {
                    $user->organizationalUnits()->attach($gerenciaInformatica->id);
                }
                elseif ($otrasUnidades->isNotEmpty())
                {
                    // Fallback: usar primera unidad disponible si no existe GI
                    $user->organizationalUnits()->attach($otrasUnidades->first()->id);
                }
                break;

            case 'random':
                if ($otrasUnidades->isNotEmpty())
                {
                    $user->organizationalUnits()->attach($otrasUnidades->random()->id);
                }
                break;

            case 'none':
            default:
                // No asignar unidad organizacional
                break;
        }
    }
}
