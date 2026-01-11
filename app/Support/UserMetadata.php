<?php

namespace App\Support;

use App\Models\User;

/**
 * Helper para capturar metadata del usuario para auditoría.
 *
 * Optimiza el tamaño de los logs capturando solo información esencial
 * del usuario en lugar del modelo completo con relaciones.
 */
class UserMetadata
{
    /**
     * Captura metadata esencial del usuario.
     *
     * Retorna solo id, name y email en lugar del modelo completo
     * para reducir el tamaño de los activity logs.
     *
     * @param User|null $user Usuario del que capturar metadata (por defecto: auth()->user())
     * @return array<string, mixed>|null
     */
    public static function capture(?User $user = null): ?array
    {
        $user = $user ?? auth()->user();

        if (!$user)
        {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    /**
     * Captura metadata completa del usuario con relaciones.
     *
     * Usar solo en casos especiales donde se necesite información completa.
     * Para la mayoría de casos, usar capture() es suficiente.
     *
     * @param User|null $user
     * @return array<string, mixed>|null
     */
    public static function captureFull(?User $user = null): ?array
    {
        $user = $user ?? auth()->user();

        if (!$user)
        {
            return null;
        }

        return User::with('person')->find($user->id)?->toArray();
    }
}
