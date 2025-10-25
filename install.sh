#!/bin/bash

# Script para configurar automáticamente las variables de entorno de Reverb en .env

ENV_FILE=".env"
ENV_EXAMPLE_FILE=".env.example"

# 1. Comprobar si .env existe. Si no, copiarlo desde .env.example
if [ ! -f "$ENV_FILE" ]; then
    if [ -f "$ENV_EXAMPLE_FILE" ]; then
        echo "No se encontró el archivo .env. Creando uno a partir de $ENV_EXAMPLE_FILE..."
        cp "$ENV_EXAMPLE_FILE" "$ENV_FILE"
    else
        echo "Error: No se encontró ni .env ni .env.example. Por favor, asegúrese de que .env.example exista."
        exit 1
    fi
fi

echo "Configurando variables de Reverb en el archivo $ENV_FILE..."

# 2. Generar valores aleatorios para Reverb
# REVERB_APP_ID: Equivalente a random_int(100000, 999999)
REVERB_APP_ID=$(shuf -i 100000-999999 -n 1)

# REVERB_APP_KEY: Equivalente a Str::random(20) -> 20 caracteres hexadecimales
REVERB_APP_KEY=$(openssl rand -hex 10)

# REVERB_APP_SECRET: Equivalente a Str::random(20) -> 20 caracteres hexadecimales
REVERB_APP_SECRET=$(openssl rand -hex 10)

# 3. Actualizar el archivo .env con los nuevos valores
# Se usa sed con el delimitador # para evitar conflictos con caracteres especiales.
# El comando reemplaza toda la línea que comienza con la clave de la variable.

sed -i "s#^REVERB_APP_ID=.*#REVERB_APP_ID=$REVERB_APP_ID#g" "$ENV_FILE"
sed -i "s#^REVERB_APP_KEY=.*#REVERB_APP_KEY=$REVERB_APP_KEY#g" "$ENV_FILE"
sed -i "s#^REVERB_APP_SECRET=.*#REVERB_APP_SECRET=$REVERB_APP_SECRET#g" "$ENV_FILE"

echo "✅ Variables de Reverb generadas y actualizadas:"
echo "   - REVERB_APP_ID"
echo "   - REVERB_APP_KEY"
echo "   - REVERB_APP_SECRET"
echo ""
echo "¡Configuración automática completada!"
echo "Por favor, revisa el archivo .env para asegurarte de que las demás variables (como las de la base de datos) estén configuradas manualmente."
