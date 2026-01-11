# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [v1.2.0+a657156] - 2026-01-11

### Added
- configure auto-versioning to use GitHub App for branch protection bypass
- implement CI/CD pipeline with auto-versioning
- add realtime dashboard with charts and data exports
- add superuser installer and user activation system
- block auth routes when system is not ready
- update to Laravel 12.34
- implement installer
- Enhance AppLogo component with dynamic app name and vertical alignment option (#32)
- merge all feature and improvements made (#30)
- disable account deletion feature from profile settings
- align old values to right in the detail view of activity logs when the event type is updated
- display the activity logs in the detail views (#21)
- add spin animation on datatable buttons
- implement restoration and hard deletion on users
- full implement realtime, live-updating notifications
- add transition opacity on pages
- implement translations on roles and permissions
- aplica cambios de estilos css para mejorar la experiencia visual
- implementa gestión de trazas
- implementa dashboard y gestión de logs
- implementa gestión de unidades administrativas
- añade observador al modelo `User`
- implementa gestión de entes
- implementa gestión de usuarios
- implementa vista detalle en gestión de usuarios
- implementando gestión de usuarios
- finaliza implementación de la gestión de roles
- implementa notificaciones flash
- implementa administración de permisos
- implementa traducciones al español
- implementa Data Tables
- implementa trazas para eventos de autenticacion
- cambia a nombre de usuario como credencial de acceso
- implementa traducciones de pantalla a español

### Fixed
- configure PostgreSQL service for CI/CD tests
- use environment variables for test database configuration
- configure tests workflow to use SQLite for CI/CD
- correct RegistrationTest mocks and configure version workflow to depend on tests
- add name field to package.json to prevent package-lock.json name changes
- add bail rule to registration id_card validation
- properly save the log name on model events
- corrects typos in translations
- update response type in ExportLogFile and improve user deletion logic in BatchDeleteUser
-  properly set `--popover-foreground` css variable
- correct permission names in observers
- resolve the sequence on roles table after running sysadmin roles and permissions seeder
- resolve an error when observers dispatch notifications
- properly show the head title
- properly show timestamps on details view
- properly show organizational unit details
- update the permission names
- buttons new
- update users seeder
- corrige selección de todas las filas en data tables
- corrige fecha en notificación de permiso editado
- corrige la aparición de notificación flash al ir atrás
- corrige el idioma español a `es_VE`
- actualiza archivo `.env.example`
- corrige comportamiento de página index al eliminar un permiso
- corrige paleta de colores a violeta

### Changed
- update core components, layouts, and styles
- update Vue pages and routes with new structure
- update models, repositories, and database structure
- update controllers with new InertiaProps and improved structure
- update action classes with improved logging and validation
- add MultiSelectCombobox and remove module-specific comboboxes
- replace Props classes with InertiaProps and add support utilities
- migrate to Laravel Fortify for authentication
- implement dynamic organic pulse animation
- update event names on auth listeners
- change the way of checking the superuser role
- renombra clase de eliminación por lote y corrige estilos
- update configuration, rules, and documentation

### Documentation
- add CHANGELOG.md for v1.1.0 release
- fix typos
- update `README.md` (#35)
- update `README.md`
- update `README.md` and `.env.example`
- update README file

## [v1.1.0+0605d3e] - 2026-01-11

[v1.2.0+a657156]: https://github.com/kimael-code/Roble/releases/tag/v1.2.0+a657156
[v1.1.0+0605d3e]: https://github.com/kimael-code/Roble/releases/tag/v1.1.0+0605d3e
