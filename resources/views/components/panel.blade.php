@props([
    'title' => null,
    'icon' => null,
    'badge' => null,
    'noPadding' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-slate-200 flex flex-col rounded-none shadow-none']) }}>
    @if($title || isset($headerActions))
        <div class="px-4 py-3 border-b border-slate-200 bg-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2">
                @if($icon)
                    <i class="fa-solid {{ $icon }} text-datamind-primary text-xs"></i>
                @endif
                @if($title)
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                        {{ $title }}
                    </h3>
                @endif
                @if($badge)
                    <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-none bg-slate-100 text-slate-600 border border-slate-200">
                        {{ $badge }}
                    </span>
                @endif
            </div>

            @if(isset($headerActions))
                <div class="flex items-center gap-1.5">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif

    <div class="{{ $noPadding ? '' : 'p-4' }} flex-1">
        {{ $slot }}
    </div>

    @if(isset($footer))
        <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between text-xs text-slate-500 shrink-0">
            {{ $footer }}
        </div>
    @endif
</div>
