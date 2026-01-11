#!/bin/bash
# translate-tests.sh - Script to translate Spanish test descriptions to English

set -e

echo "🌍 Translating test descriptions from Spanish to English..."

# Define translation pairs (Spanish -> English)
declare -A translations=(
    # Common test patterns
    ["test('usuario autorizado puede"]="test('authorized user can"
    ["test('usuario sin permisos no puede"]="test('user without permissions cannot"
    ["test('usuario sin permiso"]="test('user without permission"
    ["test('usuario no autenticado es redirigido al login"]="test('unauthenticated user is redirected to login"
    ["test('admin puede"]="test('admin can"
    ["test('un administrador puede"]="test('an administrator can"
    ["test('un usuario sin permisos no puede"]="test('a user without permissions cannot"
    
    # Specific translations
    ["ver la lista de"]="view the list of"
    ["ver un"]="view a"
    ["crear una nueva"]="create a new"
    ["crear un nuevo"]="create a new"
    ["actualizar un"]="update a"
    ["actualizar una"]="update a"
    ["eliminar un"]="delete a"
    ["eliminar una"]="delete a"
    ["desactivar un"]="deactivate a"
    ["desactivar una"]="deactivate a"
    ["activar un"]="activate a"
    ["activar una"]="activate an"
    ["gestionar"]="manage"
    ["pueden filtrarse por"]="can be filtered by"
    ["búsqueda"]="search"
    ["evento"]="event"
    ["módulo"]="module"
    ["específico"]="specific"
    ["específica"]="specific"
    ["sin asociaciones"]="without associations"
    ["asignado a"]="assigned to"
    ["asignados"]="assigned"
    ["permisos"]="permissions"
    ["roles"]="roles"
    ["usuarios"]="users"
    ["unidades administrativas"]="organizational units"
    ["unidad administrativa"]="organizational unit"
    ["organizaciones"]="organizations"
    ["organización"]="organization"
    ["activity logs"]="activity logs"
    ["archivos de log"]="log files"
    ["modo mantenimiento"]="maintenance mode"
    ["estado del"]="status of"
    ["usuario interno"]="internal user"
    ["usuario externo"]="external user"
    ["correo corporativo"]="corporate email"
    ["correo personal"]="personal email"
    ["datos personales"]="personal data"
    ["obligatorios"]="required"
    ["opcionales"]="optional"
    ["deshabilitado"]="disabled"
    ["eliminado"]="deleted"
    ["restaurar"]="restore"
    ["restablecer contraseña"]="reset password"
    ["reenviar correo de activación"]="resend activation email"
    ["activar manualmente"]="manually activate"
    ["inactivo"]="inactive"
    ["activo"]="active"
    ["a sí mismo"]="themselves"
    ["no puede eliminarse"]="cannot delete"
    ["no puede desactivarse"]="cannot deactivate"
    ["sin datos personales"]="without personal data"
    ["con datos personales"]="with personal data"
    ["a su juicio"]="at their discretion"
    ["hija"]="child"
    ["sin usuarios"]="without users"
    ["con usuarios asociados"]="with associated users"
    ["manualmente"]="manually"
    ["el único ente activo"]="the only active entity"
    ["un ente con"]="an entity with"
    ["un ente sin"]="an entity without"
    ["si hay otro activo"]="if there is another active one"
    ["se puede eliminar"]="can be deleted"
    ["no se puede eliminar"]="cannot be deleted"
    ["se sube correctamente"]="is uploaded correctly"
    ["al crear"]="when creating"
    ["las anteriores se desactivan automáticamente"]="previous ones are automatically deactivated"
    ["el logo"]="the logo"
    
    # Comments
    ["Tests de integración para"]="Integration tests for"
    ["Estos tests verifican:"]="These tests verify:"
    ["Visualización de"]="Viewing"
    ["Filtrado por"]="Filtering by"
    ["Control de acceso"]="Access control"
    ["Exportación de"]="Exporting"
    ["Eliminación de"]="Deleting"
    ["Activación/desactivación"]="Activation/deactivation"
    ["Gestión de"]="Management of"
    ["Creación de"]="Creation of"
    ["Actualización de"]="Updating"
    ["Helper para crear"]="Helper to create"
    ["Desactivar notificaciones y logging de actividad"]="Disable notifications and activity logging"
    ["Desactivar observers para evitar errores en tests"]="Disable observers to avoid errors in tests"
    ["Crear permisos base"]="Create base permissions"
    ["Resetear caché de permisos de Spatie DESPUÉS de crearlos"]="Reset Spatie permission cache AFTER creating them"
    ["Crear rol de administrador"]="Create admin role"
    ["Crear usuario administrador"]="Create admin user"
    ["Asegurar que el modo mantenimiento esté desactivado"]="Ensure maintenance mode is disabled"
    ["después de cada test"]="after each test"
    ["usuario administrador con permisos"]="admin user with permissions"
    ["de prueba"]="test"
    ["trazas de actividad"]="activity traces"
    ["archivos de log del sistema"]="system log files"
)

# Find all test files
test_files=$(find tests/Feature -name "*Test.php" -type f)

for file in $test_files; do
    echo "  Processing: $file"
    
    # Create a temporary file
    temp_file="${file}.tmp"
    cp "$file" "$temp_file"
    
    # Apply all translations
    for spanish in "${!translations[@]}"; do
        english="${translations[$spanish]}"
        sed -i "s/${spanish}/${english}/g" "$temp_file"
    done
    
    # Replace the original file
    mv "$temp_file" "$file"
done

echo "✅ Translation complete!"
echo ""
echo "Files translated:"
echo "$test_files" | wc -l
