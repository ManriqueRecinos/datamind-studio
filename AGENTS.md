# Reglas del Agente - DataMind Studio (Laravel 13)

> **Versión Actual del Proyecto:** `1.0.7`  
> **Nombre del Sistema:** DataMind Studio  
> **Framework:** Laravel 13 con PHP 8.3+ / 8.5+

---

## 📌 1. Información General de la Plataforma

Esta plataforma es la interfaz web estándar para el ecosistema **DataMind** (asistente de inteligencia artificial local y análisis multi-base de datos). 

### Principios Fundamentales:
1. **Autonomía Total Offline (Zero CDNs):**
   - Todos los scripts JS residen en `public/js/global_js/`.
   - Todos los estilos residen en `public/css/global_styles/`.
   - Las fuentes e iconos de FontAwesome residen en `public/webfonts/`.
   - Las imágenes de plugins (como `DataTables` sort images) residen en `public/css/images/` y `public/images/`.
   - **Bajo ninguna circunstancia** se deben insertar enlaces a CDNs externos (`cdn.jsdelivr.net`, `cdnjs.cloudflare.com`, Google Fonts, etc.) para mantener la operatividad en intranets.

2. **Librerías Globales Disponibles:**
   - **SweetAlert2:** `window.Swal`
   - **Toastr:** `window.toastr`
   - **jQuery:** `window.jQuery` / `window.$` (v3.6.0)
   - **DataTables:** `$.fn.DataTable` (v1.13 con soporte de responsive y scroll)
   - **Axios:** `window.axios`
   - **Select2:** `$.fn.select2`
   - **Chart.js:** `window.Chart`
   - **Pusher:** `window.Pusher`
   - **Font Awesome:** Catálogo vectorial SVG y Webfonts listo.

3. **Componentes Blade Estilizados:**
   - `<x-data-table>`: Componente listo en `resources/views/components/data-table.blade.php`.
   - Incluye opciones `5, 10, 20, 50, 100, Todos`, botón Exportar en la cabecera superior y botones de acción rápida inferiores (`Nuevo`, `Editar`, `Eliminar`).

---

## 🔢 2. Regla Obligatoria de Incremento de Versiones en Commits

Cualquier cambio o commit que se realice en este repositorio debe actualizar el número de versión respetando estrictamente el siguiente algoritmo:

```text
Versión Inicial: 1.0.0

Commit 1: 1.0.1
Commit 2: 1.0.2
Commit 3: 1.0.3
...
Commit 9: 1.0.9
Commit 10: 1.1.0  <-- (Al llegar a .9 pasa a la siguiente décima)
Commit 11: 1.1.1
...
Commit 19: 1.1.9
Commit 20: 1.2.0  <-- (Pasa a 1.2.0)
...
Commit 99: 1.9.9
Commit 100: 2.0.0
```

### Protocolo para el Agente:
1. Leer la versión actual en `README.md` o `AGENTS.md`.
2. Si el dígito PATCH es un número de `0` a `8`, sumarle 1 (ej. `1.0.4` ➔ `1.0.5`).
3. Si el dígito PATCH es `9`, resetear el parche a `0` e incrementar el dígito MINOR en 1 (ej. `1.0.9` ➔ `1.1.0`).
4. Si el dígito MINOR es `9` y el PATCH es `9`, incrementar el MAJOR y resetear los demás a 0 (ej. `1.9.9` ➔ `2.0.0`).
5. Actualizar la versión reflejada en `README.md` y `AGENTS.md` antes de generar el commit o documentar el cambio.
