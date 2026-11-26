<x-app-layout
    title="Laboratorio Neuronal (pgvector + TensorFlow)"
    ecosystem="vectorization"
    ecosystemLabel="Vectorización & Bases de Datos"
    ecosystemIcon="fa-brain"
    activeModule="Laboratorio Neuronal"
    activeContext="Red Neuronal sobre pgvector"
    :isOnline="true"
    :fullHeight="true">

    <main class="flex-1 w-full flex overflow-hidden rounded-none">
        <aside class="hidden lg:flex flex-col w-80 shrink-0 bg-white border-r border-slate-200 overflow-y-auto custom-scrollbar rounded-none">
            <x-ecosystem-sidebar ecosystemKey="vectorization" />
        </aside>

        <section id="training-main-content" class="flex-1 min-w-0 flex flex-col bg-slate-50/40 overflow-hidden">
            <!-- Header Dashboard -->
            <div class="px-5 py-3 border-b border-slate-200 bg-white flex flex-wrap items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="h-9 w-9 bg-datamind-primary text-white flex items-center justify-center font-bold text-base rounded-none">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 leading-tight uppercase tracking-wide">Monitoreo & Laboratorio Neuronal (pgvector + TensorFlow)</h1>
                        <p class="text-[11px] text-slate-500">Clasificación inteligente sobre embeddings reales (768-dim) almacenados en pgvector.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span id="nlStatusBadge" class="text-[11px] font-semibold px-2.5 py-1 bg-slate-100 border border-slate-200 text-slate-600">
                        <i class="fa-solid fa-circle text-[8px] text-slate-400 mr-1"></i> Listo para entrenar
                    </span>
                    <button type="button" id="btnTrainRealModel" class="px-3.5 py-1.5 bg-datamind-primary hover:opacity-95 text-white font-bold text-xs uppercase tracking-wider flex items-center gap-1.5 rounded-none cursor-pointer">
                        <i class="fa-solid fa-play"></i>
                        <span>Entrenar con Mis Reglas</span>
                    </button>
                </div>
            </div>

            <div class="flex-1 p-4 overflow-y-auto custom-scrollbar space-y-4">
                <!-- Barra de Parámetros -->
                <div class="bg-white border border-slate-200 p-3.5 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Épocas</label>
                        <input type="number" id="nlEpochs" value="50" min="5" max="2000" class="w-full px-3 py-1.5 border border-slate-300 text-xs rounded-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Learning Rate (Adam)</label>
                        <input type="number" id="nlLr" value="0.001" step="0.0005" min="0.0001" max="1" class="w-full px-3 py-1.5 border border-slate-300 text-xs rounded-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Capas Ocultas (ReLU + Dropout)</label>
                        <input type="text" id="nlHiddenLayers" value="128,64" placeholder="Ej: 128,64" class="w-full px-3 py-1.5 border border-slate-300 text-xs rounded-none">
                    </div>
                </div>

                <!-- Fila Superior Dashboard: Gráfica (9) + Calidad (3) -->
                <div style="display: grid; grid-template-columns: 9fr 3fr; gap: 16px;">
                    <!-- Gráfica de Evolución -->
                    <div class="bg-white border border-slate-200 p-4 flex flex-col justify-between min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Evolución de Precisión & Pérdida</span>
                            <span id="nlChartTag" class="text-[10px] font-bold text-emerald-600 uppercase">En espera</span>
                        </div>
                        <div class="text-center my-1">
                            <span id="nlHeroAccuracy" class="text-3xl font-bold text-slate-800">--</span>
                            <span class="text-xs text-slate-400 ml-1">% precisión</span>
                        </div>
                        <div style="position: relative; height: 180px; width: 100%;">
                            <canvas id="nlLossChart"></canvas>
                        </div>
                        <div class="grid grid-cols-3 text-center pt-2 border-t border-slate-100 text-[11px] text-slate-500 mt-2">
                            <div>Loss Inicial: <strong id="nlStatInitLoss" class="text-slate-700">--</strong></div>
                            <div>Loss Final: <strong id="nlStatFinalLoss" class="text-slate-700">--</strong></div>
                            <div>Épocas: <strong id="nlStatEpochs" class="text-slate-700">--</strong></div>
                        </div>
                    </div>

                    <!-- Estabilidad / Calidad del Modelo -->
                    <div class="bg-white border border-slate-200 p-4 flex flex-col justify-between min-w-0">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Calidad del Modelo</span>
                        <div class="flex items-center gap-3 my-3">
                            <div class="h-12 w-12 rounded-full border-4 border-emerald-500 flex items-center justify-center text-xs font-bold text-emerald-600 shrink-0" id="nlQualityRing">
                                --
                            </div>
                            <div>
                                <div id="nlQualityLabel" class="text-sm font-bold text-emerald-600">Sin entrenar</div>
                                <div class="text-[11px] text-slate-400">Clasificación multiclase</div>
                            </div>
                        </div>
                        <div class="space-y-2 text-xs border-t border-slate-100 pt-3">
                            <div class="flex justify-between"><span class="text-slate-500">Reglas entrenadas</span><strong id="nlSamplesCount" class="text-slate-800">--</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Categorías</span><strong id="nlClassesCount" class="text-slate-800">--</strong></div>
                            <div class="flex justify-between"><span class="text-slate-500">Tiempo (ms)</span><strong id="nlTrainTime" class="text-slate-800">--</strong></div>
                        </div>
                    </div>

                </div>

                <!-- Fila Media Dashboard: Info Técnica (6) + Probador (6) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <!-- Información Técnica de la Red -->
                    <div class="bg-white border border-slate-200 p-4 min-w-0">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-3">Información de la Red Neuronal</span>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between py-1 border-b border-slate-100"><span class="text-slate-500">Vector de Entrada</span><strong class="text-slate-800">768-dim (nomic-embed-text)</strong></div>
                            <div class="flex justify-between py-1 border-b border-slate-100"><span class="text-slate-500">Optimizador</span><strong class="text-slate-800">Adam</strong></div>
                            <div class="flex justify-between py-1 border-b border-slate-100"><span class="text-slate-500">Función de Pérdida</span><strong class="text-slate-800">Sparse Categorical Crossentropy</strong></div>
                            <div class="flex justify-between py-1 border-b border-slate-100"><span class="text-slate-500">Parámetros Totales</span><strong id="nlTotalParams" class="text-slate-800">--</strong></div>
                            <div class="flex justify-between py-1"><span class="text-slate-500">Persistencia</span><strong class="text-emerald-600">storage/neural_lab/</strong></div>
                        </div>
                        <div id="nlClassesBadges" class="mt-3 flex flex-wrap gap-1"></div>
                    </div>

                    <!-- Probador de Predicción -->
                    <div class="bg-white border border-slate-200 p-4 space-y-3 min-w-0">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Probar Predicción con Consulta Real</span>
                        <div class="flex items-center gap-2">
                            <input type="text" id="nlPredictText" placeholder="Ej: ¿Cuál es el estado de la op OTS-150423?" class="flex-1 px-3 py-1.5 border border-slate-300 text-xs rounded-none">
                            <button type="button" id="btnPredictReal" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold cursor-pointer rounded-none whitespace-nowrap">
                                Clasificar
                            </button>
                        </div>
                        <div id="nlPredictResult" class="text-xs text-slate-600 pt-1"></div>
                    </div>
                </div>

                <!-- Fila Inferior: Registro de Clasificaciones -->
                <div class="bg-white border border-slate-200 p-4">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-3">Registro de Clasificaciones</span>
                    <div class="overflow-x-auto overflow-y-auto custom-scrollbar" style="max-height: 220px;">
                        <table class="w-full text-xs text-left">
                            <thead class="sticky top-0 bg-white z-10">
                                <tr class="border-b border-slate-200 text-[10px] uppercase text-slate-400">
                                    <th class="py-2 pr-3">Hora</th>
                                    <th class="py-2 pr-3">Consulta</th>
                                    <th class="py-2 pr-3">Categoría</th>
                                    <th class="py-2 text-right">Confianza</th>
                                </tr>
                            </thead>
                            <tbody id="nlHistoryBody" class="divide-y divide-slate-100 text-slate-600">
                                <tr><td colspan="4" class="py-4 text-center text-slate-400">Aún no se han realizado clasificaciones.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="{{ asset('js/global_js/chart.umd.min.js') }}"></script>
    <script>
        (function () {
            var lossChart = null;
            var btnTrain = document.getElementById('btnTrainRealModel');
            var btnPredict = document.getElementById('btnPredictReal');

            if (btnTrain) {
                btnTrain.addEventListener('click', async function () {
                    var epochs = parseInt(document.getElementById('nlEpochs').value || '50', 10);
                    var lr = parseFloat(document.getElementById('nlLr').value || '0.001');
                    var hidden = (document.getElementById('nlHiddenLayers').value || '128,64')
                        .split(',').map(function (n) { return parseInt(n.trim(), 10); }).filter(Boolean);

                    btnTrain.disabled = true;
                    btnTrain.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> <span>Entrenando...</span>';

                    try {
                        var res = await window.axios.post('{{ route("training.neural_lab.train") }}', {
                            epochs: epochs, learning_rate: lr, hidden_layers: hidden
                        });
                        var d = res.data;

                        document.getElementById('nlHeroAccuracy').textContent = d.final_accuracy;
                        document.getElementById('nlQualityRing').textContent = Math.round(d.final_accuracy) + '%';
                        document.getElementById('nlQualityLabel').textContent = d.final_accuracy >= 85 ? 'Excelente' : 'Entrenado';
                        document.getElementById('nlChartTag').textContent = 'Modelo activo';
                        document.getElementById('nlStatusBadge').innerHTML = '<i class="fa-solid fa-circle text-[8px] text-emerald-500 mr-1"></i> Modelo entrenado';
                        document.getElementById('nlStatInitLoss').textContent = (d.loss_history && d.loss_history.length) ? d.loss_history[0] : '--';
                        document.getElementById('nlStatFinalLoss').textContent = d.final_loss;
                        document.getElementById('nlStatEpochs').textContent = d.epochs;
                        document.getElementById('nlSamplesCount').textContent = d.total_samples;
                        document.getElementById('nlClassesCount').textContent = d.num_classes;
                        document.getElementById('nlTrainTime').textContent = d.training_time_ms + ' ms';
                        document.getElementById('nlTotalParams').textContent = (d.total_params || 0).toLocaleString();
                        document.getElementById('nlClassesBadges').innerHTML = (d.classes || []).map(function (c) {
                            return '<span class="px-2 py-0.5 bg-slate-100 border border-slate-200 text-[10px] font-semibold text-slate-600">' + c + '</span>';
                        }).join('');

                        var canvas = document.getElementById('nlLossChart');
                        var ChartLib = window.Chart || (typeof Chart !== 'undefined' ? Chart : null);
                        if (!ChartLib) {
                            await new Promise(function (resolve) {
                                var sc = document.createElement('script');
                                sc.src = '{{ asset("js/global_js/chart.umd.min.js") }}';
                                sc.onload = resolve;
                                sc.onerror = resolve;
                                document.head.appendChild(sc);
                            });
                            ChartLib = window.Chart || (typeof Chart !== 'undefined' ? Chart : null);
                        }
                        if (canvas && ChartLib) {
                            var ctx = canvas.getContext('2d');
                            if (lossChart) lossChart.destroy();
                            lossChart = new ChartLib(ctx, {
                                type: 'line',
                                data: {
                                    labels: (d.loss_history || []).map(function (_, i) { return i + 1; }),
                                    datasets: [
                                        {
                                            label: 'Accuracy (%)',
                                            data: d.accuracy_history || [],
                                            borderColor: '#059669',
                                            backgroundColor: 'rgba(5, 150, 105, 0.12)',
                                            fill: true,
                                            tension: 0.3,
                                            borderWidth: 2,
                                            pointRadius: 0
                                        },
                                        {
                                            label: 'Loss',
                                            data: d.loss_history || [],
                                            borderColor: '#0476D9',
                                            backgroundColor: 'rgba(4, 118, 217, 0.08)',
                                            fill: true,
                                            tension: 0.3,
                                            borderWidth: 2,
                                            pointRadius: 0
                                        }
                                    ]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } } },
                                    scales: { x: { display: false }, y: { beginAtZero: true } }
                                }
                            });
                        }
                        if (window.toastr) window.toastr.success('Modelo entrenado con ' + d.total_samples + ' reglas (' + d.final_accuracy + '% precisión).');
                    } catch (err) {
                        console.error('Error en Neural Lab:', err);
                        if (window.toastr) window.toastr.error(err?.response?.data?.detail || err?.message || 'Error al entrenar con pgvector.');
                    } finally {
                        btnTrain.disabled = false;
                        btnTrain.innerHTML = '<i class="fa-solid fa-play"></i> <span>Entrenar con Mis Reglas</span>';
                    }
                });
            }

            if (btnPredict) {
                btnPredict.addEventListener('click', async function () {
                    var text = (document.getElementById('nlPredictText').value || '').trim();
                    if (!text) return;
                    var origHtml = btnPredict.innerHTML;
                    btnPredict.disabled = true;
                    btnPredict.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin mr-1"></i> Clasificando...';
                    try {
                        var res = await window.axios.post('{{ route("training.neural_lab.predict") }}', { text: text });
                        var d = res.data;
                        var badges = (d.top_predictions || []).map(function (p) {
                            return '<span class="inline-block px-2 py-0.5 bg-slate-100 border border-slate-200 mr-1.5 mb-1">' + p.label + ': <strong>' + p.confidence + '%</strong></span>';
                        }).join('');
                        document.getElementById('nlPredictResult').innerHTML =
                            'Categoría predicha: <span class="text-datamind-primary font-bold">' + d.predicted_label + ' (' + d.confidence + '%)</span>' +
                            '<div class="mt-2">' + badges + '</div>';

                        var tbody = document.getElementById('nlHistoryBody');
                        if (tbody) {
                            if (tbody.querySelector('td[colspan="4"]')) tbody.innerHTML = '';
                            var now = new Date().toLocaleTimeString();
                            var tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td class="py-2 pr-3 text-slate-400">' + now + '</td>' +
                                '<td class="py-2 pr-3 truncate max-w-xs">' + text + '</td>' +
                                '<td class="py-2 pr-3 font-semibold text-datamind-primary">' + d.predicted_label + '</td>' +
                                '<td class="py-2 text-right font-bold text-emerald-600">' + d.confidence + '%</td>';
                            tbody.insertBefore(tr, tbody.firstChild);
                        }
                    } catch (err) {
                        if (window.toastr) window.toastr.error('Error al clasificar la consulta.');
                    } finally {
                        btnPredict.disabled = false;
                        btnPredict.innerHTML = origHtml;
                    }
                });
            }
        })();
    </script>

    @include('settings.partials.settings-spa-nav')
</x-app-layout>
