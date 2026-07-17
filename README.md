<div align="center">
  <h1>🌌 SISTEMA ODONTOLÓGICO PREMIUM 🌌</h1>
  <p><b>Plataforma de Gestión Clínica Next-Gen | SaaS Multi-Tenant | Historias Clínicas Electrónicas</b></p>
  
  [![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
  [![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
  [![Vanilla JS](https://img.shields.io/badge/JavaScript-Vanilla-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org)
  [![CSS3](https://img.shields.io/badge/CSS3-Variables_&_Grid-1572B6?style=for-the-badge&logo=css3&logoColor=white)](https://developer.mozilla.org)
</div>

---

> Un sistema avanzado y vanguardista diseñado para transformar la manera en que las clínicas dentales administran sus operaciones. Construido con arquitectura **Multi-Tenant** y diseño paramétrico **White-Label**, este sistema se adapta visual y funcionalmente a cualquier identidad corporativa clínica con un panel de control intuitivo, profesional y futurista.

## 🚀 FUNCIONALIDADES PRINCIPALES

El ecosistema está diseñado en módulos de alta eficiencia, cubriendo integralmente todas las áreas críticas de una clínica moderna.

### 🧬 1. Gestión Integral de Pacientes (Historias Clínicas)
- **Registro Centralizado 360°:** Datos personales, antecedentes médicos y canales de contacto rápido.
- **Odontogramas Digitales Interactivos:** Interfaz visual (SVG) basada en el estándar FDI, soporte para dentición Adulto/Infantil, interacción dual (pintado rápido y diagnóstico detallado) y guardado en tiempo real sin recargas (Fetch API).
- **Galería Fotográfica Clínica:** Carga y gestión inteligente de fotografías intraorales, extraorales y radiografías.
- **Historial de Evolución Terapéutica:** Seguimiento cronológico y detallado de cada tratamiento y diagnóstico aplicado.
- **Smart Archive (Bóveda Segura):** Sistema de archivado (Soft-Delete) para mantener la interfaz principal limpia, resguardando el historial clínico permanentemente.

### 📅 2. Módulo Avanzado de Citas y Agenda
- **Programación Dinámica:** Creación, edición y asignación ágil de citas.
- **Tracker de Estados:** Seguimiento visual en tiempo real del estatus del paciente (Pendiente, Confirmada, En Espera, Atendida, Cancelada).
- **Dashboard de Agenda Diaria:** Visualización panorámica del flujo de pacientes del día para optimizar la carga de trabajo y reducir tiempos muertos.

### 📞 3. Call Center y CRM Integrado
- **Estación de Teleoperador:** Entorno de alta velocidad optimizado exclusivamente para agentes telefónicos.
- **Pipeline de Seguimiento:** Control de leads, registro de acuerdos, llamadas realizadas y recordatorios.
- **Métricas y Telemetría:** KPIs en tiempo real de la efectividad de las llamadas, conversiones a citas y métricas de retención.
- **Bitácora de Interacciones:** Registro inmutable de cada comunicación clínica-paciente.

### 🎨 4. Motor Multi-Tenant & White-Label (Personalización Total)
- **Branding Dinámico On-The-Fly:** Ajuste de logotipos y paletas de color (Primario, Secundario, Acento) directamente desde el panel de administración.
- **SaaS Architecture Ready:** Diseñado para soportar múltiples instancias de clínicas, cada una con su propia identidad visual inyectada sin modificar una sola línea de código fuente.
- **CSS Vars Injection Engine:** Aplicación de estilos instantánea y reactiva a través del DOM.

### 🔐 5. Seguridad de Grado Corporativo y Administración
- **RBAC (Control de Acceso Basado en Roles):** Perfiles estrictos de seguridad para SuperAdmin, Odontólogo, Recepción y Operador de Call Center.
- **Centro de Comando (Dashboard Analítico):** Resumen de indicadores clave de rendimiento, altas de pacientes, citas y flujo de actividad reciente.
- **Gestor de Backups Integrado:** Creación y exportación de respaldos de la base de datos SQL con un solo clic.
- **Autenticación Reforzada:** Protección de rutas mediante manejo de sesiones robustas y hashing seguro.

---

## 🛠️ STACK TECNOLÓGICO

| Capa | Tecnología | Función en la Matriz |
| --- | --- | --- |
| **Interfaz (Frontend)** | HTML5, CSS3, JS | UI/UX responsiva, custom properties para temas dinámicos, micro-animaciones e iconos FontAwesome. |
| **Núcleo (Backend)** | PHP 8.x | Procesamiento robusto, algoritmos de renderizado y lógica de autenticación segura. |
| **Persistencia (Datos)** | MySQL / MariaDB | Estructura relacional optimizada mediante acceso por PDO (Previene SQL Injection). |
| **Arquitectura** | Estructurado/Modular| Separación de responsabilidades entre Core, Admin y Call Center. |

---

## 📂 ESTRUCTURA DEL ECOSISTEMA

```text
sistema_clinica_dental/
├── 🛡️ admin/            # Centro de Comando SuperAdmin (Branding, Base de Datos)
├── 📞 callcenter/       # Módulo hiper-optimizado para teleoperadores
├── ⚙️ includes/         # Motor central (Autenticación, Inyección Tenant, Layouts)
├── 🎨 css/              # Sistema de diseño global y variables de tema
├── 💾 database/         # Esquemas SQL y archivos de despliegue inicial
├── 🖼️ uploads/          # Bóveda de archivos (Radiografías, Fotos Clínicas, Logos)
├── 📦 assets/           # Recursos visuales estáticos 
└── 📄 *.php             # Controladores de vista (Dashboards, CRUD de pacientes)
```

---

## ⚙️ DESPLIEGUE RÁPIDO (🚀 INITIALIZATION PROTOCOL)

1. **Clonar la Matriz Base:**
   ```bash
   git clone https://github.com/TU-USUARIO/sistema_clinica_dental.git
   ```

2. **Despliegue de Datos:**
   - Inicia tu servidor local (XAMPP/WAMP/LAMP).
   - Crea un esquema en MySQL (ej. `clinica_premium`).
   - Importa los archivos `.sql` ubicados en el directorio `database/`.

3. **Variables de Entorno (Enlace de Configuración):**
   - Configura las credenciales maestras de la base de datos editando `includes/config.php` o configurando tu `.env`:
     ```env
     DB_HOST=localhost
     DB_NAME=clinica_premium
     DB_USER=root
     DB_PASS=tu_password
     ```

4. **Protocolo de Permisos:**
   - Asegura la operatividad del sistema otorgando permisos de escritura a las carpetas `uploads/` y `assets/` (para subida de imágenes y logos).

5. **Activación de Sistema:**
   - Accede mediante el navegador a: `http://localhost/sistema_clinica_dental/` y disfruta de la experiencia.

---

## 🔮 DESIGN SYSTEM & EXPERIENCIA (UI/UX)

La interfaz gráfica del sistema ha sido meticulosamente concebida bajo principios estéticos modernos:
- **Glassmorphism y Efectos Neumórficos:** Paneles con sutiles transparencias y desenfoques (blurs) de fondo que ofrecen una sensación de profundidad de nivel premium.
- **Cyber-Estética y Minimalismo Funcional:** Jerarquía visual impecable, paletas de colores configurables que aseguran máxima legibilidad, contraste y elegancia (soporte para temas claros/oscuros mediante personalización).
- **Animaciones Cinemáticas:** Transiciones fluidas de 300ms, *hover effects* precisos y retroalimentación interactiva al instante, para una experiencia de usuario rápida y viva.

---
<div align="center">
  <p>Construyendo el futuro tecnológico de la odontología clínica. 🚀</p>
  <p><b>© Desarrollado por Tec. Reiner Jimenez</b></p>
</div>
