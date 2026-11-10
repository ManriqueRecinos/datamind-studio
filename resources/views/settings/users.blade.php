<x-app-layout
    title="Gestión de Usuarios & Auditoría IA"
    ecosystem="settings"
    ecosystemLabel="Ecosistema de Configuración & Apariencia"
    ecosystemIcon="fa-gear"
    activeModule="Gestión de Usuarios"
    activeContext="Colaboradores y Auditoría"
    :isOnline="$isOnline ?? false"
    :fullHeight="true">

    <!-- Layout Monolítico Recto de 2 Columnas Adosadas (Sin Bordes Redondeados) -->
    <main class="flex-1 w-full flex overflow-hidden rounded-none">

        <!-- Sidebar Izquierdo: Opciones del Ecosistema desde Base de Datos -->
        <aside class="hidden lg:flex flex-col w-80 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">
            <x-ecosystem-sidebar ecosystemKey="settings" />

            <div class="p-3.5 border-b border-slate-200 bg-white">
                <button type="button" onclick="document.getElementById('modalCreateUser').classList.remove('hidden')" class="w-full text-xs font-semibold px-3 py-2 border border-datamind-primary bg-datamind-primary text-white hover:opacity-90 transition flex items-center justify-center gap-1.5 cursor-pointer rounded-none">
                    <i class="fa-solid fa-user-plus"></i> Registrar Usuario
                </button>
            </div>

            <!-- Widget Informativo Recto -->
            <div class="p-3.5 bg-slate-50/50 text-xs text-slate-600">
                <div class="flex items-center gap-1.5 font-bold text-slate-800 mb-1">
                    <i class="fa-solid fa-users text-datamind-primary"></i> Control de Acceso Unificado
                </div>
                <p class="leading-relaxed text-[11px] text-slate-500">
                    A cada usuario se le asocia 1 único rol maestro que determina sus privilegios de navegación, permisos en cada vista y políticas aplicadas por la IA.
                </p>
            </div>
        </aside>

        <!-- Contenido Central: Tabla de Usuarios y Auditoría IA Rectos Adosados -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Barra de Pestañas Horizontales de Navegación Rápida -->
            @include('settings.partials.settings-nav-tabs', ['activeTab' => 'users'])

            <div class="flex-1 p-6 overflow-y-auto custom-scrollbar flex flex-col gap-6 bg-slate-50/30">

            @if(session('success'))
                <div class="p-3 border border-emerald-300 bg-emerald-50 text-emerald-800 text-xs flex items-center gap-2 rounded-none">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-3 border border-red-300 bg-red-50 text-red-800 text-xs flex items-center gap-2 rounded-none">
                    <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- SECCIÓN 1: Directorio de Usuarios -->
            <x-panel title="Directorio Corporativo de Usuarios" icon="fa-users">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700 border border-slate-200 rounded-none">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="p-3">Usuario</th>
                                <th class="p-3">Correo Electrónico</th>
                                <th class="p-3">Rol Asignado</th>
                                <th class="p-3">Estado</th>
                                <th class="p-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($users as $user)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3 font-semibold text-slate-900 flex items-center gap-2">
                                        <div class="h-8 w-8 rounded-none bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 font-bold text-xs">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <span class="block">{{ $user->name }}</span>
                                            <span class="text-[10px] text-slate-400 font-normal">ID: {{ $user->id }}</span>
                                        </div>
                                    </td>
                                    <td class="p-3 font-mono text-slate-600">{{ $user->email }}</td>
                                    <td class="p-3">
                                        @if($user->role)
                                            <span class="text-[11px] font-bold px-2 py-0.5 border border-slate-200 bg-slate-50 text-slate-700 rounded-none">
                                                <i class="fa-solid fa-shield-halved text-datamind-primary mr-1 text-[10px]"></i> {{ $user->role->name }}
                                            </span>
                                        @else
                                            <span class="text-[10px] font-bold px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-none">
                                                Sin Rol Asignado
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        @if($user->is_active)
                                            <span class="text-[10px] font-bold px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-none">
                                                <i class="fa-solid fa-check text-[9px] mr-0.5"></i> Activo
                                            </span>
                                        @else
                                            <span class="text-[10px] font-bold px-2 py-0.5 bg-red-50 text-red-700 border border-red-200 rounded-none">
                                                <i class="fa-solid fa-ban text-[9px] mr-0.5"></i> Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Ver Historial IA del Usuario -->
                                            <button type="button"
                                                    onclick="openAiHistoryModal({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                                    class="p-1.5 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-none cursor-pointer"
                                                    title="Ver Historial de Consultas IA">
                                                <i class="fa-solid fa-robot text-datamind-primary"></i>
                                            </button>

                                            <!-- Asignar / Cambiar Rol Directo -->
                                            <button type="button"
                                                    onclick="openAssignRoleModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->role_id ?? '' }}')"
                                                    class="p-1.5 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-none cursor-pointer"
                                                    title="Asignar Rol">
                                                <i class="fa-solid fa-user-shield text-slate-600"></i>
                                            </button>

                                            <!-- Editar Datos -->
                                            <button type="button"
                                                    onclick="openEditUserModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ $user->email }}', '{{ $user->role_id ?? '' }}', {{ $user->is_active ? 'true' : 'false' }})"
                                                    class="p-1.5 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-none cursor-pointer"
                                                    title="Editar Información">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            <!-- Cambiar Contraseña -->
                                            <button type="button"
                                                    onclick="openPasswordModal({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                                    class="p-1.5 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-none cursor-pointer"
                                                    title="Cambiar Contraseña">
                                                <i class="fa-solid fa-key text-amber-600"></i>
                                            </button>

                                            @if(auth()->id() !== $user->id)
                                                <!-- Inactivar / Activar Toggle -->
                                                <form method="POST" action="{{ route('settings.users.toggle', $user->id) }}" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="p-1.5 border border-slate-200 hover:bg-slate-50 {{ $user->is_active ? 'text-amber-600' : 'text-emerald-600' }} rounded-none cursor-pointer"
                                                            title="{{ $user->is_active ? 'Inactivar Cuenta' : 'Activar Cuenta' }}">
                                                        <i class="fa-solid {{ $user->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i>
                                                    </button>
                                                </form>

                                                <!-- Eliminar Usuario -->
                                                <form method="POST" action="{{ route('settings.users.destroy', $user->id) }}" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar al usuario {{ $user->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 border border-red-200 hover:bg-red-50 text-red-600 rounded-none cursor-pointer" title="Eliminar Usuario">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-slate-400">No se encontraron usuarios registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-panel>

            </div>
        </div>
    </main>

    <!-- Modal 1: Registrar Nuevo Usuario -->
    <x-modal id="modalCreateUser" title="Registrar Nuevo Colaborador" icon="fa-user-plus">
        <form method="POST" action="{{ route('settings.users.store') }}" id="formCreateUser">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre Completo</label>
                    <input type="text" name="name" required placeholder="Ej: Carlos Méndez" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Correo Electrónico</label>
                    <input type="email" name="email" required placeholder="usuario@datamind.com" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Contraseña Inicial</label>
                    <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Rol Asignado (1 Solo Rol)</label>
                    <select name="role_id" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                        <option value="">-- Sin Rol (Sin Privilegios) --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->slug }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="chkActiveNew" value="1" checked class="rounded-none border-slate-300 text-datamind-primary">
                    <label for="chkActiveNew" class="text-xs text-slate-700 font-semibold cursor-pointer">Cuenta Activa (Permitir inicio de sesión)</label>
                </div>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" onclick="document.getElementById('modalCreateUser').classList.add('hidden')" class="text-xs px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-none cursor-pointer">
                Cancelar
            </button>
            <button type="submit" form="formCreateUser" class="text-xs px-4 py-2 border border-datamind-primary bg-datamind-primary text-white hover:opacity-90 rounded-none cursor-pointer font-semibold">
                Guardar Usuario
            </button>
        </x-slot:footer>
    </x-modal>

    <!-- Modal 2: Editar Usuario -->
    <x-modal id="modalEditUser" title="Editar Información de Usuario" icon="fa-pen-to-square">
        <form method="POST" id="formEditUser">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre Completo</label>
                    <input type="text" name="name" id="editUserName" required class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Correo Electrónico</label>
                    <input type="email" name="email" id="editUserEmail" required class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Rol Asignado</label>
                    <select name="role_id" id="editUserRoleId" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                        <option value="">-- Sin Rol --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->slug }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="editUserIsActive" value="1" class="rounded-none border-slate-300 text-datamind-primary">
                    <label for="editUserIsActive" class="text-xs text-slate-700 font-semibold cursor-pointer">Cuenta Activa</label>
                </div>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" onclick="document.getElementById('modalEditUser').classList.add('hidden')" class="text-xs px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-none cursor-pointer">
                Cancelar
            </button>
            <button type="submit" form="formEditUser" class="text-xs px-4 py-2 border border-datamind-primary bg-datamind-primary text-white hover:opacity-90 rounded-none cursor-pointer font-semibold">
                Actualizar Cambios
            </button>
        </x-slot:footer>
    </x-modal>

    <!-- Modal 3: Asignar 1 Rol Directo -->
    <x-modal id="modalAssignRole" title="Asignar Rol al Usuario" icon="fa-user-shield">
        <form method="POST" id="formAssignRole">
            @csrf
            <div class="space-y-4">
                <p class="text-xs text-slate-600">
                    Selecciona el rol único que se asociará a <strong id="assignRoleUserName" class="text-slate-900"></strong>:
                </p>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Rol Disponible</label>
                    <select name="role_id" id="assignRoleSelect" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                        <option value="">-- Sin Rol (Revocar permisos) --</option>
                        @foreach($roles as $r)
                            <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->slug }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" onclick="document.getElementById('modalAssignRole').classList.add('hidden')" class="text-xs px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-none cursor-pointer">
                Cancelar
            </button>
            <button type="submit" form="formAssignRole" class="text-xs px-4 py-2 border border-datamind-primary bg-datamind-primary text-white hover:opacity-90 rounded-none cursor-pointer font-semibold">
                Aplicar Rol
            </button>
        </x-slot:footer>
    </x-modal>

    <!-- Modal 4: Cambiar Contraseña -->
    <x-modal id="modalPassword" title="Restablecer Contraseña" icon="fa-key">
        <form method="POST" id="formPassword">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <p class="text-xs text-slate-600">
                    Ingresa la nueva contraseña para <strong id="pwdUserName" class="text-slate-900"></strong>:
                </p>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nueva Contraseña</label>
                    <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" required minlength="6" placeholder="Repite la contraseña" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                </div>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" onclick="document.getElementById('modalPassword').classList.add('hidden')" class="text-xs px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-none cursor-pointer">
                Cancelar
            </button>
            <button type="submit" form="formPassword" class="text-xs px-4 py-2 border border-amber-500 bg-amber-500 text-white hover:opacity-90 rounded-none cursor-pointer font-semibold">
                Actualizar Contraseña
            </button>
        </x-slot:footer>
    </x-modal>

    <!-- Modal 5: Historial y Auditoría de Consultas IA del Usuario -->
    <x-modal id="modalAiHistory" title="Auditoría de Consultas IA" icon="fa-robot">
        <div id="aiHistoryLoading" class="py-12 text-center text-xs text-slate-500">
            <i class="fa-solid fa-spinner fa-spin text-datamind-primary text-xl mb-2 block"></i>
            Consultando historial de IA en el microservicio...
        </div>

        <div id="aiHistoryContent" class="hidden space-y-4">
            <div class="p-3 border border-slate-200 bg-slate-50/70 flex items-center justify-between text-xs rounded-none">
                <div>
                    <span class="font-bold text-slate-900" id="aiHistoryName"></span>
                    <span class="text-slate-500 block text-[11px]" id="aiHistoryEmail"></span>
                </div>
                <span class="px-2 py-0.5 border border-slate-200 bg-white text-[10px] font-bold text-slate-700" id="aiHistoryRole"></span>
            </div>

            <div class="max-h-72 overflow-y-auto custom-scrollbar border border-slate-200 rounded-none">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="p-2.5">Sesión / Conversación</th>
                            <th class="p-2.5">Mensajes</th>
                            <th class="p-2.5">Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="aiHistoryTableBody" class="divide-y divide-slate-100 bg-white text-[11px]">
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" onclick="document.getElementById('modalAiHistory').classList.add('hidden')" class="text-xs px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-none cursor-pointer">
                Cerrar
            </button>
        </x-slot:footer>
    </x-modal>

    <script>
        function openEditUserModal(id, name, email, roleId, isActive) {
            document.getElementById('formEditUser').action = `/settings/users/${id}`;
            document.getElementById('editUserName').value = name;
            document.getElementById('editUserEmail').value = email;
            document.getElementById('editUserRoleId').value = roleId;
            document.getElementById('editUserIsActive').checked = isActive;
            document.getElementById('modalEditUser').classList.remove('hidden');
        }

        function openAssignRoleModal(id, name, roleId) {
            document.getElementById('formAssignRole').action = `/settings/users/${id}/role`;
            document.getElementById('assignRoleUserName').textContent = name;
            document.getElementById('assignRoleSelect').value = roleId;
            document.getElementById('modalAssignRole').classList.remove('hidden');
        }

        function openPasswordModal(id, name) {
            document.getElementById('formPassword').action = `/settings/users/${id}/password`;
            document.getElementById('pwdUserName').textContent = name;
            document.getElementById('modalPassword').classList.remove('hidden');
        }

        async function openAiHistoryModal(id, name) {
            document.getElementById('modalAiHistory').classList.remove('hidden');
            document.getElementById('aiHistoryLoading').classList.remove('hidden');
            document.getElementById('aiHistoryContent').classList.add('hidden');

            try {
                const res = await fetch(`/settings/users/${id}/ai-history`);
                const data = await res.json();

                document.getElementById('aiHistoryLoading').classList.add('hidden');
                document.getElementById('aiHistoryContent').classList.remove('hidden');

                document.getElementById('aiHistoryName').textContent = data.user.name;
                document.getElementById('aiHistoryEmail').textContent = data.user.email;
                document.getElementById('aiHistoryRole').textContent = data.user.role;

                const tbody = document.getElementById('aiHistoryTableBody');
                tbody.innerHTML = '';

                if (!data.sessions || data.sessions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-slate-400">Este usuario aún no ha iniciado conversaciones con la IA.</td></tr>';
                } else {
                    data.sessions.forEach(s => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-slate-50';
                        tr.innerHTML = `
                            <td class="p-2.5 font-semibold text-slate-800">${s.title || 'Conversación sin título'}</td>
                            <td class="p-2.5">${s.message_count || 1}</td>
                            <td class="p-2.5 text-slate-400 font-mono">${(s.created_at || '').substring(0, 16)}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (err) {
                document.getElementById('aiHistoryLoading').innerHTML = '<span class="text-red-500">Error al consultar el historial del usuario.</span>';
            }
        }
    </script>
</x-app-layout>
