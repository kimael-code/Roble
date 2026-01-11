<x-mail::message>
# Saludos, {{ $firstName }}:

Recibimos una solicitud para restablecer la contraseña de tu cuenta de usuario.

Haz clic en el siguiente botón para que establezcas una nueva contraseña:

<x-mail::button :url="$url">
Restablecer Contraseña
</x-mail::button>

Este enlace para restablecer tu contraseña caducará en 60 minutos.

Si no solicitaste un restablecimiento de contraseña, no es necesario realizar ninguna otra acción.

<x-slot:subcopy>
Si tienes algún problema para hacer clic en el botón "Restablecer Contraseña", copia y pega la siguiente URL
en una nueva pestaña del navegador: <span class="break-all">[{{ $url }}]({{ $url }})</span>
</x-slot:subcopy>
</x-mail::message>
