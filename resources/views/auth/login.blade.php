<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-datamind-neutral">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DataMind Studio - Iniciar Sesión</title>

    <!-- CSS Globales / Plugins Locales (Zero CDNs) -->
    <link rel="stylesheet" href="{{ asset('css/global_styles/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/global_styles/toastr.min.css') }}">
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
<body class="min-h-full flex flex-col justify-between text-slate-800 antialiased selection:bg-datamind-primary selection:text-white">

    <!-- Navbar Superior Minimalista -->
    <header class="bg-white border-b border-datamind-light/60 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-datamind-gradient flex items-center justify-center text-white shadow-md font-bold text-xl">
                    <i class="fa-solid fa-brain"></i>
                </div>
                <div>
                    <span class="text-lg font-bold tracking-tight text-slate-900">DATAMIND <span class="text-datamind-primary">STUDIO</span></span>
                    <span class="ml-2 text-[11px] font-semibold px-2.5 py-0.5 rounded-full badge-datamind">
                        Acceso de Usuario
                    </span>
                </div>
            </a>

            <a href="{{ url('/') }}" class="text-xs font-semibold px-3 py-1.5 rounded-lg text-slate-700 hover:text-datamind-primary hover:bg-datamind-neutral border border-slate-200 transition flex items-center gap-1.5">
                <i class="fa-solid fa-house text-datamind-secondary"></i> Volver al Inicio
            </a>
        </div>
    </header>

    <!-- Contenedor Central de Login / Registro -->
    <main class="flex-1 flex items-center justify-center px-4 py-10">
        <div class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-12 bg-white rounded-3xl border border-datamind-light/70 shadow-lg overflow-hidden">

            <!-- Panel Izquierdo Informativo (5/12) -->
            <div class="md:col-span-5 bg-datamind-hero text-white p-8 flex flex-col justify-between">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-white/15 text-[#99D0F2] border border-[#99D0F2]/30 mb-5">
                        <i class="fa-solid fa-shield-halved"></i> Sesiones Privadas por Usuario
                    </span>
                    <h1 class="text-2xl font-extrabold tracking-tight leading-snug mb-3">
                        Tu Analista de Datos IA con Memoria Personal
                    </h1>
                    <p class="text-xs text-white/85 leading-relaxed mb-6">
                        Inicia sesión para guardar automáticamente tu historial de conversaciones, retomar análisis SQL previos, exportar reportes Excel y crear gráficos interactivos personalizados.
                    </p>

                    <ul class="space-y-3 text-xs text-white/90">
                        <li class="flex items-center gap-2.5">
                            <span class="h-6 w-6 rounded-lg bg-white/15 flex items-center justify-center text-[#99D0F2] shrink-0">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </span>
                            <span>Historial de conversaciones aislado por cada usuario</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="h-6 w-6 rounded-lg bg-white/15 flex items-center justify-center text-[#99D0F2] shrink-0">
                                <i class="fa-solid fa-chart-column"></i>
                            </span>
                            <span>Constructor interactivo de gráficos detallados</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <span class="h-6 w-6 rounded-lg bg-white/15 flex items-center justify-center text-[#99D0F2] shrink-0">
                                <i class="fa-solid fa-database"></i>
                            </span>
                            <span>Conexión universal PostgreSQL y MariaDB/MySQL</span>
                        </li>
                    </ul>
                </div>

                <!-- Tarjeta de Acceso Rápido Demo -->
                <div class="mt-8 pt-5 border-t border-white/20">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-[#99D0F2] mb-2">
                        Cuentas de Demostración Listas:
                    </p>
                    <div class="flex flex-col gap-2">
                        <button type="button" onclick="fillDemoCredentials('admin@datamind.local', 'datamind2026')"
                            class="w-full text-left px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 transition flex items-center justify-between text-xs cursor-pointer">
                            <span><i class="fa-solid fa-user-shield mr-1.5 text-[#99D0F2]"></i> <strong>Admin:</strong> admin@datamind.local</span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-white/20 font-semibold">Usar</span>
                        </button>
                        <button type="button" onclick="fillDemoCredentials('analista@datamind.local', 'datamind2026')"
                            class="w-full text-left px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 transition flex items-center justify-between text-xs cursor-pointer">
                            <span><i class="fa-solid fa-user-tie mr-1.5 text-[#99D0F2]"></i> <strong>Analista:</strong> analista@datamind.local</span>
                            <span class="text-[10px] px-2 py-0.5 rounded bg-white/20 font-semibold">Usar</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Panel Derecho de Formularios (7/12) -->
            <div class="md:col-span-7 p-8 flex flex-col justify-center">

                <!-- Pestañas Iniciar Sesión / Crear Cuenta -->
                <div class="flex rounded-xl bg-datamind-neutral p-1 border border-datamind-light/60 mb-6">
                    <button type="button" id="tabLoginBtn" onclick="switchAuthTab('login')"
                        class="flex-1 py-2 text-xs font-bold rounded-lg bg-datamind-primary text-white shadow-xs transition cursor-pointer">
                        <i class="fa-solid fa-right-to-bracket mr-1"></i> Iniciar Sesión
                    </button>
                    <button type="button" id="tabRegisterBtn" onclick="switchAuthTab('register')"
                        class="flex-1 py-2 text-xs font-bold rounded-lg text-slate-600 hover:text-datamind-primary transition cursor-pointer">
                        <i class="fa-solid fa-user-plus mr-1"></i> Crear Nueva Cuenta
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700">
                        <i class="fa-solid fa-circle-exclamation mr-1"></i> {{ $errors->first() }}
                    </div>
                @endif

                <!-- Formulario 1: Iniciar Sesión -->
                <form id="loginFormPanel" method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Correo Electrónico</label>
                        <div class="relative">
                            <i class="fa-solid fa-envelope absolute left-3.5 top-3 text-xs text-datamind-secondary"></i>
                            <input type="email" id="loginEmail" name="email" value="{{ old('email', 'admin@datamind.local') }}" required autofocus
                                class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-datamind-light bg-datamind-neutral/40 text-xs text-slate-800 input-datamind"
                                placeholder="usuario@datamind.local">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Contraseña</label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3.5 top-3 text-xs text-datamind-secondary"></i>
                            <input type="password" id="loginPassword" name="password" value="datamind2026" required
                                class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-datamind-light bg-datamind-neutral/40 text-xs text-slate-800 input-datamind"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs text-slate-600">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" checked class="rounded accent-[#0476D9]">
                            <span>Mantener sesión iniciada</span>
                        </label>
                        <span class="text-[11px] text-datamind-secondary">Clave demo: <code>datamind2026</code></span>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl btn-datamind-primary text-xs font-bold flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Entrar a DataMind Studio
                    </button>
                </form>

                <!-- Formulario 2: Crear Cuenta Nueva -->
                <form id="registerFormPanel" method="POST" action="{{ route('register.submit') }}" class="space-y-4 hidden">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre Completo</label>
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-3.5 top-3 text-xs text-datamind-secondary"></i>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-datamind-light bg-datamind-neutral/40 text-xs text-slate-800 input-datamind"
                                placeholder="Ej: Manrique Recinos">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Correo Electrónico</label>
                        <div class="relative">
                            <i class="fa-solid fa-envelope absolute left-3.5 top-3 text-xs text-datamind-secondary"></i>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-datamind-light bg-datamind-neutral/40 text-xs text-slate-800 input-datamind"
                                placeholder="tu.correo@empresa.com">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Contraseña (mínimo 6 caracteres)</label>
                        <div class="relative">
                            <i class="fa-solid fa-key absolute left-3.5 top-3 text-xs text-datamind-secondary"></i>
                            <input type="password" name="password" required minlength="6"
                                class="w-full pl-9 pr-3.5 py-2.5 rounded-xl border border-datamind-light bg-datamind-neutral/40 text-xs text-slate-800 input-datamind"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl btn-datamind-primary text-xs font-bold flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-user-check"></i> Crear Cuenta e Iniciar Sesión
                    </button>
                </form>

            </div>
        </div>
    </main>

    <footer class="py-4 text-center text-xs text-slate-500">
        DataMind Studio v1.0.5 &bull; Plataforma Open-Source de Analítica IA Local
    </footer>

    <script src="{{ asset('js/global_js/all.min.js') }}"></script>
    <script>
        function switchAuthTab(tab) {
            const loginForm = document.getElementById('loginFormPanel');
            const registerForm = document.getElementById('registerFormPanel');
            const tabLoginBtn = document.getElementById('tabLoginBtn');
            const tabRegisterBtn = document.getElementById('tabRegisterBtn');

            if (tab === 'register') {
                loginForm.classList.add('hidden');
                registerForm.classList.remove('hidden');
                tabRegisterBtn.className = 'flex-1 py-2 text-xs font-bold rounded-lg bg-datamind-primary text-white shadow-xs transition cursor-pointer';
                tabLoginBtn.className = 'flex-1 py-2 text-xs font-bold rounded-lg text-slate-600 hover:text-datamind-primary transition cursor-pointer';
            } else {
                registerForm.classList.add('hidden');
                loginForm.classList.remove('hidden');
                tabLoginBtn.className = 'flex-1 py-2 text-xs font-bold rounded-lg bg-datamind-primary text-white shadow-xs transition cursor-pointer';
                tabRegisterBtn.className = 'flex-1 py-2 text-xs font-bold rounded-lg text-slate-600 hover:text-datamind-primary transition cursor-pointer';
            }
        }

        function fillDemoCredentials(email, password) {
            switchAuthTab('login');
            document.getElementById('loginEmail').value = email;
            document.getElementById('loginPassword').value = password;
            document.getElementById('loginFormPanel').submit();
        }
    </script>
</body>
</html>
