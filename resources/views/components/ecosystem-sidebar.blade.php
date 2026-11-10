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
        <div class="space-y-3">
            @foreach($ecosystem->modules as $mod)
                <div>
                    <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center gap-1.5">
                        <i class="fa-solid {{ $mod->icon }} text-datamind-primary text-[10px]"></i> {{ $mod->name }}
                    </h4>
                    <div class="space-y-1">
                        @foreach($mod->interfaces as $iface)
                            @if($iface->action_type === 'trigger')
                                <button type="button"
                                        id="{{ ltrim($iface->route_or_action, '#') }}"
                                        class="w-full text-left p-2 border border-slate-200 hover:border-slate-400 hover:bg-slate-50 text-xs text-slate-700 hover:text-slate-900 transition flex items-center justify-between rounded-none cursor-pointer">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid {{ $iface->icon }} text-datamind-primary text-xs"></i>
                                        <span>{{ $iface->name }}</span>
                                    </span>
                                    @if($iface->badge_label)
                                        <span class="text-[9px] font-bold px-1 py-0.2 rounded-none bg-datamind-neutral text-datamind-dark border border-slate-200">{{ $iface->badge_label }}</span>
                                    @endif
                                </button>
                            @elseif($iface->action_type === 'modal')
                                <button type="button"
                                        id="{{ ltrim($iface->route_or_action, '#') }}"
                                        class="w-full text-left p-2 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-800 transition flex items-center justify-between rounded-none cursor-pointer">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid {{ $iface->icon }} text-datamind-primary text-xs"></i>
                                        <span>{{ $iface->name }}</span>
                                    </span>
                                    @if($iface->badge_label)
                                        <span class="text-[9px] font-bold px-1 py-0.2 rounded-none bg-datamind-primary text-white">{{ $iface->badge_label }}</span>
                                    @endif
                                </button>
                            @elseif($iface->action_type === 'anchor')
                                <a href="{{ $iface->route_or_action }}"
                                   class="block p-2 border border-slate-200 hover:border-slate-400 hover:bg-slate-50 text-xs text-slate-700 hover:text-slate-900 transition flex items-center justify-between rounded-none">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid {{ $iface->icon }} text-datamind-primary text-xs"></i>
                                        <span>{{ $iface->name }}</span>
                                    </span>
                                    @if($iface->badge_label)
                                        <span class="text-[9px] font-bold px-1 py-0.2 rounded-none bg-slate-100 text-slate-600 border border-slate-200">{{ $iface->badge_label }}</span>
                                    @endif
                                </a>
                            @else
                                <a href="{{ route($iface->route_or_action) }}"
                                   class="block p-2 border border-slate-200 hover:border-slate-400 hover:bg-slate-50 text-xs text-slate-700 hover:text-slate-900 transition flex items-center justify-between rounded-none">
                                    <span class="flex items-center gap-2">
                                        <i class="fa-solid {{ $iface->icon }} text-datamind-primary text-xs"></i>
                                        <span>{{ $iface->name }}</span>
                                    </span>
                                    @if($iface->badge_label)
                                        <span class="text-[9px] font-bold px-1 py-0.2 rounded-none bg-slate-100 text-slate-600 border border-slate-200">{{ $iface->badge_label }}</span>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
