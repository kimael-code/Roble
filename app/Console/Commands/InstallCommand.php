<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Instala el sistema en entornos de producción';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando la instalación del sistema ...');
        $this->alert('⚠️ ANTENCIÓN: Este comando eliminará y recreará todas las tablas de la base de datos. ⚠️');
        $this->alert('⚠️ Asegúrese de tener un respaldo de su base de datos si es necesario. ⚠️');
        $confirm = $this->ask('¿Continuar? (Presione Enter para continuar)', 'S');

        if ($confirm != 'S' || strtolower($confirm) != 's')
        {
            $this->question('Instalación cancelada por el usuario.');
            return;
        }

        if (!$this->checkEnvironmentFile())
        {
            return;
        }

        $this->newLine();
        $this->info('Generando clave de encriptación del sistema...');
        Artisan::call('key:generate');
        $this->info('Clave generada ✅');

        $this->newLine();
        $this->alert("🧹 Optimizando entorno 🧹");

        try
        {
            Artisan::call('optimize:clear');
            $this->info('Archivos de caché no válidos eliminados ✅');
        }
        catch (\Throwable)
        {
            $this->info('Archivos de caché no válidos eliminados ✅');
        }

        try
        {
            Artisan::call('optimize');
            $this->info('Archivos de caché válidos generados ✅');
        }
        catch (\Throwable)
        {
            Artisan::call('view:clear');
            Artisan::call('vendor:publish', ['--tag' => 'exceptions-renderer-assets', '--force' => true]);
            Artisan::call('optimize');
            $this->info('Archivos de caché válidos generados ✅');
        }

        Artisan::call('storage:link');
        $this->info('Optimización completada ✅');

        $this->newLine();
        $this->alert('🗃️ Preparando base de datos 🗃️');
        $this->info('Migrando base de datos...');
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->info('Migración completada ✅');

        $this->newLine();
        $this->info('Alimentando datos iniciales en base de datos...');
        Artisan::call('db:seed', ['--force' => true]);
        $this->info('Base de datos alimentada ✅');

        $this->newLine();
        $this->info('¡🚀 Instalación completada exitosamente 🚀!');
    }

    private function checkEnvironmentFile(): bool
    {
        $this->info('Verificando variables de entorno...');

        $envPath = base_path('.env');
        if (!file_exists($envPath))
        {
            $this->error('El archivo .env no existe. Por favor, copie .env.example a .env y configure las variables.');

            return false;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $envValues = [];

        foreach ($lines as $line)
        {
            if (str_starts_with(trim($line), '#'))
            {
                continue;
            }

            if (strpos($line, '=') !== false)
            {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if (Str::startsWith($value, '"') && Str::endsWith($value, '"'))
                {
                    $value = Str::of($value)->substr(1, -1)->__toString();
                }

                $envValues[$key] = $value;
            }
        }

        $requiredVariables = [
            'APP_NAME',
            'APP_URL',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'DB_HOST_ORG',
            'DB_PORT_ORG',
            'DB_DATABASE_ORG',
            'DB_USERNAME_ORG',
            'DB_PASSWORD_ORG',
            'REVERB_APP_ID',
            'REVERB_APP_KEY',
            'REVERB_APP_SECRET',
            'REVERB_HOST',
            'REVERB_PORT',
            'REVERB_SCHEME',
        ];

        $smtpVariables = [
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
            'MAIL_FROM_ADDRESS',
        ];

        $missingVariables = [];

        foreach ($requiredVariables as $variable)
        {
            if (!array_key_exists($variable, $envValues) || $envValues[$variable] === '')
            {
                $missingVariables[] = $variable;
            }
        }

        if (count($missingVariables) > 0)
        {
            $this->error("El instalador no puede continuar.\nLas siguientes variables de entorno deben tener un valor en el archivo .env:");

            foreach ($missingVariables as $variable)
            {
                $this->line('  - ' . $variable);
            }

            $this->comment('Consulte el archivo README.md para más detalles.');

            return false;
        }

        $missingSmtpVariables = [];
        $smtpDefaults = [
            'smtp',
            'null',
            'mailpit',
            '1025',
            'null',
            'null',
            'hello@example.com',
        ];

        foreach ($smtpVariables as $variable)
        {
            if (!array_key_exists($variable, $envValues) || $envValues[$variable] === '' || Str::contains($envValues[$variable], $smtpDefaults))
            {
                $missingSmtpVariables[] = $variable;
            }
        }

        if (count($missingSmtpVariables) > 0)
        {
            $this->warn("Las siguientes variables de entorno para el servidor de correo no están definidas.\nPuede configurarlas luego si lo desea:");

            foreach ($missingSmtpVariables as $variable)
            {
                $this->line('  - ' . $variable);
            }
        }

        $this->info('Verificación de variables de entorno completada ✅');

        return true;
    }
}
