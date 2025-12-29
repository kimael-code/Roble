<?php

namespace App\Support\Logs;

use App\Support\Logs\Level;
use App\Support\Logs\Pattern;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

class Logfile
{
    private $folder = 'logs';
    private $default = true;

    public function __construct(string $folder = 'logs', bool $default = true)
    {
        $this->folder = $folder ?? $this->folder;
        $this->default = $default ?? $this->default;
    }

    /**
     * Devuelve las rutas completas de todos los archivos `.log`.
     * @param string $path
     * @return array<int, string>
     */
    public function fullPaths(string $path): array
    {
        $contents = collect();
        $filename = '';

        foreach (scandir($path) as $node)
        {
            if ($node == '.' || $node == '..')
                continue;

            $filename = $path . '/' . $node;

            is_dir($filename)
                ? $contents->push($this->fullPaths($filename))
                : $contents->push($filename);
        }

        return $contents->flatten()
            ->filter(fn($route) => !str_ends_with($route, 'gitignore'))
            ->toArray();
    }

    /**
     * Devuelve los nombres de los archivos `.log` con sus respectivas
     * rutas relativas a la carpeta `logs`.
     * @param array $paths
     * @return array<string, string>
     */
    public function relativePaths(array $paths = []): array
    {
        $contents = [];

        if ($this->default)
        {
            $paths = $this->fullPaths(storage_path($this->folder));
        }

        foreach ($paths as $path)
        {
            $contents[substr(strrchr($path, '/'), 1)] = substr(strstr(strstr($path, 'logs'), '/'), 1);
        }

        return $contents;
    }

    /**
     * Devuelve el contenido preprocesado de un archivo `.log`.
     * @param string|null $path
     * @return array
     */
    public function logs(?string $path): array
    {
        $log = [];
        $file = '';

        if (!$path)
        {
            return $log;
        }

        if ($this->default)
        {
            $file = $this->relativePaths()[$path] ?? '';
        }
        else
        {
            $file = $this->relativePaths($this->fullPaths($this->folder))[$path];
        }

        if (!$file) {
            return $log;
        }

        if (Storage::disk('logs')->size($file) > 52428800)
        {
            $log['text'] = 'El tamaño del archivo es más de 50 MB, por favor, descárguelo.';

            return $log;
        }
        if (!Storage::disk('logs')->get($file))
        {
            $log['text'] = 'El archivo de registro no es legible.';

            return $log;
        }

        $file = Storage::disk('logs')->get($file);
        preg_match_all(Pattern::get('logs'), $file, $headings);

        if (!is_array($headings))
        {
            return $log;
        }

        $logData = preg_split(Pattern::get('logs'), $file);

        if ($logData[0] < 1)
        {
            array_shift($logData);
        }

        foreach ($headings as $h)
        {
            for ($i = 0, $j = count($h); $i < $j; $i++)
            {
                foreach (Level::all() as $level)
                {
                    if (strpos(strtolower($h[$i]), ".$level") || strpos(strtolower($h[$i]), "$level:"))
                    {
                        $pattern = Pattern::get('current_log', 0) . $level . Pattern::get('current_log', 1);
                        preg_match($pattern, $h[$i], $current);

                        if (!isset($current[4]))
                        {
                            continue;
                        }

                        $log[] = [
                            'context' => $current[3],
                            'level' => $level,
                            'levelClass' => Level::cssClass($level),
                            'levelIcon' => Level::icon($level),
                            'date' => $current[1],
                            'text' => $current[4],
                            'inFile' => $current[5] ?? null,
                            'stack' => preg_replace("/^\n*/", '', $logData[$i])
                        ];
                    }
                }
            }
        }

        if (empty($log))
        {
            $lines = explode(PHP_EOL, $file);
            $log = [];

            foreach ($lines as $key => $line)
            {
                $log[] = [
                    'context' => '',
                    'level' => '',
                    'levelClass' => '',
                    'levelIcon' => '',
                    'date' => $key + 1,
                    'text' => $line,
                    'inFile' => null,
                    'stack' => '',
                ];
            }
        }

        return array_reverse($log);
    }

    public function logSizes(): array
    {
        $logSizes = [];

        $logFiles = $this->default ? $this->relativePaths() : $this->relativePaths($this->fullPaths($this->folder));

        foreach ($logFiles as $key => $value)
        {
            if (Storage::disk('logs')->get($value))
            {
                $logSizes[][] = [
                    'logName' => $key,
                    'sizeHuman' => Number::fileSize(Storage::disk('logs')->size($value), precision: 2),
                    'sizeRaw'   => Storage::disk('logs')->size($value),
                ];
            }
            else
            {
                $logSizes[][] = ['no legible.'];
            }
        }

        return $logSizes;
    }
}
