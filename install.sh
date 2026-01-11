#!/bin/bash
#
# Asistente de Configuración de Entorno - Roble FOGADE
# 
# Este script automatiza la configuración de variables de entorno para
# despliegues en servidores de producción, staging o QA de FOGADE.
#
# Para desarrollo local, simplemente ejecuta: cp .env.example .env
#
# Uso:
#   ./install.sh
#

set -e  # Exit on error

# ============================================
# COLORES Y SÍMBOLOS
# ============================================
C_RESET='\033[0m'
C_BLUE='\033[0;34m'
C_CYAN='\033[0;36m'
C_GREEN='\033[0;32m'
C_YELLOW='\033[1;33m'
C_RED='\033[0;31m'
C_BOLD='\033[1m'
C_DIM='\033[2m'

# Símbolos Unicode
SYM_CHECK="✓"
SYM_CROSS="✗"
SYM_ARROW="→"
SYM_GEAR="⚙"
SYM_KEY="🔑"
SYM_DB="🗄"
SYM_MAIL="📧"
SYM_ROCKET="🚀"
SYM_LOCK="🔒"

# ============================================
# VARIABLES GLOBALES
# ============================================
declare -A configured_vars
CURRENT_YEAR=$(date +%Y)
ENV_FILE=".env"
ENV_EXAMPLE_FILE=".env.example"
ENV_BACKUP_FILE=".env.backup.$(date +%Y%m%d_%H%M%S)"

# ============================================
# FUNCIONES DE UTILIDAD
# ============================================

# Spinner animado para operaciones en progreso
spinner() {
    local pid=$1
    local message=$2
    local spinstr='⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏'
    local temp
    
    while kill -0 $pid 2>/dev/null; do
        temp=${spinstr#?}
        printf " ${C_CYAN}%c${C_RESET} %s" "$spinstr" "$message"
        spinstr=$temp${spinstr%"$temp"}
        sleep 0.1
        printf "\r"
    done
    printf "    \r"
}

# Mostrar mensaje con animación de progreso
show_progress() {
    local message=$1
    echo -ne "${C_CYAN}${SYM_GEAR}${C_RESET} ${message}"
    sleep 0.3
    echo -e " ${C_GREEN}${SYM_CHECK}${C_RESET}"
}

# Encabezado de sección
print_header() {
    echo ""
    echo -e "${C_BLUE}${C_BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${C_RESET}"
    echo -e "${C_BLUE}${C_BOLD}  $1${C_RESET}"
    echo -e "${C_BLUE}${C_BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${C_RESET}"
}

# Mensaje de éxito
print_success() {
    echo -e "${C_GREEN}${SYM_CHECK}${C_RESET} $1"
}

# Mensaje de advertencia
print_warning() {
    echo -e "${C_YELLOW}⚠${C_RESET}  $1"
}

# Mensaje de error
print_error() {
    echo -e "${C_RED}${SYM_CROSS}${C_RESET} $1"
}

# Mensaje informativo
print_info() {
    echo -e "${C_CYAN}${SYM_ARROW}${C_RESET} $1"
}

# ============================================
# FUNCIONES DE GESTIÓN DE .ENV
# ============================================

# Actualizar o añadir variable en .env
update_or_append_env() {
    local key=$1
    local value=$2
    local file=$3
    
    # Guardar para el resumen final
    configured_vars["$key"]="$value"
    
    # Actualizar el archivo
    if grep -q -E "^(# *)?${key}=" "$file"; then
        # Si existe, reemplazar la línea completa
        sed -i "s~^#* *${key}=.*~${key}=${value}~" "$file"
    else
        # Si no existe, añadirla al final del archivo
        echo "" >> "$file"
        echo "${key}=${value}" >> "$file"
    fi
}

# Obtener valor actual de una variable en .env.example
get_default_value() {
    local key=$1
    local file=$2
    local value=$(grep "^${key}=" "$file" 2>/dev/null | cut -d '=' -f2- | sed 's/^"\(.*\)"$/\1/')
    echo "$value"
}

# ============================================
# FUNCIONES DE VALIDACIÓN
# ============================================

# Validar subdominio (permite letras, números, guiones)
validate_subdomain() {
    local subdomain=$1
    if [[ "$subdomain" =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$ ]]; then
        return 0
    else
        return 1
    fi
}

# Validar puerto
validate_port() {
    local port=$1
    if [[ "$port" =~ ^[0-9]+$ ]] && [ "$port" -ge 1 ] && [ "$port" -le 65535 ]; then
        return 0
    else
        return 1
    fi
}

# Validar esquema (http o https)
validate_scheme() {
    local scheme=$1
    if [[ "$scheme" == "http" ]] || [[ "$scheme" == "https" ]]; then
        return 0
    else
        return 1
    fi
}

# Validar host de base de datos
validate_db_host() {
    local host=$1
    # Permite hostname, IP o 'db' para Docker
    if [[ "$host" =~ ^[a-zA-Z0-9]([a-zA-Z0-9.-]*[a-zA-Z0-9])?$ ]] || [[ "$host" =~ ^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$ ]]; then
        return 0
    else
        return 1
    fi
}

# ============================================
# FUNCIONES DE GENERACIÓN
# ============================================

# Generar contraseña segura
generate_password() {
    # Genera una contraseña de 16 caracteres con mayúsculas, minúsculas, números y especiales
    # Excluye $ para evitar interpolación de variables en archivos .env
    tr -dc 'A-Za-z0-9!@#%^&*()_=+-' < /dev/urandom | head -c 16
}

# Escapar y envolver contraseña en comillas
escape_and_quote_password() {
    local password=$1
    local escaped=$(echo "$password" | sed 's/\\/\\\\/g' | sed 's/"/\\"/g')
    echo "\"${escaped}\""
}

# Generar APP_KEY para Laravel
generate_app_key() {
    echo "base64:$(openssl rand -base64 32)"
}

# Generar credenciales de Reverb
generate_reverb_credentials() {
    local REVERB_APP_ID=$(shuf -i 100000-999999 -n 1)
    local REVERB_APP_KEY=$(openssl rand -hex 20)
    local REVERB_APP_SECRET=$(openssl rand -hex 20)
    
    echo "$REVERB_APP_ID:$REVERB_APP_KEY:$REVERB_APP_SECRET"
}

# ============================================
# FUNCIONES DE ENTRADA DE USUARIO
# ============================================

# Solicitar valor obligatorio con validación
prompt_with_validation() {
    local prompt_text=$1
    local default_value=$2
    local validation_func=$3
    local error_message=$4
    local value
    
    while true; do
        if [ -n "$default_value" ]; then
            read -p "$(echo -e "${prompt_text} ${C_DIM}[${default_value}]${C_RESET}: ")" value
            value=${value:-$default_value}
        else
            read -p "$(echo -e "${prompt_text}: ")" value
        fi
        
        if [ -z "$value" ]; then
            print_error "Este valor es obligatorio"
            continue
        fi
        
        if [ -n "$validation_func" ]; then
            if $validation_func "$value"; then
                echo "$value"
                return 0
            else
                print_error "$error_message"
            fi
        else
            echo "$value"
            return 0
        fi
    done
}

# Solicitar contraseña (visible, sin asteriscos)
prompt_password() {
    local prompt_text=$1
    local value
    
    while true; do
        read -p "$(echo -e "${prompt_text}: ")" value
        if [ -n "$value" ]; then
            echo "$value"
            return 0
        else
            print_error "La contraseña no puede estar vacía"
        fi
    done
}

# Solicitar contraseña con confirmación visual
prompt_password_with_confirm() {
    local prompt_text=$1
    local value
    
    while true; do
        read -p "$(echo -e "${prompt_text}: ")" value
        if [ -z "$value" ]; then
            print_error "La contraseña no puede estar vacía"
            continue
        fi
        
        echo -e "${C_DIM}Contraseña ingresada: ${C_CYAN}${value}${C_RESET}"
        if prompt_confirm "¿Es correcta?" "S"; then
            echo "$value"
            return 0
        fi
    done
}

# Solicitar confirmación (S/n)
prompt_confirm() {
    local prompt_text=$1
    local default=${2:-S}
    local response
    
    read -p "$(echo -e "${prompt_text} ${C_DIM}[${default}]${C_RESET}: ")" response
    response=${response:-$default}
    response=${response,,}
    
    if [[ "$response" == "s" ]] || [[ "$response" == "y" ]]; then
        return 0
    else
        return 1
    fi
}

# ============================================
# VALIDACIÓN DE CONECTIVIDAD A BASE DE DATOS
# ============================================

validate_db_connection() {
    local host=$1
    local port=$2
    local database=$3
    local username=$4
    local password=$5
    
    echo ""
    print_info "Validando conectividad a la base de datos..."
    echo -e "${C_DIM}  Host: ${host}:${port}${C_RESET}"
    echo -e "${C_DIM}  Base de datos: ${database}${C_RESET}"
    echo -e "${C_DIM}  Usuario: ${username}${C_RESET}"
    
    # Limpiar la contraseña de comillas si las tiene
    local clean_password=$(echo "$password" | sed 's/^"\(.*\)"$/\1/')
    
    # Intentar conexión usando Docker con imagen temporal de PostgreSQL
    echo ""
    echo -ne "${C_CYAN}⏳${C_RESET} Conectando..."
    local connection_test=$(docker run --rm \
        -e PGPASSWORD="$clean_password" \
        postgres:16-alpine \
        psql -h "$host" -p "$port" -U "$username" -d "$database" -c "SELECT 1;" 2>&1)
    local exit_code=$?
    echo -e "\r                    \r"  # Limpiar línea
    
    if [ $exit_code -ne 0 ] || echo "$connection_test" | grep -q "ERROR\|FATAL\|could not"; then
        print_error "No se pudo conectar a la base de datos"
        echo ""
        echo -e "${C_RED}${C_BOLD}Detalles del error:${C_RESET}"
        echo "$connection_test" | grep -E "ERROR|FATAL|could not|Connection" | head -5
        echo ""
        print_warning "Posibles causas:"
        echo -e "  ${C_DIM}•${C_RESET} Credenciales incorrectas (usuario/contraseña)"
        echo -e "  ${C_DIM}•${C_RESET} Base de datos no existe o no está accesible"
        echo -e "  ${C_DIM}•${C_RESET} Firewall bloqueando la conexión"
        echo -e "  ${C_DIM}•${C_RESET} Host o puerto incorrecto"
        echo ""
        
        if prompt_confirm "¿Deseas corregir las credenciales?" "S"; then
            return 1  # Reintentar
        else
            echo ""
            print_warning "${C_YELLOW}ADVERTENCIA:${C_RESET} Continuando sin validar la conexión"
            print_warning "La aplicación podría fallar al iniciar si las credenciales son incorrectas"
            echo ""
            if prompt_confirm "¿Estás seguro de continuar sin validar?" "N"; then
                return 0  # Continuar sin validar
            else
                return 1  # Reintentar
            fi
        fi
    else
        print_success "Conexión a la base de datos validada correctamente ${C_GREEN}✓${C_RESET}"
        return 0
    fi
}

# ============================================
# FUNCIONES DE CONFIGURACIÓN
# ============================================

configure_app_vars() {
    local file=$1
    
    print_header "${SYM_ROCKET} Configuración de la Aplicación"
    
    # APP_NAME
    local default_app_name=$(get_default_value "APP_NAME" "$ENV_EXAMPLE_FILE")
    echo ""
    echo -e "${C_BOLD}Nombre de la Aplicación${C_RESET}"
    local app_name=$(prompt_with_validation "Introduce el nombre de la aplicación" "$default_app_name" "" "")
    update_or_append_env "APP_NAME" "\"${app_name}\"" "$file"
    
    # APP_URL
    echo ""
    echo -e "${C_BOLD}URL de la Aplicación${C_RESET}"
    print_info "Dominio base: ${C_CYAN}fogade.gob.ve${C_RESET}"
    echo ""
    
    local scheme=$(prompt_with_validation "Esquema (http/https)" "https" "validate_scheme" "Debe ser 'http' o 'https'")
    local subdomain=$(prompt_with_validation "Subdominio (ej: sistema-prueba)" "" "validate_subdomain" "El subdominio solo puede contener letras, números y guiones")
    
    local app_url="${scheme}://${subdomain}.fogade.gob.ve"
    update_or_append_env "APP_URL" "$app_url" "$file"
    print_success "URL configurada: ${C_CYAN}${app_url}${C_RESET}"
    
    # APP_ENV (automático)
    echo ""
    show_progress "Configurando APP_ENV=production"
    update_or_append_env "APP_ENV" "production" "$file"
    
    # APP_DEBUG (automático)
    show_progress "Configurando APP_DEBUG=false"
    update_or_append_env "APP_DEBUG" "false" "$file"
    
    # APP_KEY (generado automáticamente)
    echo ""
    echo -ne "${C_CYAN}${SYM_KEY}${C_RESET} Generando clave de cifrado (APP_KEY)..."
    local app_key=$(generate_app_key)
    update_or_append_env "APP_KEY" "$app_key" "$file"
    echo -e " ${C_GREEN}${SYM_CHECK}${C_RESET}"
    
    print_success "Configuración de aplicación completada"
}

configure_main_db() {
    local file=$1
    
    print_header "${SYM_DB} Configuración de Base de Datos Principal"
    
    echo ""
    print_info "Esta es la base de datos propia del sistema Roble"
    echo ""
    
    # DB_HOST
    local db_host=$(prompt_with_validation "Host de la base de datos" "db" "validate_db_host" "Host inválido")
    update_or_append_env "DB_HOST" "$db_host" "$file"
    
    # DB_PORT
    local db_port=$(prompt_with_validation "Puerto" "5432" "validate_port" "Puerto inválido (1-65535)")
    update_or_append_env "DB_PORT" "$db_port" "$file"
    
    # DB_DATABASE
    local default_db_name=$(get_default_value "DB_DATABASE" "$ENV_EXAMPLE_FILE")
    local db_database=$(prompt_with_validation "Nombre de la base de datos" "$default_db_name" "" "")
    update_or_append_env "DB_DATABASE" "$db_database" "$file"
    
    # DB_USERNAME
    local db_username=$(prompt_with_validation "Usuario" "postgres" "" "")
    update_or_append_env "DB_USERNAME" "$db_username" "$file"
    
    # DB_PASSWORD (generado automáticamente)
    echo ""
    echo -ne "${C_CYAN}${SYM_LOCK}${C_RESET} Generando contraseña segura..."
    local raw_password=$(generate_password)
    local quoted_password=$(escape_and_quote_password "$raw_password")
    update_or_append_env "DB_PASSWORD" "$quoted_password" "$file"
    echo -e " ${C_GREEN}${SYM_CHECK}${C_RESET}"
    
    print_success "Base de datos principal configurada"
}

configure_org_db() {
    local file=$1
    
    print_header "${SYM_DB} Configuración de Base de Datos de Organización"
    
    echo ""
    print_info "Esta es la base de datos existente de FOGADE"
    print_warning "Las credenciales deben ser proporcionadas por el administrador de BD"
    echo ""
    
    # Variables locales para el bucle de reintento
    local db_org_host
    local db_org_port
    local db_org_database
    local db_org_username
    local db_org_password
    local quoted_org_password
    local validation_passed=false
    
    # Bucle principal de configuración con validación
    while [ "$validation_passed" = false ]; do
        # DB_ORG_HOST
        db_org_host=$(prompt_with_validation "Host de la base de datos" "" "validate_db_host" "Host inválido")
        
        # DB_ORG_PORT
        db_org_port=$(prompt_with_validation "Puerto" "5432" "validate_port" "Puerto inválido (1-65535)")
        
        # DB_ORG_DATABASE (automático con año actual)
        db_org_database="db_fogade_${CURRENT_YEAR}"
        echo ""
        print_info "Nombre de la base de datos: ${C_CYAN}${db_org_database}${C_RESET} ${C_DIM}(año actual: ${CURRENT_YEAR})${C_RESET}"
        
        # DB_ORG_USERNAME
        db_org_username=$(prompt_with_validation "Usuario" "postgres" "" "")
        
        # DB_ORG_PASSWORD (solicitado al usuario, visible)
        echo ""
        echo -e "${C_BOLD}Contraseña de la base de datos${C_RESET}"
        print_info "${C_DIM}La contraseña será visible mientras la escribes${C_RESET}"
        db_org_password=$(prompt_password "Introduce la contraseña")
        quoted_org_password=$(escape_and_quote_password "$db_org_password")
        
        # Validar conectividad si Docker está disponible
        if command -v docker &> /dev/null; then
            if validate_db_connection "$db_org_host" "$db_org_port" "$db_org_database" "$db_org_username" "$db_org_password"; then
                validation_passed=true
            fi
            # Si validate_db_connection retorna 1, el bucle continúa
            # Si retorna 0, validation_passed=true y sale del bucle
        else
            print_warning "Docker no disponible, omitiendo validación de conectividad"
            validation_passed=true
        fi
    done
    
    # Guardar las credenciales validadas
    update_or_append_env "DB_ORG_HOST" "$db_org_host" "$file"
    update_or_append_env "DB_ORG_PORT" "$db_org_port" "$file"
    update_or_append_env "DB_ORG_DATABASE" "$db_org_database" "$file"
    update_or_append_env "DB_ORG_USERNAME" "$db_org_username" "$file"
    update_or_append_env "DB_ORG_PASSWORD" "$quoted_org_password" "$file"
    
    print_success "Base de datos de organización configurada y validada"
}

configure_reverb() {
    local file=$1
    local app_url=${configured_vars["APP_URL"]}
    
    print_header "${SYM_GEAR} Configuración de Laravel Reverb (WebSockets)"
    
    echo ""
    echo -ne "${C_CYAN}${SYM_KEY}${C_RESET} Generando credenciales de Reverb..."
    
    # Generar credenciales
    local reverb_creds=$(generate_reverb_credentials)
    local REVERB_APP_ID=$(echo "$reverb_creds" | cut -d':' -f1)
    local REVERB_APP_KEY=$(echo "$reverb_creds" | cut -d':' -f2)
    local REVERB_APP_SECRET=$(echo "$reverb_creds" | cut -d':' -f3)
    
    update_or_append_env "REVERB_APP_ID" "$REVERB_APP_ID" "$file"
    update_or_append_env "REVERB_APP_KEY" "$REVERB_APP_KEY" "$file"
    update_or_append_env "REVERB_APP_SECRET" "$REVERB_APP_SECRET" "$file"
    
    echo -e " ${C_GREEN}${SYM_CHECK}${C_RESET}"
    
    # Detectar esquema y host desde APP_URL
    local external_scheme=$(echo "$app_url" | sed -E 's~^(https?)://.*~\1~')
    local external_host=$(echo "$app_url" | sed -E 's~^https?://([^:/]+).*~\1~')
    
    # Determinar puerto según esquema
    local external_port
    if [[ "$external_scheme" == "https" ]]; then
        external_port="443"
    else
        external_port="80"
    fi
    
    echo ""
    print_info "Configuración detectada desde APP_URL:"
    echo -e "  ${C_DIM}Esquema:${C_RESET} ${external_scheme}"
    echo -e "  ${C_DIM}Host:${C_RESET} ${external_host}"
    echo -e "  ${C_DIM}Puerto:${C_RESET} ${external_port}"
    
    # Configurar variables internas (servidor)
    update_or_append_env "REVERB_SCHEME" "http" "$file"
    update_or_append_env "REVERB_HOST" "localhost" "$file"
    update_or_append_env "REVERB_PORT" "8080" "$file"
    
    # Configurar variables externas (cliente)
    update_or_append_env "VITE_REVERB_SCHEME" "$external_scheme" "$file"
    update_or_append_env "VITE_REVERB_HOST" "\"\${REVERB_HOST}\"" "$file"
    update_or_append_env "VITE_REVERB_PORT" "$external_port" "$file"
    update_or_append_env "VITE_REVERB_APP_KEY" "\"\${REVERB_APP_KEY}\"" "$file"
    
    print_success "Reverb configurado para notificaciones en tiempo real"
}

configure_fortify() {
    local file=$1
    
    print_header "${SYM_LOCK} Configuración de Laravel Fortify (Autenticación)"
    
    echo ""
    echo -ne "${C_CYAN}${SYM_GEAR}${C_RESET} Aplicando configuración desde .env.example..."
    
    # Obtener valores de .env.example
    local fortify_registration=$(get_default_value "FORTIFY_REGISTRATION" "$ENV_EXAMPLE_FILE")
    local fortify_reset=$(get_default_value "FORTIFY_RESET_PASSWORDS" "$ENV_EXAMPLE_FILE")
    local fortify_email=$(get_default_value "FORTIFY_EMAIL_VERIFICATION" "$ENV_EXAMPLE_FILE")
    local fortify_2fa=$(get_default_value "FORTIFY_TWO_FACTOR_AUTHENTICATION" "$ENV_EXAMPLE_FILE")
    
    update_or_append_env "FORTIFY_REGISTRATION" "$fortify_registration" "$file"
    update_or_append_env "FORTIFY_RESET_PASSWORDS" "$fortify_reset" "$file"
    update_or_append_env "FORTIFY_EMAIL_VERIFICATION" "$fortify_email" "$file"
    update_or_append_env "FORTIFY_TWO_FACTOR_AUTHENTICATION" "$fortify_2fa" "$file"
    
    echo -e " ${C_GREEN}${SYM_CHECK}${C_RESET}"
    
    echo ""
    print_info "Características configuradas:"
    echo -e "  ${C_DIM}Registro:${C_RESET} ${fortify_registration}"
    echo -e "  ${C_DIM}Reseteo de contraseñas:${C_RESET} ${fortify_reset}"
    echo -e "  ${C_DIM}Verificación de email:${C_RESET} ${fortify_email}"
    echo -e "  ${C_DIM}Autenticación 2FA:${C_RESET} ${fortify_2fa}"
    
    print_success "Fortify configurado"
}

configure_mail() {
    local file=$1
    
    print_header "${SYM_MAIL} Configuración de Servidor de Correo"
    
    echo ""
    print_info "Usando configuración estándar de FOGADE"
    echo ""
    
    show_progress "MAIL_MAILER=smtp"
    update_or_append_env "MAIL_MAILER" "smtp" "$file"
    
    show_progress "MAIL_HOST=mail.fogade.gob.ve"
    update_or_append_env "MAIL_HOST" "mail.fogade.gob.ve" "$file"
    
    show_progress "MAIL_PORT=587"
    update_or_append_env "MAIL_PORT" "587" "$file"
    
    show_progress "MAIL_USERNAME=notificaciones@fogade.gob.ve"
    update_or_append_env "MAIL_USERNAME" "notificaciones@fogade.gob.ve" "$file"
    
    show_progress "MAIL_PASSWORD=Fogade25"
    update_or_append_env "MAIL_PASSWORD" "Fogade25" "$file"
    
    show_progress "MAIL_FROM_ADDRESS=notificaciones@fogade.gob.ve"
    update_or_append_env "MAIL_FROM_ADDRESS" "\"notificaciones@fogade.gob.ve\"" "$file"
    
    print_success "Servidor de correo configurado"
}

configure_docker_env() {
    print_header "🐳 Configuración de Docker"
    
    echo ""
    if ! prompt_confirm "¿El despliegue será mediante Docker?" "S"; then
        print_info "Configuración de Docker omitida"
        return
    fi
    
    local DOCKER_ENV_EXAMPLE_FILE="../../.env.example"
    local DOCKER_ENV_FILE="../../.env"
    
    if [ ! -f "$DOCKER_ENV_EXAMPLE_FILE" ]; then
        print_error "No se encontró el archivo '$DOCKER_ENV_EXAMPLE_FILE'"
        print_warning "Omitiendo configuración de Docker"
        return
    fi
    
    echo ""
    print_info "Configurando variables de entorno para Docker..."
    
    # Crear backup si existe
    if [ -f "$DOCKER_ENV_FILE" ]; then
        cp "$DOCKER_ENV_FILE" "${DOCKER_ENV_FILE}.backup.$(date +%Y%m%d_%H%M%S)"
        print_info "Backup creado del .env anterior"
    fi
    
    # Copiar desde ejemplo
    cp "$DOCKER_ENV_EXAMPLE_FILE" "$DOCKER_ENV_FILE"
    
    # Reutilizar valores configurados
    local db_database=${configured_vars["DB_DATABASE"]}
    local db_password=${configured_vars["DB_PASSWORD"]}
    
    # Actualizar archivo Docker .env
    update_or_append_env "DB_DATABASE" "$db_database" "$DOCKER_ENV_FILE"
    update_or_append_env "DB_USERNAME" "postgres" "$DOCKER_ENV_FILE"
    update_or_append_env "DB_PASSWORD" "$db_password" "$DOCKER_ENV_FILE"
    
    print_success "Archivo .env de Docker configurado en: ${C_CYAN}${DOCKER_ENV_FILE}${C_RESET}"
}

# ============================================
# RESUMEN FINAL
# ============================================

show_summary() {
    print_header "📋 Resumen de Configuración"
    
    echo ""
    echo -e "${C_BOLD}Variables configuradas en ${ENV_FILE}:${C_RESET}"
    echo ""
    
    # Crear tabla formateada
    printf "${C_CYAN}%-35s${C_RESET} | ${C_BOLD}%-50s${C_RESET}\n" "VARIABLE" "VALOR"
    printf "%.s─" {1..90}
    echo ""
    
    # Ordenar variables por categoría
    local categories=(
        "APP_NAME|APP_ENV|APP_DEBUG|APP_URL|APP_KEY"
        "DB_HOST|DB_PORT|DB_DATABASE|DB_USERNAME|DB_PASSWORD"
        "DB_ORG_HOST|DB_ORG_PORT|DB_ORG_DATABASE|DB_ORG_USERNAME|DB_ORG_PASSWORD"
        "REVERB_APP_ID|REVERB_APP_KEY|REVERB_APP_SECRET|REVERB_SCHEME|REVERB_HOST|REVERB_PORT|VITE_REVERB_SCHEME|VITE_REVERB_HOST|VITE_REVERB_PORT|VITE_REVERB_APP_KEY"
        "FORTIFY_REGISTRATION|FORTIFY_RESET_PASSWORDS|FORTIFY_EMAIL_VERIFICATION|FORTIFY_TWO_FACTOR_AUTHENTICATION"
        "MAIL_MAILER|MAIL_HOST|MAIL_PORT|MAIL_USERNAME|MAIL_PASSWORD|MAIL_FROM_ADDRESS"
    )
    
    local category_names=(
        "APLICACIÓN"
        "BASE DE DATOS PRINCIPAL"
        "BASE DE DATOS ORGANIZACIÓN"
        "REVERB (WEBSOCKETS)"
        "FORTIFY (AUTENTICACIÓN)"
        "CORREO ELECTRÓNICO"
    )
    
    local idx=0
    for category in "${categories[@]}"; do
        echo ""
        echo -e "${C_YELLOW}${C_BOLD}${category_names[$idx]}${C_RESET}"
        echo ""
        
        IFS='|' read -ra VARS <<< "$category"
        for var in "${VARS[@]}"; do
            if [[ -n "${configured_vars[$var]}" ]]; then
                local value="${configured_vars[$var]}"
                
                # Truncar valores muy largos
                if [ ${#value} -gt 50 ]; then
                    value="${value:0:47}..."
                fi
                
                printf "  %-33s | %s\n" "$var" "$value"
            fi
        done
        
        ((idx++))
    done
    
    echo ""
    printf "%.s─" {1..90}
    echo ""
}

# ============================================
# FUNCIÓN PRINCIPAL
# ============================================

main() {
    clear
    
    # Banner de bienvenida
    echo -e "${C_BLUE}${C_BOLD}"
    echo "╔════════════════════════════════════════════════════════════════╗"
    echo "║                                                                ║"
    echo "║        Asistente de Configuración - Roble FOGADE              ║"
    echo "║                                                                ║"
    echo "║        Configuración automatizada para entornos de             ║"
    echo "║        producción, staging y QA                                ║"
    echo "║                                                                ║"
    echo "╚════════════════════════════════════════════════════════════════╝"
    echo -e "${C_RESET}"
    
    echo ""
    print_info "Este asistente configurará las variables de entorno necesarias"
    print_info "para desplegar Roble en los servidores de FOGADE"
    echo ""
    print_warning "Para desarrollo local, usa: ${C_CYAN}cp .env.example .env${C_RESET}"
    echo ""
    
    if ! prompt_confirm "¿Deseas continuar con la configuración?" "S"; then
        echo ""
        print_warning "Configuración cancelada por el usuario"
        exit 0
    fi
    
    # Verificar archivos
    if [ ! -f "$ENV_EXAMPLE_FILE" ]; then
        echo ""
        print_error "No se encontró el archivo ${ENV_EXAMPLE_FILE}"
        print_error "Asegúrate de ejecutar este script desde el directorio raíz de Roble"
        exit 1
    fi
    
    # Crear backup si existe .env
    if [ -f "$ENV_FILE" ]; then
        echo ""
        print_warning "Ya existe un archivo .env"
        
        if prompt_confirm "¿Deseas crear uno nuevo? (se hará backup del anterior)" "N"; then
            cp "$ENV_FILE" "$ENV_BACKUP_FILE"
            print_success "Backup creado: ${C_CYAN}${ENV_BACKUP_FILE}${C_RESET}"
        else
            echo ""
            print_warning "Configuración cancelada"
            exit 0
        fi
    fi
    
    # Crear .env desde .env.example
    echo ""
    print_info "Creando archivo .env desde .env.example..."
    cp "$ENV_EXAMPLE_FILE" "$ENV_FILE"
    print_success "Archivo .env creado"
    
    # Ejecutar configuraciones
    configure_app_vars "$ENV_FILE"
    configure_main_db "$ENV_FILE"
    configure_org_db "$ENV_FILE"
    configure_reverb "$ENV_FILE"
    configure_fortify "$ENV_FILE"
    configure_mail "$ENV_FILE"
    configure_docker_env
    
    # Mostrar resumen
    show_summary
    
    # Confirmación final
    echo ""
    echo ""
    if prompt_confirm "¿Confirmas que la configuración es correcta?" "S"; then
        echo ""
        print_header "✅ Configuración Completada Exitosamente"
        echo ""
        print_success "El archivo ${C_CYAN}${ENV_FILE}${C_RESET} ha sido configurado correctamente"
        echo ""
        print_info "Próximos pasos:"
        echo -e "  ${C_DIM}1.${C_RESET} Construir las imágenes Docker: ${C_CYAN}docker compose build${C_RESET}"
        echo -e "  ${C_DIM}2.${C_RESET} Iniciar los contenedores: ${C_CYAN}docker compose up -d${C_RESET}"
        echo -e "  ${C_DIM}3.${C_RESET} Ejecutar migraciones: ${C_CYAN}docker exec roble_app php artisan migrate${C_RESET}"
        echo -e "  ${C_DIM}4.${C_RESET} Acceder al instalador de superusuario: ${C_CYAN}\${APP_URL}/su-installer${C_RESET}"
        echo ""
    else
        echo ""
        print_warning "Configuración descartada"
        
        if [ -f "$ENV_BACKUP_FILE" ]; then
            mv "$ENV_BACKUP_FILE" "$ENV_FILE"
            print_info "Archivo .env anterior restaurado"
        else
            rm "$ENV_FILE"
            print_info "Archivo .env eliminado"
        fi
        
        exit 0
    fi
}

# Ejecutar función principal
main "$@"
