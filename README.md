# ROBLE

Kit de inicio para desarrollar aplicaciones web monolíticas basadas en Laravel, Inertia.js, Vue.js y Tailwind CSS.

## Construido con 🛠️

- [Laravel](https://laravel.com/docs)
- [Vue](https://vuejs.org)
- [shadcn-vue](https://www.shadcn-vue.com)
- [Inertia](https://inertiajs.com)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [PostgreSQL](https://www.postgresql.org)

## Usuarios y Roles 👥

En Roble, ningún usuario es creado cuando se alimenta por primera vez la base de datos. Solamente se crean los perfiles (roles) mínimos necesarios, los cuales son:

1- **Superusuario**: tiene acceso a cualquier ruta del sistema y puede ejecutar cualquier acción que no viole la estabilidad del sistema. Es un perfil protegido, de sólo lectura.
2- **Administrador de Sistemas**: gestiona los datos básicos, de seguridad y de monitoreo del sistema. Es un perfil editable e incluso eliminable.

_La eliminación de roles y permisos es irreversible_, los roles o permisos, una vez eliminados, no se pueden volver a recuperar; deberán ser registrados nuevamente.

A partir del superusuario creado, se pueden crear nuevos roles y usuarios, además de gestionar cualquier proceso del sistema.

Hay que destacar que los usuarios también pueden ser creados de manera autogestionada por los propios empleados activos de la institución, sin embargo, se crearán sin perfiles asociados por lo que solamente tendrán acceso en el sistema al menú propio del usuario.

## Características 🤩

_Nota_: si lo prefiere, entiéndase la palabra 'gestión' como `CRUD` (crear, leer, editar y eliminar registros o datos), sin embargo la exportación de datos a archivos también forma parte de la gestión de los datos en ROBLE.

- Tablero básico con gráficas resúmenes de usuarios, roles y otros datos básicos.
- Gestión de:
  - los datos básicos de la organización, así como de sus respectivas unidades administrativas,
  - permisos,
  - roles (perfiles de usuarios),
  - usuarios,
  - modo de mantenimiento del sistema.
- Consulta y exportación de trazas de las actividades de los usuarios.
- Consulta, vaciado/eliminación y exportación de los registros de depuración del sistema.
- Notificaciones, en tiempo real, de las acciones realizadas por los usuarios.

## Instalación en Entorno Local 🚀

Esta guía cubre la instalación usando **Laravel Herd** (recomendado para macOS y Windows) y **Laravel Sail** (basado en Docker, para cualquier sistema operativo).

### Requisitos Previos

Asegúrate de tener instalado el software correspondiente a tu entorno de elección:

| Software                | Entorno Herd | Entorno Sail |
| ----------------------- | :----------: | :----------: |
| **Laravel Herd**        |      ✅      |              |
| **Servidor PostgreSQL** |      ✅      |              |
| **Node.js y npm**       |      ✅      |              |
| **Composer**            |      ✅      |              |
| **Docker Engine**       |              |      ✅      |

> **Nota para Herd**: Se recomienda usar [DBngin](https://dbngin.com/) para gestionar fácilmente tu servidor de PostgreSQL.

### Paso 1: Clonar el Repositorio

```sh
git clone URL_DEL_REPOSITORIO
cd roble
```

> **Nota para Herd**: Si usas Laravel Herd, clona el repositorio dentro de la carpeta que Herd esté monitorizando (normalmente `~/Herd`).

### Paso 2: Configurar Variables de Entorno (.env)

Este proyecto requiere credenciales para dos bases de datos y para el servidor de WebSockets (Laravel Reverb).

La forma más sencilla de configurar todo es usando el asistente interactivo:

```sh
./install.sh
```

Este script te guiará para configurar todas las variables necesarias.

Si prefieres hacerlo manualmente, copia el archivo de ejemplo y edítalo:

```sh
cp .env.example .env
```

Asegúrate de configurar como mínimo las variables `DB_*`, `DB_ORG_*` y `REVERB_*`.

### Paso 3: Instalar Dependencias

**Para Entorno Herd:**

Ejecuta los siguientes comandos en tu terminal:

```sh
composer install
npm install
```

**Para Entorno Sail:**

1.  Primero, inicia los contenedores de Sail. La primera vez puede tardar varios minutos mientras se descargan las imágenes de Docker.
    ```sh
    sail up -d
    ```
2.  Una vez que los contenedores estén corriendo, instala las dependencias _dentro_ de ellos:
    ```sh
    sail composer install
    sail npm install
    ```

### Paso 4: Ejecutar el Instalador de la Aplicación

Este proyecto incluye un comando para automatizar la preparación de la aplicación.

> **⚠️ ADVERTENCIA MUY IMPORTANTE ⚠️**
> Este comando **eliminará todos los datos** de tu base de datos principal y los reemplazará con los datos de prueba iniciales (`migrate:fresh --seed`). Úsalo solo en la configuración inicial.

| Entorno Herd              | Entorno Sail               |
| ------------------------- | -------------------------- |
| `php artisan app:install` | `sail artisan app:install` |

Este comando se encargará de:

- Generar la clave de la aplicación.
- Limpiar y generar cachés de configuración.
- Crear el enlace simbólico al `storage`.
- Ejecutar las migraciones y los _seeders_ de la base de datos.

### Paso 5: Iniciar Servicios en Segundo Plano

Para que las notificaciones en tiempo real y las tareas en cola funcionen, debes iniciar dos procesos. Se recomienda abrir dos terminales separadas en la raíz del proyecto para ejecutar cada uno.

| Servicio           | Comando para Herd          | Comando para Sail           |
| :----------------- | :------------------------- | :-------------------------- |
| **Laravel Reverb** | `php artisan reverb:start` | `sail artisan reverb:start` |
| **Cola de Tareas** | `php artisan queue:listen` | `sail artisan queue:listen` |

### Paso 6: Crear el Superusuario Inicial

Con el entorno ya configurado y los servicios corriendo, el paso final es crear el primer usuario con rol `Superusuario`.

1.  Abre tu navegador web.
2.  Visita la URL de tu proyecto seguida de `/su-install`.
    - **URL con Herd:** `http://roble.test/su-install`
    - **URL con Sail:** `http://localhost/su-install`
3.  Sigue las instrucciones del asistente web para crear tu usuario.

### ¡Listo!

Una vez creado el Superusuario, el sistema de autenticación se habilitará. Ahora puedes ir a la ruta `/login` para iniciar sesión con las credenciales que acabas de crear.

## Colaboradores ✒️

- Maikel Carballo [@profemaik](https://gitlab.com/profemaik)

## Contribuya, sus ideas pueden aportar mejoras significativas 🤓

Si Usted considera que esta documentación está incompleta o que pueda mejorarse:

1.  verifique que pueda tener acceso al repositorio,
2.  clónelo,
3.  cree una nueva rama,
4.  haga las correcciones que crea pertinente a este archivo,
5.  publique su nueva rama con `git push`,
    O si lo prefiere puede crear un ticket en el repositorio planteando sus correcciones o mejoras.
