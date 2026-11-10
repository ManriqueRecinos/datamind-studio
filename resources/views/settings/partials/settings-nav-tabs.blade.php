@props([
    'activeTab' => 'appearance' // 'appearance' | 'users' | 'roles'
])

<nav class="w-full flex items-center border-b border-slate-200 bg-white px-6 shrink-0 rounded-none overflow-x-auto custom-scrollbar">
    <div class="flex items-center gap-1 -mb-px">
        <!-- Pestaña 1: Apariencia & Paletas -->
        <a href="{{ route('settings.index') }}"
           class="inline-flex items-center gap-2 px-4 py-3 text-xs font-bold border-b-2 transition rounded-none whitespace-nowrap {{ $activeTab === 'appearance' ? 'border-datamind-primary text-datamind-primary bg-slate-50/50' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
            <i class="fa-solid fa-swatchbook text-xs"></i>
            <span>Apariencia & Paletas</span>
        </a>

        <!-- Pestaña 2: Directorio de Usuarios -->
        <a href="{{ route('settings.users.index') }}"
           class="inline-flex items-center gap-2 px-4 py-3 text-xs font-bold border-b-2 transition rounded-none whitespace-nowrap {{ $activeTab === 'users' ? 'border-datamind-primary text-datamind-primary bg-slate-50/50' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
            <i class="fa-solid fa-users text-xs"></i>
            <span>Gestión de Usuarios</span>
        </a>

        <!-- Pestaña 3: Roles & Permisos (RBAC) -->
        <a href="{{ route('settings.roles.index') }}"
           class="inline-flex items-center gap-2 px-4 py-3 text-xs font-bold border-b-2 transition rounded-none whitespace-nowrap {{ $activeTab === 'roles' ? 'border-datamind-primary text-datamind-primary bg-slate-50/50' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
            <i class="fa-solid fa-shield-halved text-xs"></i>
            <span>Roles & Permisos</span>
        </a>
    </div>
</nav>
