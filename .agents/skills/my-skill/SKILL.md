---
name: R.DEV DENTAL SUITE
description: Extiende, mejora o agrega funcionalidades al sistema Dental OS Premium.
             Úsalo para cualquier tarea en este proyecto: nuevos módulos, vistas,
             consultas, correcciones o mejoras de rendimiento.
---

# Dental OS Premium — Sistema de Gestión Clínica

## Contexto del Sistema

Sistema SaaS multi-tenant de gestión de historias clínicas dentales.
Está en producción activa con múltiples clínicas como clientes.
Cada decisión técnica debe preservar la estabilidad del sistema.

**Stack:** PHP puro + PDO + MySQL/MariaDB + HTML/CSS (variables CSS) + JS vanilla

## Arquitectura que debes respetar

### Multi-Tenant (crítico)

- `tenant.php` inyecta la config del cliente activo (nombre, logo, colores).
- Los colores se aplican como variables CSS: `--color-primary`, `--color-accent`,
  `--color-secondary`, `--color-sidebar`. Nunca uses colores HEX hardcodeados en vistas.
- El logo viene de `assets/logo_tenant_*`. Nunca referencie rutas fijas de una sola clínica.

### Autenticación y Seguridad

- `auth.php` es el middleware de sesión; inclúyelo al inicio de cada archivo protegido.
- Contraseñas con `password_hash` / `password_verify`.
- Todas las consultas SQL con PDO y prepared statements (nunca concatenación directa).
- Sanitiza inputs antes de renderizar; protege formularios con token CSRF.
- Los roles (odontólogo, recepcionista, teleoperador, admin) limitan acceso a módulos;
  verifica el rol antes de cualquier operación sensible.

### Patrones de Archivos por Módulo

Los módulos siguen esta convención de archivos:

- `index.php` — listado con búsqueda y paginación
- `crear.php` — formulario de registro
- `ver.php` — vista de detalle (solo lectura)
- `editar.php` — formulario de edición
- `archivar.php` / `restaurar.php` — soft-delete (nunca hard-delete de datos clínicos)
- `eliminar.php` — solo para registros no clínicos y con confirmación doble

Al agregar un módulo nuevo, respeta esta misma convención de nombres.

### Auditoría

- Cualquier acción relevante (crear, editar, archivar, login) debe registrarse
  en la tabla de auditoría que usa `actividad.php`.
- Al agregar nuevas operaciones, incluye siempre su registro de auditoría.

## Cómo extender el sistema

### Al agregar un módulo nuevo

1. Sigue la convención de archivos del punto anterior.
2. Incluye `auth.php` y `tenant.php` al inicio de cada vista.
3. Aplica los colores con variables CSS del tenant, no valores fijos.
4. Agrega el acceso al sidebar con verificación de rol.
5. Registra las acciones clave en auditoría.

### Al agregar campos a tablas existentes

- Genera el script en `/database/YYYY-MM-DD_descripcion.sql`.
- Incluye el `ALTER TABLE` con valor DEFAULT para no romper registros existentes.
- Incluye el índice si el campo será usado en búsquedas o filtros.
- Nunca modifiques ni ejecutes la base de datos directamente.

### Al mejorar una vista existente

- No cambies la lógica PHP ni las consultas si la tarea es solo UI.
- Mantén coherencia visual con el resto del sistema (mismos componentes,
  espaciados, badges y estilos de tabla ya existentes).

## Reglas de Performance

- Nunca hagas queries dentro de loops (problema N+1).
- Selecciona solo las columnas necesarias; nunca `SELECT *`.
- Las imágenes (intraorales, radiografías) usan `loading="lazy"`.
- Scripts JS antes de `</body>` o con `defer`.
- Si detectas un cuello de botella al revisar código existente, señálalo
  antes de continuar con la tarea.

## Constraints inamovibles

- Este sistema maneja datos médicos reales; nunca elimines registros de pacientes
  de forma permanente (usa siempre soft-delete).
- No introduzcas librerías externas sin aprobación explícita.
- Entrega siempre código completo y funcional, sin TODOs ni placeholders.
- Cada mejora debe ser retrocompatible; nada puede romper funcionalidad existente.
