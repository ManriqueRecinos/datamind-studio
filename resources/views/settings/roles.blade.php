<x-app-layout
    title="Roles & Permisos de Acceso"
    ecosystem="settings"
    ecosystemLabel="Ecosistema de Configuración & Apariencia"
    ecosystemIcon="fa-gear"
    activeModule="Roles & Permisos"
    activeContext="Privilegios de Vistas y Acciones"
    :isOnline="$isOnline ?? false"
    :fullHeight="true">

    <!-- Layout Monolítico Recto de 2 Columnas Adosadas (Sin Bordes Redondeados) -->
    <main class="flex-1 w-full flex overflow-hidden rounded-none">

        <!-- Sidebar Izquierdo: Opciones del Ecosistema desde Base de Datos -->
        <aside class="hidden lg:flex flex-col w-80 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">
            <x-ecosystem-sidebar ecosystemKey="settings" />

            <div class="p-3.5 border-b border-slate-200 bg-white">
                <button type="button" onclick="document.getElementById('modalCreateRole').classList.remove('hidden')" class="w-full text-xs font-semibold px-3 py-2 border border-datamind-primary bg-datamind-primary text-white hover:opacity-90 transition flex items-center justify-center gap-1.5 cursor-pointer rounded-none">
                    <i class="fa-solid fa-plus"></i> Registrar Nuevo Rol
                </button>
            </div>

            <!-- Widget Informativo Recto -->
            <div class="p-3.5 bg-slate-50/50 text-xs text-slate-600">
                <div class="flex items-center gap-1.5 font-bold text-slate-800 mb-1">
                    <i class="fa-solid fa-user-lock text-datamind-primary"></i> Gobernanza por Vistas
                </div>
                <p class="leading-relaxed text-[11px] text-slate-500">
                    Define con exactitud qué vistas están autorizadas y qué acciones (exportar, entrenar, vectorizar) puede ejecutar cada perfil.
                </p>
            </div>
        </aside>

        <!-- Contenido Central: Panel de Roles y Matriz de Permisos por Vistas -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Barra de Pestañas Horizontales de Navegación Rápida -->
            @include('settings.partials.settings-nav-tabs', ['activeTab' => 'roles'])

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

            <!-- SECCIÓN 1: Directorio de Roles -->
            <x-panel title="Perfiles y Roles de Usuario" icon="fa-shield-halved">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-700 border border-slate-200 rounded-none">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="p-3">Nombre del Rol</th>
                                <th class="p-3">Identificador (Slug)</th>
                                <th class="p-3">Usuarios Asignados</th>
                                <th class="p-3">Estado</th>
                                <th class="p-3 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($roles as $role)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-3">
                                        <div class="font-bold text-slate-900">{{ $role->name }}</div>
                                        <p class="text-[11px] text-slate-400 font-normal">{{ $role->description ?? 'Sin descripción' }}</p>
                                    </td>
                                    <td class="p-3 font-mono text-slate-600">{{ $role->slug }}</td>
                                    <td class="p-3">
                                        <button type="button"
                                                onclick="openRoleUsersModal({{ $role->id }}, '{{ addslashes($role->name) }}')"
                                                class="px-2.5 py-1 border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold rounded-none cursor-pointer text-[11px] flex items-center gap-1.5"
                                                title="Ver usuarios asignados">
                                            <i class="fa-solid fa-users text-datamind-primary text-[10px]"></i>
                                            <span>{{ $role->users->count() }} colaboradores</span>
                                        </button>
                                    </td>
                                    <td class="p-3">
                                        @if($role->is_active)
                                            <span class="text-[10px] font-bold px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-none">
                                                Activo
                                            </span>
                                        @else
                                            <span class="text-[10px] font-bold px-2 py-0.5 bg-red-50 text-red-700 border border-red-200 rounded-none">
                                                Inactivo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <!-- Configurar Permisos de Vistas -->
                                            <button type="button"
                                                    onclick="openPermissionsModal({{ $role->id }}, '{{ addslashes($role->name) }}', '{{ $role->slug }}', {{ json_encode($role->permissions->pluck('id')) }})"
                                                    class="px-2.5 py-1.5 border border-datamind-primary bg-datamind-primary text-white text-[11px] font-semibold hover:opacity-90 rounded-none cursor-pointer flex items-center gap-1"
                                                    title="Configurar Permisos">
                                                <i class="fa-solid fa-key text-[10px]"></i> Permisos
                                            </button>

                                            <!-- Editar Rol -->
                                            <button type="button"
                                                    onclick="openEditRoleModal({{ $role->id }}, '{{ addslashes($role->name) }}', '{{ addslashes($role->description ?? '') }}', {{ $role->is_active ? 'true' : 'false' }})"
                                                    class="p-1.5 border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-none cursor-pointer"
                                                    title="Editar Rol">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>

                                            @if($role->slug !== 'super-admin')
                                                <!-- Inactivar / Activar Toggle -->
                                                <form method="POST" action="{{ route('settings.roles.toggle', $role->id) }}" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                            class="p-1.5 border border-slate-200 hover:bg-slate-50 {{ $role->is_active ? 'text-amber-600' : 'text-emerald-600' }} rounded-none cursor-pointer"
                                                            title="{{ $role->is_active ? 'Inactivar Rol' : 'Activar Rol' }}">
                                                        <i class="fa-solid {{ $role->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                    </button>
                                                </form>

                                                <!-- Eliminar Rol -->
                                                <form method="POST" action="{{ route('settings.roles.destroy', $role->id) }}" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar el rol {{ $role->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="p-1.5 border border-red-200 hover:bg-red-50 text-red-600 rounded-none cursor-pointer" title="Eliminar Rol">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>

            </div>
        </div>
    </main>

    <!-- Modal 1: Registrar Rol -->
    <x-modal id="modalCreateRole" title="Registrar Nuevo Rol" icon="fa-plus">
        <form method="POST" action="{{ route('settings.roles.store') }}" id="formCreateRole">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre del Rol</label>
                    <input type="text" name="name" required placeholder="Ej: Auditor de Datos" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Descripción</label>
                    <textarea name="description" rows="2" placeholder="Propósito y alcance operativo del rol" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary"></textarea>
                </div>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" onclick="document.getElementById('modalCreateRole').classList.add('hidden')" class="text-xs px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-none cursor-pointer">
                Cancelar
            </button>
            <button type="submit" form="formCreateRole" class="text-xs px-4 py-2 border border-datamind-primary bg-datamind-primary text-white hover:opacity-90 rounded-none cursor-pointer font-semibold">
                Guardar Rol
            </button>
        </x-slot:footer>
    </x-modal>

    <!-- Modal 2: Editar Rol -->
    <x-modal id="modalEditRole" title="Editar Información del Rol" icon="fa-pen-to-square">
        <form method="POST" id="formEditRole">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre del Rol</label>
                    <input type="text" name="name" id="editRoleName" required class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Descripción</label>
                    <textarea name="description" id="editRoleDescription" rows="2" class="w-full text-xs p-2.5 border border-slate-200 bg-white text-slate-800 rounded-none focus:outline-hidden focus:border-datamind-primary"></textarea>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="editRoleIsActive" value="1" class="rounded-none border-slate-300 text-datamind-primary">
                    <label for="editRoleIsActive" class="text-xs text-slate-700 font-semibold cursor-pointer">Rol Habilitado</label>
                </div>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" onclick="document.getElementById('modalEditRole').classList.add('hidden')" class="text-xs px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-none cursor-pointer">
                Cancelar
            </button>
            <button type="submit" form="formEditRole" class="text-xs px-4 py-2 border border-datamind-primary bg-datamind-primary text-white hover:opacity-90 rounded-none cursor-pointer font-semibold">
                Actualizar Rol
            </button>
        </x-slot:footer>
    </x-modal>

    <!-- Modal 3: Matriz de Permisos por Vistas y Opciones -->
    <x-modal id="modalPermissions" title="Configurar Permisos de Vistas y Opciones" icon="fa-key">
        <form method="POST" id="formPermissions">
            @csrf
            <div class="space-y-4">
                <div class="p-3 border border-slate-200 bg-slate-50 flex items-center justify-between text-xs rounded-none">
                    <div>
                        <span>Configurando permisos para:</span>
                        <strong class="font-bold text-slate-900 block text-sm" id="permRoleName"></strong>
                    </div>
                    <span class="text-[10px] font-mono px-2 py-0.5 border border-slate-200 bg-white text-slate-600" id="permRoleSlug"></span>
                </div>

                <div id="superAdminNotice" class="hidden p-3 border border-amber-200 bg-amber-50 text-amber-800 text-xs rounded-none">
                    <i class="fa-solid fa-shield-halved mr-1"></i> El rol Super Administrador posee acceso absoluto y bypass automático a todas las vistas y acciones.
                </div>

                <!-- Grilla de Permisos agrupados por Módulos y Vistas -->
                <div class="space-y-4 max-h-96 overflow-y-auto custom-scrollbar border border-slate-200 p-3 rounded-none">
                    @foreach($groupedPermissions as $moduleName => $perms)
                        <div>
                            <h4 class="text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5 pb-1 border-b border-slate-100">
                                <i class="fa-solid fa-layer-group text-datamind-primary text-[10px]"></i> {{ $moduleName }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach($perms as $perm)
                                    <label class="flex items-start gap-2.5 p-2.5 border border-slate-200 hover:bg-slate-50 transition cursor-pointer rounded-none bg-white">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $perm->id }}"
                                               id="perm_chk_{{ $perm->id }}"
                                               class="mt-0.5 rounded-none border-slate-300 text-datamind-primary focus:ring-0 perm-checkbox">
                                        <div>
                                            <div class="text-xs font-semibold text-slate-800">{{ $perm->name }}</div>
                                            <p class="text-[10px] text-slate-400 leading-tight">{{ $perm->description }}</p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </form>

        <x-slot:footer>
            <button type="button" onclick="document.getElementById('modalPermissions').classList.add('hidden')" class="text-xs px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-none cursor-pointer">
                Cancelar
            </button>
            <button type="submit" form="formPermissions" id="btnSubmitPermissions" class="text-xs px-4 py-2 border border-datamind-primary bg-datamind-primary text-white hover:opacity-90 rounded-none cursor-pointer font-semibold">
                Guardar Permisos
            </button>
        </x-slot:footer>
    </x-modal>

    <!-- Modal 4: Visualizador de Usuarios Asignados al Rol -->
    <x-modal id="modalRoleUsers" title="Colaboradores Asignados al Rol" icon="fa-users">
        <div id="roleUsersLoading" class="py-10 text-center text-xs text-slate-500">
            <i class="fa-solid fa-spinner fa-spin text-datamind-primary text-xl mb-2 block"></i>
            Cargando usuarios...
        </div>

        <div id="roleUsersContent" class="hidden space-y-4">
            <p class="text-xs text-slate-600">Listado de usuarios que poseen el rol <strong id="roleUsersModalTitle" class="text-slate-900"></strong>:</p>
            <div class="max-h-60 overflow-y-auto custom-scrollbar border border-slate-200 rounded-none">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50 border-b border-slate-200 text-[10px] font-bold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th class="p-2.5">Nombre</th>
                            <th class="p-2.5">Correo</th>
                            <th class="p-2.5">Estado</th>
                        </tr>
                    </thead>
                    <tbody id="roleUsersTableBody" class="divide-y divide-slate-100 bg-white text-[11px]">
                    </tbody>
                </table>
            </div>
        </div>

        <x-slot:footer>
            <button type="button" onclick="document.getElementById('modalRoleUsers').classList.add('hidden')" class="text-xs px-3.5 py-2 border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-none cursor-pointer">
                Cerrar
            </button>
        </x-slot:footer>
    </x-modal>

    <script>
        function openEditRoleModal(id, name, description, isActive) {
            document.getElementById('formEditRole').action = `/settings/roles/${id}`;
            document.getElementById('editRoleName').value = name;
            document.getElementById('editRoleDescription').value = description;
            document.getElementById('editRoleIsActive').checked = isActive;
            document.getElementById('modalEditRole').classList.remove('hidden');
        }

        function openPermissionsModal(id, name, slug, assignedPermIds) {
            document.getElementById('formPermissions').action = `/settings/roles/${id}/permissions`;
            document.getElementById('permRoleName').textContent = name;
            document.getElementById('permRoleSlug').textContent = slug;

            const isSuperAdmin = (slug === 'super-admin');
            document.getElementById('superAdminNotice').classList.toggle('hidden', !isSuperAdmin);
            document.getElementById('btnSubmitPermissions').style.display = isSuperAdmin ? 'none' : 'inline-block';

            // Reset and check assigned permissions
            document.querySelectorAll('.perm-checkbox').forEach(cb => {
                const pid = parseInt(cb.value);
                const isChecked = isSuperAdmin || assignedPermIds.includes(pid);
                cb.checked = isChecked;
                cb.disabled = isSuperAdmin;
            });

            document.getElementById('modalPermissions').classList.remove('hidden');
        }

        async function openRoleUsersModal(id, name) {
            document.getElementById('modalRoleUsers').classList.remove('hidden');
            document.getElementById('roleUsersLoading').classList.remove('hidden');
            document.getElementById('roleUsersContent').classList.add('hidden');
            document.getElementById('roleUsersModalTitle').textContent = name;

            try {
                const res = await fetch(`/settings/roles/${id}/users`);
                const data = await res.json();

                document.getElementById('roleUsersLoading').classList.add('hidden');
                document.getElementById('roleUsersContent').classList.remove('hidden');

                const tbody = document.getElementById('roleUsersTableBody');
                tbody.innerHTML = '';

                if (!data.users || data.users.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="p-4 text-center text-slate-400">No hay usuarios asignados a este rol.</td></tr>';
                } else {
                    data.users.forEach(u => {
                        const tr = document.createElement('tr');
                        tr.className = 'hover:bg-slate-50';
                        tr.innerHTML = `
                            <td class="p-2.5 font-semibold text-slate-800">${u.name}</td>
                            <td class="p-2.5 font-mono text-slate-600">${u.email}</td>
                            <td class="p-2.5">
                                <span class="px-1.5 py-0.5 text-[9px] font-bold ${u.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'}">
                                    ${u.is_active ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } catch (err) {
                document.getElementById('roleUsersLoading').innerHTML = '<span class="text-red-500">Error al cargar usuarios.</span>';
            }
        }
    </script>
</x-app-layout>
