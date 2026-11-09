<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-datamind-neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DataMind AI - Asistente Corporativo RAG</title>

    <!-- CSS Globales / Plugins Locales (Zero CDNs) -->
    <link rel="stylesheet" href="{{ asset('css/global_styles/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/globales.css') }}">

    <!-- Vite Styles (Tailwind CSS v4) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--datamind-bg-neutral);
        }
    </style>
</head>
<body class="h-full flex flex-col text-slate-800 antialiased selection:bg-datamind-primary selection:text-white overflow-hidden">

    <!-- Header / Navbar Superior -->
    <header class="bg-white border-b border-datamind-light/60 shrink-0 z-30 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Logotipo e Identidad DataMind -->
                <div class="flex items-center gap-3">
                    <a href="{{ url('/') }}" class="h-10 w-10 rounded-xl bg-datamind-gradient flex items-center justify-center text-white shadow-md font-bold text-xl hover:opacity-95 transition">
                        <i class="fa-solid fa-brain"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-bold tracking-tight text-slate-900">DATAMIND <span class="text-datamind-primary">AI</span></span>
                            <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full badge-datamind">
                                RAG &bull; Text-to-SQL
                            </span>
                        </div>
                        <p class="text-[11px] text-datamind-secondary font-medium hidden sm:block">Inteligencia Artificial Corporativa On-Premise</p>
                    </div>
                </div>

                <!-- Estado del Servicio y Acciones Rápidas -->
                <div class="flex items-center gap-2.5">
                    <!-- Selector Dinámico de Base de Datos Conectada -->
                    <div class="hidden md:flex items-center gap-1.5 bg-datamind-neutral px-2.5 py-1 rounded-xl border border-datamind-light/80">
                        <i class="fa-solid fa-database text-xs text-datamind-primary"></i>
                        <select id="activeConnectionSelect" class="bg-transparent text-xs font-semibold text-datamind-dark focus:outline-hidden cursor-pointer pr-1" title="Seleccionar base de datos para consultas SQL">
                            @forelse($connections ?? [] as $conn)
                                <option value="{{ $conn['id'] }}" {{ !empty($conn['is_active']) ? 'selected' : '' }}>
                                    {{ $conn['name'] }} ({{ strtoupper($conn['engine']) }}: {{ $conn['database_name'] }})
                                </option>
                            @empty
                                <option value="">Base de Datos por Defecto</option>
                            @endforelse
                        </select>
                    </div>

                    <a href="{{ route('connections.index') }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg btn-datamind-soft flex items-center gap-1.5" title="Conectar otra base de datos y vectorizar tablas">
                        <i class="fa-solid fa-plug-circle-bolt text-datamind-primary"></i>
                        <span class="hidden sm:inline">Bases de Datos</span>
                    </a>

                    @if($isOnline ?? false)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200" title="Microservicio FastAPI conectado">
                            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="hidden lg:inline">Motor IA Activo</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200" title="Verifique que uvicorn esté ejecutándose en el puerto 8000">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            <span class="hidden lg:inline">Motor Offline</span>
                        </span>
                    @endif

                    <button id="btnNewChat" type="button" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-white text-slate-700 hover:text-datamind-primary hover:bg-datamind-neutral border border-slate-200 transition flex items-center gap-1.5 cursor-pointer">
                        <i class="fa-solid fa-plus text-datamind-primary"></i> <span class="hidden sm:inline">Nuevo Chat</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Contenedor Principal con Layout 2 Columnas -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-5 flex gap-6 overflow-hidden">

        <!-- Sidebar Izquierdo: Capacidades y Preguntas Sugeridas -->
        <aside class="hidden lg:flex flex-col w-72 shrink-0 gap-4">
            <!-- Tarjeta de Contexto RAG -->
            <div class="bg-white rounded-2xl border border-datamind-light/60 p-4 shadow-2xs">
                <h3 class="text-xs font-bold text-datamind-dark uppercase tracking-wider mb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-datamind-primary"></i> Arquitectura Segura
                </h3>
                <ul class="text-xs text-slate-600 space-y-2">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-datamind-primary mt-0.5 text-[10px]"></i>
                        <span><strong>pgvector (PostgreSQL 17):</strong> Búsqueda semántica HNSW de manuales.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-datamind-primary mt-0.5 text-[10px]"></i>
                        <span><strong>Text-to-SQL (MariaDB):</strong> Consultas de lectura con guardrail AST.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-datamind-primary mt-0.5 text-[10px]"></i>
                        <span><strong>Excel Corporativo:</strong> Generación manual bajo demanda con openpyxl.</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-datamind-primary mt-0.5 text-[10px]"></i>
                        <span><strong>Privacidad 100%:</strong> Ollama qwen2.5:3b local sin conexión a internet.</span>
                    </li>
                </ul>
            </div>

            <!-- Sugerencias Rápidas de Preguntas -->
            <div class="bg-white rounded-2xl border border-datamind-light/60 p-4 shadow-2xs flex-1 flex flex-col overflow-hidden">
                <h3 class="text-xs font-bold text-datamind-dark uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-lightbulb text-datamind-primary"></i> Preguntas Frecuentes
                </h3>
                <div class="space-y-2 overflow-y-auto custom-scrollbar pr-1 flex-1">
                    <button type="button" data-prompt="¿Qué tablas están registradas en la base de datos?" class="quick-suggestion-btn w-full text-left p-2.5 rounded-xl border border-slate-200/80 hover:border-datamind-light hover:bg-datamind-light/20 text-xs text-slate-700 hover:text-datamind-dark transition cursor-pointer">
                        <i class="fa-solid fa-database text-datamind-primary mr-1.5"></i> Explorar tablas registradas
                    </button>
                    <button type="button" data-prompt="¿Cuál es el procedimiento documentado en los manuales?" class="quick-suggestion-btn w-full text-left p-2.5 rounded-xl border border-slate-200/80 hover:border-datamind-light hover:bg-datamind-light/20 text-xs text-slate-700 hover:text-datamind-dark transition cursor-pointer">
                        <i class="fa-solid fa-book-open text-datamind-secondary mr-1.5"></i> Consultar manuales RAG
                    </button>
                    <button type="button" data-prompt="Muestra los últimos 5 registros de la tabla principal" class="quick-suggestion-btn w-full text-left p-2.5 rounded-xl border border-slate-200/80 hover:border-datamind-light hover:bg-datamind-light/20 text-xs text-slate-700 hover:text-datamind-dark transition cursor-pointer">
                        <i class="fa-solid fa-clock-rotate-left text-datamind-primary mr-1.5"></i> Últimos 5 registros en SQL
                    </button>
                    <button type="button" data-prompt="Hola, ¿en qué me puedes ayudar hoy?" class="quick-suggestion-btn w-full text-left p-2.5 rounded-xl border border-slate-200/80 hover:border-datamind-light hover:bg-datamind-light/20 text-xs text-slate-700 hover:text-datamind-dark transition cursor-pointer">
                        <i class="fa-solid fa-comments text-datamind-secondary mr-1.5"></i> Saludo y capacidades
                    </button>
                </div>

                <!-- Footer Informativo -->
                <div class="mt-4 pt-3 border-t border-slate-100 text-[11px] text-datamind-secondary font-medium text-center">
                    DataMind Studio v1.0.3
                </div>
            </div>
        </aside>

        <!-- Área Central del Chat -->
        <section class="flex-1 flex flex-col bg-white rounded-3xl border border-datamind-light/60 shadow-sm overflow-hidden">
            
            <!-- Ventana de Mensajes con Scroll -->
            <div id="chatScrollableArea" class="flex-1 p-6 overflow-y-auto custom-scrollbar">
                
                <div id="chatMessagesContainer" class="max-w-4xl mx-auto flex flex-col">
                    
                    <!-- Estado Inicial / Placeholder de Bienvenida -->
                    <div id="emptyStatePlaceholder" class="my-auto py-12 flex flex-col items-center justify-center text-center">
                        <div class="h-16 w-16 rounded-2xl badge-datamind flex items-center justify-center text-2xl mb-4 shadow-xs">
                            <i class="fa-solid fa-comments text-datamind-primary"></i>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 mb-1">¡Hola! Soy tu Analista de Datos AI</h2>
                        <p class="text-sm text-slate-500 max-w-md mb-6">
                            Puedo ayudarte a consultar manuales de procesos internos mediante <strong class="text-datamind-dark">RAG</strong> o extraer y analizar datos de tus bases de datos <strong class="text-datamind-dark">PostgreSQL / MariaDB</strong> con exportación a Excel.
                        </p>
                        <div class="flex flex-wrap gap-2 justify-center max-w-lg">
                            <button type="button" data-prompt="¿Qué tablas están registradas en la base de datos?" class="quick-suggestion-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-datamind-neutral hover:bg-datamind-light/35 hover:text-datamind-dark border border-slate-200 hover:border-datamind-light text-xs font-medium text-slate-700 transition cursor-pointer">
                                <i class="fa-solid fa-database text-xs text-datamind-primary"></i> Consultar esquema de Base de Datos
                            </button>
                            <button type="button" data-prompt="Explícame los procedimientos documentados en la base de conocimiento" class="quick-suggestion-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-datamind-neutral hover:bg-datamind-light/35 hover:text-datamind-dark border border-slate-200 hover:border-datamind-light text-xs font-medium text-slate-700 transition cursor-pointer">
                                <i class="fa-solid fa-book text-xs text-datamind-secondary"></i> Consultar base de conocimiento
                            </button>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Barra Inferior de Entrada de Texto -->
            <div class="p-4 bg-datamind-neutral/85 border-t border-datamind-light/50 shrink-0">
                <div class="max-w-4xl mx-auto">
                    <form id="chatForm" onsubmit="return false;" class="flex items-center gap-2.5">
                        <div class="relative flex-1">
                            <input 
                                type="text" 
                                id="chatPromptInput" 
                                placeholder="Escribe tu consulta sobre procesos o bases de datos (Ej: Muestra los últimos 5 registros)..." 
                                class="w-full pl-4 pr-10 py-3 rounded-2xl border border-datamind-light bg-white text-sm text-slate-800 placeholder-slate-400 input-datamind transition shadow-2xs"
                                autocomplete="off"
                            >
                        </div>
                        
                        <button 
                            type="button" 
                            id="btnSendMessage" 
                            class="inline-flex items-center justify-center px-5 py-3 rounded-2xl btn-datamind-primary text-sm font-semibold shrink-0 gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-paper-plane"></i>
                            <span class="hidden sm:inline">Enviar</span>
                        </button>
                    </form>
                    
                    <div class="flex items-center justify-between mt-2 px-2 text-[11px] text-slate-500">
                        <span><i class="fa-solid fa-lock text-[10px] text-datamind-secondary mr-1"></i> Microservicio On-Premise &bull; 0% fugas a la nube</span>
                        <span class="hidden sm:inline">Presiona <kbd class="px-1.5 py-0.5 bg-white border border-datamind-light/80 rounded text-datamind-dark font-mono font-semibold">Enter</kbd> para enviar</span>
                    </div>
                </div>
            </div>

        </section>

    </main>

    <!-- Scripts Locales Obligatorios (Zero CDNs) -->
    <script src="{{ asset('js/global_js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('js/global_js/all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/global_js/axios.min.js') }}"></script>
    <script src="{{ asset('js/global_js/jquery.dataTables.min.js') }}"></script>

    <!-- Cliente SSE de Streaming para DataMind AI -->
    <script src="{{ asset('js/chat_stream.js') }}"></script>

</body>
</html>
