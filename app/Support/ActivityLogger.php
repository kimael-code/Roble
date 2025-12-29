<?php

namespace App\Support;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\RequestMetadata;
use App\Support\UserMetadata;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;

/**
 * Centraliza la lógica de logging de actividades del sistema.
 *
 * Esta clase reduce la duplicación de código de activity logging
 * que se repite en todas las Actions.
 */
class ActivityLogger
{
    /**
     * Registra una actividad en el sistema.
     *
     * @param string $logName Nombre del log (ej: ActivityLog::LOG_NAMES['users'])
     * @param Model $subject Modelo sobre el que se realiza la acción
     * @param string $event Tipo de evento (ej: ActivityLog::EVENT_NAMES['created'])
     * @param string $message Mensaje descriptivo del log
     * @param array<string, mixed> $additionalProperties Propiedades adicionales para el log
     * @param User|null $causer Usuario que causa la acción (por defecto: auth()->user())
     * @return Activity|null Retorna null si el logging está deshabilitado
     */
    public function __invoke(
        string $logName,
        Model $subject,
        string $event,
        string $message,
        array $additionalProperties = [],
        ?User $causer = null
    ): ?Activity {
        $causer = $causer ?? auth()->user();

        $properties = array_merge([
            'request' => RequestMetadata::capture(),
            'causer' => UserMetadata::capture($causer),
        ], $additionalProperties);

        return activity($logName)
            ->causedBy($causer)
            ->performedOn($subject)
            ->event($event)
            ->withProperties($properties)
            ->log($message);
    }

    /**
     * Registra la creación de un modelo.
     *
     * @param string $logName
     * @param Model $subject
     * @param string $message
     * @param array<string, mixed> $additionalProperties
     * @return Activity|null Retorna null si el logging está deshabilitado
     */
    public function logCreated(
        string $logName,
        Model $subject,
        string $message,
        array $additionalProperties = []
    ): ?Activity {
        $properties = array_merge([
            'attributes' => $subject->toArray(),
        ], $additionalProperties);

        return $this->__invoke(
            $logName,
            $subject,
            ActivityLog::EVENT_NAMES['created'],
            $message,
            $properties
        );
    }

    /**
     * Registra la actualización de un modelo.
     *
     * @param string $logName
     * @param Model $subject
     * @param string $message
     * @param array<string, mixed> $additionalProperties
     * @return Activity|null Retorna null si el logging está deshabilitado
     */
    public function logUpdated(
        string $logName,
        Model $subject,
        string $message,
        array $additionalProperties = []
    ): ?Activity {
        $properties = array_merge([
            'attributes' => $subject->getChanges(),
            'old' => $subject->getOriginal(),
        ], $additionalProperties);

        return $this->__invoke(
            $logName,
            $subject,
            ActivityLog::EVENT_NAMES['updated'],
            $message,
            $properties
        );
    }

    /**
     * Registra la eliminación de un modelo.
     *
     * @param string $logName
     * @param Model $subject
     * @param string $message
     * @param array<string, mixed> $additionalProperties
     * @return Activity|null Retorna null si el logging está deshabilitado
     */
    public function logDeleted(
        string $logName,
        Model $subject,
        string $message,
        array $additionalProperties = []
    ): ?Activity {
        return $this->__invoke(
            $logName,
            $subject,
            ActivityLog::EVENT_NAMES['deleted'],
            $message,
            $additionalProperties
        );
    }

    /**
     * Registra una acción de autorización (asignación de roles/permisos).
     *
     * @param string $logName
     * @param Model $subject
     * @param string $message
     * @param array<string, mixed> $additionalProperties
     * @return Activity|null Retorna null si el logging está deshabilitado
     */
    public function logAuthorized(
        string $logName,
        Model $subject,
        string $message,
        array $additionalProperties = []
    ): ?Activity {
        return $this->__invoke(
            $logName,
            $subject,
            ActivityLog::EVENT_NAMES['authorized'],
            $message,
            $additionalProperties
        );
    }

    /**
     * Registra la desactivación de un modelo.
     *
     * @param string $logName
     * @param Model $subject
     * @param string $message
     * @param array<string, mixed> $additionalProperties
     * @return Activity|null Retorna null si el logging está deshabilitado
     */
    public function logDisabled(
        string $logName,
        Model $subject,
        string $message,
        array $additionalProperties = []
    ): ?Activity {
        return $this->__invoke(
            $logName,
            $subject,
            ActivityLog::EVENT_NAMES['disabled'],
            $message,
            $additionalProperties
        );
    }

    /**
     * Registra la activación de un modelo.
     *
     * @param string $logName
     * @param Model $subject
     * @param string $message
     * @param array<string, mixed> $additionalProperties
     * @return Activity|null Retorna null si el logging está deshabilitado
     */
    public function logEnabled(
        string $logName,
        Model $subject,
        string $message,
        array $additionalProperties = []
    ): ?Activity {
        return $this->__invoke(
            $logName,
            $subject,
            ActivityLog::EVENT_NAMES['enabled'],
            $message,
            $additionalProperties
        );
    }
}

