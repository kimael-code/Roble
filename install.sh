#!/bin/bash

# Colores para la salida
C_RESET='\033[0m'
C_BLUE='\033[0;34m'
C_GREEN='\033[0;32m'
C_YELLOW='\033[1;33m'
C_RED='\033[0;31m'

# Array asociativo para almacenar las variables configuradas para el resumen final
declare -A configured_vars

# Función para actualizar una variable en .env o añadirla si no existe
update_or_append_env() {
    _update_env_file "$1" "$2" "$3"
    # Guardar para el resumen final
    configured_vars["$1"]="$2"
}

# Función interna para modificar el archivo .env sin afectar el resumen
_update_env_file() {
    local key=${1}
    local value=${2}
    local file=${3}

    # Comprobar si la clave existe (incluso si está comentada)
    if grep -q -E "^(# *)?${key}=" "$file"; then
        # Si existe, reemplazar la línea completa
        sed -i "s~^#* *${key}=.*~${key}=${value}~" "$file"
    else
        # Si no existe, añadirla al final del archivo
        echo "" >> "$file"
        echo "${key}=${value}" >> "$file"
    fi
}

prompt_for_mandatory() {
    local prompt_text=$1
    local default_value=$2
    local var_name

    while true; do
        read -p "$prompt_text" var_name
        var_name=${var_name:-$default_value}
        if [ -n "$var_name" ]; then
            echo "$var_name"
            break
        else
            echo -e "${C_RED}Error: Este valor es obligatorio.${C_RESET}"
        fi
    done
}

prompt_for_password() {
    local prompt_text=$1
    local var_name

    while true; do
        read -p "$prompt_text" var_name
        if [ -n "$var_name" ]; then
            # Escapar comillas dobles y barras invertidas y envolver en comillas dobles
            local escaped_value=$(echo "$var_name" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
            echo "\"${escaped_value}\""
            break
        else
            echo -e "${C_RED}Error: Este valor es obligatorio.${C_RESET}"
        fi
    done
}

generate_password() {
    # Genera una contraseña de 13 caracteres con mayúsculas, minúsculas, números y especiales
    # Nota: Se excluye $ para evitar interpolación de variables en archivos .env
    tr -dc 'A-Za-z0-9!@#%^&*()_=+-' < /dev/urandom | head -c 13
}

escape_and_quote_password() {
    local escaped_value=$(echo "$1" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
    echo "\"${escaped_value}\""
}

configure_app_vars() {
    local file=$1
    echo -e "\n${C_BLUE}--- Configuración de la Aplicación ---${C_RESET}"

    # APP_NAME con valor por defecto
    local default_app_name=$(grep "^APP_NAME=" "$file" | cut -d '=' -f2-)
    # Quita las comillas si existen
    default_app_name="${default_app_name//\"/}"
    local app_name=$(prompt_for_mandatory "Introduce APP_NAME [${default_app_name}]: " "$default_app_name")
    update_or_append_env "APP_NAME" "\"${app_name}\"" "$file"

    # APP_ENV y APP_DEBUG automáticos
    echo "Configurando APP_ENV a 'production'..."
    update_or_append_env "APP_ENV" "production" "$file"
    echo "Configurando APP_DEBUG a 'false'..."
    update_or_append_env "APP_DEBUG" "false" "$file"
    
    # APP_URL con validación
    while true; do
        local app_url=$(prompt_for_mandatory "Introduce APP_URL (ej. http://localhost): ")
        if [[ "$app_url" =~ ^https?://[a-zA-Z0-9._-]+(:[0-9]+)?(/.*)?$ ]]; then
            update_or_append_env "APP_URL" "$app_url" "$file"
            break
        else
            echo -e "${C_RED}Error: La URL no es válida. Debe empezar con http:// o https://.${C_RESET}"
        fi
    done
}

configure_db_vars() {
    local file=$1
    local prefix=$2 # Prefijo para las variables (ej. DB_ o DB_ORG_)
    local title=$3

    echo -e "\n${C_BLUE}--- Configuración de la Base de Datos (${title}) ---${C_RESET}"

    local db_host=$(prompt_for_mandatory "Introduce ${prefix}HOST: ")
    update_or_append_env "${prefix}HOST" "$db_host" "$file"

    while true; do # Bucle para validación de puerto
        local db_port=$(prompt_for_mandatory "Introduce ${prefix}PORT: ")
        if [[ "$db_port" =~ ^[0-9]+$ && "$db_port" -ge 1 && "$db_port" -le 65535 ]]; then
            update_or_append_env "${prefix}PORT" "$db_port" "$file"
            break
        else
            echo -e "${C_RED}Error: El puerto debe ser un número entre 1 y 65535.${C_RESET}"
        fi
    done

    local db_database=$(prompt_for_mandatory "Introduce ${prefix}DATABASE: ")
    update_or_append_env "${prefix}DATABASE" "$db_database" "$file"

    local db_username=$(prompt_for_mandatory "Introduce ${prefix}USERNAME [postgres]: " "postgres")
    update_or_append_env "${prefix}USERNAME" "$db_username" "$file"

    if [[ "$prefix" == "DB_ORG_" ]]; then
        # Para la BD existente, pedir la contraseña
        local db_password=$(prompt_for_password "Introduce ${prefix}PASSWORD: ")
        update_or_append_env "${prefix}PASSWORD" "$db_password" "$file"
    else
        # Para la BD principal, generar la contraseña
        echo "Generando contraseña para ${prefix}PASSWORD..."
        local raw_password=$(generate_password)
        local quoted_password=$(escape_and_quote_password "$raw_password")
        update_or_append_env "${prefix}PASSWORD" "$quoted_password" "$file"
    fi
}

configure_mandatory_reverb_vars() {
    local file=$1
    echo -e "\n${C_BLUE}--- Configuración de Laravel Reverb (Obligatorio) ---${C_RESET}"
    echo "Generando valores aleatorios para las credenciales de Reverb..."

    # Generar valores aleatorios para Reverb
    local REVERB_APP_ID=$(shuf -i 100000-999999 -n 1)
    local REVERB_APP_KEY=$(openssl rand -hex 20)
    local REVERB_APP_SECRET=$(openssl rand -hex 20)

    # Actualizar el archivo .env con los nuevos valores
    update_or_append_env "REVERB_APP_ID" "$REVERB_APP_ID" "$file"
    update_or_append_env "REVERB_APP_KEY" "$REVERB_APP_KEY" "$file"
    update_or_append_env "REVERB_APP_SECRET" "$REVERB_APP_SECRET" "$file"

    echo -e "${C_GREEN}Credenciales de Reverb generadas y configuradas.${C_RESET}"
}

configure_optional_reverb_vars() {
    local file=$1
    echo ""
    read -p "¿Deseas configurar las variables de conexión de Reverb (host, port, scheme)? (s/N): " config_optional
    config_optional=${config_optional,,}

    if [[ "$config_optional" != "s" ]]; then
        echo -e "\n${C_YELLOW}Se omitió la configuración de las variables de conexión. Se usarán los valores por defecto del archivo .env.${C_RESET}"
        return
    fi

    # --- Configurar REVERB_HOST ---
    while true; do
        read -p "Introduce REVERB_HOST [localhost]: " host
        host=${host:-localhost} # Valor por defecto si la entrada está vacía
        # Validar si es 'localhost' o una IP v4
        if [[ "$host" == "localhost" || "$host" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]; then
            update_or_append_env "REVERB_HOST" "$host" "$file"
            break
        else
            echo -e "${C_RED}Error: El valor debe ser 'localhost' o una dirección IP v4 válida.${C_RESET}"
        fi
    done

    # --- Configurar REVERB_PORT ---
    while true; do
        read -p "Introduce REVERB_PORT [8080]: " port
        port=${port:-8080} # Valor por defecto
        # Validar si es un número entero en el rango de puertos
        if [[ "$port" =~ ^[0-9]+$ && "$port" -ge 1 && "$port" -le 65535 ]]; then
            update_or_append_env "REVERB_PORT" "$port" "$file"
            break
        else
            echo -e "${C_RED}Error: El puerto debe ser un número entre 1 y 65535.${C_RESET}"
        fi
    done

    # --- Configurar REVERB_SCHEME ---
    while true; do
        read -p "Introduce REVERB_SCHEME [http]: " scheme
        scheme=${scheme:-http} # Valor por defecto
        scheme=${scheme,,} # Convertir a minúsculas
        if [[ "$scheme" == "http" || "$scheme" == "https" ]]; then
            update_or_append_env "REVERB_SCHEME" "$scheme" "$file"
            break
        else
            echo -e "${C_RED}Error: El esquema debe ser 'http' o 'https'.${C_RESET}"
        fi
    done

    echo -e "\n${C_GREEN}✅ Variables de conexión de Reverb actualizadas.${C_RESET}"
}

configure_fortify_features() {
    local file=$1
    echo ""
    read -p "¿Deseas configurar las características de Fortify (registration, password reset, etc.)? (s/N): " config_optional
    config_optional=${config_optional,,}

    if [[ "$config_optional" != "s" ]]; then
        echo -e "\n${C_YELLOW}Se omitió la configuración de las características de Fortify. Se usarán los valores por defecto.${C_RESET}"
        return
    fi

    echo -e "\n${C_BLUE}--- Configuración de Características de Fortify ---${C_RESET}"
    echo "Introduce 'true' o 'false' para cada característica."

    # Helper for prompting for boolean-like values
    prompt_for_feature() {
        local var_name=$1
        local default_value=$2
        local response

        while true; do
            read -p "Habilitar ${var_name} [${default_value}]: " response
            response=${response:-$default_value}
            response=${response,,}
            if [[ "$response" == "true" || "$response" == "false" ]]; then
                update_or_append_env "$var_name" "$response" "$file"
                break
            else
                echo -e "${C_RED}Valor inválido. Por favor, introduce 'true' o 'false'.${C_RESET}"
            fi
        done
    }

    prompt_for_feature "FORTIFY_REGISTRATION" "true"
    prompt_for_feature "FORTIFY_RESET_PASSWORDS" "true"
    prompt_for_feature "FORTIFY_EMAIL_VERIFICATION" "true"
    prompt_for_feature "FORTIFY_TWO_FACTOR_AUTHENTICATION" "true"

    echo -e "\n${C_GREEN}✅ Características de Fortify actualizadas.${C_RESET}"
}

configure_mail_vars() {
    local file=$1
    echo -e "\n${C_BLUE}--- Configuración del Servidor de Correo (SMTP) ---${C_RESET}"

    # MAIL_MAILER automático
    echo "Configurando MAIL_MAILER a 'smtp'..."
    update_or_append_env "MAIL_MAILER" "smtp" "$file"

    # MAIL_HOST obligatorio (con valor por defecto)
    local mail_host=$(prompt_for_mandatory "Introduce MAIL_HOST [mail.empresa.com]: " "mail.empresa.com")
    update_or_append_env "MAIL_HOST" "$mail_host" "$file"

    # MAIL_PORT obligatorio con validación
    while true; do
        local mail_port=$(prompt_for_mandatory "Introduce MAIL_PORT (ej. 587, 465, 25): ")
        if [[ "$mail_port" =~ ^[0-9]+$ && "$mail_port" -ge 1 && "$mail_port" -le 65535 ]]; then
            update_or_append_env "MAIL_PORT" "$mail_port" "$file"
            break
        else
            echo -e "${C_RED}Error: El puerto debe ser un número entre 1 y 65535.${C_RESET}"
        fi
    done

    # MAIL_USERNAME obligatorio
    local mail_username=$(prompt_for_mandatory "Introduce MAIL_USERNAME (ej. usuario o usuario@dominio.com): ")
    update_or_append_env "MAIL_USERNAME" "$mail_username" "$file"

    # MAIL_PASSWORD obligatorio
    local mail_password=$(prompt_for_password "Introduce MAIL_PASSWORD: ")
    update_or_append_env "MAIL_PASSWORD" "$mail_password" "$file"

    # MAIL_FROM_ADDRESS - construir automáticamente o solicitar
    echo ""
    echo "La dirección de remitente (MAIL_FROM_ADDRESS) se puede construir automáticamente"
    echo "a partir del nombre de usuario con el dominio @empresa.com"
    
    # Extraer solo el nombre de usuario sin dominio si viene con @
    local username_without_domain=$(echo "$mail_username" | cut -d '@' -f1)
    local suggested_from="${username_without_domain}@empresa.com"
    
    read -p "¿Usar '${suggested_from}' como MAIL_FROM_ADDRESS? (S/n): " use_suggested
    use_suggested=${use_suggested,,}
    
    if [[ "$use_suggested" != "n" ]]; then
        update_or_append_env "MAIL_FROM_ADDRESS" "\"${suggested_from}\"" "$file"
    else
        local mail_from=$(prompt_for_mandatory "Introduce MAIL_FROM_ADDRESS (ej. noreply@dominio.com): ")
        update_or_append_env "MAIL_FROM_ADDRESS" "\"${mail_from}\"" "$file"
    fi

    echo -e "\n${C_GREEN}✅ Configuración del servidor de correo completada.${C_RESET}"
}

configure_docker_env() {
    echo ""
    read -p "¿El despliegue será mediante Docker? (s/N): " use_docker
    use_docker=${use_docker,,}

    if [[ "$use_docker" != "s" ]]; then
        return
    fi

    echo -e "\n${C_BLUE}--- Configuración del entorno Docker ---${C_RESET}"
    local DOCKER_ENV_EXAMPLE_FILE="../../.env.example"
    local DOCKER_ENV_FILE="../../.env"

    if [ ! -f "$DOCKER_ENV_EXAMPLE_FILE" ]; then
        echo -e "${C_RED}Error: No se encontró el archivo '$DOCKER_ENV_EXAMPLE_FILE'. No se puede continuar con la configuración de Docker.${C_RESET}"
        return
    fi

    echo "Creando archivo de entorno para Docker en '$DOCKER_ENV_FILE'..."
    cp "$DOCKER_ENV_EXAMPLE_FILE" "$DOCKER_ENV_FILE"

    # Reutilizar valores ya configurados
    local db_database=${configured_vars["DB_DATABASE"]}
    local db_password=${configured_vars["DB_PASSWORD"]}

    echo "Configurando variables en el .env de Docker..."
    _update_env_file "DB_DATABASE" "$db_database" "$DOCKER_ENV_FILE"
    _update_env_file "DB_USERNAME" "postgres" "$DOCKER_ENV_FILE"
    _update_env_file "DB_PASSWORD" "$db_password" "$DOCKER_ENV_FILE"

    echo -e "${C_GREEN}✅ Archivo de entorno Docker configurado en '$DOCKER_ENV_FILE'.${C_RESET}"
}

main() {
    echo -e "${C_BLUE}--------------------------------------------------${C_RESET}"
    echo -e "${C_BLUE} Asistente de Configuración de Entorno ${C_RESET}"
    echo -e "${C_BLUE}--------------------------------------------------${C_RESET}"
    echo "Este script te guiará para configurar las variables de"
    echo "entorno mínimas y obligatorias para que el sistema funcione."
    echo ""
    read -p "¿Deseas continuar? (S/n): " confirm
    # Convertir la respuesta a minúsculas y establecer 's' como valor por defecto
    confirm=${confirm,,}
    if [[ "$confirm" != "s" && "$confirm" != "" ]]; then
        echo -e "\n${C_YELLOW}Operación cancelada por el usuario. No se han modificado las variables de entorno.${C_RESET}"
        exit 0
    fi
    echo ""

    local ENV_FILE=".env"
    local ENV_EXAMPLE_FILE=".env.example"

    # 1. Comprobar si .env existe. Si no, copiarlo desde .env.example
    if [ ! -f "$ENV_FILE" ]; then
        if [ -f "$ENV_EXAMPLE_FILE" ]; then
            echo -e "${C_YELLOW}No se encontró el archivo .env. Creando uno a partir de $ENV_EXAMPLE_FILE...${C_RESET}"
            cp "$ENV_EXAMPLE_FILE" "$ENV_FILE"
        else
            echo -e "${C_RED}Error: No se encontró ni .env ni .env.example. Por favor, asegúrese de que .env.example exista.${C_RESET}"
            exit 1
        fi
    fi

    # 2. Configurar variables de la aplicación y Reverb (simples primero)
    configure_app_vars "$ENV_FILE"
    configure_mandatory_reverb_vars "$ENV_FILE"
    configure_optional_reverb_vars "$ENV_FILE"
    configure_fortify_features "$ENV_FILE"
    configure_mail_vars "$ENV_FILE"

    # 3. Configurar bases de datos
    configure_db_vars "$ENV_FILE" "DB_" "Principal"
    configure_db_vars "$ENV_FILE" "DB_ORG_" "Organización"

    # 4. Configuración extra para Docker
    configure_docker_env

    echo ""
    echo -e "${C_BLUE}--------------------------------------------------${C_RESET}"
    echo -e "${C_BLUE}       Resumen de la Configuración Realizada      ${C_RESET}"
    echo -e "${C_BLUE}--------------------------------------------------${C_RESET}"
    
    # Imprimir el resumen
    for key in "${!configured_vars[@]}"; do
        # Mostrar todas las variables configuradas con sus valores
        printf "%-20s = %s\n" "$key" "${configured_vars[$key]}"
    done

    echo ""
    echo -e "${C_GREEN}¡Configuración automática completada!${C_RESET}"
}

main "$@"
