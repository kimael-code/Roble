<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Notifications\UserActivationMail;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\URL;

class ResendActivation
{
    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    /**
     * Reenvía el enlace de activación a un usuario.
     *
     * @param User $user Usuario al que se le reenviará la activación
     * @return void
     */
    public function __invoke(User $user): void
    {
        // Enviar notificación (la URL se genera automáticamente en la notificación)
        $user->notify(new UserActivationMail());

        // Registrar actividad usando ActivityLogger
        ($this->logger)(
            ActivityLog::LOG_NAMES['users'],
            $user,
            ActivityLog::EVENT_NAMES['password'], // Reutilizar evento existente
            "reenviado enlace de activación para [:subject.name] [:subject.email]"
        );

        // Flash message para el usuario
        session()->flash('message', [
            'content' => "Enlace de activación enviado a {$user->email}",
            'title' => '¡ENVIADO!',
            'type' => 'success',
        ]);
    }
}
