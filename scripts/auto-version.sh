#!/bin/bash
# auto-version.sh - Script de versionado semántico automático para Roble FOGADE
# Basado en Conventional Commits (https://www.conventionalcommits.org/)
# Autor: Sistema de CI/CD Automatizado
# Licencia: MIT

set -e  # Exit on error

# ============================================================================
# CONFIGURACIÓN
# ============================================================================

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Versión por defecto si no hay tags
DEFAULT_VERSION="1.0.0"

# Modo dry-run (no hace cambios reales)
DRY_RUN=false

# Modo verbose
VERBOSE=false

# ============================================================================
# FUNCIONES AUXILIARES
# ============================================================================

log_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

log_success() {
    echo -e "${GREEN}✓${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

log_error() {
    echo -e "${RED}✗${NC} $1"
}

log_verbose() {
    if [ "$VERBOSE" = true ]; then
        echo -e "${NC}  → $1${NC}"
    fi
}

usage() {
    cat << EOF
Uso: $0 [OPCIONES]

Opciones:
    --dry-run       Previsualiza cambios sin aplicarlos
    --verbose       Muestra información detallada del proceso
    -h, --help      Muestra esta ayuda

Descripción:
    Calcula automáticamente la siguiente versión semántica basándose en
    Conventional Commits desde el último tag git.

    Tipos de commit que incrementan versión:
      - fix:       → incrementa PATCH (1.0.0 → 1.0.1)
      - feat:      → incrementa MINOR (1.0.0 → 1.1.0)
      - feat!:     → incrementa MAJOR (1.0.0 → 2.0.0)
      - BREAKING CHANGE: → incrementa MAJOR

    Tipos que NO incrementan versión:
      - docs:, style:, refactor:, test:, chore:, perf:

Ejemplos:
    $0                  # Ejecuta versionado normal
    $0 --dry-run        # Solo muestra qué haría
    $0 --verbose        # Muestra detalles del análisis

EOF
    exit 0
}

# ============================================================================
# ARGUMENTOS DE LÍNEA DE COMANDOS
# ============================================================================

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        -h|--help)
            usage
            ;;
        *)
            log_error "Opción desconocida: $1"
            usage
            ;;
    esac
done

# ============================================================================
# VALIDACIONES PREVIAS
# ============================================================================

log_info "Iniciando proceso de versionado automático..."

# Verificar que estamos en un repositorio git
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    log_error "No se encontró un repositorio git"
    exit 1
fi

log_verbose "Repositorio git detectado"

# Verificar que estamos en la raíz del proyecto
if [ ! -f "composer.json" ]; then
    log_error "Este script debe ejecutarse desde la raíz del proyecto"
    exit 1
fi

log_verbose "Ejecutando desde raíz del proyecto"

# ============================================================================
# OBTENER ÚLTIMO TAG Y COMMITS
# ============================================================================

# Obtener el último tag semántico
LAST_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "")

if [ -z "$LAST_TAG" ]; then
    log_warning "No se encontró ningún tag previo, usando versión por defecto: v${DEFAULT_VERSION}"
    LAST_TAG="v${DEFAULT_VERSION}"
    CURRENT_VERSION="$DEFAULT_VERSION"
    # Para el primer tag, analizamos todos los commits
    COMMIT_RANGE="HEAD"
else
    log_verbose "Último tag encontrado: $LAST_TAG"
    # Extraer versión sin la 'v' y sin hash si existe
    CURRENT_VERSION=$(echo "$LAST_TAG" | sed 's/^v//' | sed 's/+.*//')
    COMMIT_RANGE="${LAST_TAG}..HEAD"
fi

log_info "Versión actual: ${CURRENT_VERSION}"

# Obtener commits desde el último tag
COMMITS=$(git log "$COMMIT_RANGE" --pretty=format:"%s" 2>/dev/null || echo "")

if [ -z "$COMMITS" ]; then
    log_warning "No hay commits nuevos desde ${LAST_TAG}"
    log_info "No se requiere nueva versión"
    exit 0
fi

NUM_COMMITS=$(echo "$COMMITS" | wc -l)
log_verbose "Analizando ${NUM_COMMITS} commits desde ${LAST_TAG}"

# ============================================================================
# ANALIZAR COMMITS SEGÚN CONVENTIONAL COMMITS
# ============================================================================

HAS_BREAKING=false
HAS_FEAT=false
HAS_FIX=false
HAS_INCREMENTABLE=false

log_verbose "Analizando tipos de commits..."

while IFS= read -r commit; do
    log_verbose "  - $commit"
    
    # Detectar BREAKING CHANGE en el mensaje
    if echo "$commit" | grep -qE "BREAKING CHANGE:|!:"; then
        HAS_BREAKING=true
        HAS_INCREMENTABLE=true
        log_verbose "    → BREAKING CHANGE detectado"
        continue
    fi
    
    # Detectar tipo de commit
    commit_type=$(echo "$commit" | cut -d':' -f1 | tr -d '!' | tr -d ' ')
    
    case $commit_type in
        feat)
            HAS_FEAT=true
            HAS_INCREMENTABLE=true
            log_verbose "    → Feature (incrementa MINOR)"
            ;;
        fix)
            HAS_FIX=true
            HAS_INCREMENTABLE=true
            log_verbose "    → Fix (incrementa PATCH)"
            ;;
        docs|style|refactor|test|chore|perf)
            log_verbose "    → Commit no incremental (${commit_type})"
            ;;
        *)
            log_verbose "    → Tipo desconocido: ${commit_type}"
            ;;
    esac
done <<< "$COMMITS"

# ============================================================================
# DETERMINAR TIPO DE INCREMENTO
# ============================================================================

if [ "$HAS_INCREMENTABLE" = false ]; then
    log_warning "Solo hay commits no incrementales (docs, refactor, style, test, chore, perf)"
    log_info "No se crea nueva versión según las reglas de Conventional Commits"
    log_info "Esperando commits tipo fix:, feat:, o BREAKING CHANGE:"
    exit 0
fi

# Parsear versión actual
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

log_verbose "Versión parseada: MAJOR=$MAJOR, MINOR=$MINOR, PATCH=$PATCH"

# Calcular nueva versión
if [ "$HAS_BREAKING" = true ]; then
    MAJOR=$((MAJOR + 1))
    MINOR=0
    PATCH=0
    INCREMENT_TYPE="MAJOR (BREAKING CHANGE)"
    log_info "Incremento MAJOR detectado (breaking change)"
elif [ "$HAS_FEAT" = true ]; then
    MINOR=$((MINOR + 1))
    PATCH=0
    INCREMENT_TYPE="MINOR (nueva feature)"
    log_info "Incremento MINOR detectado (nueva feature)"
elif [ "$HAS_FIX" = true ]; then
    PATCH=$((PATCH + 1))
    INCREMENT_TYPE="PATCH (fix)"
    log_info "Incremento PATCH detectado (fix)"
fi

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"

# ============================================================================
# OBTENER COMMIT HASH
# ============================================================================

COMMIT_HASH=$(git rev-parse --short=7 HEAD)
NEW_VERSION_WITH_HASH="${NEW_VERSION}+${COMMIT_HASH}"
NEW_TAG="v${NEW_VERSION_WITH_HASH}"

log_success "Nueva versión calculada: ${NEW_VERSION_WITH_HASH}"

# ============================================================================
# RESUMEN DE CAMBIOS
# ============================================================================

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║              RESUMEN DE VERSIONADO                               ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
echo "  Versión actual:  ${CURRENT_VERSION}"
echo "  Nueva versión:   ${NEW_VERSION_WITH_HASH}"
echo "  Tag:             ${NEW_TAG}"
echo "  Incremento:      ${INCREMENT_TYPE}"
echo "  Commits:         ${NUM_COMMITS} desde ${LAST_TAG}"
echo "  Hash:            ${COMMIT_HASH}"
echo ""

if [ "$DRY_RUN" = true ]; then
    log_warning "Modo DRY-RUN activado - No se realizarán cambios"
    exit 0
fi

# ============================================================================
# APLICAR CAMBIOS
# ============================================================================

log_info "Aplicando cambios..."

# 1. Actualizar .env.example
log_verbose "Actualizando .env.example..."
if [ -f ".env.example" ]; then
    sed -i "s/^APP_VERSION=.*/APP_VERSION=${NEW_VERSION_WITH_HASH}/" .env.example
    log_success "Actualizado .env.example"
else
    log_warning "No se encontró .env.example"
fi

# 2. Actualizar .env si existe (opcional, para desarrollo local)
log_verbose "Actualizando .env si existe..."
if [ -f ".env" ]; then
    sed -i "s/^APP_VERSION=.*/APP_VERSION=${NEW_VERSION_WITH_HASH}/" .env
    log_verbose "Actualizado .env"
fi

# 3. Actualizar o crear CHANGELOG.md
log_verbose "Actualizando CHANGELOG.md..."
CHANGELOG_FILE="CHANGELOG.md"
CHANGELOG_DATE=$(date +%Y-%m-%d)

# Crear CHANGELOG.md si no existe
if [ ! -f "$CHANGELOG_FILE" ]; then
    cat > "$CHANGELOG_FILE" << EOF
# Changelog

Todas las versiones notables de este proyecto serán documentadas en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

EOF
fi

# Preparar entrada del changelog
CHANGELOG_ENTRY="## [${NEW_VERSION}] - ${CHANGELOG_DATE} (${COMMIT_HASH})\n\n"

# Agrupar commits por tipo
FEAT_COMMITS=$(echo "$COMMITS" | grep "^feat:" || true)
FIX_COMMITS=$(echo "$COMMITS" | grep "^fix:" || true)
BREAKING_COMMITS=$(echo "$COMMITS" | grep -E "BREAKING CHANGE:|!:" || true)

if [ -n "$BREAKING_COMMITS" ]; then
    CHANGELOG_ENTRY+="### ⚠️ BREAKING CHANGES\n\n"
    while IFS= read -r commit; do
        commit_msg=$(echo "$commit" | sed 's/^[^:]*: //')
        CHANGELOG_ENTRY+="- ${commit_msg}\n"
    done <<< "$BREAKING_COMMITS"
    CHANGELOG_ENTRY+="\n"
fi

if [ -n "$FEAT_COMMITS" ]; then
    CHANGELOG_ENTRY+="### ✨ Features\n\n"
    while IFS= read -r commit; do
        commit_msg=$(echo "$commit" | sed 's/^feat: //')
        CHANGELOG_ENTRY+="- ${commit_msg}\n"
    done <<< "$FEAT_COMMITS"
    CHANGELOG_ENTRY+="\n"
fi

if [ -n "$FIX_COMMITS" ]; then
    CHANGELOG_ENTRY+="### 🐛 Bug Fixes\n\n"
    while IFS= read -r commit; do
        commit_msg=$(echo "$commit" | sed 's/^fix: //')
        CHANGELOG_ENTRY+="- ${commit_msg}\n"
    done <<< "$FIX_COMMITS"
    CHANGELOG_ENTRY+="\n"
fi

# Insertar nueva entrada después de la cabecera
sed -i "/^# Changelog/a\\
\\
$CHANGELOG_ENTRY" "$CHANGELOG_FILE"

log_success "Actualizado CHANGELOG.md"

# 4. Crear tag git
log_verbose "Creando tag git: ${NEW_TAG}..."
git tag -a "$NEW_TAG" -m "Release ${NEW_VERSION}" -m "Tipo de cambio: ${INCREMENT_TYPE}" -m "Commits incluidos: ${NUM_COMMITS}"
log_success "Tag creado: ${NEW_TAG}"

# ============================================================================
# FINALIZACIÓN
# ============================================================================

echo ""
log_success "¡Versionado completado exitosamente!"
echo ""
echo "Próximos pasos:"
echo "  1. Revisar los cambios en .env.example y CHANGELOG.md"
echo "  2. Hacer commit de los cambios:"
echo "     git add .env.example CHANGELOG.md"
echo "     git commit -m \"chore: bump version to ${NEW_VERSION}\""
echo "  3. Hacer push del tag:"
echo "     git push origin ${NEW_TAG}"
echo "  4. Hacer push de los cambios:"
echo "     git push origin main"
echo ""
