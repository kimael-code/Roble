<x-mail::message>
    # Hola, {{ $firstName }}:

    Se ha creado una cuenta de usuario para ti en el sistema {{ config('app.name') }}. Para comenzar a utilizarla, solo
    necesitas activar tu cuenta y establecer tu contraseña personal.

    Por favor, haz clic en el botón de abajo para ir a la página de activación:

    <x-mail::button :url="$url">
        Activar mi cuenta
    </x-mail::button>

    **Importante:** Este enlace de activación es válido por 24 horas.

    Si no esperabas recibir este correo, por favor, ignóralo. Tu cuenta no será activada.

    Gracias,<br>
    El equipo de {{ config('app.name') }}.

    <x-slot:subcopy>
        Si tienes problemas con el botón "Activar mi cuenta", copia y pega la siguiente URL en tu navegador web: <span
            class="break-all">[{{ $url }}]({{ $url }})</span>
    </x-slot:subcopy>
</x-mail::message>