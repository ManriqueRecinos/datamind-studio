# 🖥️ DataMind Studio — Open-Source Multi-Database AI Workspace & Chat Portal

[![Laravel](https://img.shields.io/badge/Laravel-13.x-0476D9?logo=laravel&logoColor=white)](https://laravel.com/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-v4.0-0468BF?logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![Offline Ready](https://img.shields.io/badge/Architecture-100%25%20Offline%20%2F%20Zero%20CDNs-4B89BF)](#)
[![Version](https://img.shields.io/badge/Version-1.0.3-emerald.svg)](#)

> **Versión Actual:** `1.0.3`  
> **Estado:** Plataforma Web Open-Source para Análisis de Bases de Datos con IA Local  
> **Arquitectura Frontend:** 100% Local / Offline-Ready (Cero CDNs externos)

**DataMind Studio** es la interfaz web interactiva para [**DataMind Core (`datamind-core`)**](https://github.com/ManriqueRecinos/datamind-core). Construida sobre **Laravel 13** y **Tailwind CSS v4**, permite conectar gráficamente cualquier base de datos **PostgreSQL** o **MariaDB / MySQL**, inspeccionar todas sus tablas detectadas en tiempo real, seleccionar con *checkboxes* cuáles tablas vectorizar en `pgvector` y conversar con tus bases de datos mediante **Streaming SSE**, tablas interactivas **DataTables** y descarga de reportes **Excel (`.xlsx`)**.

---

## ✨ Características Principales

- 🔌 **Gestor Visual de Conexiones (`/connections`)**:
  - Conecta servidores **PostgreSQL** o **MariaDB / MySQL** ingresando Host, Puerto, Base de Datos, Usuario y Contraseña.
  - Botón **"Probar Conexión"** que verifica credenciales en vivo, mide la latencia en milisegundos y reporta cuántas tablas fueron detectadas.
- 🗂️ **Explorador de Esquemas y Vectorización Selectiva**:
  - Lista todas las tablas de la base de datos conectada mostrando cantidad de columnas, vista previa de campos y estado en la IA (`Vectorizada` vs `Pendiente`).
  - Filtros rápidos (*Seleccionar Visibles*, *Solo No Vectorizadas*, buscador instantáneo por nombre de tabla o columna) para vectorizar en `pgvector` únicamente las tablas relevantes.
- 💬 **Asistente Conversacional con Streaming SSE (`/chat`)**:
  - Selector de base de datos activa directamente en la barra superior del chat para alternar entre múltiples bases de datos al instante.
  - Consumo en tiempo real de eventos Server-Sent Events (`session`, `token`, `dataset`, `done`, `error`).
- 📊 **Previsualización Interactiva y Exportación Excel**:
  - Cuando una consulta SQL devuelve registros, el mensaje del asistente despliega automáticamente una tabla interactiva con paginación y filtro local, auditoría de la consulta SQL ejecutada y el botón **`Generar Excel (.xlsx)`**.
- 🎨 **Sistema de Diseño Global y 100% Offline (Zero CDNs)**:
  - Todas las librerías (`jQuery 3.6`, `DataTables 1.13`, `SweetAlert2`, `Toastr`, `Axios`, `Select2`, `Chart.js`, `FontAwesome 6`) están empaquetadas localmente en `public/` para operar incluso en redes empresariales cerradas o intranets sin salida a internet.
  - Paleta de colores centralizada en `:root` (`public/css/global_styles/globales.css`) e integrada con `@theme` de Tailwind CSS v4 (`#0476D9`, `#0468BF`, `#4B89BF`, `#99D0F2`, `#F2F2F2`).

---

## 🏛️ Estructura del Proyecto

```text
datamind-studio/
├── app/
│   ├── Http/Controllers/
│   │   ├── ChatController.php               # Vista del asistente, proxy SSE (/chat/stream) y descarga Excel
│   │   └── DatabaseConnectionController.php # CRUD de conexiones, test en vivo, listado y vectorización
│   └── Services/
│       └── FastApiClient.php                # Cliente HTTP/Guzzle con soporte de streaming hacia DataMind Core
├── public/
│   ├── css/global_styles/
│   │   └── globales.css                     # Tokens CSS (:root) de la paleta de colores y utilidades globales
│   └── js/
│       ├── chat_stream.js                   # Cliente SSE en vivo, renderizado de tablas y cambio de BD activa
│       ├── connections_manager.js           # Lógica de prueba de conexión, explorador de tablas y vectorización
│       └── global_js/                       # Librerías JS locales (Zero CDNs)
├── resources/
│   ├── css/app.css                          # Configuración Tailwind CSS v4 (@theme)
│   └── views/
│       ├── chat/index.blade.php             # Interfaz principal del Asistente AI
│       ├── connections/index.blade.php      # Gestor de Bases de Datos y Explorador de Tablas
│       ├── components/data-table.blade.php  # Componente Blade reutilizable <x-data-table>
│       └── welcome.blade.php                # Dashboard de inicio y catálogo de componentes
├── routes/
│   └── web.php                              # Definición de rutas (/chat, /connections, /)
└── tests/
    └── Feature/ChatTest.php                 # Suite de pruebas automatizadas de integración
```

---

## 🛠️ Requisitos Previos

1. **PHP >= 8.2 / 8.3 / 8.5** con extensiones estándar (`curl`, `mbstring`, `openssl`, `pdo_sqlite` o `pdo_mysql`).
2. **Composer 2.x**
3. **Node.js >= 20.x y NPM** (para compilar Tailwind CSS v4 con Vite).
4. **Motor Backend [`datamind-core`](https://github.com/ManriqueRecinos/datamind-core)** ejecutándose (por defecto en `http://127.0.0.1:8000`).

---

## 🚀 Guía de Instalación Paso a Paso

### 1. Clonar el repositorio e instalar dependencias

```powershell
git clone https://github.com/ManriqueRecinos/datamind-studio.git
cd datamind-studio

# Instalar dependencias de PHP
composer install

# Instalar dependencias de Node.js
npm install
```

### 2. Configurar el archivo de entorno (`.env`)

```powershell
copy .env.example .env
php artisan key:generate
```

Abre `.env` y verifica la URL y el Token de conexión hacia tu instancia de **DataMind Core**:

```ini
APP_NAME="DataMind Studio"
APP_URL=http://127.0.0.1:8085

# Conexión con el motor Python FastAPI (datamind-core)
FASTAPI_URL=http://127.0.0.1:8000/api/v1
FASTAPI_KEY=datamind_api_secret_token_2026
```

### 3. Ejecutar migraciones base y compilar estilos

```powershell
# Crear base de datos SQLite local para sesiones/caché de Laravel (si aplica)
php artisan migrate --force

# Compilar el bundle de Tailwind CSS v4 con Vite
npm run build
```

### 4. Iniciar el servidor web

```powershell
php artisan serve --host=127.0.0.1 --port=8085
```

Abre tu navegador en:
- 🔌 **Gestor de Bases de Datos y Vectorización**: [http://127.0.0.1:8085/connections](http://127.0.0.1:8085/connections)
- 💬 **Asistente Conversacional DataMind AI**: [http://127.0.0.1:8085/chat](http://127.0.0.1:8085/chat)
- 🏠 **Dashboard Principal**: [http://127.0.0.1:8085/](http://127.0.0.1:8085/)

---

## 📖 Cómo Conectar y Entrenar una Nueva Base de Datos

1. Ingresa a **`http://127.0.0.1:8085/connections`**.
2. En el panel izquierdo (**Conectar Base de Datos**):
   - Escribe un **Alias** (ej. `"ERP Ventas"` o `"Analytics PostgreSQL"`).
   - Selecciona el **Motor SQL** (`MariaDB / MySQL` o `PostgreSQL`).
   - Ingresa `Host`, `Puerto`, `Nombre de Base de Datos`, `Usuario` y `Contraseña`.
3. Haz clic en **"⚡ Probar Conexión"** para confirmar que el servidor responde y ver cuántas tablas tiene.
4. Haz clic en **"💾 Guardar y Analizar"**.
5. En el panel derecho (**Explorador de Tablas Detectadas**), marca con un *check* las tablas que quieras que el asistente aprenda y haz clic en **"🧠 Vectorizar Seleccionadas"**.
6. Haz clic en **"Ir al Chat AI"** (`http://127.0.0.1:8085/chat`), asegúrate de que tu base de datos esté seleccionada en la barra superior y realiza cualquier consulta en lenguaje natural.

---

## 🎨 Personalización de la Paleta de Colores Global

La paleta de colores vive centralizada en `public/css/global_styles/globales.css` y `resources/css/app.css`:

```css
:root {
    --datamind-primary: #0476D9;       /* Azul Primario Vibrante */
    --datamind-primary-dark: #0468BF;  /* Azul Profundo / Hover */
    --datamind-secondary: #4B89BF;     /* Azul Acero Secundario */
    --datamind-accent-light: #99D0F2;  /* Azul Cielo Suave / Bordes / Focus */
    --datamind-bg-neutral: #F2F2F2;    /* Gris Neutro Claro / Fondo General */
}
```
Si modificas estos valores y ejecutas `npm run build`, toda la aplicación actualizará su tema visual automáticamente.

---

## 🧪 Ejecución de Pruebas Automatizadas

```powershell
php artisan test
```

---

## 🔢 Regla de Versionado Semántico

El proyecto utiliza una progresión decimal secuencial de 3 dígitos (`MAJOR.MINOR.PATCH`):
- `1.0.0` ➔ `1.0.1` ➔ ... ➔ `1.0.9` ➔ `1.1.0`.

---

## 📄 Licencia

Distribuido bajo la licencia **MIT**.
