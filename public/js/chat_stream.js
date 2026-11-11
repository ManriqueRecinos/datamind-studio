/**
 * DataMind AI - Chat Streaming Client
 * Protocolo SSE (Server-Sent Events) sobre HTTP POST.
 * Zero CDNs - Dependencias locales: jQuery, DataTables, Toastr, SweetAlert2.
 */

(function () {
    'use strict';

    let currentSessionId = null;
    let isProcessing = false;
    const chartInstances = {};
    const datasetRowsCache = {};

    // Sistema de Multi-Session Background Streaming & Concurrencia
    const ActiveJobStore = new Map();
    let viewedStreamId = null;
    let currentNavToken = 0;

    // Exponer inmediatamente en window para disponibilidad sincrónica
    window.ActiveJobStore = ActiveJobStore;

    /**
     * Comprueba si una sesión específica está ocupada generando una respuesta.
     */
    function isSessionBusy(sessionId) {
        if (!sessionId) {
            // Si es nueva conversación, comprobar si hay algún job provisional en primer plano generando
            for (const job of ActiveJobStore.values()) {
                if (!job.sessionId && (job.status === 'connecting' || job.status === 'streaming')) {
                    return true;
                }
            }
            return false;
        }
        const job = ActiveJobStore.get(sessionId);
        return Boolean(job && (job.status === 'connecting' || job.status === 'streaming'));
    }

    /**
     * Cuenta total de streams activos en el navegador para cumplir el tope de <= 3.
     */
    function countActiveStreams() {
        let count = 0;
        for (const job of ActiveJobStore.values()) {
            if (job.status === 'connecting' || job.status === 'streaming') {
                count++;
            }
        }
        return count;
    }

    /**
     * Manejo del conjunto de IDs en vuelo en sessionStorage para detección inmediata ante F5.
     */
    function trackInflightSession(sessionId, add = true) {
        if (!sessionId || typeof sessionStorage === 'undefined') return;
        try {
            const raw = sessionStorage.getItem('inflightSessionIds');
            let list = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(list)) list = [];

            if (add) {
                if (!list.includes(sessionId)) list.push(sessionId);
            } else {
                list = list.filter(id => id !== sessionId);
            }
            sessionStorage.setItem('inflightSessionIds', JSON.stringify(list));
        } catch (e) {
            console.warn('[DataMind Chat] Error actualizando inflightSessionIds en sessionStorage:', e);
        }
    }

    function isInflightInSessionStorage(sessionId) {
        if (!sessionId || typeof sessionStorage === 'undefined') return false;
        try {
            const raw = sessionStorage.getItem('inflightSessionIds');
            if (!raw) return false;
            const list = JSON.parse(raw);
            return Array.isArray(list) && list.includes(sessionId);
        } catch (e) {
            return false;
        }
    }

    // Advertencia beforeunload si hay jobs activos
    window.addEventListener('beforeunload', function (e) {
        if (countActiveStreams() > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Sistema de Notificaciones Toastr Singleton con Prioridad de Severidad
    // Escala: error (4) > warning (3) > success (2) > info (1)
    let currentToastSeverity = 0;
    let currentToastRef = null;

    const TOAST_SEVERITY_LEVELS = {
        info: 1,
        success: 2,
        warning: 3,
        error: 4
    };

    /**
     * Muestra una notificación Toastr respetando la jerarquía de severidad.
     * Severidades menores se ignoran silenciosamente; advertencias nunca pisan errores activos.
     */
    function notify(type, message, title = 'DataMind AI', options = {}) {
        if (!window.toastr) return;

        const newSeverity = TOAST_SEVERITY_LEVELS[type] || 1;

        // Regla: no permitir que una notificación de menor severidad sobrescriba una activa
        if (newSeverity < currentToastSeverity) {
            return;
        }

        // Si hay una notificación previa de menor o igual severidad, limpiar
        window.toastr.clear();

        const toastId = (typeof crypto !== 'undefined' && crypto.randomUUID) 
            ? crypto.randomUUID() 
            : 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        currentToastRef = toastId;
        currentToastSeverity = newSeverity;

        const defaultOpts = {
            closeButton: true,
            progressBar: true,
            newestOnTop: true,
            positionClass: 'toast-bottom-right',
            timeOut: type === 'error' ? 8000 : 3500,
            extendedTimeOut: type === 'error' ? 4000 : 1500,
            onHidden: function () {
                if (currentToastRef === toastId) {
                    currentToastSeverity = 0;
                    currentToastRef = null;
                }
            }
        };

        const mergedOpts = Object.assign({}, defaultOpts, options);

        if (typeof window.toastr[type] === 'function') {
            window.toastr[type](message, title, mergedOpts);
        } else {
            window.toastr.info(message, title, mergedOpts);
        }
    }

    // Exponer función de notificación unificada en window para llamadas inline
    window.notify = notify;

    // Paleta de colores corporativa DataMind para gráficos
    const DATAMIND_CHART_PALETTE = [
        '#0476D9', // Primary
        '#0468BF', // Dark
        '#4B89BF', // Secondary
        '#0ea5e9', // Sky
        '#6366f1', // Indigo
        '#14b8a6', // Teal
        '#f59e0b', // Amber
        '#99D0F2', // Light
        '#8b5cf6', // Violet
        '#ec4899'  // Pink
    ];

    // Elementos del DOM (Soporte con fallback robusto)
    const chatContainer = document.getElementById('chatMessagesContainer');
    const promptInput = document.getElementById('promptInput') || document.getElementById('chatPromptInput');
    const sendButton = document.getElementById('btnSendMessage');
    const btnNewChat = document.getElementById('btnNewChat') || document.querySelector('[data-action="new-chat"]');
    const emptyState = document.getElementById('emptyStatePlaceholder');
    const activeConnectionSelect = document.getElementById('activeConnectionSelect');
    const userSessionsList = document.getElementById('userSessionsList');
    const historyCountBadge = document.getElementById('historyCountBadge');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Elementos de adjuntos de archivos
    const btnAttachFile = document.getElementById('btnAttachFile');
    const fileAttachmentInput = document.getElementById('fileAttachmentInput');
    const attachedFilesContainer = document.getElementById('attachedFilesContainer');
    let activeAttachments = []; // Array de { id, original_name, row_count, file_size_bytes, mime_type }

    // Inicialización al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        if (!chatContainer || !promptInput || !sendButton) {
            console.warn('[DataMind Chat] Elementos requeridos no encontrados en el DOM:', {
                chatContainer: Boolean(chatContainer),
                promptInput: Boolean(promptInput),
                sendButton: Boolean(sendButton)
            });
            return;
        }

        // Auto-foco en el input
        promptInput.focus();

        // Escuchar envío por click
        sendButton.addEventListener('click', handleSendMessage);

        // Escuchar tecla Enter (sin Shift) y auto-ajustar altura del textarea
        promptInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSendMessage();
            }
        });

        promptInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 160) + 'px';
        });

        // Delegación global para copiar bloques de código Markdown
        chatContainer.addEventListener('click', function (e) {
            const copyBtn = e.target.closest('.md-copy-btn');
            if (!copyBtn) return;

            const block = copyBtn.closest('.md-code-block');
            const codeElem = block?.querySelector('code');
            if (!codeElem) return;

            const textToCopy = codeElem.innerText || codeElem.textContent || '';
            navigator.clipboard?.writeText(textToCopy).then(() => {
                const originalHtml = copyBtn.innerHTML;
                copyBtn.innerHTML = `<i class="fa-solid fa-check text-emerald-400"></i> Copiado`;
                setTimeout(() => {
                    copyBtn.innerHTML = originalHtml;
                }, 1800);
            }).catch(() => {
                notify('info', 'Código seleccionado.', 'Portapapeles');
            });
        });

        // Botón Nueva Conversación
        if (btnNewChat) {
            btnNewChat.addEventListener('click', handleResetChat);
        }

        // Gestión de subida de archivos adjuntos (Clip 📎)
        if (btnAttachFile && fileAttachmentInput) {
            btnAttachFile.addEventListener('click', () => {
                if (activeAttachments.length >= 3) {
                    notify('warning', 'Límite alcanzado: máximo 3 archivos por conversación.', 'Archivos');
                    return;
                }
                fileAttachmentInput.click();
            });

            fileAttachmentInput.addEventListener('change', async function () {
                const file = this.files[0];
                if (!file) return;

                // Validar tamaño máximo en cliente (15 MB)
                if (file.size > 15 * 1024 * 1024) {
                    notify('error', 'El archivo no puede exceder 15 MB.', 'Archivo demasiado grande');
                    this.value = '';
                    return;
                }

                // Asegurar que exista un sessionId temporal para asociar la subida
                if (!currentSessionId) {
                    currentSessionId = (typeof crypto !== 'undefined' && crypto.randomUUID) 
                        ? crypto.randomUUID() 
                        : 'sess_' + Date.now();
                }

                const formData = new FormData();
                formData.append('file', file);
                formData.append('session_id', currentSessionId);

                // Renderizar chip en estado de carga
                const tempChipId = 'uploading_' + Date.now();
                renderAttachmentChip({
                    id: tempChipId,
                    original_name: file.name,
                    file_size_bytes: file.size,
                    isUploading: true
                });

                try {
                    const res = await fetch('/chat/attachments', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const json = await res.json();
                    const uploadingElem = document.getElementById(tempChipId);
                    if (uploadingElem) uploadingElem.remove();

                    if (!res.ok || !json.success) {
                        throw new Error(json.message || 'Error al procesar el archivo');
                    }

                    const attData = json.data;
                    activeAttachments.push(attData);
                    renderAttachmentChip(attData);
                    notify('success', `Archivo "${file.name}" cargado y listo para consultas DuckDB.`, 'Archivo Adjunto');
                } catch (err) {
                    const uploadingElem = document.getElementById(tempChipId);
                    if (uploadingElem) uploadingElem.remove();
                    notify('error', err.message, 'Error de Archivo');
                } finally {
                    fileAttachmentInput.value = '';
                }
            });
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
                    if (res.ok) {
                        const selectedLabel = this.options[this.selectedIndex]?.text || 'Base de Datos';
                        notify('info', `Consultando ahora sobre: ${selectedLabel}`, 'Base de Datos Cambiada');
                    }
                } catch (e) {
                    console.warn('No se pudo actualizar la conexión por defecto:', e);
                }
            });
        }

        // Delegación para el panel lateral de Historial de Conversaciones ("Mis Conversaciones")
        if (userSessionsList) {
            userSessionsList.addEventListener('click', function (e) {
                const deleteBtn = e.target.closest('.btn-delete-session');
                if (deleteBtn) {
                    e.stopPropagation();
                    const sessionId = deleteBtn.getAttribute('data-session-id');
                    if (sessionId) {
                        handleDeleteSession(sessionId);
                    }
                    return;
                }

                const sessionItem = e.target.closest('.session-history-item');
                if (sessionItem) {
                    const sessionId = sessionItem.getAttribute('data-session-id');
                    if (sessionId) {
                        loadConversationSession(sessionId);
                    }
                }
            });
        }
    });

    /**
     * Actualiza la lista lateral de sesiones de conversación del usuario.
     */
    async function refreshUserSessionsSidebar() {
        if (!userSessionsList) return;
        try {
            const res = await fetch('/chat/sessions', {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const json = await res.json();
            const sessions = json.data || [];

            if (historyCountBadge) {
                historyCountBadge.textContent = String(sessions.length);
            }

            if (sessions.length === 0) {
                userSessionsList.innerHTML = `
                    <div id="emptySessionsNotice" class="text-center py-6 text-xs text-slate-400">
                        Aún no tienes conversaciones guardadas. Envía una consulta para crear la primera.
                    </div>
                `;
                return;
            }

            userSessionsList.innerHTML = sessions.map(sess => {
                const isActive = sess.id === currentSessionId;
                const activeClasses = isActive
                    ? 'border-datamind-primary bg-datamind-light/25 shadow-2xs'
                    : 'border-slate-200/80 hover:border-datamind-light hover:bg-datamind-light/20';

                // Determinar badge dinámico desde ActiveJobStore
                const job = ActiveJobStore.get(sess.id);
                let badgeHtml = '';
                if (job) {
                    if (job.status === 'connecting' || job.status === 'streaming') {
                        badgeHtml = `<span class="badge-status-streaming text-amber-500 font-mono text-[9px] flex items-center gap-1 font-bold">
                            <i class="fa-solid fa-spinner fa-spin text-[8px]"></i> Generando...
                        </span>`;
                    } else if (job.status === 'completed' && job.hasUnread) {
                        badgeHtml = `<span class="badge-status-unread text-emerald-600 font-bold text-[9px] flex items-center gap-1">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Listo
                        </span>`;
                    } else if (job.status === 'error' && job.hasUnread) {
                        badgeHtml = `<span class="badge-status-error text-red-500 font-bold text-[9px] flex items-center gap-1">
                            <i class="fa-solid fa-triangle-exclamation text-[8px]"></i> Error
                        </span>`;
                    }
                }

                return `
                    <div class="session-history-item group flex items-center justify-between gap-2 p-2.5 rounded-xl border ${activeClasses} transition cursor-pointer"
                         data-session-id="${escapeHtml(sess.id)}">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            <i class="fa-regular fa-message text-xs text-datamind-primary shrink-0"></i>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold text-slate-800 truncate">${escapeHtml(sess.title || 'Conversación')}</p>
                                <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                    <span>${sess.message_count ?? 0} mensajes</span>
                                    ${badgeHtml}
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn-delete-session opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-600 p-1 transition cursor-pointer"
                                data-session-id="${escapeHtml(sess.id)}" title="Eliminar conversación">
                            <i class="fa-solid fa-trash-can text-[11px]"></i>
                        </button>
                    </div>
                `;
            }).join('');
        } catch (err) {
            console.warn('No se pudo refrescar el historial de sesiones:', err);
        }
    }

    /**
     * Carga y restaura una conversación pasada o se reacopla síncronamente si está en streaming activo.
     * Utiliza navToken incremental para descartar respuestas asíncronas lentas si el usuario navega a otra parte.
     */
    async function loadConversationSession(sessionId, options = {}) {
        if (!sessionId) return;

        const thisNavToken = ++currentNavToken;
        currentSessionId = sessionId;

        // Comprobar si hay un trabajo activo en memoria para esta sesión
        const existingJob = ActiveJobStore.get(sessionId);

        // 1. REACOPLAMIENTO SÍNCRONO SI ESTÁ EN STREAMING
        if (existingJob && (existingJob.status === 'connecting' || existingJob.status === 'streaming')) {
            viewedStreamId = existingJob.streamId;
            chatContainer.innerHTML = '';
            if (emptyState) emptyState.classList.add('hidden');

            // Renderizar prompt original del usuario
            appendUserMessage(existingJob.prompt);

            // Crear y acoplar síncronamente la burbuja viva del asistente sin await
            const { messageCard, textSpan, contentWrapper, footerBadge } = createAssistantMessageCard();
            existingJob.textSpan = textSpan;
            existingJob.contentWrapper = contentWrapper;
            existingJob.footerBadge = footerBadge;

            // Inyectar el snapshot síncrono del texto acumulado hasta este milisegundo
            if (existingJob.rawMarkdown) {
                const loader = textSpan.querySelector('.ai-loading-indicator');
                if (loader) loader.remove();
                textSpan.dataset.rawMarkdown = existingJob.rawMarkdown;
                textSpan.innerHTML = renderMarkdownPreview(existingJob.rawMarkdown);
            }

            // Inyectar dataset acumulado si ya se recibió
            if (existingJob.dataset && contentWrapper) {
                renderDatasetSection(existingJob.dataset, contentWrapper);
            }

            updateSendButtonState();
            refreshUserSessionsSidebar();
            scrollToBottom();
            return;
        }

        // 2. SESIÓN RETENIDA CON ERROR EN BACKGROUND
        if (existingJob && existingJob.status === 'error' && existingJob.hasUnread) {
            viewedStreamId = null;
            chatContainer.innerHTML = '';
            if (emptyState) emptyState.classList.add('hidden');

            if (existingJob.prompt) {
                appendUserMessage(existingJob.prompt);
            }

            // Renderizar tarjeta de error con botón de reintento
            renderErrorCard({
                sessionId: sessionId,
                prompt: existingJob.prompt,
                errorDetail: existingJob.errorDetail || 'Ocurrió un error al procesar la respuesta en segundo plano.'
            });

            ActiveJobStore.delete(sessionId);
            updateSendButtonState();
            refreshUserSessionsSidebar();
            scrollToBottom();
            return;
        }

        // 3. CARGA ASÍNCRONA DESDE BASE DE DATOS
        viewedStreamId = null;
        updateSendButtonState();

        try {
            setProcessingState(true);
            const res = await fetch(`/chat/sessions/${encodeURIComponent(sessionId)}`, {
                headers: { 'Accept': 'application/json' }
            });

            // Guard contra condición de carrera: si el usuario cambió de vista durante el fetch, descartar
            if (thisNavToken !== currentNavToken) {
                return;
            }

            const json = await res.json();
            if (!res.ok || !json.success || !json.data) {
                throw new Error(json.message || 'No se pudo cargar la conversación seleccionada.');
            }

            const sessionData = json.data;

            // Limpiar contenedor de mensajes y ocultar estado vacío
            chatContainer.innerHTML = '';
            if (emptyState) {
                emptyState.classList.add('hidden');
            }

            // Limpiar adjuntos activos al cambiar de sesión
            activeAttachments = [];
            if (attachedFilesContainer) {
                attachedFilesContainer.innerHTML = '';
            }

            const messages = sessionData.messages || [];
            if (messages.length === 0) {
                if (emptyState) {
                    chatContainer.appendChild(emptyState);
                    emptyState.classList.remove('hidden');
                }
            } else {
                messages.forEach(msg => {
                    if (msg.role === 'user') {
                        const msgAttachments = msg.attachments || (msg.metadata && msg.metadata.attachments) || [];
                        appendUserMessage(msg.content || '', msgAttachments);
                    } else {
                        const { textSpan, contentWrapper, footerBadge } = createAssistantMessageCard();
                        const loader = textSpan.querySelector('.ai-loading-indicator');
                        if (loader) loader.remove();

                        textSpan.dataset.rawMarkdown = msg.content || '';
                        textSpan.innerHTML = renderMarkdownPreview(msg.content || '');

                        if (footerBadge) {
                            const latency = msg.latency_ms ? `${msg.latency_ms} ms` : 'Historial';
                            let badgeHtml = `<span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-400">
                                <i class="fa-solid fa-clock-rotate-left text-datamind-primary"></i> ${latency}
                            </span>`;

                            const appliedRules = msg.rules_applied || (msg.metadata && msg.metadata.rules_applied) || [];
                            if (Array.isArray(appliedRules) && appliedRules.length > 0) {
                                const ruleTitles = appliedRules.map(r => escapeHtml(r.title || 'Regla')).join(', ');
                                badgeHtml = `<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full mr-2" title="${ruleTitles}">
                                    <i class="fa-solid fa-brain text-emerald-600"></i> Reglas aplicadas: ${escapeHtml(appliedRules[0].title || 'Regla')}${appliedRules.length > 1 ? ` (+${appliedRules.length - 1})` : ''}
                                </span>` + badgeHtml;
                            }

                            footerBadge.innerHTML = badgeHtml;
                        }

                        if (msg.has_tabular_data && msg.dataset_id) {
                            renderDatasetSection({
                                dataset_id: msg.dataset_id,
                                row_count: msg.row_count || 0,
                                columns: msg.columns || [],
                                preview_rows: msg.preview_rows || [],
                                sql: msg.generated_sql || null,
                                chart_config: msg.chart_config || null
                            }, contentWrapper);
                        }
                    }
                });

                // Detectar si la sesión quedó inconclusa/interrumpida
                // Criterio: el último mensaje cronológico es de rol 'user' sin respuesta de asistente
                const lastMsg = messages[messages.length - 1];
                const isInterruptedByStorage = isInflightInSessionStorage(sessionId);
                const isInterruptedByTime = (lastMsg && lastMsg.role === 'user' && !existingJob);

                if (lastMsg && lastMsg.role === 'user' && (isInterruptedByStorage || isInterruptedByTime)) {
                    renderInterruptedSessionCard({
                        sessionId: sessionId,
                        messageId: lastMsg.id,
                        prompt: lastMsg.content
                    });
                }
            }

            // Si estaba en ActiveJobStore marcada como completada en background, purgarla
            if (existingJob && existingJob.status === 'completed') {
                ActiveJobStore.delete(sessionId);
            }

            await refreshUserSessionsSidebar();
            scrollToBottom();
            if (!options.preserveView) {
                notify('info', `Conversación cargada: ${sessionData.title}`, 'Historial DataMind');
            }
        } catch (err) {
            if (thisNavToken !== currentNavToken) return;
            console.error('Error al cargar sesión:', err);
            notify('error', err.message || 'Error al cargar la conversación.', 'Historial');
        } finally {
            if (thisNavToken === currentNavToken) {
                setProcessingState(false);
                updateSendButtonState();
            }
        }
    }

    /**
     * Renderiza una tarjeta informativa cuando una sesión fue interrumpida antes de generar su respuesta.
     */
    function renderInterruptedSessionCard(info) {
        const row = document.createElement('div');
        row.className = 'flex justify-start mb-6';
        row.innerHTML = `
            <div class="max-w-xl w-full p-4 bg-amber-50/90 border border-amber-200 text-amber-900 rounded-none shadow-2xs">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-500 text-base mt-0.5"></i>
                    <div class="flex-1">
                        <h4 class="text-xs font-bold uppercase tracking-wider mb-1 text-amber-950">Consulta Incompleta o Interrumpida</h4>
                        <p class="text-xs text-amber-800 leading-relaxed mb-3">
                            Esta consulta no completó su respuesta (posible recarga de página o desconexión del servidor).
                        </p>
                        <button type="button" class="btn-retry-chat px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs uppercase tracking-wider transition cursor-pointer flex items-center gap-1.5 rounded-none"
                                data-session-id="${escapeHtml(info.sessionId)}"
                                data-message-id="${escapeHtml(info.messageId || '')}"
                                data-prompt="${escapeHtml(info.prompt || '')}">
                            <i class="fa-solid fa-rotate-right text-xs"></i>
                            <span>Reintentar Consulta</span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        chatContainer.appendChild(row);

        const retryBtn = row.querySelector('.btn-retry-chat');
        if (retryBtn) {
            retryBtn.addEventListener('click', function () {
                const sId = this.getAttribute('data-session-id');
                const mId = this.getAttribute('data-message-id');
                const pText = this.getAttribute('data-prompt');
                row.remove();
                handleRetryMessage({
                    sessionId: sId,
                    retryMessageId: mId,
                    prompt: pText
                });
            });
        }
    }

    /**
     * Renderiza una tarjeta de error retenida con opción a reintentar.
     */
    function renderErrorCard(info) {
        const row = document.createElement('div');
        row.className = 'flex justify-start mb-6';
        row.innerHTML = `
            <div class="max-w-xl w-full p-4 bg-red-50 border border-red-200 text-red-900 rounded-none shadow-2xs">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-circle-exclamation text-red-500 text-base mt-0.5"></i>
                    <div class="flex-1">
                        <h4 class="text-xs font-bold uppercase tracking-wider mb-1 text-red-950">Error al Procesar la Consulta</h4>
                        <p class="text-xs text-red-700 leading-relaxed mb-3">
                            ${escapeHtml(info.errorDetail)}
                        </p>
                        ${info.prompt ? `
                        <button type="button" class="btn-retry-error px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider transition cursor-pointer flex items-center gap-1.5 rounded-none"
                                data-session-id="${escapeHtml(info.sessionId)}"
                                data-prompt="${escapeHtml(info.prompt)}">
                            <i class="fa-solid fa-rotate-right text-xs"></i>
                            <span>Reintentar</span>
                        </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        chatContainer.appendChild(row);

        const retryBtn = row.querySelector('.btn-retry-error');
        if (retryBtn) {
            retryBtn.addEventListener('click', function () {
                const sId = this.getAttribute('data-session-id');
                const pText = this.getAttribute('data-prompt');
                row.remove();
                handleRetryMessage({
                    sessionId: sId,
                    prompt: pText
                });
            });
        }
    }

    /**
     * Reintenta una consulta reutilizando el mensaje en el backend si retry_message_id está disponible.
     */
    function handleRetryMessage(options) {
        handleSendMessage({
            prompt: options.prompt,
            sessionId: options.sessionId,
            retryMessageId: options.retryMessageId || null
        });
    }

    /**
     * Elimina una conversación del historial del usuario cancelando streams activos si los hubiera.
     */
    async function handleDeleteSession(sessionId) {
        if (!sessionId) return;

        const executeDelete = async () => {
            try {
                // Si la sesión tiene un stream activo, abortarlo y limpiarlo
                const activeJob = ActiveJobStore.get(sessionId);
                if (activeJob) {
                    if (activeJob.inactivityTimer) clearTimeout(activeJob.inactivityTimer);
                    if (activeJob.reader) {
                        try { await activeJob.reader.cancel(); } catch (e) {}
                    }
                    if (activeJob.abortController) {
                        activeJob.abortController.abort();
                    }
                    ActiveJobStore.delete(sessionId);
                    trackInflightSession(sessionId, false);
                }

                const res = await fetch(`/chat/sessions/${encodeURIComponent(sessionId)}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                if (!res.ok) {
                    throw new Error('No se pudo eliminar la conversación.');
                }

                if (currentSessionId === sessionId) {
                    resetChatSession();
                }
                await refreshUserSessionsSidebar();

                notify('success', 'Conversación eliminada correctamente.', 'Historial');
            } catch (err) {
                notify('error', err.message || 'Error al eliminar la conversación.', 'Error');
            }
        };

        if (window.Swal) {
            Swal.fire({
                title: '¿Eliminar conversación?',
                text: 'Esta conversación se borrará permanentemente de tu historial.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#4B89BF',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeDelete();
                }
            });
        } else if (confirm('¿Deseas eliminar esta conversación de tu historial?')) {
            executeDelete();
        }
    }

    /**
     * Resetea la conversación actual a un lienzo limpio sin modales de confirmación.
     * Preserva el borrador si el usuario ya tenía texto redactado en el textarea.
     */
    function handleResetChat() {
        if (isProcessing) return;

        resetChatSession();
    }

    function resetChatSession() {
        currentNavToken++;
        currentSessionId = null;
        viewedStreamId = null;
        chatContainer.innerHTML = '';
        if (emptyState) {
            chatContainer.appendChild(emptyState);
            emptyState.classList.remove('hidden');
        }

        // Limpieza de archivos adjuntos activos
        activeAttachments = [];
        if (attachedFilesContainer) {
            attachedFilesContainer.innerHTML = '';
        }

        // Preservación de borrador: solo limpiar si estaba vacío o con espacios
        if (promptInput) {
            const hasDraft = promptInput.value && promptInput.value.trim().length > 0;
            if (!hasDraft) {
                promptInput.value = '';
                promptInput.style.height = 'auto';
            }
            setTimeout(() => {
                promptInput.focus();
            }, 50);
        }
        
        // Desmarcar selecciones previas en el sidebar
        if (userSessionsList) {
            userSessionsList.querySelectorAll('.session-history-item').forEach(el => {
                el.classList.remove('border-datamind-primary', 'bg-datamind-light/25', 'bg-slate-100');
            });
        }

        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, '', '/chat');
        }

        updateSendButtonState();
        refreshUserSessionsSidebar();
        notify('success', 'Nueva conversación iniciada.', 'DataMind AI');
    }

    /**
     * Procesa y envía la pregunta del usuario con soporte de reintento y background streaming.
     */
    async function handleSendMessage(options = {}) {
        const isRetry = Boolean(options.retryMessageId);
        const text = isRetry ? options.prompt : promptInput.value.trim();
        if (!text) return;

        const targetSessionId = isRetry ? options.sessionId : currentSessionId;

        // Comprobar bloqueo por sesión
        if (isSessionBusy(targetSessionId)) {
            notify('warning', 'Esta conversación ya está procesando una consulta. Espera a que termine.', 'DataMind AI');
            return;
        }

        // Tope de concurrencia: máximo 3 streams simultáneos
        if (countActiveStreams() >= 3) {
            notify('warning', 'Hay 3 consultas procesándose en segundo plano. Espera a que una finalice para iniciar otra.', 'Límite de Concurrencia');
            return;
        }

        const streamId = (typeof crypto !== 'undefined' && crypto.randomUUID) 
            ? crypto.randomUUID() 
            : 'stream_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        // Identificador de trabajo en ActiveJobStore
        // Para sesiones existentes se indexa por su sessionId real desde el inicio; para nuevas por streamId como tempId
        const jobKey = targetSessionId || streamId;

        const abortController = new AbortController();

        const job = {
            streamId: streamId,
            sessionId: targetSessionId,
            tempId: targetSessionId ? null : streamId,
            prompt: text,
            sessionTitle: text.substring(0, 45) + (text.length > 45 ? '...' : ''),
            connectionId: activeConnectionSelect?.value || null,
            startedAt: Date.now(),
            lastActivityAt: Date.now(),
            status: 'connecting',
            rawMarkdown: '',
            dataset: null,
            errorDetail: null,
            latencyMs: null,
            hasUnread: false,
            navToken: currentNavToken,
            reader: null,
            abortController: abortController,
            inactivityTimer: null,
            textSpan: null,
            contentWrapper: null,
            footerBadge: null
        };

        ActiveJobStore.set(jobKey, job);
        if (targetSessionId) {
            trackInflightSession(targetSessionId, true);
        }

        // Si la acción proviene de la vista actual, asociar al contexto visible
        viewedStreamId = streamId;

        // Ocultar placeholder de bienvenida
        if (emptyState) {
            emptyState.classList.add('hidden');
        }

        if (!isRetry) {
            // Guardar copia de los adjuntos enviados con este mensaje para mostrarlos en la burbuja
            const sentAttachments = [...activeAttachments];
            appendUserMessage(text, sentAttachments);
            promptInput.value = '';
            promptInput.style.height = 'auto';

            // Limpiar la barra de entrada una vez que el mensaje y sus adjuntos fueron despachados
            activeAttachments = [];
            if (attachedFilesContainer) {
                attachedFilesContainer.innerHTML = '';
            }
        }

        const { messageCard, textSpan, contentWrapper, footerBadge } = createAssistantMessageCard();
        job.textSpan = textSpan;
        job.contentWrapper = contentWrapper;
        job.footerBadge = footerBadge;

        scrollToBottom();
        updateSendButtonState();
        refreshUserSessionsSidebar();

        // Función para reiniciar el timeout de inactividad (ampliado a 180 segundos para CPU local pesada)
        const resetInactivityTimer = () => {
            if (job.inactivityTimer) clearTimeout(job.inactivityTimer);
            job.lastActivityAt = Date.now();
            job.inactivityTimer = setTimeout(() => {
                console.warn(`[DataMind Chat] Timeout por inactividad (180s) en stream: ${job.streamId}`);
                job.status = 'error';
                job.errorDetail = 'Tiempo de espera agotado por inactividad del servidor (180s).';
                job.abortController.abort();
                if (viewedStreamId === job.streamId) {
                    if (job.textSpan) {
                        job.textSpan.innerHTML += `<div class="mt-2 p-3 bg-red-50 text-red-700 text-xs rounded-xl border border-red-200">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i> ${escapeHtml(job.errorDetail)}
                        </div>`;
                    }
                } else {
                    job.hasUnread = true;
                    notify('error', `Error en la sesión "${job.sessionTitle}": tiempo de espera agotado`, 'Sesión en Segundo Plano');
                }
                updateSendButtonState();
                refreshUserSessionsSidebar();
            }, 180000);
        };

        resetInactivityTimer();

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
                    session_id: targetSessionId,
                    connection_id: activeConnectionSelect?.value || null,
                    retry_message_id: options.retryMessageId || null,
                    attachment_ids: activeAttachments.map(a => a.id)
                }),
                signal: abortController.signal
            });

            if (!response.ok) {
                throw new Error(`Error en el servidor (${response.status}): ${response.statusText}`);
            }

            job.status = 'streaming';
            updateSendButtonState();
            refreshUserSessionsSidebar();

            const reader = response.body.getReader();
            job.reader = reader;
            const decoder = new TextDecoder('utf-8');
            let buffer = '';

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                resetInactivityTimer();
                buffer += decoder.decode(value, { stream: true });
                const events = buffer.split('\n\n');
                buffer = events.pop() || '';

                for (const rawEvent of events) {
                    if (!rawEvent.trim()) continue;

                    // Ignorar líneas de comentario SSE (latidos de ping)
                    if (rawEvent.startsWith(':')) continue;

                    const lines = rawEvent.split('\n');
                    let eventName = 'message';
                    let eventData = '';

                    for (const line of lines) {
                        if (line.startsWith(':')) continue; // Latido de ping
                        if (line.startsWith('event:')) {
                            eventName = line.substring(6).trim();
                        } else if (line.startsWith('data:')) {
                            eventData = line.substring(5).trim();
                        }
                    }

                    if (!eventData) continue;

                    try {
                        const parsed = JSON.parse(eventData);
                        handleJobSSEEvent(job, eventName, parsed);
                    } catch (err) {
                        console.warn('Error al parsear SSE JSON:', err, eventData);
                    }
                }
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            console.error('Error en streaming de DataMind AI:', error);
            job.status = 'error';
            job.errorDetail = error.message;

            if (job.sessionId) {
                trackInflightSession(job.sessionId, false);
            }

            if (viewedStreamId === job.streamId) {
                if (job.textSpan) {
                    job.textSpan.innerHTML += `<div class="mt-2 p-3 bg-red-50 text-red-700 text-xs rounded-xl border border-red-200">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> ${escapeHtml(error.message)}
                    </div>`;
                }
                notify('error', error.message, 'Error de Comunicación');
                ActiveJobStore.delete(jobKey);
            } else {
                job.hasUnread = true;
                notify('error', `Error en la sesión "${job.sessionTitle}": ${error.message}`, 'Sesión en Segundo Plano');
            }
        } finally {
            if (job.inactivityTimer) clearTimeout(job.inactivityTimer);
            updateSendButtonState();
            refreshUserSessionsSidebar();
            if (viewedStreamId === job.streamId) {
                scrollToBottom();
                promptInput.focus();
            }
        }
    }

    /**
     * Procesa cada evento SSE canalizándolo al job correspondiente y respetando viewedStreamId.
     */
    function handleJobSSEEvent(job, eventName, data) {
        const isCurrentView = (viewedStreamId === job.streamId);

        // Remover indicador de carga en primer token o evento
        if (isCurrentView && job.textSpan) {
            const loader = job.textSpan.querySelector('.ai-loading-indicator');
            if (loader) loader.remove();
        }

        switch (eventName) {
            case 'session':
                if (data.session_id) {
                    const realSessionId = data.session_id;
                    const oldKey = job.sessionId || job.tempId || job.streamId;

                    job.sessionId = realSessionId;
                    trackInflightSession(realSessionId, true);

                    if (oldKey !== realSessionId) {
                        ActiveJobStore.delete(oldKey);
                        ActiveJobStore.set(realSessionId, job);
                    }

                    if (isCurrentView) {
                        currentSessionId = realSessionId;
                        if (window.history && window.history.replaceState) {
                            window.history.replaceState({}, '', `/chat?session=${encodeURIComponent(realSessionId)}`);
                        }
                    }
                    refreshUserSessionsSidebar();
                }
                break;

            case 'token':
                if (data.text) {
                    job.rawMarkdown += data.text;
                    if (isCurrentView && job.textSpan) {
                        job.textSpan.dataset.rawMarkdown = job.rawMarkdown;
                        job.textSpan.innerHTML = renderMarkdownPreview(job.rawMarkdown);
                        scrollToBottom();
                    }
                }
                break;

            case 'clarification':
                job.clarification = data;
                if (isCurrentView && job.contentWrapper) {
                    renderClarificationButtons(data, job.contentWrapper);
                }
                break;

            case 'dataset':
                job.dataset = data;
                if (isCurrentView && job.contentWrapper) {
                    renderDatasetSection(data, job.contentWrapper);
                }
                break;

            case 'done':
                job.status = 'completed';
                job.latencyMs = data.latency_ms || null;
                job.rulesApplied = data.rules_applied || [];

                if (job.footerBadge) {
                    const latency = job.latencyMs ? `${job.latencyMs} ms` : 'Listo';
                    let badgeHtml = `<span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-400">
                        <i class="fa-solid fa-clock-rotate-left text-datamind-primary"></i> ${latency}
                    </span>`;
                    if (Array.isArray(job.rulesApplied) && job.rulesApplied.length > 0) {
                        const ruleTitles = job.rulesApplied.map(r => escapeHtml(r.title || 'Regla')).join(', ');
                        badgeHtml = `<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full mr-2" title="${ruleTitles}">
                            <i class="fa-solid fa-brain text-emerald-600"></i> Reglas aplicadas: ${escapeHtml(job.rulesApplied[0].title || 'Regla')}${job.rulesApplied.length > 1 ? ` (+${job.rulesApplied.length - 1})` : ''}
                        </span>` + badgeHtml;
                    }
                    job.footerBadge.innerHTML = badgeHtml;
                }

                if (job.sessionId) {
                    trackInflightSession(job.sessionId, false);
                }

                if (isCurrentView) {
                    // Si finalizó en primer plano: hidratar estado final persistido desde BD y purgar job
                    ActiveJobStore.delete(job.sessionId || job.streamId);
                    viewedStreamId = null;
                    if (job.sessionId) {
                        loadConversationSession(job.sessionId, { preserveView: true });
                    }
                } else {
                    // Si finalizó en segundo plano: conservar en ActiveJobStore con badge de listo
                    job.hasUnread = true;
                    notify('success', `La consulta en "${job.sessionTitle}" ha finalizado con éxito.`, 'Sesión Lista');
                    refreshUserSessionsSidebar();
                }
                break;

            case 'error':
                job.status = 'error';
                job.errorDetail = data.detail || 'Error desconocido';

                if (job.sessionId) {
                    trackInflightSession(job.sessionId, false);
                }

                if (isCurrentView) {
                    if (job.textSpan) {
                        job.textSpan.innerHTML += `<div class="mt-2 p-3 bg-red-50 text-red-700 text-xs rounded-xl border border-red-200">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i> ${escapeHtml(job.errorDetail)}
                        </div>`;
                    }
                    ActiveJobStore.delete(job.sessionId || job.streamId);
                    notify('error', job.errorDetail, 'Error de Asistente');
                } else {
                    job.hasUnread = true;
                    notify('error', `Error en "${job.sessionTitle}": ${job.errorDetail}`, 'Sesión en Segundo Plano');
                    refreshUserSessionsSidebar();
                }
                break;
        }
    }

    /**
     * Recalcula el estado de habilitación del botón de enviar según la vista activa.
     */
    function updateSendButtonState() {
        if (!sendButton) return;
        const busy = isSessionBusy(currentSessionId);
        sendButton.disabled = busy;
        sendButton.classList.toggle('opacity-50', busy);
        sendButton.classList.toggle('cursor-not-allowed', busy);
    }

    /**
     * Renderiza la vista previa de datos, el Constructor Interactivo de Gráficos y el botón de descarga de Excel.
     */
    function renderDatasetSection(dataset, contentWrapper) {
        const { dataset_id, row_count, columns, preview_rows, sql, chart_config } = dataset;
        const safeId = dataset_id.replace(/[^a-zA-Z0-9_]/g, '_');
        const uniqueTableId = 'dt_' + safeId;
        const tablePanelId = 'table_panel_' + safeId;
        const tableToggleBtnId = 'table_toggle_' + safeId;
        const chartPanelId = 'chart_panel_' + safeId;
        const chartToggleBtnId = 'chart_toggle_' + safeId;
        const autoExpandChart = Boolean(chart_config && chart_config.wants_chart);
        const autoExpandTable = Boolean(chart_config && chart_config.wants_table);

        const card = document.createElement('div');
        card.className = 'mt-5 pt-4 border-t border-slate-200/80 w-full';

        // Cabecera de la sección con botones bajo demanda (Tabla, Gráfico, Enseñar SQL y Descarga Excel)
        let html = `
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3 bg-datamind-neutral/85 p-3.5 rounded-xl border border-datamind-light/70">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 rounded-lg badge-datamind flex items-center justify-center text-sm font-bold shadow-xs">
                        <i class="fa-solid fa-database text-datamind-primary"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-800">Resultado de Consulta Corporativa</h4>
                        <p class="text-[11px] text-datamind-secondary font-medium">${row_count} registros devueltos &bull; SQL Analítico</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="${tableToggleBtnId}"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-white hover:bg-slate-50 text-datamind-dark border border-datamind-light/85 text-xs font-semibold shadow-2xs transition duration-150 cursor-pointer">
                        <i class="fa-solid fa-table text-datamind-primary text-xs"></i>
                        <span>${autoExpandTable ? 'Ocultar Tabla' : 'Ver Tabla (' + row_count + ')'}</span>
                    </button>
                    <button type="button" id="${chartToggleBtnId}"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg btn-datamind-primary text-xs font-semibold shadow-xs transition duration-150 cursor-pointer">
                        <i class="fa-solid fa-chart-pie text-sm"></i>
                        <span>${autoExpandChart ? 'Ocultar Gráfico' : 'Personalizar / Ver Gráfico'}</span>
                    </button>
                    ${sql ? `
                    <button type="button" class="btn-teach-sql inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-datamind-light/30 hover:bg-datamind-light/50 text-datamind-dark border border-datamind-light text-xs font-semibold shadow-2xs transition cursor-pointer"
                            data-sql="${escapeHtml(sql)}" title="Guardar esta consulta como ejemplo SQL de oro para que la IA aprenda de ella">
                        <i class="fa-solid fa-brain text-datamind-primary"></i>
                        <span>Enseñar este SQL a la IA</span>
                    </button>
                    ` : ''}
                    <a href="/chat/export/${dataset_id}" 
                       onclick="if(window.notify){ window.notify('info', 'Descargando archivo Excel estilizado...', 'DataMind AI'); }"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold shadow-xs transition duration-150">
                        <i class="fa-solid fa-file-excel text-sm"></i>
                        <span>Generar Excel (.xlsx)</span>
                    </a>
                </div>
            </div>
        `;

        // Panel del Constructor Interactivo de Gráficos (DataMind Visual Analytics - Flujo Guiado Opción A)
        if (columns && columns.length > 0) {
            const dimCols = (chart_config && Array.isArray(chart_config.dimension_columns) && chart_config.dimension_columns.length > 0)
                ? chart_config.dimension_columns
                : columns;
            const metricCols = (chart_config && Array.isArray(chart_config.metric_columns))
                ? chart_config.metric_columns
                : [];
            const colSamples = (chart_config && chart_config.column_samples) ? chart_config.column_samples : {};
            const needsFieldSelection = Boolean(chart_config && chart_config.needs_field_selection);
            const defaultType = (chart_config && (chart_config.suggested_type || chart_config.chart_type)) || 'bar';
            const defaultX = (chart_config && (chart_config.suggested_x || chart_config.x_field)) || dimCols[0] || columns[0];
            const defaultY = (chart_config && (chart_config.suggested_y || chart_config.y_field)) || metricCols[0] || columns[0];
            const defaultAgg = (chart_config && (chart_config.suggested_agg || chart_config.aggregation)) || (metricCols.length > 0 ? 'SUM' : 'COUNT');

            html += `
                <div id="${chartPanelId}" class="${autoExpandChart ? '' : 'hidden '}w-full bg-white rounded-2xl border border-datamind-light/85 shadow-xs overflow-hidden mt-3 mb-4 transition-all">
                    <div class="p-3.5 bg-datamind-neutral border-b border-datamind-light/60 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="h-7 w-7 rounded-lg bg-datamind-primary/15 text-datamind-primary flex items-center justify-center text-xs font-bold">
                                <i class="fa-solid fa-chart-column"></i>
                            </span>
                            <div>
                                <h5 class="text-xs font-bold text-datamind-dark">
                                    ${needsFieldSelection ? 'Asistente de Selección de Campos (Paso 1 de 2)' : 'Constructor Interactivo de Gráficos'}
                                </h5>
                                <p class="text-[11px] text-slate-500">
                                    ${needsFieldSelection
                                        ? 'La IA analizó las tablas relacionadas. Elige con qué campos deseas generar tu gráfico:'
                                        : 'Selecciona con un clic qué campos y operación deseas visualizar'}
                                </p>
                            </div>
                        </div>
                        <button type="button" class="btn-download-chart ${needsFieldSelection ? 'hidden ' : ''}inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white hover:bg-slate-50 text-datamind-dark border border-datamind-light/80 text-xs font-semibold transition cursor-pointer shadow-2xs">
                            <i class="fa-solid fa-camera text-datamind-primary"></i> Descargar PNG
                        </button>
                    </div>

                    <!-- Banner de Confirmación Guiada (Opción A: cuando la IA pregunta con qué campos crear el gráfico) -->
                    <div class="chart-wizard-banner ${needsFieldSelection ? '' : 'hidden '}p-4 bg-datamind-light/20 border-b border-datamind-light/70">
                        <div class="flex items-start gap-3">
                            <div class="h-8 w-8 rounded-xl bg-datamind-primary text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-2xs">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </div>
                            <div class="flex-1">
                                <h6 class="text-xs font-bold text-datamind-dark mb-1">
                                    ¿Con qué campos de las tablas consultadas deseas crear el gráfico?
                                </h6>
                                <p class="text-[11px] text-slate-600 mb-3">
                                    He detectado <strong>${dimCols.length} campos de agrupación/estado</strong> y <strong>${metricCols.length} campos numéricos</strong>. Selecciona abajo los ejes que prefieras y pulsa <strong>Generar Gráfico</strong> (o pídeselo a la IA).
                                </p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" class="btn-confirm-generate-chart inline-flex items-center gap-1.5 px-4 py-2 rounded-xl btn-datamind-primary text-xs font-bold shadow-xs transition cursor-pointer">
                                        <i class="fa-solid fa-chart-Simple"></i> ✨ Generar Gráfico con estos Campos
                                    </button>
                                    <button type="button" class="btn-ask-ai-chart inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white hover:bg-datamind-neutral text-datamind-dark border border-datamind-light text-xs font-semibold transition cursor-pointer">
                                        <i class="fa-solid fa-robot text-datamind-primary"></i> 💬 Pedirle a la IA en el Chat
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjetas / Chips rápidos de selección de campo principal (Eje X) con muestra de dato real -->
                    <div class="px-4 pt-3 pb-2.5 border-b border-slate-100 bg-slate-50/60">
                        <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider block mb-2">
                            <i class="fa-solid fa-layer-group text-datamind-primary mr-1"></i> Clic rápido para elegir Campo Principal (Eje X):
                        </span>
                        <div class="flex flex-wrap gap-1.5 chart-field-chips">
                            ${columns.map(col => {
                                const isSuggestedDim = dimCols.includes(col);
                                const isActiveChip = col === defaultX;
                                const sampleInfo = colSamples[col] || {};
                                const isFkId = sampleInfo.kind === 'foreign_key';
                                const sampleVal = sampleInfo.sample || (preview_rows && preview_rows[0] && preview_rows[0][col] !== undefined ? String(preview_rows[0][col]).substring(0, 22) : '');
                                return `
                                    <button type="button" data-col="${escapeHtml(col)}"
                                            title="${isFkId ? 'Llave foránea / ID de relación (' + escapeHtml(col) + ')' : (sampleVal ? 'Ejemplo: ' + escapeHtml(sampleVal) : escapeHtml(col))}"
                                            class="chart-chip-btn text-[11px] px-2.5 py-1.5 rounded-xl border font-semibold transition cursor-pointer flex items-center gap-1.5 ${
                                                isActiveChip
                                                    ? 'bg-datamind-primary text-white border-datamind-primary shadow-2xs'
                                                    : 'bg-white text-slate-700 border-slate-200 hover:border-datamind-light hover:bg-datamind-light/20'
                                            }">
                                        <i class="fa-solid ${isFkId ? 'fa-key' : (isSuggestedDim ? 'fa-tag' : 'fa-hashtag')} text-[10px] opacity-80"></i>
                                        <span>${escapeHtml(col)}</span>
                                        ${sampleVal ? `<span class="text-[9px] px-1.5 py-0.2 rounded bg-black/10 opacity-85 truncate max-w-[95px]">ej: ${escapeHtml(sampleVal)}</span>` : ''}
                                    </button>
                                `;
                            }).join('')}
                        </div>
                    </div>

                    <!-- 4 Controles Súper Sencillos: Tipo, Eje X, Operación y Eje Y -->
                    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 bg-white border-b border-slate-100">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">1. Tipo de Gráfico</label>
                            <select class="chart-type-select w-full px-2.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-800 focus:outline-hidden focus:border-datamind-primary cursor-pointer">
                                <option value="bar" ${defaultType === 'bar' ? 'selected' : ''}>📊 Barras Verticales</option>
                                <option value="horizontalBar" ${defaultType === 'horizontalBar' ? 'selected' : ''}>📉 Barras Horizontales</option>
                                <option value="line" ${defaultType === 'line' ? 'selected' : ''}>📈 Líneas / Tendencia</option>
                                <option value="doughnut" ${defaultType === 'doughnut' ? 'selected' : ''}>🍩 Anillo / Dona</option>
                                <option value="pie" ${defaultType === 'pie' ? 'selected' : ''}>🥧 Pastel / Proporción</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">2. Eje X (Agrupar por)</label>
                            <select class="chart-x-select w-full px-2.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-800 focus:outline-hidden focus:border-datamind-primary cursor-pointer">
                                ${columns.map(col => `<option value="${escapeHtml(col)}" ${col === defaultX ? 'selected' : ''}>${escapeHtml(col)}${dimCols.includes(col) ? ' (Categoría)' : ''}</option>`).join('')}
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">3. Operación / Cálculo</label>
                            <select class="chart-agg-select w-full px-2.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-800 focus:outline-hidden focus:border-datamind-primary cursor-pointer">
                                <option value="COUNT" ${defaultAgg === 'COUNT' ? 'selected' : ''}>🔢 Contar registros (COUNT)</option>
                                <option value="SUM" ${defaultAgg === 'SUM' ? 'selected' : ''}>➕ Sumar valores (SUM)</option>
                                <option value="AVG" ${defaultAgg === 'AVG' ? 'selected' : ''}>➗ Promedio (AVG)</option>
                                <option value="RAW" ${defaultAgg === 'RAW' ? 'selected' : ''}>📍 Valor directo por fila</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">4. Eje Y (Campo Numérico)</label>
                            <select class="chart-y-select w-full px-2.5 py-2 rounded-xl border border-slate-200 bg-slate-50 text-xs font-semibold text-slate-800 focus:outline-hidden focus:border-datamind-primary cursor-pointer" ${defaultAgg === 'COUNT' ? 'disabled' : ''}>
                                ${columns.map(col => `<option value="${escapeHtml(col)}" ${col === defaultY ? 'selected' : ''}>${escapeHtml(col)}${metricCols.includes(col) ? ' (Numérico)' : ''}</option>`).join('')}
                            </select>
                        </div>
                    </div>

                    <!-- Lienzo de Renderizado Chart.js (Oculto en Paso 1 hasta que el usuario confirme los campos) -->
                    <div class="chart-canvas-stage ${needsFieldSelection ? 'hidden ' : ''}p-4 bg-white">
                        <div class="relative w-full h-72 sm:h-80 flex items-center justify-center">
                            <canvas id="canvas_${safeId}"></canvas>
                        </div>
                    </div>
                </div>
            `;
        }

        // Si se devuelven columnas y filas de previsualización, renderizar la tabla (visible solo si wants_table === true o al pulsar Ver Tabla)
        if (columns && columns.length > 0 && preview_rows && preview_rows.length > 0) {
            html += `
                <div id="${tablePanelId}" class="${autoExpandTable ? '' : 'hidden '}w-full bg-white rounded-xl border border-datamind-light/75 shadow-2xs overflow-hidden mt-3">
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

    /**
     * Renderiza opciones de desambiguación y aclaración interactiva (Feature 006).
     */
    function renderClarificationButtons(clarificationData, contentWrapper) {
        if (!clarificationData || !clarificationData.options || !contentWrapper) return;

        const container = document.createElement('div');
        container.className = 'clarification-card mt-3 p-4 bg-amber-50/70 border border-amber-200/80 rounded-2xl shadow-2xs';

        const title = escapeHtml(clarificationData.question || '¿A cuál de las siguientes opciones te refieres?');
        const term = escapeHtml(clarificationData.extracted_term || '');

        let optionsHtml = clarificationData.options.map((opt, idx) => {
            const safeLabel = escapeHtml(opt.label || opt.id);
            const safeDesc = escapeHtml(opt.description || opt.hint || '');
            const safeVal = escapeHtml(opt.id || opt.label);

            return `
                <button type="button" 
                        class="btn-clarification-option w-full text-left p-3 rounded-xl border border-amber-200 bg-white hover:bg-amber-100/60 hover:border-amber-300 transition shadow-2xs flex items-center justify-between group cursor-pointer mb-2"
                        data-target-value="${safeVal}">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-bold">
                                ${idx + 1}
                            </span>
                            <span class="text-xs font-bold text-slate-800 group-hover:text-amber-900 truncate">
                                ${safeLabel}
                            </span>
                        </div>
                        ${safeDesc ? `<p class="text-[11px] text-slate-500 mt-1 pl-7 line-clamp-2">${safeDesc}</p>` : ''}
                    </div>
                    <i class="fa-solid fa-arrow-right text-xs text-amber-600 opacity-0 group-hover:opacity-100 transition-transform transform group-hover:translate-x-1 shrink-0 ml-3"></i>
                </button>
            `;
        }).join('');

        container.innerHTML = `
            <div class="flex items-center gap-2 mb-2.5 text-amber-900 font-bold text-xs">
                <i class="fa-solid fa-circle-question text-amber-600"></i>
                <span>${title}</span>
            </div>
            <div class="clarification-options-list">
                ${optionsHtml}
            </div>
        `;

        // Interacción de clic en las opciones
        container.querySelectorAll('.btn-clarification-option').forEach(btn => {
            btn.addEventListener('click', function () {
                const chosenTarget = this.dataset.targetValue;
                // Deshabilitar todos los botones para evitar doble clic
                container.querySelectorAll('.btn-clarification-option').forEach(b => {
                    b.disabled = true;
                    b.classList.add('opacity-50', 'cursor-not-allowed');
                });
                this.classList.remove('opacity-50');
                this.classList.add('bg-amber-200/80', 'border-amber-400');

                // Enviar comando contextual /clarify <target>
                if (promptInput) {
                    promptInput.value = `/clarify ${chosenTarget}`;
                    handleSendMessage();
                }
            });
        });

        contentWrapper.appendChild(container);
        scrollToBottom();
    }

        // Inicializar eventos del Constructor Interactivo de Gráficos
        if (columns && columns.length > 0) {
            const needsFieldSelection = Boolean(chart_config && chart_config.needs_field_selection);
            setupInteractiveChartBuilder(card, dataset_id, safeId, preview_rows || [], autoExpandChart, needsFieldSelection);
        }

        // Función perezosa para inicializar DataTables solo cuando la tabla sea visible
        let dataTableInitialized = false;
        function ensureDataTableInit() {
            if (dataTableInitialized) return;
            if (window.jQuery && window.jQuery.fn.DataTable && preview_rows && preview_rows.length > 0) {
                dataTableInitialized = true;
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
                }, 40);
            }
        }

        if (autoExpandTable) {
            ensureDataTableInit();
        }

        // Botón para Mostrar / Ocultar la Tabla Interactiva bajo demanda
        const tableToggleBtn = card.querySelector(`#${tableToggleBtnId}`);
        const tablePanel = card.querySelector(`#${tablePanelId}`);
        if (tableToggleBtn && tablePanel) {
            tableToggleBtn.addEventListener('click', function () {
                const isHidden = tablePanel.classList.contains('hidden');
                if (isHidden) {
                    tablePanel.classList.remove('hidden');
                    tableToggleBtn.querySelector('span').textContent = 'Ocultar Tabla';
                    ensureDataTableInit();
                } else {
                    tablePanel.classList.add('hidden');
                    tableToggleBtn.querySelector('span').textContent = `Ver Tabla (${row_count})`;
                }
            });
        }

        // Botón para abrir el Centro de Entrenamiento IA precargando este SQL como Ejemplo de Oro (Few-Shot)
        const teachSqlBtn = card.querySelector('.btn-teach-sql');
        if (teachSqlBtn) {
            teachSqlBtn.addEventListener('click', function () {
                const learnedSql = this.getAttribute('data-sql') || sql || '';
                openTrainingCenterWithSql(learnedSql);
            });
        }

        scrollToBottom();
    }

    /**
     * Configura los eventos e inicializa el gráfico interactivo con Chart.js local (Flujo Guiado Opción A).
     */
    function setupInteractiveChartBuilder(cardElement, datasetId, safeId, fallbackRows, autoExpand, initialNeedsFieldSelection) {
        const toggleBtn = cardElement.querySelector(`#chart_toggle_${safeId}`);
        const chartPanel = cardElement.querySelector(`#chart_panel_${safeId}`);
        const wizardBanner = cardElement.querySelector('.chart-wizard-banner');
        const canvasStage = cardElement.querySelector('.chart-canvas-stage');
        const confirmGenerateBtn = cardElement.querySelector('.btn-confirm-generate-chart');
        const askAiChartBtn = cardElement.querySelector('.btn-ask-ai-chart');
        const typeSelect = cardElement.querySelector('.chart-type-select');
        const xSelect = cardElement.querySelector('.chart-x-select');
        const aggSelect = cardElement.querySelector('.chart-agg-select');
        const ySelect = cardElement.querySelector('.chart-y-select');
        const downloadBtn = cardElement.querySelector('.btn-download-chart');
        const chipButtons = cardElement.querySelectorAll('.chart-chip-btn');
        const canvasElem = cardElement.querySelector(`#canvas_${safeId}`);
        let isWaitingFieldConfirmation = Boolean(initialNeedsFieldSelection);

        if (!chartPanel || !canvasElem) return;

        // Obtener todas las filas del dataset (desde caché en memoria, endpoint JSON o fallback a preview_rows)
        async function resolveDatasetRows() {
            if (datasetRowsCache[datasetId]) {
                return datasetRowsCache[datasetId];
            }
            try {
                const res = await fetch(`/chat/dataset/${encodeURIComponent(datasetId)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const json = await res.json();
                    if (json.success && json.data && Array.isArray(json.data.rows) && json.data.rows.length > 0) {
                        datasetRowsCache[datasetId] = json.data.rows;
                        return json.data.rows;
                    }
                }
            } catch (e) {
                console.warn('Usando preview_rows locales para el gráfico:', e);
            }
            datasetRowsCache[datasetId] = fallbackRows;
            return fallbackRows;
        }

        function syncChipSelection(selectedX) {
            chipButtons.forEach(btn => {
                const col = btn.getAttribute('data-col');
                if (col === selectedX) {
                    btn.className = 'chart-chip-btn text-[11px] px-2.5 py-1.5 rounded-xl border font-semibold transition cursor-pointer flex items-center gap-1.5 bg-datamind-primary text-white border-datamind-primary shadow-2xs';
                } else {
                    btn.className = 'chart-chip-btn text-[11px] px-2.5 py-1.5 rounded-xl border font-semibold transition cursor-pointer flex items-center gap-1.5 bg-white text-slate-700 border-slate-200 hover:border-datamind-light hover:bg-datamind-light/20';
                }
            });
        }

        function revealChartCanvasStage() {
            isWaitingFieldConfirmation = false;
            if (wizardBanner) {
                wizardBanner.classList.add('hidden');
            }
            if (canvasStage) {
                canvasStage.classList.remove('hidden');
            }
            if (downloadBtn) {
                downloadBtn.classList.remove('hidden');
            }
        }

        async function updateChartVisualization(forceRender = false) {
            if (!window.Chart) return;

            const aggOp = aggSelect.value;
            const xCol = xSelect.value;
            ySelect.disabled = (aggOp === 'COUNT');
            syncChipSelection(xCol);

            // Si estamos en Paso 1 (Opción A: esperando que el usuario confirme los campos) y aún no ha confirmado,
            // solo actualizamos los selectores/chips sin dibujar el gráfico prematuramente.
            if (isWaitingFieldConfirmation && !forceRender) {
                return;
            }

            revealChartCanvasStage();

            const rows = await resolveDatasetRows();
            if (!rows || rows.length === 0) return;

            const rawType = typeSelect.value;
            const yCol = ySelect.value;

            // Agrupar y calcular datos según la selección del usuario
            const grouped = new Map();
            if (aggOp === 'RAW') {
                rows.slice(0, 30).forEach((row, idx) => {
                    const label = String(row[xCol] ?? `Fila ${idx + 1}`);
                    const val = parseNumericCell(row[yCol]);
                    grouped.set(`${label} (#${idx + 1})`, { sum: val, count: 1 });
                });
            } else {
                rows.forEach(row => {
                    const rawKey = row[xCol];
                    const key = (rawKey === null || rawKey === undefined || String(rawKey).trim() === '')
                        ? 'Sin especificar'
                        : String(rawKey).trim();
                    if (!grouped.has(key)) {
                        grouped.set(key, { sum: 0, count: 0 });
                    }
                    const entry = grouped.get(key);
                    entry.count += 1;
                    if (aggOp !== 'COUNT') {
                        entry.sum += parseNumericCell(row[yCol]);
                    }
                });
            }

            // Limitar a las 20 categorías principales para legibilidad óptima
            const entries = Array.from(grouped.entries()).map(([label, stats]) => {
                let value = stats.count;
                if (aggOp === 'SUM' || aggOp === 'RAW') {
                    value = Number(stats.sum.toFixed(2));
                } else if (aggOp === 'AVG') {
                    value = stats.count > 0 ? Number((stats.sum / stats.count).toFixed(2)) : 0;
                }
                return { label, value };
            });

            if (aggOp !== 'RAW') {
                entries.sort((a, b) => b.value - a.value);
            }
            const topEntries = entries.slice(0, 20);

            const labels = topEntries.map(e => e.label.length > 28 ? e.label.substring(0, 25) + '...' : e.label);
            const values = topEntries.map(e => e.value);

            const isHorizontal = (rawType === 'horizontalBar');
            const chartJsType = isHorizontal ? 'bar' : rawType;
            const isCircular = (chartJsType === 'doughnut' || chartJsType === 'pie');

            const metricDescription = aggOp === 'COUNT'
                ? `Cantidad de registros por ${xCol}`
                : `${aggOp}(${yCol}) agrupado por ${xCol}`;

            if (chartInstances[safeId]) {
                chartInstances[safeId].destroy();
            }

            const bgColors = isCircular
                ? labels.map((_, i) => DATAMIND_CHART_PALETTE[i % DATAMIND_CHART_PALETTE.length])
                : (chartJsType === 'line' ? 'rgba(4, 118, 217, 0.14)' : 'rgba(4, 118, 217, 0.82)');

            const borderColors = isCircular
                ? '#ffffff'
                : '#0476D9';

            chartInstances[safeId] = new window.Chart(canvasElem.getContext('2d'), {
                type: chartJsType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: metricDescription,
                        data: values,
                        backgroundColor: bgColors,
                        borderColor: borderColors,
                        borderWidth: isCircular ? 2 : 2,
                        borderRadius: chartJsType === 'bar' ? 6 : 0,
                        fill: chartJsType === 'line',
                        tension: chartJsType === 'line' ? 0.32 : 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: isHorizontal ? 'y' : 'x',
                    plugins: {
                        legend: {
                            display: true,
                            position: isCircular ? 'right' : 'top',
                            labels: {
                                font: { size: 11, weight: '600' },
                                color: '#334155'
                            }
                        },
                        title: {
                            display: true,
                            text: metricDescription,
                            font: { size: 13, weight: '700' },
                            color: '#0f172a',
                            padding: { bottom: 10 }
                        }
                    },
                    scales: isCircular ? {} : {
                        x: {
                            ticks: { font: { size: 10 }, color: '#475569' },
                            grid: { color: 'rgba(148, 163, 184, 0.15)' }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { font: { size: 10 }, color: '#475569' },
                            grid: { color: 'rgba(148, 163, 184, 0.2)' }
                        }
                    }
                }
            });
        }

        // Botón mostrar/ocultar constructor de gráficos
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const isHidden = chartPanel.classList.contains('hidden');
                if (isHidden) {
                    chartPanel.classList.remove('hidden');
                    toggleBtn.querySelector('span').textContent = 'Ocultar Gráfico';
                    if (!isWaitingFieldConfirmation) {
                        updateChartVisualization(true);
                    }
                } else {
                    chartPanel.classList.add('hidden');
                    toggleBtn.querySelector('span').textContent = 'Personalizar / Ver Gráfico';
                }
            });
        }

        // Botón Paso 1 -> Paso 2: Confirmar y generar el gráfico inmediatamente
        if (confirmGenerateBtn) {
            confirmGenerateBtn.addEventListener('click', function () {
                updateChartVisualization(true);
                notify('success', `Gráfico generado con el campo "${xSelect.value}"`, 'DataMind Visual Analytics');
            });
        }

        // Botón Paso 1 -> Pedirle a la IA en el chat que genere y explique el gráfico con los campos elegidos
        if (askAiChartBtn) {
            askAiChartBtn.addEventListener('click', function () {
                if (isProcessing || !promptInput) return;
                const xCol = xSelect.value;
                const aggOp = aggSelect.value;
                const yCol = ySelect.value;
                const typeLabel = typeSelect.options[typeSelect.selectedIndex]?.text.replace(/^[^\wÁÉÍÓÚáéíóú]+/, '').trim() || 'Barras';
                const metricPhrase = aggOp === 'COUNT'
                    ? 'contando la cantidad de registros'
                    : `calculando ${aggOp} del campo ${yCol}`;

                promptInput.value = `Genera el gráfico de ${typeLabel} agrupando por el campo ${xCol} y ${metricPhrase}`;
                handleSendMessage();
            });
        }

        // Eventos en los 4 selectores
        [typeSelect, xSelect, aggSelect, ySelect].forEach(sel => {
            if (sel) {
                sel.addEventListener('change', function () {
                    updateChartVisualization(false);
                });
            }
        });

        // Eventos en los chips rápidos del Eje X
        chipButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const col = this.getAttribute('data-col');
                if (col && xSelect) {
                    xSelect.value = col;
                    updateChartVisualization(false);
                }
            });
        });

        // Botón para descargar el gráfico como imagen PNG
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function () {
                const chart = chartInstances[safeId];
                if (!chart) return;
                const link = document.createElement('a');
                link.download = `datamind_grafico_${datasetId}.png`;
                link.href = canvasElem.toDataURL('image/png', 1.0);
                link.click();
                notify('success', 'Gráfico descargado en formato PNG.', 'DataMind Visual Analytics');
            });
        }

        // Si el usuario ya especificó los campos exactos (needs_field_selection === false), renderizar el gráfico automáticamente
        if (autoExpand && !isWaitingFieldConfirmation) {
            setTimeout(() => {
                updateChartVisualization(true);
            }, 60);
        }
    }

    /**
     * Convierte de forma segura cualquier celda a número flotante para agregaciones de gráficos.
     */
    function parseNumericCell(val) {
        if (val === null || val === undefined) return 0;
        if (typeof val === 'number') return isFinite(val) ? val : 0;
        const cleaned = String(val).replace(/[,$%\s]/g, '');
        const parsed = parseFloat(cleaned);
        return isFinite(parsed) ? parsed : 0;
    }

    /**
     * Agrega el mensaje del usuario al chat renderizado como Vista Previa Markdown,
     * mostrando además chips visuales de los archivos adjuntados en ese mensaje específico.
     */
    function appendUserMessage(text, attachments = []) {
        const row = document.createElement('div');
        row.className = 'flex justify-end mb-6';

        let attachmentsHtml = '';
        if (Array.isArray(attachments) && attachments.length > 0) {
            const chips = attachments.map(att => {
                const name = escapeHtml(att.original_name || 'Archivo adjunto');
                const nameLower = (att.original_name || '').toLowerCase();
                let iconClass = 'fa-file-csv text-blue-200';
                if (nameLower.endsWith('.xlsx') || nameLower.endsWith('.xls')) {
                    iconClass = 'fa-file-excel text-emerald-200';
                } else if (nameLower.endsWith('.pdf')) {
                    iconClass = 'fa-file-pdf text-red-200';
                } else if (nameLower.endsWith('.docx')) {
                    iconClass = 'fa-file-word text-blue-200';
                }
                const sizeKb = Math.round((att.file_size_bytes || 0) / 1024);
                return `
                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/15 border border-white/25 text-white text-[11px] font-mono rounded-lg shadow-2xs backdrop-blur-xs">
                        <i class="fa-solid ${iconClass} text-xs"></i>
                        <span class="truncate max-w-[180px] font-medium" title="${name}">${name}</span>
                        <span class="text-[9px] opacity-75">(${sizeKb} KB)</span>
                    </div>
                `;
            }).join('');

            attachmentsHtml = `<div class="flex flex-wrap gap-1.5 mb-2.5 justify-end">${chips}</div>`;
        }

        row.innerHTML = `
            <div class="max-w-2xl flex items-start gap-3 flex-row-reverse">
                <div class="h-9 w-9 rounded-xl bg-datamind-primary text-white flex items-center justify-center text-sm font-bold shadow-md shrink-0">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="flex flex-col items-end">
                    ${attachmentsHtml}
                    <div class="user-message-bubble bg-datamind-primary text-white p-4 rounded-2xl rounded-tr-xs shadow-sm border border-datamind-dark/20">
                        <div class="markdown-body markdown-user">${renderMarkdownPreview(text)}</div>
                    </div>
                </div>
            </div>
        `;

        chatContainer.appendChild(row);
    }

    /**
     * Crea la tarjeta base para el mensaje del asistente con contenedor de Vista Previa Markdown.
     */
    function createAssistantMessageCard() {
        const row = document.createElement('div');
        row.className = 'flex justify-start mb-6 w-full';

        row.innerHTML = `
            <div class="max-w-3xl w-full flex items-start gap-3">
                <div class="h-9 w-9 rounded-xl bg-datamind-avatar text-white flex items-center justify-center text-sm font-bold shadow-sm shrink-0">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="bg-white border border-datamind-light/70 text-slate-800 p-5 rounded-2xl rounded-tl-xs shadow-xs grow flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 mb-3">
                        <span class="text-xs font-bold text-datamind-dark flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-datamind-primary"></span> DataMind AI
                        </span>
                        <div class="footer-badge"></div>
                    </div>
                    <div class="ai-content-wrapper flex flex-col">
                        <div class="ai-text markdown-body markdown-ai text-sm leading-relaxed text-slate-700" data-raw-markdown="">
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
     * Renderiza un chip visual para un archivo adjunto.
     */
    function renderAttachmentChip(att) {
        if (!attachedFilesContainer) return;

        const isUploading = Boolean(att.isUploading);
        const chip = document.createElement('div');
        chip.id = att.id;
        chip.className = 'inline-flex items-center gap-2 px-2.5 py-1.5 bg-white border border-slate-200 text-xs text-slate-700 shadow-2xs rounded-none transition';

        const sizeKb = Math.round((att.file_size_bytes || 0) / 1024);
        const nameLower = (att.original_name || '').toLowerCase();
        let iconClass = 'fa-file-csv text-blue-600';
        if (nameLower.endsWith('.xlsx') || nameLower.endsWith('.xls')) {
            iconClass = 'fa-file-excel text-emerald-600';
        } else if (nameLower.endsWith('.pdf')) {
            iconClass = 'fa-file-pdf text-red-600';
        } else if (nameLower.endsWith('.docx')) {
            iconClass = 'fa-file-word text-blue-700';
        }

        if (isUploading) {
            chip.innerHTML = `
                <i class="fa-solid fa-spinner fa-spin text-datamind-primary text-xs"></i>
                <span class="font-medium text-slate-600 max-w-[160px] truncate">${escapeHtml(att.original_name)}</span>
                <span class="text-[10px] text-slate-400">Procesando...</span>
            `;
        } else {
            const rowsCount = att.row_count ? `${att.row_count.toLocaleString()} filas` : `${sizeKb} KB`;
            chip.innerHTML = `
                <i class="fa-solid ${iconClass} text-xs"></i>
                <span class="font-medium text-slate-800 max-w-[180px] truncate" title="${escapeHtml(att.original_name)}">${escapeHtml(att.original_name)}</span>
                <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded-none font-mono">${rowsCount}</span>
                <button type="button" class="btn-remove-attachment text-slate-400 hover:text-red-600 p-0.5 cursor-pointer" data-id="${att.id}" title="Quitar archivo">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            `;

            chip.querySelector('.btn-remove-attachment')?.addEventListener('click', async function (e) {
                e.stopPropagation();
                const attId = this.getAttribute('data-id');
                chip.remove();
                activeAttachments = activeAttachments.filter(a => a.id !== attId);
                try {
                    await fetch(`/chat/attachments/${attId}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken }
                    });
                } catch (err) {
                    console.warn('Error eliminando adjunto:', err);
                }
            });
        }

        attachedFilesContainer.appendChild(chip);
    }

    /**
     * Motor Local de Vista Previa Markdown (100% Offline, Seguro contra XSS y compatible con Streaming).
     * Soporta encabezados, negritas, cursivas, código en línea, bloques de código con botón copiar,
     * listas ordenadas/desordenadas, citas (blockquotes), divisores y tablas GFM.
     */
    function renderMarkdownPreview(rawText) {
        if (!rawText) return '';

        let text = String(rawText).replace(/\r\n/g, '\n');

        // 1. Extraer bloques de código cercados (```lang ... ```) incluso si aún se están cerrando en streaming
        const codeBlocks = [];
        const fenceRegex = /```([a-zA-Z0-9_-]*)\n?([\s\S]*?)(?:```|$)/g;
        text = text.replace(fenceRegex, function (_, lang, codeContent) {
            const languageLabel = (lang || 'code').toUpperCase();
            const safeCode = escapeHtml(codeContent.replace(/\n$/, ''));
            const blockHtml = `
                <div class="md-code-block">
                    <div class="md-code-header">
                        <span><i class="fa-solid fa-terminal mr-1.5"></i>${escapeHtml(languageLabel)}</span>
                        <button type="button" class="md-copy-btn">
                            <i class="fa-regular fa-copy"></i> Copiar
                        </button>
                    </div>
                    <pre class="md-code-pre"><code>${safeCode}</code></pre>
                </div>
            `;
            const token = `\x00CODEBLOCK_${codeBlocks.length}\x00`;
            codeBlocks.push(blockHtml);
            return token;
        });

        // 2. Procesar línea por línea para bloques estructurales (Tablas, Listas, Citas, Títulos, Párrafos)
        const lines = text.split('\n');
        const htmlOut = [];
        let inUl = false;
        let inOl = false;
        let inBlockquote = false;
        let blockquoteLines = [];
        let inTable = false;
        let tableLines = [];
        let paragraphLines = [];

        function flushParagraph() {
            if (paragraphLines.length > 0) {
                htmlOut.push(`<p>${ paragraphLines.map(l => parseInlineMarkdown(l)).join('<br>') }</p>`);
                paragraphLines = [];
            }
        }

        function flushLists() {
            if (inUl) { htmlOut.push('</ul>'); inUl = false; }
            if (inOl) { htmlOut.push('</ol>'); inOl = false; }
        }

        function flushBlockquote() {
            if (inBlockquote) {
                htmlOut.push(`<blockquote>${ blockquoteLines.map(l => parseInlineMarkdown(l)).join('<br>') }</blockquote>`);
                inBlockquote = false;
                blockquoteLines = [];
            }
        }

        function flushTable() {
            if (inTable) {
                htmlOut.push(buildMarkdownTable(tableLines));
                inTable = false;
                tableLines = [];
            }
        }

        function flushAll() {
            flushParagraph();
            flushLists();
            flushBlockquote();
            flushTable();
        }

        for (let i = 0; i < lines.length; i++) {
            const rawLine = lines[i];
            const trimmed = rawLine.trim();

            // Placeholder de bloque de código
            if (/^\x00CODEBLOCK_\d+\x00$/.test(trimmed)) {
                flushAll();
                htmlOut.push(trimmed);
                continue;
            }

            // Línea vacía
            if (trimmed === '') {
                flushAll();
                continue;
            }

            // Tablas Markdown (| col1 | col2 |)
            if (trimmed.startsWith('|') && trimmed.endsWith('|') && trimmed.length > 2) {
                flushParagraph();
                flushLists();
                flushBlockquote();
                inTable = true;
                tableLines.push(trimmed);
                continue;
            } else if (inTable) {
                flushTable();
            }

            // Línea divisoria horizontal (---, ***, ___)
            if (/^(?:---|\*\*\*|___)\s*$/.test(trimmed)) {
                flushAll();
                htmlOut.push('<hr>');
                continue;
            }

            // Encabezados (# a ####)
            const headingMatch = trimmed.match(/^(#{1,4})\s+(.+)$/);
            if (headingMatch) {
                flushAll();
                const level = headingMatch[1].length;
                htmlOut.push(`<h${level}>${parseInlineMarkdown(headingMatch[2])}</h${level}>`);
                continue;
            }

            // Citas (> texto)
            const quoteMatch = trimmed.match(/^>\s?(.*)$/);
            if (quoteMatch) {
                flushParagraph();
                flushLists();
                inBlockquote = true;
                blockquoteLines.push(quoteMatch[1]);
                continue;
            } else if (inBlockquote) {
                flushBlockquote();
            }

            // Lista desordenada (- item o * item)
            const ulMatch = rawLine.match(/^\s*[-*+]\s+(.+)$/);
            if (ulMatch) {
                flushParagraph();
                if (inOl) { htmlOut.push('</ol>'); inOl = false; }
                if (!inUl) { htmlOut.push('<ul>'); inUl = true; }
                htmlOut.push(`<li>${parseInlineMarkdown(ulMatch[1])}</li>`);
                continue;
            }

            // Lista ordenada (1. item)
            const olMatch = rawLine.match(/^\s*\d+\.\s+(.+)$/);
            if (olMatch) {
                flushParagraph();
                if (inUl) { htmlOut.push('</ul>'); inUl = false; }
                if (!inOl) { htmlOut.push('<ol>'); inOl = true; }
                htmlOut.push(`<li>${parseInlineMarkdown(olMatch[1])}</li>`);
                continue;
            }

            // Si estábamos en lista y viene texto normal, cerrar lista
            if (inUl || inOl) {
                flushLists();
            }

            paragraphLines.push(rawLine);
        }

        flushAll();

        // 3. Restaurar bloques de código
        let finalHtml = htmlOut.join('\n');
        finalHtml = finalHtml.replace(/\x00CODEBLOCK_(\d+)\x00/g, function (_, idx) {
            return codeBlocks[parseInt(idx, 10)] || '';
        });

        return finalHtml;
    }

    /**
     * Formatea elementos Markdown en línea (código `inline`, negritas **texto**, cursivas *texto*, tachado ~~texto~~ y enlaces).
     */
    function parseInlineMarkdown(line) {
        if (!line) return '';

        // 1. Extraer código en línea (`...`) antes de escapar y aplicar estilos
        const inlineCodes = [];
        let processed = String(line).replace(/`([^`]+)`/g, function (_, codeText) {
            const token = `\x00INLINECODE_${inlineCodes.length}\x00`;
            inlineCodes.push(`<code class="md-inline-code">${escapeHtml(codeText)}</code>`);
            return token;
        });

        // 2. Escapar HTML peligroso
        processed = escapeHtml(processed);

        // 3. Negrita + Cursiva (***texto***)
        processed = processed.replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>');

        // 4. Negrita (**texto** o __texto__)
        processed = processed.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        processed = processed.replace(/__(.+?)__/g, '<strong>$1</strong>');

        // 5. Cursiva (*texto* o _texto_)
        processed = processed.replace(/(^|[^\*])\*([^\*\n]+)\*(?!\*)/g, '$1<em>$2</em>');

        // 6. Tachado (~~texto~~)
        processed = processed.replace(/~~(.+?)~~/g, '<del>$1</del>');

        // 7. Enlaces seguros ([texto](https://...))
        processed = processed.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+|\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="underline font-semibold hover:opacity-85">$1</a>');

        // 8. Restaurar tokens de código en línea
        processed = processed.replace(/\x00INLINECODE_(\d+)\x00/g, function (_, idx) {
            return inlineCodes[parseInt(idx, 10)] || '';
        });

        return processed;
    }

    /**
     * Construye una tabla HTML a partir de líneas Markdown GFM.
     */
    function buildMarkdownTable(lines) {
        if (!lines || lines.length === 0) return '';

        const parseRow = (rowStr) => rowStr
            .replace(/^\||\|$/g, '')
            .split('|')
            .map(cell => cell.trim());

        const headers = parseRow(lines[0]);
        let startIdx = 1;

        // Verificar si la segunda fila es separador (|---|---|)
        if (lines.length > 1 && /^[\s|:-]+$/.test(lines[1])) {
            startIdx = 2;
        }

        const thead = `<thead><tr>${headers.map(h => `<th>${parseInlineMarkdown(h)}</th>`).join('')}</tr></thead>`;
        const bodyRows = lines.slice(startIdx).map(line => {
            const cells = parseRow(line);
            return `<tr>${cells.map(c => `<td>${parseInlineMarkdown(c)}</td>`).join('')}</tr>`;
        }).join('');

        return `<div class="md-table-wrapper"><table class="md-table">${thead}<tbody>${bodyRows}</tbody></table></div>`;
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

    // =========================================================================
    // CENTRO DE ENTRENAMIENTO IA ("🎓 Entrenar IA" - Fase 16)
    // =========================================================================
    const aiTrainingModal = document.getElementById('modalTrainingCenter') || document.getElementById('aiTrainingModal');
    const btnOpenTrainingCenter = document.getElementById('btnOpenTrainingCenter');
    const btnCloseTrainingModal = document.getElementById('btnCloseTrainingModal') || document.querySelector('[data-modal-close="modalTrainingCenter"]');
    const trainingTabBtns = document.querySelectorAll('.training-tab-btn');
    const trainingTabPanes = document.querySelectorAll('.training-tab-pane');
    const templateTypeBtns = document.querySelectorAll('.template-type-btn');
    const trainCategoryInput = document.getElementById('trainCategoryInput');
    const trainTitleInput = document.getElementById('trainTitleInput');
    const trainContentInput = document.getElementById('trainContentInput');
    const customRelationFields = document.getElementById('customRelationFields');
    const sqlExampleFieldBox = document.getElementById('sqlExampleFieldBox');
    const trainSourceTable = document.getElementById('trainSourceTable');
    const trainSourceCol = document.getElementById('trainSourceCol');
    const trainTargetTable = document.getElementById('trainTargetTable');
    const trainTargetCol = document.getElementById('trainTargetCol');
    const trainLabelCol = document.getElementById('trainLabelCol');
    const trainSqlQueryInput = document.getElementById('trainSqlQueryInput');
    const btnFillExampleTemplate = document.getElementById('btnFillExampleTemplate');
    const btnSaveTrainingRule = document.getElementById('btnSaveTrainingRule');
    const btnRefreshRulesList = document.getElementById('btnRefreshRulesList');
    const learnedRulesContainer = document.getElementById('learnedRulesContainer');
    const learnedRulesCount = document.getElementById('learnedRulesCount');

    function switchTrainingTab(targetTabId) {
        trainingTabPanes.forEach(pane => {
            if (pane.id === targetTabId) {
                pane.classList.remove('hidden');
            } else {
                pane.classList.add('hidden');
            }
        });
        trainingTabBtns.forEach(btn => {
            const tab = btn.getAttribute('data-target') || btn.getAttribute('data-tab');
            if (tab === targetTabId) {
                btn.className = 'training-tab-btn active px-4 py-2 text-xs font-bold border-b-2 border-datamind-primary text-datamind-primary bg-white cursor-pointer rounded-none';
            } else {
                btn.className = 'training-tab-btn px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900 cursor-pointer rounded-none';
            }
        });
    }

    function selectTrainingCategory(cat) {
        if (!trainCategoryInput) return;
        trainCategoryInput.value = cat;

        templateTypeBtns.forEach(btn => {
            const bCat = btn.getAttribute('data-category');
            if (bCat === cat) {
                btn.className = 'template-type-btn p-3 rounded-2xl border-2 border-datamind-primary bg-datamind-light/20 text-left transition cursor-pointer';
            } else {
                btn.className = 'template-type-btn p-3 rounded-2xl border border-slate-200 hover:border-datamind-light bg-white text-left transition cursor-pointer';
            }
        });

        if (customRelationFields) {
            customRelationFields.classList.toggle('hidden', cat !== 'custom_relation');
        }
        if (sqlExampleFieldBox) {
            sqlExampleFieldBox.classList.toggle('hidden', cat !== 'sql_example');
        }
    }

    function openTrainingCenterWithSql(sqlQuery) {
        if (!aiTrainingModal) return;
        aiTrainingModal.classList.remove('hidden');
        switchTrainingTab('tabAddRule');
        selectTrainingCategory('sql_example');
        if (trainSqlQueryInput) trainSqlQueryInput.value = sqlQuery || '';
        if (trainTitleInput) trainTitleInput.value = 'Consulta SQL Verificada de Negocio';
        if (trainContentInput) trainContentInput.value = 'Usa este patrón SELECT ... JOIN cuando el usuario consulte o grafique estas tablas.';
        loadLearnedRules();
    }

    async function loadLearnedRules() {
        if (!learnedRulesContainer) return;
        try {
            const connId = activeConnectionSelect ? activeConnectionSelect.value : '';
            const res = await fetch(`/chat/training/rules${connId ? '?connection_id=' + encodeURIComponent(connId) : ''}`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();
            const rules = (json.data && json.data.rules) ? json.data.rules : [];

            if (learnedRulesCount) {
                learnedRulesCount.textContent = String(rules.length);
            }

            if (rules.length === 0) {
                learnedRulesContainer.innerHTML = `
                    <div class="text-center py-8 text-xs text-slate-400 bg-slate-50 rounded-2xl border border-slate-200/80">
                        Aún no has registrado cápsulas personalizadas. Usa la Pestaña 1 o escribe una regla directamente en el chat.
                    </div>
                `;
                return;
            }

            const badgeLabels = {
                custom_relation: '🔗 Relación JOIN',
                business_rule: '📖 Diccionario / Estado',
                kpi_formula: '📐 Fórmula / KPI',
                sql_example: '🏆 Ejemplo SQL'
            };

            learnedRulesContainer.innerHTML = rules.map(rule => `
                <div class="p-3.5 rounded-2xl border border-slate-200 bg-white flex items-start justify-between gap-3 hover:border-datamind-light transition">
                    <div class="space-y-1 min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full badge-datamind">
                                ${escapeHtml(badgeLabels[rule.category] || rule.category)}
                            </span>
                            <h5 class="text-xs font-bold text-slate-800 truncate">${escapeHtml(rule.title)}</h5>
                        </div>
                        <p class="text-xs text-slate-600 whitespace-pre-line">${escapeHtml(rule.content)}</p>
                    </div>
                    <button type="button" class="btn-delete-rule text-slate-400 hover:text-red-600 p-1.5 rounded-lg hover:bg-red-50 transition cursor-pointer shrink-0"
                            data-rule-id="${escapeHtml(rule.id)}" title="Eliminar esta regla">
                        <i class="fa-solid fa-trash-can text-xs"></i>
                    </button>
                </div>
            `).join('');
        } catch (err) {
            console.warn('Error cargando reglas aprendidas:', err);
        }
    }

    if (btnOpenTrainingCenter && aiTrainingModal) {
        btnOpenTrainingCenter.addEventListener('click', function () {
            aiTrainingModal.classList.remove('hidden');
            loadLearnedRules();
        });
    }

    if (btnCloseTrainingModal && aiTrainingModal) {
        btnCloseTrainingModal.addEventListener('click', function () {
            aiTrainingModal.classList.add('hidden');
        });
    }

    trainingTabBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.getAttribute('data-target') || this.getAttribute('data-tab');
            if (tab) {
                switchTrainingTab(tab);
                if (tab === 'tabLearnedList') {
                    loadLearnedRules();
                }
            }
        });
    });

    templateTypeBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const cat = this.getAttribute('data-category');
            if (cat) selectTrainingCategory(cat);
        });
    });

    if (btnFillExampleTemplate) {
        btnFillExampleTemplate.addEventListener('click', function () {
            const cat = trainCategoryInput ? trainCategoryInput.value : 'custom_relation';
            if (cat === 'custom_relation') {
                if (trainTitleInput) trainTitleInput.value = 'Relación users con companies por current_company_id';
                if (trainSourceTable) trainSourceTable.value = 'users';
                if (trainSourceCol) trainSourceCol.value = 'current_company_id';
                if (trainTargetTable) trainTargetTable.value = 'companies';
                if (trainTargetCol) trainTargetCol.value = 'id';
                if (trainLabelCol) trainLabelCol.value = 'name';
                if (trainContentInput) {
                    trainContentInput.value = 'La tabla users se relaciona con la tabla companies uniendo users.current_company_id = companies.id. Cuando el usuario pida datos o gráficos por empresa, nunca muestres current_company_id; haz siempre JOIN companies y muestra companies.name.';
                }
            } else if (cat === 'business_rule') {
                if (trainTitleInput) trainTitleInput.value = 'Diccionario de estados de rollos y usuarios';
                if (trainContentInput) {
                    trainContentInput.value = 'Cuando el usuario pregunte por registros activos, filtra status = 1 (o estado = 1). En gráficos, muestra "Activo" cuando sea 1 e "Inactivo" cuando sea 0 usando CASE WHEN.';
                }
            } else if (cat === 'kpi_formula') {
                if (trainTitleInput) trainTitleInput.value = 'Cálculo de Peso Neto y Porcentaje de Merma';
                if (trainContentInput) {
                    trainContentInput.value = 'El Peso Neto se calcula restando peso_bruto - peso_tara. El Porcentaje de Merma se calcula como ((peso_desperdicio / peso_bruto) * 100).';
                }
            } else if (cat === 'sql_example') {
                if (trainTitleInput) trainTitleInput.value = 'Total de usuarios agrupados por nombre de empresa';
                if (trainSqlQueryInput) {
                    trainSqlQueryInput.value = 'SELECT c.name AS nombre_empresa, COUNT(u.id) AS total_usuarios FROM users u INNER JOIN companies c ON u.current_company_id = c.id GROUP BY c.name';
                }
                if (trainContentInput) {
                    trainContentInput.value = 'Ejemplo verificado para contar o graficar usuarios mostrando el nombre real de la empresa en lugar del ID.';
                }
            }
        });
    }

    if (btnSaveTrainingRule) {
        btnSaveTrainingRule.addEventListener('click', async function () {
            const cat = trainCategoryInput ? trainCategoryInput.value : 'business_rule';
            const title = (trainTitleInput ? trainTitleInput.value : '').trim();
            const content = (trainContentInput ? trainContentInput.value : '').trim();
            const connId = activeConnectionSelect ? activeConnectionSelect.value : null;

            if (!title || !content) {
                notify('warning', 'Completa el título y la explicación para enseñar a la IA.', 'Entrenar IA');
                return;
            }

            const payload = {
                category: cat,
                title: title,
                content: content,
                connection_id: connId || null,
                source_table: trainSourceTable ? trainSourceTable.value.trim() : null,
                source_column: trainSourceCol ? trainSourceCol.value.trim() : null,
                target_table: trainTargetTable ? trainTargetTable.value.trim() : null,
                target_column: trainTargetCol ? trainTargetCol.value.trim() : 'id',
                label_column: trainLabelCol ? trainLabelCol.value.trim() : null,
                sql_query: trainSqlQueryInput ? trainSqlQueryInput.value.trim() : null,
            };

            const origText = btnSaveTrainingRule.innerHTML;
            btnSaveTrainingRule.disabled = true;
            btnSaveTrainingRule.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Vectorizando...';

            try {
                const res = await fetch('/chat/training/train', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (!res.ok || !json.success) {
                    throw new Error(json.message || 'No se pudo guardar la regla.');
                }

                notify('success', '¡Conocimiento vectorizado y aprendido por la IA en pgvector!', 'Centro de Entrenamiento IA');
                await loadLearnedRules();
                switchTrainingTab('tabLearnedList');
            } catch (err) {
                notify('error', err.message || 'Error al vectorizar la regla.', 'Entrenar IA');
            } finally {
                btnSaveTrainingRule.disabled = false;
                btnSaveTrainingRule.innerHTML = origText;
            }
        });
    }

    if (btnRefreshRulesList) {
        btnRefreshRulesList.addEventListener('click', loadLearnedRules);
    }

    if (learnedRulesContainer) {
        learnedRulesContainer.addEventListener('click', async function (e) {
            const delBtn = e.target.closest('.btn-delete-rule');
            if (!delBtn) return;
            const ruleId = delBtn.getAttribute('data-rule-id');
            if (!ruleId) return;

            try {
                const res = await fetch(`/chat/training/rules/${encodeURIComponent(ruleId)}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                if (res.ok) {
                    notify('info', 'Regla eliminada de pgvector.', 'Entrenar IA');
                    await loadLearnedRules();
                }
            } catch (err) {
                console.warn('Error eliminando regla:', err);
            }
        });
    }

    // =========================================================================
    // Manejo Interactivo de Candidatos de Conocimiento Implícito (Fase 1)
    // =========================================================================
    document.addEventListener('click', async function (e) {
        const confirmBtn = e.target.closest('.btn-confirm-candidate');
        if (confirmBtn) {
            const chip = confirmBtn.closest('.candidate-rule-chip');
            const candidateId = chip ? chip.getAttribute('data-candidate-id') : null;
            if (!candidateId) return;

            confirmBtn.disabled = true;
            try {
                const res = await fetch(`/chat/candidates/${encodeURIComponent(candidateId)}/confirm-user`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    notify('success', 'Regla confirmada y activa para tu usuario.', 'Conocimiento Personal');
                    confirmBtn.replaceWith(document.createRange().createContextualFragment(
                        '<span class="text-[10px] text-emerald-700 font-semibold flex items-center gap-1"><i class="fa-solid fa-check"></i> Activa para ti</span>'
                    ));
                } else {
                    notify('error', data.message || 'No se pudo confirmar la regla.', 'Error');
                    confirmBtn.disabled = false;
                }
            } catch (err) {
                notify('error', 'Error de conexión al confirmar la regla.', 'Error');
                confirmBtn.disabled = false;
            }
            return;
        }

        const proposeBtn = e.target.closest('.btn-propose-candidate');
        if (proposeBtn) {
            const chip = proposeBtn.closest('.candidate-rule-chip');
            const candidateId = chip ? chip.getAttribute('data-candidate-id') : null;
            if (!candidateId) return;

            proposeBtn.disabled = true;
            try {
                const res = await fetch(`/chat/candidates/${encodeURIComponent(candidateId)}/propose-global`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    notify('info', 'Regla postulada a revisión global corporativa.', 'Conocimiento Corporativo');
                    proposeBtn.replaceWith(document.createRange().createContextualFragment(
                        '<span class="text-[10px] text-amber-700 italic">(Postulada a global)</span>'
                    ));
                } else {
                    notify('error', data.message || 'No se pudo postular la regla.', 'Error');
                    proposeBtn.disabled = false;
                }
            } catch (err) {
                notify('error', 'Error de conexión al postular la regla.', 'Error');
                proposeBtn.disabled = false;
            }
            return;
        }

        const rejectBtn = e.target.closest('.btn-reject-candidate');
        if (rejectBtn) {
            const chip = rejectBtn.closest('.candidate-rule-chip');
            const candidateId = chip ? chip.getAttribute('data-candidate-id') : null;
            if (!candidateId) return;

            try {
                const res = await fetch(`/chat/candidates/${encodeURIComponent(candidateId)}/reject`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ reason: 'Descartada por el usuario en la interfaz' })
                });
                if (res.ok) {
                    notify('info', 'Sugerencia descartada.', 'Conocimiento');
                    chip.remove();
                } else {
                    const data = await res.json();
                    notify('error', data.message || 'No se pudo descartar la sugerencia.', 'Error');
                }
            } catch (err) {
                notify('error', 'Error al descartar la sugerencia.', 'Error');
            }
            return;
        }
    });

    // Exponer API para testing e interactividad programática
    window.ActiveJobStore = ActiveJobStore;
    window.loadConversationSession = loadConversationSession;
    window.refreshUserSessionsSidebar = refreshUserSessionsSidebar;
    window.handleResetChat = handleResetChat;
    window.handleSendMessage = handleSendMessage;
    window.handleRetryMessage = handleRetryMessage;

})();
