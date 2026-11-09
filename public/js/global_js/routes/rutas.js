/**
 * RouteController
 * Módulo para la gestión dinámica de scripts y limpieza de estado entre vistas.
 */
class RouteController {
    constructor() {
        this.currentScript = null;
        this.activeView = null;
        this.loadedScripts = [];
        // Variables globales conocidas que deben limpiarse entre vistas
        this.globalVarsToClean = [
            // Dotación - Puesto
            'puestos', 'puestosFiltrados', 'puestoSeleccionado', 'paginaActualP', 'registrosPorPaginaP',
            // Dotación - Tipo Dotación
            'tiposDotacion', 'tiposDotacionFiltrados', 'tipoDotacionSeleccionado', 'paginaActualTD', 'registrosPorPaginaTD',
            // Seguridad
            'usuarioSeleccionado', 'cafeteriasSeleccionadas', 'usuariosData', 'usuariosFiltrados', 'paginaActual', 'USUARIOS_POR_PAGINA', 'URLS',
            // Funciones expuestas
            'mostrarModalAgregar', 'mostrarModalEditar', 'mostrarModalSubirPlano', 'cerrarModal', 'descargarPlano', 'subirPlano',
            // Caja - Detalle de venta
            'cajaCarrito', 'cajaProductoSeleccionado', 'cajaBuscarCodigoTimeout',
            // Gerencia - Empleados
            'inicializarVistaEmp', 'configurarEventListenersEmp', 'descargarPlanoEmp', 'procesarPlanoEmp',
            'initAsigSupervisor', 'setupEventsAsig', 'procesarAsignacionSupervisor',
        ];
        // Inicializar limpieza al instanciar (carga inicial de la app)
        this.init();
    }

    init() {
        // Asegurar que empezamos limpios
        this.cleanup();
        console.log('RouteController: Inicializado.');
    }

    /**
     * Carga un script específico para una vista, asegurando limpieza previa.
     * @param {string} viewName - Identificador de la vista (ej: 'dashboard', 'login').
     * @param {string} scriptPath - URL del script a cargar.
     */
    load(viewName, scriptPath) {
        console.log(`RouteController: Cargando vista "${viewName}"...`);

        // 1. Limpieza completa del estado anterior
        this.cleanup();

        // 2. Establecer nueva vista activa
        this.activeView = viewName;

        // 3. Cargar el nuevo script
        this._loadScript(scriptPath);
    }

    /**
     * Realiza la limpieza de scripts anteriores y almacenamiento local.
     */
    cleanup() {
        // 1. Ejecutar función de cleanup de la vista si existe
        if (typeof window.routeCleanup === 'function') {
            try {
                window.routeCleanup();
                console.log('RouteController: Cleanup de vista ejecutado.');
            } catch (e) {
                console.error('RouteController: Error en cleanup de vista:', e);
            }
            window.routeCleanup = null;
        }

        // 2. Limpiar variables globales conocidas
        this._cleanGlobalVars();

        // 3. Eliminar scripts anteriores del DOM
        this._removeLoadedScripts();

        // 4. Limpiar LocalStorage (Requisito estricto)
        localStorage.clear();
        console.log('RouteController: LocalStorage limpiado.');

        // 5. Limpiar event listeners de jQuery en elementos dinámicos
        this._cleanEventListeners();

        // 6. Limpiar intervalos y timeouts pendientes (opcional pero recomendado)
        this._cleanTimers();

        this.activeView = null;
    }

    /**
     * Limpia las variables globales conocidas
     */
    _cleanGlobalVars() {
        this.globalVarsToClean.forEach(varName => {
            if (typeof window[varName] !== 'undefined') {
                try {
                    delete window[varName];
                } catch (e) {
                    window[varName] = undefined;
                }
            }
        });
        console.log('RouteController: Variables globales limpiadas.');
    }

    /**
     * Elimina los scripts cargados dinámicamente
     */
    _removeLoadedScripts() {
        // Eliminar script actual
        if (this.currentScript) {
            this.currentScript.remove();
            this.currentScript = null;
        }

        // Eliminar todos los scripts cargados previamente
        this.loadedScripts.forEach(script => {
            if (script && script.parentNode) {
                script.remove();
            }
        });
        this.loadedScripts = [];

        // También buscar y eliminar scripts de módulos conocidos
        const scriptPatterns = [
            '/js/dotacion/',
            '/js/seguridad/',
            '/js/visitas/',
            '/js/gerencia/'
        ];

        document.querySelectorAll('script[src]').forEach(script => {
            const src = script.getAttribute('src') || '';
            if (scriptPatterns.some(pattern => src.includes(pattern))) {
                script.remove();
            }
        });

        console.log('RouteController: Scripts anteriores eliminados.');
    }

    /**
     * Limpia event listeners de jQuery
     */
    _cleanEventListeners() {
        // Limpiar eventos de elementos comunes que pueden tener listeners
        if (typeof $ !== 'undefined') {
            // Limpiar eventos del contenido principal
            $('#main-content').off();
            // Limpiar eventos de formularios dinámicos
            $('form').off('submit');
            // Limpiar eventos de inputs
            $('#txt_buscar').off();
        }
        console.log('RouteController: Event listeners limpiados.');
    }

    /**
     * Limpia timers pendientes (intervalos y timeouts)
     */
    _cleanTimers() {
        // Limpiar intervalos registrados
        if (window._routeIntervals) {
            window._routeIntervals.forEach(id => clearInterval(id));
            window._routeIntervals = [];
        }
        // Limpiar timeouts registrados
        if (window._routeTimeouts) {
            window._routeTimeouts.forEach(id => clearTimeout(id));
            window._routeTimeouts = [];
        }
    }

    /**
     * Método privado para inyectar el tag script.
     * @param {string} path 
     */
    _loadScript(path) {
        // Agregar timestamp para evitar cache
        const timestamp = new Date().getTime();
        const pathWithCache = path + (path.includes('?') ? '&' : '?') + '_t=' + timestamp;

        const script = document.createElement('script');
        script.src = pathWithCache;
        script.type = 'text/javascript';
        script.async = true;

        script.onload = () => {
            console.log(`RouteController: Script cargado -> ${path}`);
        };

        script.onerror = () => {
            console.error(`RouteController: Error cargando script -> ${path}`);
        };

        document.body.appendChild(script);
        this.currentScript = script;
        this.loadedScripts.push(script);
    }

    /**
     * Registra un intervalo para limpieza posterior
     */
    registerInterval(intervalId) {
        if (!window._routeIntervals) window._routeIntervals = [];
        window._routeIntervals.push(intervalId);
    }

    /**
     * Registra un timeout para limpieza posterior
     */
    registerTimeout(timeoutId) {
        if (!window._routeTimeouts) window._routeTimeouts = [];
        window._routeTimeouts.push(timeoutId);
    }
}

// Instanciar globalmente
window.routeController = new RouteController();
