<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-datamind-neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DataMind - Plantilla Base Laravel 13</title>

    <!-- CSS Globales / Plugins Locales -->
    <link rel="stylesheet" href="{{ asset('css/global_styles/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/toastr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/jquery.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/globales.css') }}">

    <!-- Vite Styles -->
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
<body class="min-h-full flex flex-col justify-between text-slate-800 antialiased selection:bg-datamind-primary selection:text-white">

    <!-- Header / Navbar -->
    <header class="bg-white border-b border-datamind-light/60 sticky top-0 z-40 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-datamind-gradient flex items-center justify-center text-white shadow-md font-bold text-xl">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <span class="text-xl font-bold tracking-tight text-slate-900">DATAMIND</span>
                        <span class="ml-2 text-xs font-semibold px-2.5 py-0.5 rounded-full badge-datamind">Laravel 13 Starter</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('connections.index') }}" class="text-xs font-semibold px-3.5 py-1.5 rounded-lg btn-datamind-soft flex items-center gap-1.5">
                        <i class="fa-solid fa-database text-datamind-primary"></i> Conectar Bases de Datos
                    </a>
                    <a href="{{ route('chat.index') }}" class="text-xs font-semibold px-3.5 py-1.5 rounded-lg btn-datamind-primary flex items-center gap-1.5">
                        <i class="fa-solid fa-brain"></i> Abrir Asistente AI
                    </a>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        Servidor Activo
                    </span>
                    <a href="https://laravel.com/docs" target="_blank" class="text-sm font-medium text-slate-600 hover:text-datamind-primary transition flex items-center gap-1">
                        <i class="fa-solid fa-book text-xs text-datamind-secondary"></i> Documentación
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Hero & Showcase -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10">
        
        <!-- Welcome Hero Banner -->
        <div class="relative overflow-hidden rounded-3xl bg-datamind-hero text-white p-8 sm:p-12 mb-10 shadow-xl border border-datamind-dark">
            <div class="relative z-10 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-datamind-light text-xs font-medium mb-4 backdrop-blur-sm border border-datamind-light/40">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Plantilla Maestra Configurada
                </div>
                <h1 class="text-3xl sm:text-5xl font-extrabold tracking-tight mb-4 leading-tight">
                    Inventario DataMind <br>
                    <span class="text-datamind-light">Lista para empezar desde cero</span>
                </h1>
                <p class="text-white/90 text-base sm:text-lg mb-8 leading-relaxed">
                    Tu arquitectura base ya cuenta con todas las librerías frontend instaladas, enlazadas y listas para su uso con PHP 8.5+ y Laravel 13.
                </p>
                <div class="flex flex-wrap gap-3">
                    <button id="btnTestSwal" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white text-datamind-dark hover:bg-datamind-neutral text-sm font-semibold shadow-lg transition duration-150 cursor-pointer">
                        <i class="fa-solid fa-bell text-datamind-primary"></i> Probar SweetAlert2
                    </button>
                    <button id="btnTestToastr" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-datamind-dark/80 hover:bg-datamind-dark text-white text-sm font-semibold border border-datamind-light/40 transition duration-150 cursor-pointer">
                        <i class="fa-solid fa-comment-dots text-datamind-light"></i> Probar Toastr
                    </button>
                    <button id="btnTestAxios" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-white text-sm font-semibold backdrop-blur-sm border border-white/20 transition duration-150 cursor-pointer">
                        <i class="fa-solid fa-bolt text-datamind-light"></i> Probar Axios
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid de Librerías Disponibles -->
        <div class="mb-10">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Librerías Globales Incorporadas</h2>
                    <p class="text-sm text-slate-500">Módulos CSS y Javascript precargados e integrados para el proyecto.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                
                <!-- Card SweetAlert2 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl mb-4 border border-amber-100">
                        <i class="fa-solid fa-bell"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">SweetAlert2</h3>
                    <p class="text-slate-500 text-xs mb-3">Ventanas modales interactivas y confirmaciones elegantes.</p>
                    <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                        <i class="fa-solid fa-check text-[10px] mr-1"></i> Cargado en window.Swal
                    </span>
                </div>

                <!-- Card Toastr -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl mb-4 border border-sky-100">
                        <i class="fa-solid fa-comment-dots"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">Toastr</h3>
                    <p class="text-slate-500 text-xs mb-3">Notificaciones emergentes no bloqueantes configuradas.</p>
                    <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                        <i class="fa-solid fa-check text-[10px] mr-1"></i> Cargado en window.toastr
                    </span>
                </div>

                <!-- Card jQuery & DataTables -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl mb-4 border border-blue-100">
                        <i class="fa-solid fa-table"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">jQuery & DataTables</h3>
                    <p class="text-slate-500 text-xs mb-3">Tablas dinámicas con ordenación, paginación y búsqueda.</p>
                    <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                        <i class="fa-solid fa-check text-[10px] mr-1"></i> jQuery 3.6 + DataTables
                    </span>
                </div>

                <!-- Card FontAwesome 6 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl mb-4 border border-indigo-100">
                        <i class="fa-solid fa-icons"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">Font Awesome</h3>
                    <p class="text-slate-500 text-xs mb-3">Catálogo completo de iconografía SVG y fuentes vectoriales.</p>
                    <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                        <i class="fa-solid fa-check text-[10px] mr-1"></i> Todos los iconos listos
                    </span>
                </div>

                <!-- Card Chart.js -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center text-xl mb-4 border border-pink-100">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">Chart.js</h3>
                    <p class="text-slate-500 text-xs mb-3">Gráficas estadísticas reactivas y dashboards analíticos.</p>
                    <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                        <i class="fa-solid fa-check text-[10px] mr-1"></i> chart.umd.min.js
                    </span>
                </div>

                <!-- Card Select2 -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center text-xl mb-4 border border-violet-100">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">Select2</h3>
                    <p class="text-slate-500 text-xs mb-3">Selects con filtrado, búsqueda instantánea y estilos.</p>
                    <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                        <i class="fa-solid fa-check text-[10px] mr-1"></i> select2.min.js
                    </span>
                </div>

                <!-- Card Axios -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl mb-4 border border-teal-100">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">Axios</h3>
                    <p class="text-slate-500 text-xs mb-3">Cliente HTTP para peticiones asíncronas con token CSRF.</p>
                    <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                        <i class="fa-solid fa-check text-[10px] mr-1"></i> axios.min.js
                    </span>
                </div>

                <!-- Card Pusher -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl mb-4 border border-rose-100">
                        <i class="fa-solid fa-satellite-dish"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-base mb-1">Pusher</h3>
                    <p class="text-slate-500 text-xs mb-3">Soporte nativo para WebSockets y eventos en tiempo real.</p>
                    <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">
                        <i class="fa-solid fa-check text-[10px] mr-1"></i> pusher.min.js
                    </span>
                </div>

            </div>
        </div>

        <!-- Demo interactiva: Componente DataTables con Tailwind CSS -->
        <div class="mb-10">
            <x-data-table 
                id="datamindInventoryTable"
                :headers="['#', 'Código', 'Nombre del Recurso', 'Categoría', 'Existencia', 'Estado', 'Acción Rápida']"
                :pageLength="5"
                scrollY="380px"
            >
                <x-slot:title>
                    Catálogo de Muestra de Inventario (65 Registros Activos)
                </x-slot:title>
                
                <x-slot:description>
                    Componente con altura fija, scroll interno suave, opciones de paginación (5, 10, 20, 50, 100, Todos) y barra de acciones inferiores.
                </x-slot:description>

                <!-- 65 Registros cargados -->
                @forelse($items as $item)
                    <tr class="hover:bg-datamind-neutral/80 transition" data-id="{{ $item['id'] }}" data-name="{{ $item['name'] }}">
                        <td class="py-3 px-4 text-xs text-slate-400 font-medium">{{ $item['id'] }}</td>
                        <td class="py-3 px-4 font-mono text-xs font-bold text-datamind-primary">{{ $item['code'] }}</td>
                        <td class="py-3 px-4 font-medium text-slate-900">{{ $item['name'] }}</td>
                        <td class="py-3 px-4 text-slate-500 text-xs">{{ $item['category'] }}</td>
                        <td class="py-3 px-4 font-semibold text-slate-800 text-xs">{{ $item['stock'] }}</td>
                        <td class="py-3 px-4">
                            @if($item['state'] === 'Disponible')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Disponible
                                </span>
                            @elseif($item['state'] === 'Stock Bajo')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Stock Bajo
                                </span>
                            @elseif($item['state'] === 'Agotado')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Agotado
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                    <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span> En Tránsito
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <button type="button" onclick="notifyItem('{{ $item['name'] }}')" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-xs font-medium text-slate-700 transition">
                                <i class="fa-solid fa-eye text-[11px]"></i> Ver
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-400 text-xs">No hay datos disponibles</td>
                    </tr>
                @endforelse
            </x-data-table>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p>DataMind &copy; {{ date('Y') }} - Plantilla Maestra Limpia. Listo para crear controladores, modelos y vistas de tu inventario.</p>
        </div>
    </footer>

    <!-- Scripts Globales -->
    <script src="{{ asset('js/global_js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('js/global_js/all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('js/global_js/toastr.min.js') }}"></script>
    <script src="{{ asset('js/global_js/axios.min.js') }}"></script>
    <script src="{{ asset('js/global_js/select2.min.js') }}"></script>
    <script src="{{ asset('js/global_js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('js/global_js/chart.umd.min.js') }}"></script>
    <script src="{{ asset('js/global_js/pusher.min.js') }}"></script>

    @stack('scripts')

    <!-- Inicializadores y Pruebas -->
    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            if ($.fn.DataTable) {
                $('#sampleTable').DataTable({
                    pageLength: 5,
                    language: {
                        search: "Buscar:",
                        lengthMenu: "Mostrar _MENU_ registros",
                        info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        paginate: {
                            first: "Primero",
                            last: "Último",
                            next: "Siguiente",
                            previous: "Anterior"
                        }
                    }
                });
            }

            // Test SweetAlert2
            $('#btnTestSwal').on('click', function() {
                if (window.Swal) {
                    Swal.fire({
                        title: '¡SweetAlert2 Funcionando!',
                        text: 'Todas las librerías frontend se encuentran cargadas y listas en tu plantilla Laravel 13.',
                        icon: 'success',
                        confirmButtonText: 'Genial',
                        confirmButtonColor: '#0476D9'
                    });
                } else {
                    alert('Swal no está definido');
                }
            });

            // Test Toastr
            $('#btnTestToastr').on('click', function() {
                if (window.toastr) {
                    toastr.info('Notificación Toastr ejecutada correctamente.', 'DataMind Plantilla', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 3000
                    });
                } else {
                    alert('Toastr no está definido');
                }
            });

            // Test Axios
            $('#btnTestAxios').on('click', function() {
                if (window.axios) {
                    toastr.success('Librería Axios lista para llamadas API asíncronas.', 'Axios OK');
                } else {
                    alert('Axios no está definido');
                }
            });
        });

        function notifyItem(nombre) {
            if (window.Swal) {
                Swal.fire({
                    title: nombre,
                    text: 'Componente activo y operativo en el sistema.',
                    icon: 'info',
                    confirmButtonColor: '#0476D9'
                });
            }
        }
    </script>
</body>
</html>
