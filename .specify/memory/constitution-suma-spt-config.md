# Constitución del Sistema Empresarial: SUMA SPT — Módulo de Configuración

**Proyecto Origen**: `D:\Laravel\SUMA\suma_spt`  
**Destino de Documentación**: `D:\Laravel\Supertex AI\.specify\memory\constitution-suma-spt-config.md`  
**Versión**: 1.0.0 | **Fecha**: 2026-11-24

---

## 1. Arquitectura del Sistema (`suma_spt`)

`suma_spt` sigue una arquitectura PHP MVC modular organizada en:
- **`Controlador/` y `Modulos/Controlador/`**: Lógica de controladores y endpoints AJAX/REST internos.
- **`Modulos/Modelo/`**: Acceso a datos, consultas SQL y procedimientos almacenados sobre la base de datos empresarial (`bd_suma`).
- **`Modulos/Vista/`**: Vistas HTML/PHP, scripts JS (`Modulos/mis_scripts/`) y componentes de interfaz.
- **`DB Objects/`**: Definiciones de tablas, vistas, funciones y procedimientos almacenados.
- **`FlaskApp/`**: Servicios auxiliares en Python.

---

## 2. Principios del Módulo de Configuración

1. **Integridad de Catálogos y Parámetros**:
   - Toda modificación de parámetros globales, catálogos maestros, estados o permisos en el módulo de configuración debe validar tipos, rangos y dependencias referenciales antes de persistir.
2. **Control de Acceso y Auditoría**:
   - Solo usuarios con rol administrativo autorizado pueden alterar configuraciones del sistema.
   - Los cambios críticos (permisos, parámetros operativos, catálogos) deben quedar registrados con usuario y fecha.
3. **Separación de Responsabilidades**:
   - La lógica de negocio y las consultas a `bd_suma` residen en `Modelo/` o procedimientos almacenados en `DB Objects/`, nunca embebidas directamente en las vistas (`Vista/`).

---

## 3. Gobernanza e Integración con DataMind AI (`Supertex AI`)

1. **Acceso de Solo Lectura**:
   - DataMind AI consulta `bd_suma` exclusivamente mediante credenciales de solo lectura (`SELECT`), sin capacidad de ejecutar `INSERT`, `UPDATE`, `DELETE`, `DROP` o `ALTER`.
2. **Allowlist por Vectorización**:
   - Aunque `bd_suma` contiene 450+ tablas, la IA solo puede acceder a las tablas, funciones y procedimientos almacenados explícitamente vectorizados y habilitados desde `Supertex AI`.
3. **Confidencialidad en Respuestas**:
   - Las respuestas al usuario final deben expresarse en lenguaje de negocio, sin exponer nombres internos de tablas, columnas ni sentencias SQL.
