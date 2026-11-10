<x-app-layout
    title="Asistente Corporativo RAG"
    ecosystem="chat"
    ecosystemLabel="Ecosistema de Chat & Analítica"
    ecosystemIcon="fa-comments"
    activeModule="Consola Conversacional IA"
    activeContext="Listo para consultas"
    :isOnline="$isOnline ?? false"
    :fullHeight="true">

    <!-- Layout Monolítico Recto de 2 Columnas Adosadas (Sin Espacios Muertos, Sin Bordes Redondeados) -->
    <main class="flex-1 w-full flex overflow-hidden rounded-none">

        <!-- Sidebar Izquierdo Adosado Continuo (Piso a Techo) -->
        <aside class="hidden lg:flex flex-col w-80 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">

            <!-- Módulo Dinámico desde Base de Datos -->
            <x-ecosystem-sidebar ecosystemKey="chat" />

            <!-- Selector Recto de Base de Datos Activa para Chat -->
            <div class="p-3.5 border-b border-slate-200 bg-white">
                <label for="activeConnectionSelect" class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1 flex items-center gap-1">
                    <i class="fa-solid fa-database text-datamind-primary text-[10px]"></i> Base de Datos para Consultas
                </label>
                <select id="activeConnectionSelect" class="w-full bg-slate-50 text-xs font-semibold text-slate-800 p-2 border border-slate-200 focus:outline-hidden cursor-pointer rounded-none" title="Seleccionar base de datos para consultas SQL">
                    @forelse($connections ?? [] as $conn)
                        <option value="{{ $conn['id'] }}" {{ !empty($conn['is_active']) ? 'selected' : '' }}>
                            {{ $conn['name'] }} ({{ strtoupper($conn['engine']) }}: {{ $conn['database_name'] }})
                        </option>
                    @empty
                        <option value="">Base de Datos por Defecto</option>
                    @endforelse
                </select>
            </div>

            <!-- Módulo: Historial de Conversaciones del Usuario (Recto) -->
            <div class="p-3.5 border-b border-slate-200 flex-1 flex flex-col overflow-hidden">
                <div class="flex items-center justify-between mb-2.5 pb-2 border-b border-slate-100">
                    <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-clock-rotate-left text-datamind-primary"></i> Mis Conversaciones
                    </h3>
                    <span id="historyCountBadge" class="text-[9px] font-bold px-1.5 py-0.2 rounded-none bg-slate-100 text-slate-600 border border-slate-200">
                        {{ count($chatSessions ?? []) }}
                    </span>
                </div>

                @guest
                    <div class="mb-3 p-2 bg-slate-50 border border-slate-200 text-[11px] text-slate-600 flex items-center justify-between gap-2 rounded-none">
                        <span>Inicia sesión para guardar tu historial.</span>
                        <a href="{{ route('login') }}" class="font-bold text-datamind-primary hover:underline shrink-0">Entrar</a>
                    </div>
                @endguest

                <div id="userSessionsList" class="space-y-1 overflow-y-auto custom-scrollbar pr-1 flex-1">
                    @forelse($chatSessions ?? [] as $sess)
                        <div class="session-history-item group flex items-center justify-between gap-2 p-2 border border-slate-200 hover:border-slate-400 hover:bg-slate-50 transition cursor-pointer rounded-none"
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
                            Aún no tienes conversaciones guardadas.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Sugerencias Rápidas Rectas -->
            <div class="p-3.5 bg-slate-50/50">
                <h3 class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-wand-magic-sparkles text-datamind-primary"></i> Acciones Rápidas
                </h3>
                <div class="space-y-1">
                    <button type="button" data-prompt="Hazme un gráfico detallado con los registros principales de la base de datos" class="quick-suggestion-btn w-full text-left p-2 border border-slate-200 hover:border-slate-400 hover:bg-white text-xs text-slate-700 hover:text-slate-900 transition rounded-none cursor-pointer">
                        <i class="fa-solid fa-chart-column text-datamind-primary mr-1"></i> Generar gráfico interactivo
                    </button>
                    <button type="button" data-prompt="¿Qué tablas están registradas en la base de datos?" class="quick-suggestion-btn w-full text-left p-2 border border-slate-200 hover:border-slate-400 hover:bg-white text-xs text-slate-700 hover:text-slate-900 transition rounded-none cursor-pointer">
                        <i class="fa-solid fa-database text-datamind-primary mr-1"></i> Explorar tablas registradas
                    </button>
                    <button type="button" data-prompt="Muestra los últimos 5 registros de la tabla principal" class="quick-suggestion-btn w-full text-left p-2 border border-slate-200 hover:border-slate-400 hover:bg-white text-xs text-slate-700 hover:text-slate-900 transition rounded-none cursor-pointer">
                        <i class="fa-solid fa-table text-datamind-secondary mr-1"></i> Últimos 5 registros en SQL
                    </button>
                </div>
            </div>
        </aside>

        <!-- Área Central del Chat Monolítica Recta (Adosada Directamente al Sidebar) -->
        <section class="flex-1 flex flex-col bg-white overflow-hidden rounded-none border-0">
            
            <!-- Ventana de Mensajes con Scroll -->
            <div id="chatScrollableArea" class="flex-1 p-6 overflow-y-auto custom-scrollbar">
                
                <div id="chatMessagesContainer" class="max-w-4xl mx-auto flex flex-col">
                    
                    <!-- Estado Inicial / Placeholder de Bienvenida -->
                    <div id="emptyStatePlaceholder" class="my-auto py-12 flex flex-col items-center justify-center text-center">
                        <div class="h-14 w-14 bg-datamind-neutral border border-slate-200 flex items-center justify-center text-2xl mb-4 text-datamind-primary rounded-none">
                            <i class="fa-solid fa-comments"></i>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900 mb-1">¡Hola! Soy tu Analista de Datos AI</h2>
                        <p class="text-xs text-slate-500 max-w-md mb-6 leading-relaxed">
                            Puedo ayudarte a consultar manuales de procesos internos mediante <strong class="text-slate-800">RAG</strong> o extraer y analizar datos de tus bases de datos <strong class="text-slate-800">PostgreSQL / MariaDB</strong> con exportación a Excel.
                        </p>
                        <div class="flex flex-wrap gap-2 justify-center max-w-lg">
                            <button type="button" data-prompt="¿Qué tablas están registradas en la base de datos?" class="quick-suggestion-btn inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-medium text-slate-700 transition cursor-pointer rounded-none">
                                <i class="fa-solid fa-database text-xs text-datamind-primary"></i> Consultar esquema de Base de Datos
                            </button>
                            <button type="button" data-prompt="Explícame los procedimientos documentados en la base de conocimiento" class="quick-suggestion-btn inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-medium text-slate-700 transition cursor-pointer rounded-none">
                                <i class="fa-solid fa-book text-xs text-datamind-secondary"></i> Consultar base de conocimiento
                            </button>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Barra Inferior de Entrada de Texto -->
            <div class="p-3.5 bg-slate-50 border-t border-slate-200 shrink-0">
                <form id="chatForm" onsubmit="return false;" class="max-w-4xl mx-auto flex flex-col gap-2">
                    <div class="flex items-center gap-2">
                        <div class="relative flex-1">
                            <textarea id="promptInput" rows="2" placeholder="Pregúntale a DataMind AI... (ej: Muestra el total de ventas por sucursal)"
                                class="w-full pl-3.5 pr-10 py-2.5 text-xs text-slate-800 bg-white border border-slate-200 focus:outline-hidden focus:border-datamind-primary transition rounded-none resize-none custom-scrollbar"></textarea>
                            
                            <button type="button" id="btnVoiceInput" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-datamind-primary p-1 cursor-pointer" title="Entrada por voz">
                                <i class="fa-solid fa-microphone text-xs"></i>
                            </button>
                        </div>

                        <button type="submit" id="btnSendMessage" class="h-full px-5 py-2.5 bg-datamind-primary text-white font-bold text-xs flex items-center justify-center gap-1.5 hover:opacity-95 transition cursor-pointer rounded-none shrink-0">
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span class="hidden sm:inline">Enviar</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-400 px-1">
                        <span>Presiona <kbd class="px-1 py-0.5 border border-slate-200 bg-white rounded-none">Enter</kbd> para enviar o <kbd class="px-1 py-0.5 border border-slate-200 bg-white rounded-none">Shift + Enter</kbd> para nueva línea</span>
                        <span class="flex items-center gap-1"><i class="fa-solid fa-lock text-[10px]"></i> Consulta segura AST SQLGlot</span>
                    </div>
                </form>
            </div>

        </section>

    </main>

    <!-- Modal Dinámico Reutilizable del Centro de Entrenamiento -->
    <x-slot:modals>
        @include('chat.partials.training-center-modal')
    </x-slot:modals>

    <!-- Scripts Adicionales de Chat -->
    <x-slot:scripts>
        <script src="{{ asset('js/global_js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('js/global_js/chart.umd.min.js') }}"></script>
        <script src="{{ asset('js/chat_stream.js') }}"></script>
    </x-slot:scripts>

</x-app-layout>
