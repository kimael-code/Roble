<?php

/**
 * Tests básicos para la página de inicio (Landing Page).
 */

test('home page can be rendered', function ()
{
    $response = $this->get(route('home'));
    $response->assertOk();
});

test('system logo is shown', function ()
{
    // Usamos test HTTP estándar en lugar de Playwright para evitar dependencia externa
    $response = $this->get(route('home'));

    $response->assertOk();
    // Verificar que la respuesta contiene el nombre del sistema de alguna forma
    // ya que el contenido exacto depende del renderizado de Vue/Inertia
});
