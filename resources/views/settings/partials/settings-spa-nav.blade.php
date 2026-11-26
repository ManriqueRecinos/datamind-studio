<script>
    (function () {
        if (window.__settingsSpaNavInitialized) return;
        window.__settingsSpaNavInitialized = true;

        var MAIN_SELECTOR = '#settings-main-content, #training-main-content';
        var EXTRA_SELECTOR = '#settings-sidebar-extra';

        function buildSkeletonHtml(targetUrl) {
            var path = '';
            try {
                path = new URL(targetUrl, window.location.origin).pathname.toLowerCase();
            } catch (e) {
                path = String(targetUrl || '').toLowerCase();
            }

            // 1. Directorio & Auditoría (Tabla Corporativa de Usuarios)
            if (path.indexOf('users') !== -1 || path.indexOf('usuarios') !== -1) {
                var rows = '';
                for (var i = 0; i < 5; i++) {
                    rows += '' +
                        '<div class="flex items-center justify-between py-3.5 border-b border-slate-100">' +
                        '  <div class="flex items-center gap-3 w-1/3">' +
                        '    <div class="size-9 rounded-full bg-slate-200 shrink-0"></div>' +
                        '    <div class="space-y-1.5 flex-1">' +
                        '      <div class="h-2.5 bg-slate-200 rounded w-3/4"></div>' +
                        '      <div class="h-2 bg-slate-100 rounded w-1/3"></div>' +
                        '    </div>' +
                        '  </div>' +
                        '  <div class="h-2.5 bg-slate-200 rounded w-1/5"></div>' +
                        '  <div class="h-5 bg-slate-200 rounded w-24"></div>' +
                        '  <div class="h-5 bg-slate-200 rounded w-16"></div>' +
                        '  <div class="flex gap-1.5">' +
                        '    <div class="size-7 bg-slate-200"></div>' +
                        '    <div class="size-7 bg-slate-200"></div>' +
                        '    <div class="size-7 bg-slate-200"></div>' +
                        '  </div>' +
                        '</div>';
                }
                return '' +
                    '<div class="flex-1 p-6 overflow-y-auto bg-slate-50/30">' +
                    '  <div class="bg-white border border-slate-200 p-5 animate-pulse">' +
                    '    <div class="flex items-center justify-between pb-4 border-b border-slate-200 mb-3">' +
                    '      <div class="h-3.5 bg-slate-200 rounded w-56"></div>' +
                    '      <div class="h-8 bg-slate-200 rounded w-48"></div>' +
                    '    </div>' +
                    rows +
                    '  </div>' +
                    '</div>';
            }

            // 2. Roles & Permisos (Matriz de Permisos por Módulo)
            if (path.indexOf('roles') !== -1) {
                var roleCards = '';
                for (var r = 0; r < 3; r++) {
                    roleCards += '' +
                        '<div class="bg-white border border-slate-200 p-5 space-y-4 animate-pulse">' +
                        '  <div class="flex items-center justify-between border-b border-slate-100 pb-3">' +
                        '    <div class="h-3.5 bg-slate-200 rounded w-40"></div>' +
                        '    <div class="h-5 bg-slate-200 rounded w-20"></div>' +
                        '  </div>' +
                        '  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">' +
                        '    <div class="h-8 bg-slate-100 border border-slate-200"></div>' +
                        '    <div class="h-8 bg-slate-100 border border-slate-200"></div>' +
                        '    <div class="h-8 bg-slate-100 border border-slate-200"></div>' +
                        '    <div class="h-8 bg-slate-100 border border-slate-200"></div>' +
                        '  </div>' +
                        '</div>';
                }
                return '' +
                    '<div class="flex-1 p-6 overflow-y-auto bg-slate-50/30 space-y-5">' +
                    roleCards +
                    '</div>';
            }

            // 3. Vectorización / Centro de Aprendizaje (pgvector)
            if (path.indexOf('training') !== -1 || path.indexOf('vector') !== -1) {
                var ruleCards = '';
                for (var t = 0; t < 6; t++) {
                    ruleCards += '' +
                        '<div class="p-4 border border-slate-200 bg-white space-y-3 animate-pulse">' +
                        '  <div class="flex items-center justify-between">' +
                        '    <div class="h-4 bg-slate-200 rounded w-24"></div>' +
                        '    <div class="h-3 bg-slate-100 rounded w-16"></div>' +
                        '  </div>' +
                        '  <div class="h-3.5 bg-slate-200 rounded w-3/4"></div>' +
                        '  <div class="space-y-1.5">' +
                        '    <div class="h-2.5 bg-slate-100 rounded w-full"></div>' +
                        '    <div class="h-2.5 bg-slate-100 rounded w-5/6"></div>' +
                        '  </div>' +
                        '  <div class="flex items-center justify-between pt-2 border-t border-slate-100">' +
                        '    <div class="h-2.5 bg-slate-100 rounded w-20"></div>' +
                        '    <div class="h-2.5 bg-slate-200 rounded w-16"></div>' +
                        '  </div>' +
                        '</div>';
                }
                return '' +
                    '<div class="flex-1 flex flex-col bg-white overflow-hidden">' +
                    '  <div class="px-5 py-3 border-b border-slate-200 bg-slate-50/70 flex items-center justify-between animate-pulse">' +
                    '    <div class="flex items-center gap-3">' +
                    '      <div class="h-9 w-9 bg-slate-200"></div>' +
                    '      <div class="space-y-1.5">' +
                    '        <div class="h-3.5 bg-slate-200 rounded w-48"></div>' +
                    '        <div class="h-2.5 bg-slate-200 rounded w-32"></div>' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="h-8 w-28 bg-slate-200"></div>' +
                    '  </div>' +
                    '  <div class="flex-1 p-5 overflow-y-auto bg-slate-50/20 space-y-5">' +
                    '    <div class="flex flex-wrap items-center justify-between gap-3 pb-2 border-b border-slate-200 animate-pulse">' +
                    '      <div class="flex items-center gap-2">' +
                    '        <div class="h-4 bg-slate-200 rounded w-36"></div>' +
                    '        <div class="h-7 bg-slate-100 border border-slate-200 w-44"></div>' +
                    '        <div class="h-7 bg-slate-100 border border-slate-200 w-36"></div>' +
                    '      </div>' +
                    '      <div class="h-7 bg-slate-100 border border-slate-200 w-64"></div>' +
                    '    </div>' +
                    '    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">' +
                    ruleCards +
                    '    </div>' +
                    '  </div>' +
                    '</div>';
            }

            // 4. Apariencia & Paletas (Grid de 5 Paletas + Selectores)
            var palettes = '';
            for (var p = 0; p < 5; p++) {
                palettes += '' +
                    '<div class="p-3 border border-slate-200 bg-white flex flex-col items-center gap-2">' +
                    '  <div class="h-4 w-full bg-slate-200"></div>' +
                    '  <div class="h-2.5 w-20 bg-slate-200 rounded"></div>' +
                    '</div>';
            }
            return '' +
                '<div class="flex-1 p-6 overflow-y-auto bg-slate-50/30 space-y-6">' +
                '  <div class="bg-white border border-slate-200 p-5 animate-pulse space-y-4">' +
                '    <div class="h-3.5 bg-slate-200 rounded w-56"></div>' +
                '    <div class="h-2.5 bg-slate-100 rounded w-80"></div>' +
                '    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">' +
                palettes +
                '    </div>' +
                '  </div>' +
                '  <div class="bg-white border border-slate-200 p-5 animate-pulse space-y-4">' +
                '    <div class="h-3.5 bg-slate-200 rounded w-48"></div>' +
                '    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">' +
                '      <div class="h-10 bg-slate-100 border border-slate-200"></div>' +
                '      <div class="h-10 bg-slate-100 border border-slate-200"></div>' +
                '      <div class="h-10 bg-slate-100 border border-slate-200"></div>' +
                '    </div>' +
                '  </div>' +
                '</div>';
        }

        function executeInlineScripts(container, parsedDoc) {
            var origAddEventListener = document.addEventListener.bind(document);
            document.addEventListener = function (type, listener, options) {
                if (type === 'DOMContentLoaded' && typeof listener === 'function') {
                    try { listener(); } catch (e) { console.error(e); }
                    return;
                }
                return origAddEventListener(type, listener, options);
            };

            try {
                var scripts = [];
                if (container) {
                    container.querySelectorAll('script').forEach(function (s) { scripts.push(s); });
                }
                if (parsedDoc && parsedDoc.body) {
                    parsedDoc.body.querySelectorAll('script').forEach(function (s) {
                        if (s.src) {
                            if (s.src.indexOf('/js/') !== -1 && s.src.indexOf('/global_js/') === -1) {
                                scripts.push(s);
                            }
                            return;
                        }
                        if (s.textContent && s.textContent.indexOf('__settingsSpaNavInitialized') === -1) {
                            if (!container || !container.contains(s)) {
                                scripts.push(s);
                            }
                        }
                    });
                }

                var externalCount = 0;
                var loadedExternal = 0;

                function checkExternalDone() {
                    loadedExternal++;
                    if (loadedExternal >= externalCount) {
                        document.dispatchEvent(new Event('DOMContentLoaded'));
                    }
                }

                scripts.forEach(function (oldScript) {
                    var newScript = document.createElement('script');
                    if (oldScript.src) {
                        externalCount++;
                        var baseSrc = oldScript.src.split('?')[0];
                        document.body.querySelectorAll('script[src]').forEach(function (existing) {
                            if (existing.src.split('?')[0] === baseSrc) {
                                existing.parentNode.removeChild(existing);
                            }
                        });
                        newScript.src = baseSrc + '?v=' + Date.now();
                        newScript.async = false;
                        newScript.onload = checkExternalDone;
                        newScript.onerror = checkExternalDone;
                        document.body.appendChild(newScript);
                    } else {
                        newScript.textContent = '(function(){\n' + oldScript.textContent + '\n})();';
                        document.body.appendChild(newScript);
                        document.body.removeChild(newScript);
                    }
                });
            } finally {
                document.addEventListener = origAddEventListener;
            }
        }

        function updateSidebarActiveState(targetUrl) {
            var aside = document.querySelector('main aside');
            if (!aside) return;

            var targetPath;
            try {
                targetPath = new URL(targetUrl, window.location.origin).pathname;
            } catch (e) {
                targetPath = targetUrl;
            }

            aside.querySelectorAll('a[href]').forEach(function (link) {
                var linkPath;
                try {
                    linkPath = new URL(link.getAttribute('href'), window.location.origin).pathname;
                } catch (e) {
                    return;
                }
                if (linkPath === targetPath) {
                    link.classList.add('border-l-datamind-primary', 'bg-datamind-neutral/40', 'font-semibold', 'text-datamind-primary');
                    link.classList.remove('border-l-transparent', 'text-slate-600');
                } else {
                    link.classList.remove('border-l-datamind-primary', 'bg-datamind-neutral/40', 'font-semibold', 'text-datamind-primary');
                    link.classList.add('border-l-transparent', 'text-slate-600');
                }
            });
        }

        async function navigateSettings(url, pushState) {
            var mainEl = document.querySelector(MAIN_SELECTOR);
            if (!mainEl) {
                window.location.href = url;
                return;
            }

            mainEl.innerHTML = buildSkeletonHtml(url);

            try {
                var fetchUrl = url + (url.indexOf('?') === -1 ? '?_spa_ts=' : '&_spa_ts=') + Date.now();
                var htmlText = '';
                if (window.axios) {
                    var res = await window.axios.get(fetchUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
                    });
                    htmlText = res.data;
                } else {
                    var resp = await fetch(fetchUrl, {
                        cache: 'no-store',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Cache-Control': 'no-cache' }
                    });
                    htmlText = await resp.text();
                }

                var parser = new DOMParser();
                var doc = parser.parseFromString(htmlText, 'text/html');
                var newMain = doc.querySelector(MAIN_SELECTOR);

                if (!newMain) {
                    window.location.href = url;
                    return;
                }

                var cleanMain = mainEl.cloneNode(false);
                cleanMain.innerHTML = newMain.innerHTML;
                mainEl.parentNode.replaceChild(cleanMain, mainEl);
                executeInlineScripts(cleanMain, doc);

                var extraEl = document.querySelector(EXTRA_SELECTOR);
                var newExtra = doc.querySelector(EXTRA_SELECTOR);
                if (extraEl && newExtra) {
                    extraEl.innerHTML = newExtra.innerHTML;
                    executeInlineScripts(extraEl);
                }

                if (doc.title) {
                    document.title = doc.title;
                }

                updateSidebarActiveState(url);

                if (pushState) {
                    window.history.pushState({ settingsUrl: url }, '', url);
                }
            } catch (err) {
                console.error('Error cargando sección:', err);
                window.location.href = url;
            }
        }

        document.addEventListener('click', function (e) {
            var link = e.target.closest('main aside a[href]');
            if (!link) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || link.target === '_blank') return;

            var href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;

            e.preventDefault();
            if (href === window.location.href || new URL(href, window.location.origin).pathname === window.location.pathname) {
                return;
            }
            navigateSettings(href, true);
        });

        window.addEventListener('popstate', function () {
            if (document.querySelector(MAIN_SELECTOR)) {
                navigateSettings(window.location.href, false);
            }
        });
    })();
</script>
