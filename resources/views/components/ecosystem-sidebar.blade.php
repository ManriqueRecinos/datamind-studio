@props([
    'ecosystemKey' => 'chat',
    'activeInterface' => null,
])

@php
    $ecosystem = null;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('ecosystems')) {
            $ecosystem = \App\Models\Ecosystem::with(['modules.interfaces'])
                ->where('key', $ecosystemKey)
                ->first();
        }
    } catch (\Throwable $e) {
        $ecosystem = null;
    }
@endphp

@if($ecosystem)
    <div class="bg-white border-b border-slate-200 p-3.5 flex flex-col gap-3 rounded-none">
        <!-- Encabezado Recto del Ecosistema en el Sidebar -->
        <div class="flex items-center justify-between pb-2 border-b border-slate-100">
            <span class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                <i class="fa-solid {{ $ecosystem->icon }} text-datamind-primary"></i> {{ $ecosystem->short_name }}
            </span>
            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-none border border-slate-200 bg-slate-50 text-slate-600">
                {{ $ecosystem->badge_text ?? 'Activo' }}
            </span>
        </div>

        <!-- Módulos e Interfaces Cargados Dinámicamente desde Base de Datos -->
        <div class="space-y-1">
            @foreach($ecosystem->modules as $mod)
                @php
                    $activeInterfaces = $mod->interfaces->where('is_active', true);
                @endphp
                @if($activeInterfaces->isNotEmpty())
                    @foreach($activeInterfaces as $iface)
                        @if($iface->action_type === 'trigger' || $iface->action_type === 'modal')
                            <button type="button"
                                    id="{{ ltrim($iface->route_or_action, '#') }}"
                                    class="w-full text-left px-2.5 py-1.5 border-l-2 border-l-transparent text-xs text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-all duration-150 flex items-center justify-between cursor-pointer">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid {{ $iface->icon }} text-datamind-primary text-xs"></i>
                                    <span>{{ $iface->name }}</span>
                                </span>
                                @if($iface->badge_label)
                                    <span class="text-[9px] font-semibold text-slate-400">{{ $iface->badge_label }}</span>
                                @endif
                            </button>
                        @elseif($iface->action_type === 'anchor')
                            <a href="{{ $iface->route_or_action }}"
                               class="px-2.5 py-1.5 border-l-2 border-l-transparent text-xs text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-all duration-150 flex items-center justify-between">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid {{ $iface->icon }} text-datamind-primary text-xs"></i>
                                    <span>{{ $iface->name }}</span>
                                </span>
                                @if($iface->badge_label)
                                    <span class="text-[9px] font-semibold text-slate-400">{{ $iface->badge_label }}</span>
                                @endif
                            </a>
                        @else
                            @php
                                $ifaceUrl = route($iface->route_or_action);
                                $isActive = request()->url() === $ifaceUrl || request()->routeIs($iface->route_or_action);
                            @endphp
                            <a href="{{ $ifaceUrl }}"
                               class="{{ $isActive ? 'px-2.5 py-1.5 border-l-2 border-l-datamind-primary bg-datamind-neutral/40 text-xs font-semibold text-datamind-primary transition-all duration-150 flex items-center justify-between' : 'px-2.5 py-1.5 border-l-2 border-l-transparent text-xs text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-all duration-150 flex items-center justify-between' }}">
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid {{ $iface->icon }} text-datamind-primary text-xs"></i>
                                    <span>{{ $iface->name }}</span>
                                </span>
                                @if($iface->badge_label)
                                    <span class="text-[9px] font-semibold text-slate-400">{{ $iface->badge_label }}</span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if($ecosystemKey === 'vectorization' && Route::has('training.neural_lab.index'))
                @php
                    $nlUrl = route('training.neural_lab.index');
                    $nlActive = request()->url() === $nlUrl || request()->routeIs('training.neural_lab.*');
                @endphp
                <a href="{{ $nlUrl }}"
                   class="{{ $nlActive ? 'px-2.5 py-1.5 border-l-2 border-l-datamind-primary bg-datamind-neutral/40 text-xs font-semibold text-datamind-primary transition-all duration-150 flex items-center justify-between' : 'px-2.5 py-1.5 border-l-2 border-l-transparent text-xs text-slate-600 hover:text-slate-900 hover:bg-slate-50 transition-all duration-150 flex items-center justify-between' }}">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-brain text-datamind-primary text-xs"></i>
                        <span>Laboratorio Neuronal</span>
                    </span>
                </a>
            @endif
        </div>
    </div>
@endif
