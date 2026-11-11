/**
 * DataMind AI - Gestor del Centro de Aprendizaje / Entrenamiento de la IA
 * Funciona de manera autónoma tanto en la Consola Conversacional como en el Ecosistema de Vectorización.
 */

(function () {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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
    const activeConnectionSelect = document.getElementById('activeConnectionSelect');

    function notify(type, message, title = 'Centro de Entrenamiento IA') {
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
                btn.className = 'training-tab-btn active px-4 py-2.5 text-xs font-bold border-b-2 border-datamind-primary text-datamind-primary bg-white cursor-pointer rounded-none';
            } else {
                btn.className = 'training-tab-btn px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-900 cursor-pointer rounded-none';
            }
        });
    }

    function selectTrainingCategory(cat) {
        if (!trainCategoryInput) return;
        trainCategoryInput.value = cat;

        templateTypeBtns.forEach(btn => {
            const bCat = btn.getAttribute('data-category');
            if (bCat === cat) {
                btn.className = 'template-type-btn active p-3 border-2 border-datamind-primary bg-datamind-light/10 text-left transition cursor-pointer rounded-lg hover:shadow-xs';
            } else {
                btn.className = 'template-type-btn p-3 border border-slate-200 hover:border-slate-400 bg-white text-left transition cursor-pointer rounded-lg hover:shadow-xs';
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
        if (!learnedRulesContainer) return;
        try {
            let connId = '';
            if (activeConnectionSelect) {
                connId = activeConnectionSelect.value;
            } else if (window.currentConnectionId) {
                connId = window.currentConnectionId;
            }

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
                    <div class="text-center py-8 text-xs text-slate-400 bg-slate-50 rounded-none border border-slate-200">
                        Aún no has registrado cápsulas personalizadas para esta conexión. Usa la Pestaña 1 para enseñarle una regla.
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
                <div class="p-3.5 rounded-none border border-slate-200 bg-white flex items-start justify-between gap-3 hover:border-slate-400 transition">
                    <div class="space-y-1 min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-none bg-slate-100 text-slate-700 border border-slate-200">
                                ${escapeHtml(badgeLabels[rule.category] || rule.category)}
                            </span>
                            <h5 class="text-xs font-bold text-slate-800 truncate">${escapeHtml(rule.title)}</h5>
                        </div>
                        <p class="text-xs text-slate-600 whitespace-pre-line">${escapeHtml(rule.content)}</p>
                    </div>
                    <button type="button" class="btn-delete-rule text-slate-400 hover:text-red-600 p-1.5 transition cursor-pointer shrink-0"
                            data-rule-id="${escapeHtml(rule.id)}" title="Eliminar esta regla">
                        <i class="fa-solid fa-trash-can text-xs"></i>
                    </button>
                </div>
            `).join('');
        } catch (err) {
            console.warn('Error cargando reglas aprendidas:', err);
        }
    }

    // Inicializar listeners del Centro de Entrenamiento
    function initTrainingCenter() {
        if (!aiTrainingModal) return;

        // Soporte para botones que abran el modal tanto en el Sidebar como en la barra o tarjetas
        document.querySelectorAll('#btnOpenTrainingCenter, .btn-open-training-center').forEach(btn => {
            btn.addEventListener('click', function () {
                aiTrainingModal.classList.remove('hidden');
                loadLearnedRules();
            });
        });

        if (btnCloseTrainingModal) {
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
                    if (trainTitleInput) trainTitleInput.value = 'Consulta de Usuarios con Roles y Permisos';
                    if (trainSqlQueryInput) {
                        trainSqlQueryInput.value = 'SELECT u.name AS usuario, r.name AS rol, i.name AS interfaz, p.name AS permiso FROM users u JOIN role_user ru ON u.id = ru.user_id JOIN roles r ON ru.role_id = r.id JOIN role_interface_permission rip ON r.id = rip.role_id JOIN interfaces i ON rip.interface_id = i.id JOIN permissions p ON rip.permission_id = p.id LIMIT 50;';
                    }
                    if (trainContentInput) {
                        trainContentInput.value = 'Ejemplo verificado para consultar los roles y permisos de cada usuario en el sistema.';
                    }
                }
            });
        }

        if (btnSaveTrainingRule) {
            btnSaveTrainingRule.addEventListener('click', async function () {
                const cat = trainCategoryInput ? trainCategoryInput.value : 'business_rule';
                const title = (trainTitleInput ? trainTitleInput.value : '').trim();
                const content = (trainContentInput ? trainContentInput.value : '').trim();
                
                let connId = null;
                if (activeConnectionSelect) {
                    connId = activeConnectionSelect.value;
                } else if (window.currentConnectionId) {
                    connId = window.currentConnectionId;
                }

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

                const origText = btnSaveTrainingRule.innerHTML;
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

                    notify('success', '¡Conocimiento vectorizado y aprendido por la IA en pgvector!', 'Centro de Aprendizaje');
                    await loadLearnedRules();
                    switchTrainingTab('tabLearnedList');
                } catch (err) {
                    notify('error', err.message || 'Error al vectorizar la regla.', 'Centro de Aprendizaje');
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
                        notify('info', 'Regla eliminada de pgvector.', 'Centro de Aprendizaje');
                        await loadLearnedRules();
                    }
                } catch (err) {
                    console.warn('Error eliminando regla:', err);
                }
            });
        }
    }

    // Exponer globalmente
    window.DataMindTrainingCenter = {
        open: function () {
            if (aiTrainingModal) {
                aiTrainingModal.classList.remove('hidden');
                loadLearnedRules();
            }
        },
        close: function () {
            if (aiTrainingModal) {
                aiTrainingModal.classList.add('hidden');
            }
        },
        reload: loadLearnedRules
    };

    document.addEventListener('DOMContentLoaded', initTrainingCenter);
})();
