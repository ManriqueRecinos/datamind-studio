/**
 * DataMind AI - Chat Streaming Client
 * Protocolo SSE (Server-Sent Events) sobre HTTP POST.
 * Zero CDNs - Dependencias locales: jQuery, DataTables, Toastr, SweetAlert2.
 */

(function () {
    'use strict';

    let currentSessionId = null;
    let isProcessing = false;

    // Elementos del DOM
    const chatContainer = document.getElementById('chatMessagesContainer');
    const promptInput = document.getElementById('chatPromptInput');
    const sendButton = document.getElementById('btnSendMessage');
    const btnNewChat = document.getElementById('btnNewChat');
    const emptyState = document.getElementById('emptyStatePlaceholder');
    const activeConnectionSelect = document.getElementById('activeConnectionSelect');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Inicialización al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        if (!chatContainer || !promptInput || !sendButton) {
            return;
        }

        // Auto-foco en el input
        promptInput.focus();

        // Escuchar envío por click
        sendButton.addEventListener('click', handleSendMessage);

        // Escuchar tecla Enter (sin Shift)
        promptInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSendMessage();
            }
        });

        // Botón Nueva Conversación
        if (btnNewChat) {
            btnNewChat.addEventListener('click', handleResetChat);
        }

        // Sugerencias rápidas
        document.querySelectorAll('.quick-suggestion-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                promptInput.value = this.getAttribute('data-prompt') || this.innerText.trim();
                handleSendMessage();
            });
        });

        // Cambio de conexión activa desde el selector superior
        if (activeConnectionSelect) {
            activeConnectionSelect.addEventListener('change', async function () {
                const selectedConnId = this.value;
                if (!selectedConnId) return;
                try {
                    const res = await fetch(`/connections/${selectedConnId}/activate`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    if (res.ok && window.toastr) {
                        const selectedLabel = this.options[this.selectedIndex]?.text || 'Base de Datos';
                        toastr.info(`Consultando ahora sobre: ${selectedLabel}`, 'Base de Datos Cambiada');
                    }
                } catch (e) {
                    console.warn('No se pudo actualizar la conexión por defecto:', e);
                }
            });
        }
    });

    /**
     * Resetea la conversación actual.
     */
    function handleResetChat() {
        if (isProcessing) return;

        if (window.Swal) {
            Swal.fire({
                title: '¿Iniciar nuevo chat?',
                text: 'Se limpiará la vista actual del asistente.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0476D9',
                cancelButtonColor: '#4B89BF',
                confirmButtonText: 'Sí, nuevo chat',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    resetChatSession();
                }
            });
        } else {
            if (confirm('¿Desea iniciar una nueva conversación?')) {
                resetChatSession();
            }
        }
    }

    function resetChatSession() {
        currentSessionId = null;
        chatContainer.innerHTML = '';
        if (emptyState) {
            chatContainer.appendChild(emptyState);
            emptyState.classList.remove('hidden');
        }
        promptInput.value = '';
        promptInput.focus();
        if (window.toastr) {
            toastr.success('Nueva sesión conversacional iniciada.', 'DataMind AI');
        }
    }

    /**
     * Procesa y envía la pregunta del usuario.
     */
    async function handleSendMessage() {
        const text = promptInput.value.trim();
        if (!text || isProcessing) return;

        // Ocultar placeholder de bienvenida
        if (emptyState) {
            emptyState.classList.add('hidden');
        }

        // 1. Renderizar mensaje del usuario
        appendUserMessage(text);
        promptInput.value = '';
        setProcessingState(true);

        // 2. Crear burbuja vacía del asistente con indicador de carga
        const { messageCard, textSpan, contentWrapper, footerBadge } = createAssistantMessageCard();
        scrollToBottom();

        try {
            const response = await fetch('/chat/stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    prompt: text,
                    session_id: currentSessionId,
                    connection_id: activeConnectionSelect?.value || null
                })
            });

            if (!response.ok) {
                throw new Error(`Error en el servidor (${response.status}): ${response.statusText}`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder('utf-8');
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                buffer += decoder.decode(value, { stream: true });
                const events = buffer.split('\n\n');
                buffer = events.pop() || '';

                for (const rawEvent of events) {
                    if (!rawEvent.trim()) continue;

                    const lines = rawEvent.split('\n');
                    let eventName = 'message';
                    let eventData = '';

                    for (const line of lines) {
                        if (line.startsWith('event:')) {
                            eventName = line.substring(6).trim();
                        } else if (line.startsWith('data:')) {
                            eventData = line.substring(5).trim();
                        }
                    }

                    if (!eventData) continue;

                    try {
                        const parsed = JSON.parse(eventData);
                        processSSEEvent(eventName, parsed, textSpan, contentWrapper, footerBadge);
                    } catch (err) {
                        console.warn('Error al parsear SSE JSON:', err, eventData);
                    }
                }
            }
        } catch (error) {
            console.error('Error en streaming de DataMind AI:', error);
            textSpan.innerHTML += `<div class="mt-2 p-3 bg-red-50 text-red-700 text-xs rounded-xl border border-red-200">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i> ${escapeHtml(error.message)}
            </div>`;
            if (window.toastr) {
                toastr.error(error.message, 'Error de Comunicación');
            }
        } finally {
            setProcessingState(false);
            scrollToBottom();
            promptInput.focus();
        }
    }

    /**
     * Procesa cada tipo de evento SSE emitido por FastAPI.
     */
    function processSSEEvent(eventName, data, textSpan, contentWrapper, footerBadge) {
        // Remover indicador de carga en primer token o evento de datos
        const loader = textSpan.querySelector('.ai-loading-indicator');
        if (loader) {
            loader.remove();
        }

        switch (eventName) {
            case 'session':
                if (data.session_id) {
                    currentSessionId = data.session_id;
                }
                break;

            case 'token':
                if (data.text) {
                    // Reemplazar saltos de línea con <br> y renderizar de forma segura
                    const formatted = data.text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/\n/g, '<br>');
                    textSpan.innerHTML += formatted;
                    scrollToBottom();
                }
                break;

            case 'dataset':
                renderDatasetSection(data, contentWrapper);
                break;

            case 'done':
                if (data.session_id) {
                    currentSessionId = data.session_id;
                }
                if (footerBadge) {
                    const latency = data.latency_ms ? `${data.latency_ms} ms` : 'Local';
                    footerBadge.innerHTML = `<span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-400">
                        <i class="fa-solid fa-bolt text-amber-500"></i> ${latency} &bull; qwen2.5:3b
                    </span>`;
                }
                break;

            case 'error':
                textSpan.innerHTML += `<div class="mt-2 p-2.5 bg-amber-50 text-amber-800 text-xs rounded-lg border border-amber-200">
                    <i class="fa-solid fa-circle-exclamation mr-1"></i> ${escapeHtml(data.detail || 'Ocurrió un error.')}
                </div>`;
                break;
        }
    }

    /**
     * Renderiza la vista previa de datos y el botón de descarga de Excel para un dataset devuelto.
     */
    function renderDatasetSection(dataset, contentWrapper) {
        const { dataset_id, row_count, columns, preview_rows, sql } = dataset;
        const uniqueTableId = 'dt_' + dataset_id.replace(/[^a-zA-Z0-9_]/g, '_');

        const card = document.createElement('div');
        card.className = 'mt-5 pt-4 border-t border-slate-200/80 w-full';

        // Cabecera de la sección con botón de descarga
        let html = `
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 bg-datamind-neutral/85 p-3.5 rounded-xl border border-datamind-light/70">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-lg badge-datamind flex items-center justify-center text-sm font-bold shadow-xs">
                        <i class="fa-solid fa-table text-datamind-primary"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-800">Resultado de Consulta Corporativa</h4>
                        <p class="text-[11px] text-datamind-secondary font-medium">${row_count} registros devueltos &bull; MariaDB</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="/chat/export/${dataset_id}" 
                       onclick="if(window.toastr){ toastr.info('Descargando archivo Excel estilizado...', 'DataMind AI'); }"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold shadow-xs transition duration-150">
                        <i class="fa-solid fa-file-excel text-sm"></i>
                        <span>Generar Excel (.xlsx)</span>
                    </a>
                </div>
            </div>
        `;

        // Si se devuelven columnas y filas de previsualización, renderizar la tabla con estilos nativos DataMind
        if (columns && columns.length > 0 && preview_rows && preview_rows.length > 0) {
            html += `
                <div class="w-full bg-white rounded-xl border border-datamind-light/75 shadow-2xs overflow-hidden mt-3">
                    <div class="p-3 bg-datamind-neutral border-b border-datamind-light/60 flex items-center justify-between">
                        <span class="text-xs font-semibold text-datamind-dark flex items-center gap-1.5">
                            <i class="fa-solid fa-eye text-datamind-primary"></i> Vista Previa Interactiva (Primeros ${preview_rows.length} registros)
                        </span>
                        <span class="text-[11px] text-datamind-secondary font-mono">${columns.length} columnas &bull; Scroll activo</span>
                    </div>
                    <div class="p-3 w-full overflow-hidden">
                        <table id="${uniqueTableId}" class="w-full text-xs text-left text-slate-700 stripe hover display nowrap">
                            <thead>
                                <tr class="bg-datamind-neutral border-b border-datamind-light/70 text-[11px] font-semibold text-datamind-dark uppercase tracking-wider">
                                    ${columns.map(c => `<th class="py-2.5 px-3 font-semibold text-datamind-dark text-left whitespace-nowrap">${escapeHtml(c)}</th>`).join('')}
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-normal">
                                ${preview_rows.map(row => `
                                    <tr class="hover:bg-datamind-light/25 transition">
                                        ${columns.map(col => `<td class="py-2 px-3 whitespace-nowrap">${escapeHtml(String(row[col] ?? ''))}</td>`).join('')}
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }

        // Consulta SQL colapsable para auditoría técnica
        if (sql) {
            html += `
                <details class="mt-2.5 text-[11px] text-datamind-secondary">
                    <summary class="cursor-pointer hover:text-datamind-primary font-semibold">Ver consulta SQL ejecutada (solo lectura)</summary>
                    <pre class="mt-1.5 p-2.5 bg-slate-900 text-datamind-light rounded-lg overflow-x-auto font-mono text-[11px] border border-datamind-secondary/40">${escapeHtml(sql)}</pre>
                </details>
            `;
        }

        card.innerHTML = html;
        contentWrapper.appendChild(card);

        // Inicializar DataTables con scroll horizontal/vertical sincronizado para el thead y tbody
        if (window.jQuery && window.jQuery.fn.DataTable && preview_rows && preview_rows.length > 0) {
            setTimeout(() => {
                $(`#${uniqueTableId}`).DataTable({
                    pageLength: 5,
                    scrollX: true,
                    scrollY: '240px',
                    scrollCollapse: true,
                    autoWidth: false,
                    searching: true,
                    paging: preview_rows.length > 5,
                    info: true,
                    language: {
                        search: "Filtrar vista:",
                        lengthMenu: "Mostrar _MENU_",
                        info: "Mostrando _START_ a _END_ de _TOTAL_",
                        paginate: {
                            first: "Primero",
                            last: "Último",
                            next: "Sig.",
                            previous: "Ant."
                        }
                    }
                });
            }, 50);
        }

        scrollToBottom();
    }

    /**
     * Agrega el mensaje del usuario al chat.
     */
    function appendUserMessage(text) {
        const row = document.createElement('div');
        row.className = 'flex justify-end mb-6';

        row.innerHTML = `
            <div class="max-w-2xl flex items-start gap-3 flex-row-reverse">
                <div class="h-9 w-9 rounded-xl bg-datamind-primary text-white flex items-center justify-center text-sm font-bold shadow-md shrink-0">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="bg-datamind-primary text-white p-4 rounded-2xl rounded-tr-xs shadow-sm border border-datamind-dark/20">
                    <p class="text-sm leading-relaxed whitespace-pre-wrap">${escapeHtml(text)}</p>
                </div>
            </div>
        `;

        chatContainer.appendChild(row);
    }

    /**
     * Crea la tarjeta base para el mensaje del asistente.
     */
    function createAssistantMessageCard() {
        const row = document.createElement('div');
        row.className = 'flex justify-start mb-6 w-full';

        row.innerHTML = `
            <div class="max-w-3xl w-full flex items-start gap-3">
                <div class="h-9 w-9 rounded-xl bg-datamind-avatar text-white flex items-center justify-center text-sm font-bold shadow-sm shrink-0">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="bg-white border border-datamind-light/70 text-slate-800 p-5 rounded-2xl rounded-tl-xs shadow-xs grow flex flex-col">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                        <span class="text-xs font-bold text-datamind-dark flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-datamind-primary"></span> DataMind AI
                        </span>
                        <div class="footer-badge"></div>
                    </div>
                    <div class="ai-content-wrapper flex flex-col">
                        <div class="ai-text text-sm leading-relaxed text-slate-700">
                            <div class="ai-loading-indicator inline-flex items-center gap-1.5 text-slate-400 py-1">
                                <span class="h-2 w-2 rounded-full bg-datamind-primary animate-bounce"></span>
                                <span class="h-2 w-2 rounded-full bg-datamind-secondary animate-bounce" style="animation-delay: 0.15s"></span>
                                <span class="h-2 w-2 rounded-full bg-datamind-light animate-bounce" style="animation-delay: 0.3s"></span>
                                <span class="text-xs ml-1 text-datamind-secondary font-medium">Analizando contexto y procesos...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        chatContainer.appendChild(row);

        const textSpan = row.querySelector('.ai-text');
        const contentWrapper = row.querySelector('.ai-content-wrapper');
        const footerBadge = row.querySelector('.footer-badge');

        return { messageCard: row, textSpan, contentWrapper, footerBadge };
    }

    /**
     * Controla el estado visual de los controles durante el procesamiento.
     */
    function setProcessingState(processing) {
        isProcessing = processing;
        promptInput.disabled = processing;
        sendButton.disabled = processing;

        if (processing) {
            sendButton.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i>`;
            sendButton.classList.add('opacity-70', 'cursor-not-allowed');
        } else {
            sendButton.innerHTML = `<i class="fa-solid fa-paper-plane mr-1.5"></i> Enviar`;
            sendButton.classList.remove('opacity-70', 'cursor-not-allowed');
        }
    }

    /**
     * Desplaza el scroll del chat hacia el final suavemente.
     */
    function scrollToBottom() {
        const scrollElem = document.getElementById('chatScrollableArea') || chatContainer;
        scrollElem.scrollTop = scrollElem.scrollHeight;
    }

    /**
     * Escapa caracteres HTML para prevenir XSS.
     */
    function escapeHtml(string) {
        if (!string) return '';
        return String(string)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

})();
