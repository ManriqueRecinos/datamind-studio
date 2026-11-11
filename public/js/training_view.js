/**
 * DataMind AI - Controlador Frontend para la Vista Dedicada del Centro de Aprendizaje
 * Gestiona el formulario de reglas, galería de cards interactivas, modal de detalle e ingesta de archivos.
 */

(function () {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Elementos de la vista
    const activeConnectionSelect = document.getElementById('activeConnectionSelect');
    const totalRulesCounter = document.getElementById('totalRulesCounter');
    const btnToggleNewRuleForm = document.getElementById('btnToggleNewRuleForm');
    const btnCloseNewRuleForm = document.getElementById('btnCloseNewRuleForm');
    const newRuleFormContainer = document.getElementById('newRuleFormContainer');

    // Formulario de Nueva Regla
    const formTrain = document.getElementById('formTrainKnowledgeView');
    const templateTypeBtns = document.querySelectorAll('.template-type-btn');
    const trainCategoryInput = document.getElementById('trainCategoryInput');
    const customRelationFields = document.getElementById('customRelationFields');
    const sqlExampleFieldBox = document.getElementById('sqlExampleFieldBox');
    const trainSourceTable = document.getElementById('trainSourceTable');
    const trainSourceCol = document.getElementById('trainSourceCol');
    const trainTargetTable = document.getElementById('trainTargetTable');
    const trainTargetCol = document.getElementById('trainTargetCol');
    const trainLabelCol = document.getElementById('trainLabelCol');
    const trainSqlQueryInput = document.getElementById('trainSqlQueryInput');
    const trainTitleInput = document.getElementById('trainTitleInput');
    const trainContentInput = document.getElementById('trainContentInput');
    const btnFillExampleTemplate = document.getElementById('btnFillExampleTemplate');
    const btnSaveTrainingRule = document.getElementById('btnSaveTrainingRule');

    // Galería y Búsqueda de Cards
    const filterCategorySelect = document.getElementById('filterCategorySelect');
    const searchRulesInput = document.getElementById('searchRulesInput');
    const rulesCardsGrid = document.getElementById('rulesCardsGrid');

    // Modal de Detalle de Regla
    const modalRuleDetail = document.getElementById('modalRuleDetail');
    const detailCategoryBadge = document.getElementById('detailCategoryBadge');
    const detailRuleTitle = document.getElementById('detailRuleTitle');
    const detailRuleContent = document.getElementById('detailRuleContent');
    const detailSqlWrapper = document.getElementById('detailSqlWrapper');
    const detailRuleSql = document.getElementById('detailRuleSql');
    const detailRuleMeta = document.getElementById('detailRuleMeta');
    const btnDeleteCurrentRule = document.getElementById('btnDeleteCurrentRule');
    const btnCloseRuleDetail = document.getElementById('btnCloseRuleDetail');
    const btnCloseDetailFooter = document.getElementById('btnCloseDetailFooter');

    // Modal de Ingesta (Subida de Archivos)
    const btnOpenIngestModal = document.getElementById('btnOpenIngestModal');
    const modalIngestFile = document.getElementById('modalIngestFile');
    const btnCloseIngestModal = document.getElementById('btnCloseIngestModal');
    const btnCancelIngest = document.getElementById('btnCancelIngest');
    const formIngestFile = document.getElementById('formIngestFile');
    const dropZoneContainer = document.getElementById('dropZoneContainer');
    const ingestFileInput = document.getElementById('ingestFileInput');
    const selectedFileInfo = document.getElementById('selectedFileInfo');
    const selectedFileName = document.getElementById('selectedFileName');
    const btnClearSelectedFile = document.getElementById('btnClearSelectedFile');
    const ingestTitleInput = document.getElementById('ingestTitleInput');
    const ingestDeptInput = document.getElementById('ingestDeptInput');
    const ingestProgressBanner = document.getElementById('ingestProgressBanner');
    const btnSubmitIngest = document.getElementById('btnSubmitIngest');

    let allLoadedRules = [];
    let currentInspectedRule = null;

    function notify(type, message, title = 'Centro de Aprendizaje') {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message, title);
        } else if (type === 'error') {
            console.error(`[${title}] ${message}`);
            alert(message);
        } else {
            console.log(`[${title}] ${message}`);
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resetNewRuleForm() {
        if (trainTitleInput) trainTitleInput.value = '';
        if (trainContentInput) trainContentInput.value = '';
        if (trainSourceTable) trainSourceTable.value = '';
        if (trainSourceCol) trainSourceCol.value = '';
        if (trainTargetTable) trainTargetTable.value = '';
        if (trainTargetCol) trainTargetCol.value = 'id';
        if (trainLabelCol) trainLabelCol.value = '';
        if (trainSqlQueryInput) trainSqlQueryInput.value = '';
        selectTrainingCategory('custom_relation');
    }

    function selectTrainingCategory(cat) {
        if (!trainCategoryInput) return;
        trainCategoryInput.value = cat;

        templateTypeBtns.forEach(btn => {
            const bCat = btn.getAttribute('data-category');
            if (bCat === cat) {
                btn.className = 'template-type-btn active p-3 border-2 border-datamind-primary bg-datamind-light/10 text-left transition cursor-pointer rounded-none';
            } else {
                btn.className = 'template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-none';
            }
        });

        if (customRelationFields) {
            customRelationFields.classList.toggle('hidden', cat !== 'custom_relation');
        }
        if (sqlExampleFieldBox) {
            sqlExampleFieldBox.classList.toggle('hidden', cat !== 'sql_example');
        }
    }

    async function loadLearnedRules() {
        if (!rulesCardsGrid) return;
        try {
            const connId = activeConnectionSelect ? activeConnectionSelect.value : '';
            const res = await fetch(`/chat/training/rules${connId ? '?connection_id=' + encodeURIComponent(connId) : ''}`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();
            allLoadedRules = (json.data && json.data.rules) ? json.data.rules : [];

            if (totalRulesCounter) {
                totalRulesCounter.textContent = String(allLoadedRules.length);
            }

            renderRulesCards();
        } catch (err) {
            console.error('Error cargando reglas:', err);
            rulesCardsGrid.innerHTML = `
                <div class="col-span-full py-12 text-center text-red-500 text-xs">
                    <i class="fa-solid fa-triangle-exclamation text-xl mb-2"></i>
                    <p>No se pudieron recuperar las reglas desde la memoria vectorial.</p>
                </div>
            `;
        }
    }

    function renderRulesCards() {
        if (!rulesCardsGrid) return;

        const categoryFilter = filterCategorySelect ? filterCategorySelect.value : 'all';
        const searchText = (searchRulesInput ? searchRulesInput.value : '').trim().toLowerCase();

        const filtered = allLoadedRules.filter(rule => {
            if (categoryFilter !== 'all' && rule.category !== categoryFilter) {
                return false;
            }
            if (!searchText) return true;
            const fullText = (rule.title + ' ' + rule.content + ' ' + (rule.category || '')).toLowerCase();
            return fullText.includes(searchText);
        });

        if (filtered.length === 0) {
            rulesCardsGrid.innerHTML = `
                <div class="col-span-full py-12 text-center text-slate-400 text-xs bg-white border border-slate-200 p-8">
                    <i class="fa-solid fa-brain text-2xl text-slate-300 mb-2"></i>
                    <p class="font-bold text-slate-700 mb-1">No se encontraron aprendizajes registrados</p>
                    <p class="text-[11px] text-slate-400">Usa el botón "+ Nueva Regla" o "Ingest" en la barra superior para agregar conocimiento.</p>
                </div>
            `;
            return;
        }

        const categoryMeta = {
            custom_relation: { label: '🔗 Relación JOIN', bg: 'bg-blue-50 text-blue-700 border-blue-200' },
            business_rule: { label: '📖 Códigos & Estados', bg: 'bg-amber-50 text-amber-700 border-amber-200' },
            sql_example: { label: '🏆 Ejemplo SQL', bg: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
            kpi_formula: { label: '📐 Fórmula & KPI', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
            process_manual: { label: '📄 Documento Ingestado', bg: 'bg-slate-100 text-slate-700 border-slate-200' },
        };

        rulesCardsGrid.innerHTML = filtered.map(rule => {
            const meta = categoryMeta[rule.category] || { label: rule.category, bg: 'bg-slate-50 text-slate-600 border-slate-200' };
            const previewText = (rule.content || '').substring(0, 160) + ((rule.content || '').length > 160 ? '...' : '');

            return `
                <div class="rule-card group p-4 bg-white border border-slate-200 hover:border-datamind-primary transition duration-150 cursor-pointer flex flex-col justify-between shadow-2xs hover:shadow-xs rounded-none"
                     data-rule-id="${escapeHtml(rule.id)}">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-[9px] font-bold px-2 py-0.5 border rounded-none ${meta.bg}">
                                ${escapeHtml(meta.label)}
                            </span>
                            <span class="text-[10px] text-slate-400 font-mono">
                                <i class="fa-solid fa-vector-square mr-1"></i>pgvector
                            </span>
                        </div>
                        <h4 class="text-xs font-bold text-slate-800 group-hover:text-datamind-primary transition mb-1.5 line-clamp-1">
                            ${escapeHtml(rule.title)}
                        </h4>
                        <p class="text-[11px] text-slate-600 line-clamp-3 leading-relaxed mb-3">
                            ${escapeHtml(previewText)}
                        </p>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                        <span class="flex items-center gap-1">
                            <i class="fa-solid fa-clock-rotate-left"></i> ${rule.created_at ? new Date(rule.created_at).toLocaleDateString() : 'Activa'}
                        </span>
                        <span class="text-datamind-primary font-semibold flex items-center gap-1 group-hover:translate-x-0.5 transition-transform">
                            Ver detalle <i class="fa-solid fa-arrow-right text-[9px]"></i>
                        </span>
                    </div>
                </div>
            `;
        }).join('');

        // Listener en cada Card para abrir el Modal de Detalle
        rulesCardsGrid.querySelectorAll('.rule-card').forEach(card => {
            card.addEventListener('click', function () {
                const id = this.getAttribute('data-rule-id');
                const rule = allLoadedRules.find(r => r.id === id);
                if (rule) openRuleDetailModal(rule);
            });
        });
    }

    function openRuleDetailModal(rule) {
        currentInspectedRule = rule;
        detailRuleTitle.textContent = rule.title || 'Detalle del Aprendizaje';
        detailRuleContent.textContent = rule.content || '';

        const categoryLabels = {
            custom_relation: 'Relación JOIN (2 Tablas)',
            business_rule: 'Códigos y Estados de Negocio',
            sql_example: 'Ejemplo SQL de Oro',
            kpi_formula: 'Fórmula o Cálculo Matemático',
            process_manual: 'Documento Ingestado'
        };
        detailCategoryBadge.textContent = categoryLabels[rule.category] || rule.category;

        // SQL asociado si existe
        const meta = rule.metadata || {};
        const sql = meta.sql_query || meta.sql_example || rule.sql_query || null;
        if (sql) {
            detailRuleSql.textContent = sql;
            detailSqlWrapper.classList.remove('hidden');
        } else {
            detailSqlWrapper.classList.add('hidden');
        }

        // Metadatos
        detailRuleMeta.innerHTML = `
            <div><strong>ID Documento:</strong> <span class="text-slate-700">${escapeHtml(rule.id)}</span></div>
            <div><strong>Categoría:</strong> <span class="text-slate-700">${escapeHtml(rule.category)}</span></div>
            <div><strong>Conexión ID:</strong> <span class="text-slate-700">${escapeHtml(meta.connection_id || 'Global')}</span></div>
            <div><strong>Registrado:</strong> <span class="text-slate-700">${rule.created_at ? new Date(rule.created_at).toLocaleString() : 'N/A'}</span></div>
        `;

        modalRuleDetail.classList.remove('hidden');
    }

    function closeRuleDetailModal() {
        modalRuleDetail.classList.add('hidden');
        currentInspectedRule = null;
    }

    // Inicialización de Eventos DOM
    document.addEventListener('DOMContentLoaded', function () {
        loadLearnedRules();

        if (activeConnectionSelect) {
            activeConnectionSelect.addEventListener('change', loadLearnedRules);
        }

        if (btnToggleNewRuleForm && newRuleFormContainer) {
            btnToggleNewRuleForm.addEventListener('click', function () {
                newRuleFormContainer.classList.toggle('hidden');
                if (!newRuleFormContainer.classList.contains('hidden')) {
                    newRuleFormContainer.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }

        if (btnCloseNewRuleForm && newRuleFormContainer) {
            btnCloseNewRuleForm.addEventListener('click', function () {
                newRuleFormContainer.classList.add('hidden');
            });
        }

        templateTypeBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const cat = this.getAttribute('data-category');
                if (cat) selectTrainingCategory(cat);
            });
        });

        if (filterCategorySelect) {
            filterCategorySelect.addEventListener('change', renderRulesCards);
        }

        if (searchRulesInput) {
            searchRulesInput.addEventListener('input', renderRulesCards);
        }

        // Cargar Plantilla de Ejemplo en el Formulario
        if (btnFillExampleTemplate) {
            btnFillExampleTemplate.addEventListener('click', function () {
                const cat = trainCategoryInput ? trainCategoryInput.value : 'custom_relation';
                if (cat === 'custom_relation') {
                    if (trainTitleInput) trainTitleInput.value = 'Relación users con roles por role_user';
                    if (trainSourceTable) trainSourceTable.value = 'users';
                    if (trainSourceCol) trainSourceCol.value = 'id';
                    if (trainTargetTable) trainTargetTable.value = 'roles';
                    if (trainTargetCol) trainTargetCol.value = 'id';
                    if (trainLabelCol) trainLabelCol.value = 'name';
                    if (trainContentInput) {
                        trainContentInput.value = 'La tabla users se relaciona con la tabla roles por medio de la tabla intermedia role_user uniendo users.id = role_user.user_id y role_user.role_id = roles.id. Muestra siempre roles.name como el nombre del rol.';
                    }
                } else if (cat === 'business_rule') {
                    if (trainTitleInput) trainTitleInput.value = 'Diccionario de estados de usuarios y accesos';
                    if (trainContentInput) {
                        trainContentInput.value = 'Cuando el usuario pregunte por usuarios activos, filtra status = 1 (o active = true).';
                    }
                } else if (cat === 'kpi_formula') {
                    if (trainTitleInput) trainTitleInput.value = 'Cálculo de Porcentaje de Asignación';
                    if (trainContentInput) {
                        trainContentInput.value = 'El Porcentaje de Asignación se calcula como ((roles_asignados / total_roles) * 100).';
                    }
                } else if (cat === 'sql_example') {
                    if (trainTitleInput) trainTitleInput.value = 'Consulta Canónica de Usuarios con Roles y Permisos';
                    if (trainSqlQueryInput) {
                        trainSqlQueryInput.value = 'SELECT u.name AS usuario, r.name AS rol, i.name AS interfaz, p.name AS permiso FROM users u JOIN role_user ru ON u.id = ru.user_id JOIN roles r ON ru.role_id = r.id JOIN role_interface_permission rip ON r.id = rip.role_id JOIN interfaces i ON rip.interface_id = i.id JOIN permissions p ON rip.permission_id = p.id LIMIT 50;';
                    }
                    if (trainContentInput) {
                        trainContentInput.value = 'Ejemplo verificado para consultar los roles y permisos de cada usuario en el sistema sin inventar columnas.';
                    }
                }
            });
        }

        // Guardar Regla y LIMPIAR TODO
        if (btnSaveTrainingRule) {
            btnSaveTrainingRule.addEventListener('click', async function () {
                const cat = trainCategoryInput ? trainCategoryInput.value : 'business_rule';
                const title = (trainTitleInput ? trainTitleInput.value : '').trim();
                const content = (trainContentInput ? trainContentInput.value : '').trim();
                const connId = activeConnectionSelect ? activeConnectionSelect.value : null;

                if (!title || !content) {
                    notify('warning', 'Completa el título y la explicación para enseñar a la IA.', 'Centro de Aprendizaje');
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

                const origHtml = btnSaveTrainingRule.innerHTML;
                btnSaveTrainingRule.disabled = true;
                btnSaveTrainingRule.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Vectorizando en pgvector...';

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

                    notify('success', '¡Conocimiento vectorizado con éxito en pgvector!', 'Centro de Aprendizaje');
                    
                    // REQUERIMIENTO DEL USUARIO: Limpiar todo el formulario al guardar
                    resetNewRuleForm();
                    if (newRuleFormContainer) newRuleFormContainer.classList.add('hidden');

                    await loadLearnedRules();
                } catch (err) {
                    notify('error', err.message || 'Error al vectorizar la regla.', 'Centro de Aprendizaje');
                } finally {
                    btnSaveTrainingRule.disabled = false;
                    btnSaveTrainingRule.innerHTML = origHtml;
                }
            });
        }

        // Eliminar Regla desde Modal Detalle
        if (btnDeleteCurrentRule) {
            btnDeleteCurrentRule.addEventListener('click', async function () {
                if (!currentInspectedRule || !currentInspectedRule.id) return;
                if (!confirm(`¿Estás seguro de eliminar el aprendizaje "${currentInspectedRule.title}" de pgvector?`)) return;

                try {
                    const res = await fetch(`/chat/training/rules/${encodeURIComponent(currentInspectedRule.id)}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    });
                    if (res.ok) {
                        notify('info', 'Regla eliminada de la memoria vectorial.', 'Centro de Aprendizaje');
                        closeRuleDetailModal();
                        await loadLearnedRules();
                    }
                } catch (err) {
                    console.error('Error eliminando regla:', err);
                }
            });
        }

        if (btnCloseRuleDetail) btnCloseRuleDetail.addEventListener('click', closeRuleDetailModal);
        if (btnCloseDetailFooter) btnCloseDetailFooter.addEventListener('click', closeRuleDetailModal);

        // =====================================================================
        // MODAL Y FLUJO DE INGESTA DE DOCUMENTOS (PDF, EXCEL, MD, DOCX)
        // =====================================================================
        if (btnOpenIngestModal && modalIngestFile) {
            btnOpenIngestModal.addEventListener('click', function () {
                modalIngestFile.classList.remove('hidden');
            });
        }

        if (btnCloseIngestModal && modalIngestFile) {
            btnCloseIngestModal.addEventListener('click', function () {
                modalIngestFile.classList.add('hidden');
            });
        }

        if (btnCancelIngest && modalIngestFile) {
            btnCancelIngest.addEventListener('click', function () {
                modalIngestFile.classList.add('hidden');
            });
        }

        if (dropZoneContainer && ingestFileInput) {
            dropZoneContainer.addEventListener('click', () => ingestFileInput.click());

            dropZoneContainer.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('border-emerald-500', 'bg-emerald-50/30');
            });

            dropZoneContainer.addEventListener('dragleave', function (e) {
                e.preventDefault();
                this.classList.remove('border-emerald-500', 'bg-emerald-50/30');
            });

            dropZoneContainer.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('border-emerald-500', 'bg-emerald-50/30');
                if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                    ingestFileInput.files = e.dataTransfer.files;
                    handleFileSelected(e.dataTransfer.files[0]);
                }
            });

            ingestFileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    handleFileSelected(this.files[0]);
                }
            });
        }

        function handleFileSelected(file) {
            if (!file) return;
            selectedFileName.innerHTML = `<i class="fa-solid fa-file mr-1"></i> ${escapeHtml(file.name)} (${(file.size / 1024).toFixed(1)} KB)`;
            selectedFileInfo.classList.remove('hidden');
            if (ingestTitleInput && !ingestTitleInput.value) {
                ingestTitleInput.value = file.name.replace(/\.[^/.]+$/, "");
            }
        }

        if (btnClearSelectedFile) {
            btnClearSelectedFile.addEventListener('click', function () {
                if (ingestFileInput) ingestFileInput.value = '';
                selectedFileInfo.classList.add('hidden');
            });
        }

        // Subir y Vectorizar Archivo (POST /training/ingest)
        if (formIngestFile) {
            formIngestFile.addEventListener('submit', async function (e) {
                e.preventDefault();
                const file = ingestFileInput ? ingestFileInput.files[0] : null;
                if (!file) {
                    notify('warning', 'Por favor selecciona un archivo PDF, Excel, Markdown o Word.', 'Ingest');
                    return;
                }

                const formData = new FormData();
                formData.append('file', file);
                formData.append('title', (ingestTitleInput ? ingestTitleInput.value : '').trim() || file.name);
                formData.append('department', (ingestDeptInput ? ingestDeptInput.value : '').trim() || 'General');
                if (activeConnectionSelect && activeConnectionSelect.value) {
                    formData.append('connection_id', activeConnectionSelect.value);
                }

                btnSubmitIngest.disabled = true;
                btnSubmitIngest.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Vectorizando...';
                if (ingestProgressBanner) ingestProgressBanner.classList.remove('hidden');

                try {
                    const response = await fetch('/training/ingest', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const json = await response.json();
                    if (!response.ok || !json.success) {
                        throw new Error(json.message || 'Error en la ingesta del archivo.');
                    }

                    notify('success', `¡Documento "${file.name}" particionado y vectorizado con éxito!`, 'Ingest RAG');
                    
                    // Resetear formulario de archivo
                    formIngestFile.reset();
                    selectedFileInfo.classList.add('hidden');
                    modalIngestFile.classList.add('hidden');

                    await loadLearnedRules();
                } catch (err) {
                    notify('error', err.message || 'Error al procesar el archivo.', 'Ingest RAG');
                } finally {
                    btnSubmitIngest.disabled = false;
                    btnSubmitIngest.innerHTML = '<i class="fa-solid fa-bolt"></i> <span>Vectorizar Documento</span>';
                    if (ingestProgressBanner) ingestProgressBanner.classList.add('hidden');
                }
            });
        }
    });
})();
