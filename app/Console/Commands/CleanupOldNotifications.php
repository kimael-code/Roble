<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Comando para limpiar notificaciones antiguas.
 *
 * Este comando elimina todas las notificaciones que tienen más
 * del número especificado de días de antigüedad.
 */
class CleanupOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup {--days=90 : Número de días después del cual se eliminan las notificaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina notificaciones antiguas de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        $cutoffDate = now()->subDays($days);

        $count = DatabaseNotification::where('created_at', '<', $cutoffDate)->count();

        if ($count === 0)
        {
            $this->info("No hay notificaciones con más de {$days} días de antigüedad.");
            return self::SUCCESS;
        }

        $this->info("Se encontraron {$count} notificaciones con más de {$days} días de antigüedad.");

        DatabaseNotification::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Se eliminaron {$count} notificaciones exitosamente.");

        return self::SUCCESS;
    }
}
