<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Captura metadata de la petición HTTP actual para auditoría.
 *
 * Esta clase centraliza la lógica de captura de información de request
 * que se usa repetidamente en activity logging.
 */
class RequestMetadata
{
    /**
     * Captura la metadata completa de la petición actual.
     *
     * @return array<string, mixed>
     */
    public static function capture(?Request $request = null): array
    {
        $request = $request ?? request();

        return [
            'ip_address' => $request->ip(),
            'user_agent' => $request->header('user-agent'),
            'user_agent_lang' => $request->header('accept-language'),
            'referer' => $request->header('referer'),
            'http_method' => $request->method(),
            'request_url' => $request->fullUrl(),
        ];
    }

    /**
     * Captura solo la IP de la petición actual.
     *
     * @return string
     */
    public static function ip(?Request $request = null): string
    {
        $request = $request ?? request();
        return $request->ip();
    }

    /**
     * Captura solo el user agent de la petición actual.
     *
     * @return string|null
     */
    public static function userAgent(?Request $request = null): ?string
    {
        $request = $request ?? request();
        return $request->header('user-agent');
    }
}
