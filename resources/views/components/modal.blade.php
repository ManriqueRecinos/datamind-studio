@props([
    'id' => 'customModal',
    'title' => 'Modal',
    'icon' => null,
    'size' => 'max-w-2xl', // max-w-lg, max-w-2xl, max-w-4xl, max-w-6xl
])

<div id="{{ $id }}" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="relative w-full {{ $size }} bg-white border border-slate-300 shadow-2xl flex flex-col max-h-[90vh] rounded-none animate-in fade-in zoom-in-95 duration-150">
        
        <!-- Header del Modal -->
        <div class="px-5 py-3.5 border-b border-slate-200 bg-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                @if($icon)
                    <div class="h-8 w-8 bg-datamind-neutral border border-slate-200 flex items-center justify-center text-datamind-primary text-sm rounded-none">
                        <i class="fa-solid {{ $icon }}"></i>
                    </div>
                @endif
                <div>
                    <h3 class="text-sm font-bold text-slate-900 tracking-tight">
                        {{ $title }}
                    </h3>
                    @if(isset($subtitle))
                        <p class="text-[11px] text-slate-500 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>

            <!-- Botón de Cerrar Recto -->
            <button type="button"
                    onclick="document.getElementById('{{ $id }}').classList.add('hidden')"
                    class="h-8 w-8 border border-slate-200 hover:border-slate-400 hover:bg-slate-100 text-slate-500 hover:text-slate-800 flex items-center justify-center transition cursor-pointer rounded-none"
                    title="Cerrar modal (Esc)">
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <!-- Contenido Central / Body con Scroll Interno -->
        <div class="p-5 overflow-y-auto custom-scrollbar flex-1">
            {{ $slot }}
        </div>

        <!-- Footer del Modal (Opcional) -->
        @if(isset($footer))
            <div class="px-5 py-3 border-t border-slate-200 bg-slate-50/70 flex items-center justify-end gap-2 shrink-0">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
