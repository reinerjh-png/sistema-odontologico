# Sistema de Gestión de Historias Clínicas - Clínica Dental Premium

Un sistema completo de gestión para clínicas dentales desarrollado en PHP. Permite la administración de pacientes, citas y configuración dinámica de branding (White-Label) mediante un sistema Tenant.

## Características Principales

- **Panel de Administración (Dashboard):** Estadísticas clave de citas y pacientes.
- **Gestión de Pacientes (CRUD):** Creación, edición, visualización y archivado de pacientes, con soporte para odontogramas y fotografías clínicas.
- **Módulo de Citas:** Agenda de próximas citas.
- **Call Center:** Módulo dedicado para gestionar seguimientos, llamadas de pacientes y reportes.
- **Multi-Tenant & White-Label:** Configuración dinámica de colores corporativos (Primario, Secundario, Acento, Sidebar) y logotipos de clínicas desde un menú de administración propio sin tocar código.
- **Diseño Responsivo:** Layout dividido en Login y vistas principales adaptables a dispositivos móviles y tablets.
- **Seguridad:** Manejo seguro de contraseñas y sesiones estructuradas.

## Tecnologías Utilizadas

- **Frontend:** HTML5, CSS3 (Vanilla), JavaScript, FontAwesome (Íconos).
- **Backend:** PHP 8.x (Vanilla / Estructurado).
- **Base de Datos:** MySQL / MariaDB (conexión PDO).

## Estructura del Proyecto

```text
sistema_clinica_dental/
├── admin/            # Panel de control del sistema (Branding, Exportar BD, Roles)
├── assets/           # Imágenes y logotipos
├── callcenter/       # Módulo para operadores telefónicos (Registros y Reportes)
├── css/              # Hojas de estilo globales
├── database/         # Scripts y estructura de la BD (.sql)
├── includes/         # Lógica central: Auth, Funciones, Tenant, Layouts
├── uploads/          # Imágenes de odontogramas / pacientes subidos
└── *.php             # Archivos de vistas y procesamiento principal del sistema (CRUD)
```

## Requisitos Previos

- XAMPP / WAMP / LAMP stack o cualquier servidor web (Apache/Nginx).
- PHP >= 8.0
- MySQL >= 5.7 o MariaDB.
- Habilitar la extensión PHP PDO en `php.ini`.

## Instalación y Configuración

1. **Clonar el Repositorio:**
   ```bash
   git clone https://github.com/TU-USUARIO/sistema_clinica_dental.git
   ```
2. **Ubicación:** 
   Mueve el directorio del proyecto a la carpeta pública de tu servidor web (ej: `htdocs/` en XAMPP).
3. **Base de Datos:**
   - Crea una base de datos en MySQL/MariaDB (ej: `clinica_dental`).
   - Importa los archivos `.sql` localizados en la carpeta `database/` para generar las tablas correspondientes.
4. **Configuración del Entorno:**
   - Localiza o crea el archivo `includes/config.php` y configura tus credenciales de la base de datos (DB_HOST, DB_NAME, DB_USER, DB_PASS).
   - Opcionalmente, configura un archivo `.env` en la raíz (respaldado desde `.env.example` si existe).
5. **Permisos de Carpetas:**
   Asegúrate de conceder permisos de escritura a las carpetas `/uploads` y `/assets` para permitir la carga correcta de imágenes de pacientes y logotipos tenant.
6. **Ejecución:**
   Abre el navegador e ingresa a `http://localhost/sistema_clinica_dental/`.

## Notas del Desarrollador

Este proyecto está diseñado usando CSS Vars (Custom Properties) para ser altamente personalizable. El sistema lee de la base de datos `tenant_config` para ajustar la interfaz (Header, Login y Menú Lateral) según la estética de la clínica actual.

---
© Desarrollado por Tec. Reiner Jimenez
