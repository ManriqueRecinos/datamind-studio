{{-- Chip Discreto para Candidatos de Conocimiento Implícito (Fase 1) --}}
{{-- Mitigación estricta de XSS: todos los títulos, nombres y contenidos se renderizan con e() / {{ }} --}}

<div class="candidate-rule-chip inline-flex items-center gap-2 p-1.5 px-3 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-none shadow-none my-1" data-candidate-id="{{ e($candidate['id']) }}">
    <span class="flex items-center gap-1.5 font-medium">
        <i class="fa-solid fa-lightbulb text-amber-500 text-[11px]"></i>
        <span>Sugerencia aprendida: <strong>{{ e($candidate['title']) }}</strong></span>
    </span>

    <div class="flex items-center gap-1 ml-2 border-l border-amber-300 pl-2">
        @if(empty($candidate['confirmed_at']))
            <button type="button" 
                    class="btn-confirm-candidate px-2 py-0.5 bg-amber-600 hover:bg-amber-700 text-white font-semibold text-[10px] uppercase tracking-wider rounded-none transition"
                    title="Mantener esta preferencia activa solo para mis consultas">
                Confirmar para mí
            </button>
        @else
            <span class="text-[10px] text-emerald-700 font-semibold flex items-center gap-1">
                <i class="fa-solid fa-check"></i> Activa para ti
            </span>
        @endif

        @if(empty($candidate['proposed_global_at']))
            <button type="button" 
                    class="btn-propose-candidate px-2 py-0.5 bg-white border border-amber-400 hover:bg-amber-100 text-amber-900 font-semibold text-[10px] uppercase tracking-wider rounded-none transition"
                    title="Postular para que un administrador la evalúe corporativamente">
                Postular a global
            </button>
        @else
            <span class="text-[10px] text-amber-700 italic">
                (Postulada a global)
            </span>
        @endif

        <button type="button" 
                class="btn-reject-candidate text-slate-400 hover:text-red-600 px-1 text-[11px] transition"
                title="Descartar sugerencia">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>
