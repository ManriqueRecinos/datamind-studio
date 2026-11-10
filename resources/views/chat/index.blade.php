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

                    <button id="btnOpenTrainingCenter" type="button" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-datamind-light/30 hover:bg-datamind-light/50 text-datamind-dark border border-datamind-light flex items-center gap-1.5 transition cursor-pointer" title="Centro de Entrenamiento IA: Enseñar relaciones, reglas, fórmulas y ejemplos SQL">
                        <i class="fa-solid fa-graduation-cap text-datamind-primary"></i>
                        <span class="hidden sm:inline">Entrenar IA</span>
                    </button>

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

                    <!-- Perfil de Usuario Autenticado / Botón de Login -->
                    @auth
                        <div class="flex items-center gap-2 pl-2 border-l border-slate-200">
                            <span class="hidden sm:inline-flex items-center gap-1.5 text-xs font-semibold text-datamind-dark bg-datamind-neutral px-2.5 py-1 rounded-lg border border-datamind-light/60" title="{{ auth()->user()->email }}">
                                <i class="fa-solid fa-circle-user text-datamind-primary"></i>
                                {{ auth()->user()->name }}
                            </span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg text-slate-600 hover:text-red-600 hover:bg-red-50 border border-slate-200 transition cursor-pointer" title="Cerrar sesión">
                                    <i class="fa-solid fa-right-from-bracket"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg btn-datamind-primary flex items-center gap-1.5">
                            <i class="fa-solid fa-user-lock"></i> <span class="hidden sm:inline">Iniciar Sesión</span>
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Contenedor Principal con Layout 2 Columnas -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-5 flex gap-6 overflow-hidden">

        <!-- Sidebar Izquierdo: Historial de Conversaciones del Usuario y Preguntas Sugeridas -->
        <aside class="hidden lg:flex flex-col w-72 shrink-0 gap-4">

            <!-- Tarjeta 1: Historial de Conversaciones del Usuario -->
            <div class="bg-white rounded-2xl border border-datamind-light/60 p-4 shadow-2xs flex-1 flex flex-col overflow-hidden">
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-100">
                    <h3 class="text-xs font-bold text-datamind-dark uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-datamind-primary"></i> Mis Conversaciones
                    </h3>
                    <span id="historyCountBadge" class="text-[10px] font-bold px-2 py-0.5 rounded-full badge-datamind">
                        {{ count($chatSessions ?? []) }}
                    </span>
                </div>

                @guest
                    <div class="mb-3 p-2.5 rounded-xl bg-datamind-neutral/80 border border-datamind-light/70 text-[11px] text-slate-600 flex items-center justify-between gap-2">
                        <span>Inicia sesión para guardar tu historial personal.</span>
                        <a href="{{ route('login') }}" class="font-bold text-datamind-primary hover:underline shrink-0">Entrar</a>
                    </div>
                @endguest

                <div id="userSessionsList" class="space-y-1.5 overflow-y-auto custom-scrollbar pr-1 flex-1">
                    @forelse($chatSessions ?? [] as $sess)
                        <div class="session-history-item group flex items-center justify-between gap-2 p-2.5 rounded-xl border border-slate-200/80 hover:border-datamind-light hover:bg-datamind-light/20 transition cursor-pointer"
                             data-session-id="{{ $sess['id'] }}">
                            <div class="flex items-center gap-2 min-w-0 flex-1">
                                <i class="fa-regular fa-message text-xs text-datamind-primary shrink-0"></i>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold text-slate-800 truncate">{{ $sess['title'] }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $sess['message_count'] ?? 0 }} mensajes</p>
                                </div>
                            </div>
                            <button type="button" class="btn-delete-session opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-600 p-1 transition cursor-pointer"
                                    data-session-id="{{ $sess['id'] }}" title="Eliminar conversación">
                                <i class="fa-solid fa-trash-can text-[11px]"></i>
                            </button>
                        </div>
                    @empty
                        <div id="emptySessionsNotice" class="text-center py-6 text-xs text-slate-400">
                            Aún no tienes conversaciones guardadas. Envía una consulta para crear la primera.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Tarjeta 2: Sugerencias Rápidas (Consultas y Gráficos) -->
            <div class="bg-white rounded-2xl border border-datamind-light/60 p-4 shadow-2xs flex flex-col">
                <h3 class="text-xs font-bold text-datamind-dark uppercase tracking-wider mb-2.5 flex items-center gap-2">
                    <i class="fa-solid fa-wand-magic-sparkles text-datamind-primary"></i> Acciones Rápidas
                </h3>
                <div class="space-y-1.5">
                    <button type="button" data-prompt="Hazme un gráfico detallado con los registros principales de la base de datos" class="quick-suggestion-btn w-full text-left p-2 rounded-xl border border-slate-200/80 hover:border-datamind-light hover:bg-datamind-light/20 text-xs text-slate-700 hover:text-datamind-dark transition cursor-pointer">
                        <i class="fa-solid fa-chart-column text-datamind-primary mr-1.5"></i> Generar gráfico interactivo
                    </button>
                    <button type="button" data-prompt="¿Qué tablas están registradas en la base de datos?" class="quick-suggestion-btn w-full text-left p-2 rounded-xl border border-slate-200/80 hover:border-datamind-light hover:bg-datamind-light/20 text-xs text-slate-700 hover:text-datamind-dark transition cursor-pointer">
                        <i class="fa-solid fa-database text-datamind-primary mr-1.5"></i> Explorar tablas registradas
                    </button>
                    <button type="button" data-prompt="Muestra los últimos 5 registros de la tabla principal" class="quick-suggestion-btn w-full text-left p-2 rounded-xl border border-slate-200/80 hover:border-datamind-light hover:bg-datamind-light/20 text-xs text-slate-700 hover:text-datamind-dark transition cursor-pointer">
                        <i class="fa-solid fa-table text-datamind-secondary mr-1.5"></i> Últimos 5 registros en SQL
                    </button>
                </div>

                <!-- Footer Informativo -->
                <div class="mt-3 pt-2.5 border-t border-slate-100 text-[11px] text-datamind-secondary font-medium text-center">
                    DataMind Studio v1.0.8
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
                    <form id="chatForm" onsubmit="return false;" class="flex items-end gap-2.5">
                        <div class="relative flex-1">
                            <textarea 
                                id="chatPromptInput" 
                                rows="1"
                                placeholder="Escribe tu consulta o formato **Markdown** (Ej: ¿Qué tablas están registradas? o pega un bloque ```sql)..." 
                                class="w-full pl-4 pr-10 py-3 rounded-2xl border border-datamind-light bg-white text-sm text-slate-800 placeholder-slate-400 input-datamind transition shadow-2xs resize-none max-h-40 overflow-y-auto custom-scrollbar leading-relaxed"
                                autocomplete="off"
                            ></textarea>
                        </div>
                        
                        <button 
                            type="button" 
                            id="btnSendMessage" 
                            class="inline-flex items-center justify-center px-5 py-3 rounded-2xl btn-datamind-primary text-sm font-semibold shrink-0 gap-1.5 cursor-pointer h-[46px]"
                        >
                            <i class="fa-solid fa-paper-plane"></i>
                            <span class="hidden sm:inline">Enviar</span>
                        </button>
                    </form>
                    
                    <div class="flex items-center justify-between mt-2 px-2 text-[11px] text-slate-500">
                        <span><i class="fa-solid fa-code text-[10px] text-datamind-primary mr-1"></i> Vista previa <strong>Markdown</strong> activa &bull; Microservicio 100% Local</span>
                        <span class="hidden sm:inline"><kbd class="px-1.5 py-0.5 bg-white border border-datamind-light/80 rounded text-datamind-dark font-mono font-semibold">Enter</kbd> enviar &bull; <kbd class="px-1.5 py-0.5 bg-white border border-datamind-light/80 rounded text-datamind-dark font-mono font-semibold">Shift+Enter</kbd> nueva línea</span>
                    </div>
                </div>
            </div>

        </section>

    </main>

    <!-- Modal / Centro de Entrenamiento IA ("🎓 Entrenar IA" - Fase 16) -->
    <div id="aiTrainingModal" class="hidden fixed inset-0 z-50 bg-slate-900/55 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl border border-datamind-light/80 shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <!-- Cabecera del Modal -->
            <div class="p-4 sm:px-6 bg-datamind-neutral border-b border-datamind-light/70 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-datamind-gradient text-white flex items-center justify-center text-lg shadow-xs">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900">Centro de Entrenamiento IA &bull; Aprende a Enseñarle a DataMind</h3>
                        <p class="text-xs text-slate-500">Enséñale relaciones `JOIN`, códigos de estado, fórmulas de gráficos y ejemplos SQL en `pgvector`</p>
                    </div>
                </div>
                <button id="btnCloseTrainingModal" type="button" class="h-8 w-8 rounded-xl bg-white hover:bg-red-50 text-slate-500 hover:text-red-600 border border-slate-200 flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Pestañas Superiores -->
            <div class="px-6 pt-3 bg-slate-50 border-b border-slate-200 flex flex-wrap gap-2">
                <button type="button" data-tab="tabTeachForm" class="training-tab-btn px-3.5 py-2 rounded-t-xl text-xs font-bold bg-white text-datamind-primary border-t border-x border-slate-200 cursor-pointer">
                    <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> 1. Enseñar Nueva Regla / Relación
                </button>
                <button type="button" data-tab="tabLearnedList" class="training-tab-btn px-3.5 py-2 rounded-t-xl text-xs font-semibold text-slate-600 hover:text-datamind-dark cursor-pointer">
                    <i class="fa-solid fa-brain mr-1"></i> 2. Conocimiento Aprendido (<span id="learnedRulesCount">0</span>)
                </button>
                <button type="button" data-tab="tabCurriculum" class="training-tab-btn px-3.5 py-2 rounded-t-xl text-xs font-semibold text-slate-600 hover:text-datamind-dark cursor-pointer">
                    <i class="fa-solid fa-list-check mr-1"></i> 3. Tu Guía de 5 Niveles (Cómo Entrenarla)
                </button>
            </div>

            <!-- Cuerpo con Scroll -->
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 space-y-4">

                <!-- PESTAÑA 1: Formulario Guiado con 4 Plantillas -->
                <div id="tabTeachForm" class="training-tab-pane space-y-4">
                    <div class="p-3.5 rounded-2xl bg-datamind-light/20 border border-datamind-light/70 text-xs text-slate-700">
                        <strong class="text-datamind-dark"><i class="fa-solid fa-lightbulb text-datamind-primary mr-1"></i> Tip Rápido:</strong>
                        También puedes enseñarle directamente escribiendo en el chat (ej. <em>"La tabla users se relaciona con companies uniendo users.current_company_id = companies.id; muestra siempre companies.name"</em>) o usando cualquiera de estas <strong>4 plantillas guiadas</strong>:
                    </div>

                    <!-- Selector de 4 Plantillas con 1 Clic -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
                        <button type="button" data-category="custom_relation" class="template-type-btn p-3 rounded-2xl border-2 border-datamind-primary bg-datamind-light/20 text-left transition cursor-pointer">
                            <div class="text-xs font-bold text-datamind-dark flex items-center gap-1.5 mb-1">
                                <i class="fa-solid fa-link text-datamind-primary"></i> 1. Relación entre Tablas
                            </div>
                            <p class="text-[11px] text-slate-500">Unir tabla A con tabla B y mostrar el nombre real en vez del ID</p>
                        </button>
                        <button type="button" data-category="business_rule" class="template-type-btn p-3 rounded-2xl border border-slate-200 hover:border-datamind-light bg-white text-left transition cursor-pointer">
                            <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                                <i class="fa-solid fa-book-open text-datamind-primary"></i> 2. Estados y Sinónimos
                            </div>
                            <p class="text-[11px] text-slate-500">Explicar qué significa estado=1, 2 o palabras clave de tu negocio</p>
                        </button>
                        <button type="button" data-category="kpi_formula" class="template-type-btn p-3 rounded-2xl border border-slate-200 hover:border-datamind-light bg-white text-left transition cursor-pointer">
                            <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                                <i class="fa-solid fa-calculator text-datamind-primary"></i> 3. Fórmula / Indicador
                            </div>
                            <p class="text-[11px] text-slate-500">Enseñar cómo se calcula Peso Neto, Merma, Eficiencia o Atraso</p>
                        </button>
                        <button type="button" data-category="sql_example" class="template-type-btn p-3 rounded-2xl border border-slate-200 hover:border-datamind-light bg-white text-left transition cursor-pointer">
                            <div class="text-xs font-bold text-slate-800 flex items-center gap-1.5 mb-1">
                                <i class="fa-solid fa-code text-datamind-primary"></i> 4. Ejemplo SQL de Oro
                            </div>
                            <p class="text-[11px] text-slate-500">Darle una consulta SELECT ... JOIN perfecta como ejemplo a imitar</p>
                        </button>
                    </div>

                    <form id="formTrainKnowledge" onsubmit="return false;" class="space-y-3.5 bg-slate-50/70 p-4 rounded-2xl border border-slate-200/80">
                        <input type="hidden" id="trainCategoryInput" value="custom_relation">

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Título corto de la enseñanza</label>
                            <input type="text" id="trainTitleInput" placeholder="Ej: Relación users con companies por current_company_id"
                                   class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-hidden focus:border-datamind-primary">
                        </div>

                        <!-- Campos guiados adicionales cuando es Relación entre Tablas (custom_relation) -->
                        <div id="customRelationFields" class="grid grid-cols-1 sm:grid-cols-5 gap-2.5 p-3 rounded-xl bg-white border border-datamind-light/70">
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Tabla Hija (Origen)</label>
                                <input type="text" id="trainSourceTable" placeholder="ej: users" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Columna ID Foráneo</label>
                                <input type="text" id="trainSourceCol" placeholder="ej: current_company_id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Tabla Maestra</label>
                                <input type="text" id="trainTargetTable" placeholder="ej: companies" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">PK Tabla Maestra</label>
                                <input type="text" id="trainTargetCol" value="id" placeholder="ej: id" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-datamind-primary mb-1">Columna Nombre Real</label>
                                <input type="text" id="trainLabelCol" placeholder="ej: name" class="w-full px-2.5 py-1.5 rounded-lg border border-datamind-light bg-datamind-light/10 text-xs font-mono">
                            </div>
                        </div>

                        <!-- Campo SQL adicional cuando es sql_example -->
                        <div id="sqlExampleFieldBox" class="hidden">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Consulta SQL `SELECT` Verificada (Solo lectura)</label>
                            <textarea id="trainSqlQueryInput" rows="3" placeholder="SELECT c.name AS empresa, COUNT(u.id) AS total FROM users u JOIN companies c ON u.current_company_id = c.id GROUP BY c.name"
                                      class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-slate-900 text-datamind-light font-mono text-xs"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Instrucción o explicación para la IA</label>
                            <textarea id="trainContentInput" rows="3"
                                      placeholder="Ej: La tabla users se relaciona con la tabla companies uniendo users.current_company_id = companies.id. Cuando el usuario pida datos o gráficos por empresa, nunca muestres current_company_id; haz siempre JOIN companies y muestra companies.name."
                                      class="w-full px-3.5 py-2 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 focus:outline-hidden focus:border-datamind-primary"></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1">
                            <button type="button" id="btnFillExampleTemplate" class="px-3.5 py-2 rounded-xl bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-xs font-semibold transition cursor-pointer">
                                <i class="fa-solid fa-flask text-datamind-primary mr-1"></i> Cargar Ejemplo de Muestra
                            </button>
                            <button type="button" id="btnSaveTrainingRule" class="px-5 py-2 rounded-xl btn-datamind-primary text-xs font-bold shadow-xs transition cursor-pointer flex items-center gap-1.5">
                                <i class="fa-solid fa-brain"></i> Vectorizar y Enseñar a la IA
                            </button>
                        </div>
                    </form>
                </div>

                <!-- PESTAÑA 2: Lista de Conocimiento Aprendido -->
                <div id="tabLearnedList" class="training-tab-pane hidden space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs text-slate-600">Estas son las reglas, relaciones `JOIN`, fórmulas y ejemplos SQL que la IA consulta en `pgvector` antes de responder:</p>
                        <button type="button" id="btnRefreshRulesList" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-semibold text-slate-700 cursor-pointer">
                            <i class="fa-solid fa-rotate mr-1"></i> Actualizar
                        </button>
                    </div>
                    <div id="learnedRulesContainer" class="space-y-2">
                        <div class="text-center py-8 text-xs text-slate-400">Cargando cápsulas de conocimiento...</div>
                    </div>
                </div>

                <!-- PESTAÑA 3: Tu Currículo Práctico de 5 Niveles -->
                <div id="tabCurriculum" class="training-tab-pane hidden space-y-3">
                    <div class="p-3.5 rounded-2xl bg-datamind-neutral border border-datamind-light/70 text-xs text-slate-700">
                        <strong class="text-datamind-dark">🎓 Guía Práctica (Tasks T103–T107):</strong> Sigue estos 5 pasos en orden para convertir a tu IA en experta absoluta de tu base de datos:
                    </div>
                    <div class="space-y-2.5 text-xs">
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                            <span class="font-bold text-datamind-primary">Nivel 1 (T103) — Vectorizar Parejas Maestra + Detalle juntas:</span>
                            <p class="text-slate-600 mt-1">En <strong>Bases de Datos</strong> (`/connections`), selecciona siempre la tabla hija (ej. `users` o `rollos`) junto con su tabla maestra (`companies` o `ordenes`) en el mismo lote.</p>
                        </div>
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                            <span class="font-bold text-datamind-primary">Nivel 2 (T104) — Enseñar Relaciones con Nombres Especiales (`JOIN`):</span>
                            <p class="text-slate-600 mt-1">Si una columna se llama `current_company_id` o `cod_cia`, escríbelo en el chat o en la Pestaña 1 indicando qué columna de la tabla maestra tiene el nombre real (`name` o `razon_social`).</p>
                        </div>
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                            <span class="font-bold text-datamind-primary">Nivel 3 (T105) — Enseñar Diccionario de Estados y Sinónimos:</span>
                            <p class="text-slate-600 mt-1">Enséñale qué significa cada número de estado (ej. `estado = 1` es Activo, `2` es En Proceso) para que los gráficos muestren nombres legibles en vez de números.</p>
                        </div>
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                            <span class="font-bold text-datamind-primary">Nivel 4 (T106) — Enseñar Fórmulas de Negocio:</span>
                            <p class="text-slate-600 mt-1">Registra cómo se calculan tus indicadores (ej. `Peso Neto = peso_bruto - peso_tara`).</p>
                        </div>
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-white">
                            <span class="font-bold text-datamind-primary">Nivel 5 (T107) — Guardar Consultas SQL de Oro (Few-Shot):</span>
                            <p class="text-slate-600 mt-1">Cuando una consulta del chat te dé el resultado exacto que querías, pulsa el botón <strong>"🧠 Enseñar este SQL a la IA"</strong> debajo de la respuesta para que la IA aprenda ese patrón.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts Locales Obligatorios (Zero CDNs) -->
    <script src="{{ asset('js/global_js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('js/global_js/all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/global_js/axios.min.js') }}"></script>
    <script src="{{ asset('js/global_js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/global_js/chart.umd.min.js') }}"></script>

    <!-- Cliente SSE de Streaming para DataMind AI -->
    <script src="{{ asset('js/chat_stream.js') }}"></script>

</body>
</html>
