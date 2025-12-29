<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Arr;

class ManuallyActivateUser
{
    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    /**
     * Activa manualmente un usuario estableciendo contraseña conocida.
     *
     * @param User $user Usuario a activar
     * @return array Datos para mostrar en el dialog
     */
    public function __invoke(User $user): array
    {
        // Contraseña: cédula o nombre (se hasheará automáticamente por el cast 'hashed')
        $password = $user->person?->id_card ?? $user->name;

        $user->is_active = true;
        $user->disabled_at = null;
        $user->deleted_at = null;
        $user->password = $password; // Laravel hashea automáticamente
        $user->save();

        // Log con indicador de activación manual
        $this->logger->logEnabled(
            ActivityLog::LOG_NAMES['users'],
            $user,
            'activó manualmente usuario [:subject.name] [:subject.email]',
            [
                'attributes' => Arr::except($user->getChanges(), ['password']),
                'old' => Arr::except($user->getOriginal(), ['password']),
                'manual_activation' => true,
            ]
        );

        return [
            'password' => $password,
            'user' => $user->name,
            'email' => $user->email,
        ];
    }
}
