@props([
    'id' => 'customDataTable',
    'headers' => [],
    'pageLength' => 5,
    'searchable' => true,
    'paging' => true,
    'info' => true,
    'scrollY' => '380px'
])

<div class="w-full bg-white rounded-2xl border border-datamind-light/65 shadow-sm overflow-hidden flex flex-col">
    <!-- Header del componente -->
    @if(isset($title) || isset($description))
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white shrink-0">
            <div>
                @if(isset($title))
                    <h3 class="text-base font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <i class="fa-solid fa-table-list text-datamind-primary"></i>
                        {{ $title }}
                    </h3>
                @endif
                @if(isset($description))
                    <p class="text-xs text-slate-500 mt-0.5">{{ $description }}</p>
                @endif
            </div>
            @if(isset($headerActions))
                <div class="flex items-center gap-2">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif

    <!-- Contenedor del DataTables con altura estática y scroll interno -->
    <div class="p-6 grow flex flex-col">
        <div class="custom-dt-container w-full">
            <table id="{{ $id }}" class="w-full text-sm text-left text-slate-700 stripe hover display responsive no-wrap">
                <thead>
                    <tr class="bg-datamind-neutral border-b border-datamind-light/70 text-xs font-semibold text-datamind-dark uppercase tracking-wider">
                        @if(count($headers) > 0)
                            @foreach($headers as $header)
                                <th scope="col" class="py-3 px-4 font-semibold text-datamind-dark text-left">
                                    {{ $header }}
                                </th>
                            @endforeach
                        @else
                            {{ $thead ?? '' }}
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-normal">
                    {{ $slot }}
                </tbody>
            </table>
        </div>
    </div>

    <!-- Barra de acciones inferior separada por línea idéntica al header -->
    <div class="px-6 py-4 border-t border-datamind-light/50 bg-datamind-neutral/60 flex flex-wrap items-center justify-between gap-3 shrink-0">
        <div class="flex items-center gap-2">
            @if(isset($footerLeft))
                {{ $footerLeft }}
            @else
                <span class="text-xs text-slate-600 font-medium flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-datamind-primary text-[11px]"></i>
                    Acciones de lote y gestión rápida
                </span>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if(isset($actions))
                {{ $actions }}
            @else
                <!-- Botón Nuevo (Paleta DataMind Primary: #0476D9 -> #0468BF) -->
                <button type="button" onclick="if(window.dtCreateAction){ window.dtCreateAction(); } else { notifyItem('Crear Nuevo Ítem'); }" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg btn-datamind-primary text-xs font-semibold cursor-pointer">
                    <i class="fa-solid fa-plus"></i> Nuevo
                </button>

                <!-- Botón Editar (Paleta DataMind Secondary: #4B89BF -> #0468BF) -->
                <button type="button" onclick="if(window.dtEditAction){ window.dtEditAction(); } else { notifyItem('Editar Registro Seleccionado'); }" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-datamind-secondary hover:bg-datamind-dark active:bg-datamind-dark text-xs font-semibold text-white shadow-xs transition duration-150 cursor-pointer">
                    <i class="fa-solid fa-pen-to-square"></i> Editar
                </button>

                <!-- Botón Eliminar (Rojo estilo Danger: #dc3545 / Red-600) -->
                <button type="button" onclick="if(window.dtDeleteAction){ window.dtDeleteAction(); } else { notifyItem('Eliminar Registro Seleccionado'); }" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-red-600 hover:bg-red-700 active:bg-red-800 text-xs font-semibold text-white shadow-sm transition duration-150 cursor-pointer">
                    <i class="fa-solid fa-trash-can"></i> Eliminar
                </button>
            @endif
        </div>
    </div>
</div>

<style>
    /* Estilos Tailwind para DataTables con tamaño estático y scroll interno */
    #{{ $id }}_wrapper {
        font-family: inherit;
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    /* Fila superior que contiene length y filter */
    #{{ $id }}_wrapper > .dt-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    #{{ $id }}_wrapper .dt-length-container {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    #{{ $id }}_wrapper .dataTables_length,
    #{{ $id }}_wrapper .dataTables_filter {
        font-size: 0.8125rem;
        color: #475569;
        font-weight: 500;
        margin: 0;
    }

    #{{ $id }}_wrapper .dataTables_length select {
        padding: 0.35rem 2rem 0.35rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid var(--datamind-accent-light);
        background-color: var(--datamind-bg-neutral);
        font-size: 0.8125rem;
        color: #1e293b;
        outline: none;
        transition: all 0.15s ease;
    }

    #{{ $id }}_wrapper .dataTables_length select:focus {
        border-color: var(--datamind-primary);
        background-color: #ffffff;
        box-shadow: 0 0 0 3px var(--datamind-focus-ring);
    }

    #{{ $id }}_wrapper .dataTables_filter input {
        padding: 0.4rem 0.875rem;
        border-radius: 0.5rem;
        border: 1px solid var(--datamind-accent-light);
        background-color: var(--datamind-bg-neutral);
        font-size: 0.8125rem;
        margin-left: 0.5rem;
        width: 230px;
        outline: none;
        transition: all 0.15s ease;
    }

    #{{ $id }}_wrapper .dataTables_filter input:focus {
        border-color: var(--datamind-primary);
        background-color: #ffffff;
        box-shadow: 0 0 0 3px var(--datamind-focus-ring);
    }

    /* Scroll interno con altura fija */
    #{{ $id }}_wrapper .dataTables_scroll {
        border: 1px solid rgba(153, 208, 242, 0.55);
        border-radius: 0.75rem;
        overflow: hidden;
    }

    #{{ $id }}_wrapper .dataTables_scrollBody {
        min-height: {{ $scrollY }} !important;
        max-height: {{ $scrollY }} !important;
        overflow-y: auto !important;
        scrollbar-width: thin;
        scrollbar-color: var(--datamind-accent-light) var(--datamind-bg-neutral);
    }

    #{{ $id }}_wrapper .dataTables_scrollBody::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    #{{ $id }}_wrapper .dataTables_scrollBody::-webkit-scrollbar-track {
        background: var(--datamind-bg-neutral);
    }

    #{{ $id }}_wrapper .dataTables_scrollBody::-webkit-scrollbar-thumb {
        background-color: var(--datamind-accent-light);
        border-radius: 9999px;
    }

    #{{ $id }}_wrapper .dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
        background-color: var(--datamind-secondary);
    }

    #{{ $id }}_wrapper .dataTables_info {
        font-size: 0.75rem;
        color: #64748b;
        padding-top: 1rem;
    }

    #{{ $id }}_wrapper .dataTables_paginate {
        padding-top: 0.875rem;
        font-size: 0.8125rem;
    }

    #{{ $id }}_wrapper .dataTables_paginate .paginate_button {
        padding: 0.35rem 0.75rem !important;
        border-radius: 0.5rem !important;
        border: 1px solid transparent !important;
        color: #475569 !important;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
        margin: 0 0.15rem;
    }

    #{{ $id }}_wrapper .dataTables_paginate .paginate_button:hover {
        background: var(--datamind-accent-soft) !important;
        border-color: var(--datamind-accent-light) !important;
        color: var(--datamind-primary-dark) !important;
    }

    #{{ $id }}_wrapper .dataTables_paginate .paginate_button.current,
    #{{ $id }}_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: var(--datamind-primary) !important;
        border-color: var(--datamind-primary-dark) !important;
        color: #ffffff !important;
        box-shadow: 0 2px 5px rgba(4, 118, 217, 0.35);
    }

    #{{ $id }}_wrapper .dataTables_paginate .paginate_button.disabled,
    #{{ $id }}_wrapper .dataTables_paginate .paginate_button.disabled:hover {
        color: #cbd5e1 !important;
        cursor: not-allowed;
        background: transparent !important;
        border-color: transparent !important;
    }

    #{{ $id }} tbody tr {
        transition: background-color 0.15s ease;
        cursor: pointer;
    }

    #{{ $id }} tbody tr:hover {
        background-color: var(--datamind-bg-neutral) !important;
    }

    #{{ $id }} tbody tr.selected-row {
        background-color: var(--datamind-accent-soft) !important;
        color: var(--datamind-primary-dark) !important;
    }
</style>

@pushOnce('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof jQuery !== 'undefined' && $.fn.DataTable) {
            var table = $('#{{ $id }}').DataTable({
                pageLength: {{ $pageLength }},
                lengthMenu: [
                    [5, 10, 20, 50, 100, -1],
                    [5, 10, 20, 50, 100, "Todos"]
                ],
                scrollY: '{{ $scrollY }}',
                scrollX: true,
                scrollCollapse: true,
                autoWidth: false,
                searching: {{ $searchable ? 'true' : 'false' }},
                paging: {{ $paging ? 'true' : 'false' }},
                info: {{ $info ? 'true' : 'false' }},
                dom: '<"dt-top-row"<"dt-length-container"l>f>rt<"flex flex-col sm:flex-row items-center justify-between gap-4"ip>',
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Buscar registro...",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    zeroRecords: "No se encontraron coincidencias",
                    paginate: {
                        first: '<i class="fa-solid fa-angles-left"></i>',
                        last: '<i class="fa-solid fa-angles-right"></i>',
                        next: '<i class="fa-solid fa-angle-right"></i>',
                        previous: '<i class="fa-solid fa-angle-left"></i>'
                    }
                },
                initComplete: function() {
                    // Inyectar el botón Exportar (Verde) justo al lado de Mostrar registros
                    var exportBtnHtml = `
                        <button type="button" id="btnExportTop_{{ $id }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-xs font-semibold text-white shadow-sm transition duration-150">
                            <i class="fa-solid fa-file-excel"></i> Exportar
                        </button>
                    `;
                    $('#{{ $id }}_wrapper .dt-length-container').append(exportBtnHtml);

                    $('#btnExportTop_{{ $id }}').on('click', function() {
                        if (window.dtExportAction) {
                            window.dtExportAction();
                        } else {
                            notifyItem('Exportar a Excel / CSV');
                        }
                    });
                }
            });

            // Permitir selección de fila para las acciones inferiores
            $('#{{ $id }} tbody').on('click', 'tr', function () {
                $(this).toggleClass('selected-row').siblings().removeClass('selected-row');
            });
        }
    });
</script>
@endpushOnce
