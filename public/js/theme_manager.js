/**
 * DataMind Studio - Motor Reactivo de Personalización de Ecosistemas
 * Sincroniza Colores, Tipografía, Tamaño y Tema en caliente (Zero-FOUC).
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'datamind_ui_preferences_v1';

    const PRESET_PALETTES = {
        ocean: {
            id: 'ocean',
            name: 'DataMind Ocean (Oficial)',
            primary: '#0476D9',
            primaryDark: '#0468BF',
            secondary: '#4B89BF',
            accentLight: '#99D0F2',
            bgNeutral: '#F2F2F2'
        },
        emerald: {
            id: 'emerald',
            name: 'Emerald Analytics',
            primary: '#059669',
            primaryDark: '#047857',
            secondary: '#10B981',
            accentLight: '#A7F3D0',
            bgNeutral: '#F0FDF4'
        },
        indigo: {
            id: 'indigo',
            name: 'Royal Indigo AI',
            primary: '#4F46E5',
            primaryDark: '#4338CA',
            secondary: '#6366F1',
            accentLight: '#C7D2FE',
            bgNeutral: '#F5F3FF'
        },
        amber: {
            id: 'amber',
            name: 'Executive Amber',
            primary: '#D97706',
            primaryDark: '#B45309',
            secondary: '#F59E0B',
            accentLight: '#FDE68A',
            bgNeutral: '#FFFBEB'
        },
        graphite: {
            id: 'graphite',
            name: 'Minimalist Graphite',
            primary: '#334155',
            primaryDark: '#1E293B',
            secondary: '#64748B',
            accentLight: '#CBD5E1',
            bgNeutral: '#F8FAFC'
        }
    };

    const FONT_MAP = {
        inter: "'Inter', 'Instrument Sans', system-ui, -apple-system, sans-serif",
        roboto: "'Roboto', system-ui, -apple-system, sans-serif",
        jakarta: "'Plus Jakarta Sans', system-ui, sans-serif",
        system: "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif",
        mono: "ui-monospace, 'JetBrains Mono', SFMono-Regular, Menlo, Consolas, monospace",
        serif: "Georgia, Cambria, 'Times New Roman', Times, serif"
    };

    const FONT_SIZES = {
        xs: '12px',
        sm: '13px',
        base: '14px',
        lg: '15px',
        xl: '16px'
    };

    const DEFAULT_PREFS = {
        preset: 'ocean',
        primary: '#0476D9',
        primaryDark: '#0468BF',
        secondary: '#4B89BF',
        accentLight: '#99D0F2',
        bgNeutral: '#F2F2F2',
        fontKey: 'inter',
        fontFamilyCss: FONT_MAP.inter,
        fontSizeKey: 'base',
        fontSizePx: '14px',
        themeMode: 'light' // 'light' | 'dark'
    };

    function hexToRgbString(hex) {
        let clean = String(hex || '#0476D9').replace('#', '').trim();
        if (clean.length === 3) {
            clean = clean[0] + clean[0] + clean[1] + clean[1] + clean[2] + clean[2];
        }
        if (clean.length !== 6) {
            return '4, 118, 217';
        }
        const r = parseInt(clean.substring(0, 2), 16) || 4;
        const g = parseInt(clean.substring(2, 4), 16) || 118;
        const b = parseInt(clean.substring(4, 6), 16) || 217;
        return `${r}, ${g}, ${b}`;
    }

    function loadPreferences() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) return { ...DEFAULT_PREFS };
            const parsed = JSON.parse(raw);
            return { ...DEFAULT_PREFS, ...parsed };
        } catch (e) {
            return { ...DEFAULT_PREFS };
        }
    }

    function applyPreferences(prefs) {
        const p = prefs || loadPreferences();
        const root = document.documentElement;

        // Variables CSS Hex
        root.style.setProperty('--datamind-primary', p.primary);
        root.style.setProperty('--datamind-primary-dark', p.primaryDark);
        root.style.setProperty('--datamind-secondary', p.secondary);
        root.style.setProperty('--datamind-accent-light', p.accentLight);
        root.style.setProperty('--datamind-bg-neutral', p.bgNeutral);

        // Canales RGB para composiciones rgba y opacidades
        root.style.setProperty('--datamind-primary-rgb', hexToRgbString(p.primary));
        root.style.setProperty('--datamind-primary-dark-rgb', hexToRgbString(p.primaryDark));
        root.style.setProperty('--datamind-secondary-rgb', hexToRgbString(p.secondary));
        root.style.setProperty('--datamind-accent-light-rgb', hexToRgbString(p.accentLight));
        root.style.setProperty('--datamind-bg-neutral-rgb', hexToRgbString(p.bgNeutral));

        // Gradientes dinámicos
        root.style.setProperty('--datamind-gradient-primary', `linear-gradient(135deg, ${p.primaryDark} 0%, ${p.primary} 60%, ${p.secondary} 100%)`);
        root.style.setProperty('--datamind-gradient-hero', `linear-gradient(135deg, ${p.primaryDark} 0%, ${p.primary} 65%, ${p.secondary} 100%)`);
        root.style.setProperty('--datamind-gradient-avatar', `linear-gradient(135deg, ${p.primaryDark} 0%, ${p.secondary} 100%)`);

        // Tipografía y tamaño
        const fontCss = p.fontFamilyCss || FONT_MAP[p.fontKey] || FONT_MAP.inter;
        root.style.setProperty('--datamind-font-family', fontCss);
        const fontSizePx = p.fontSizePx || FONT_SIZES[p.fontSizeKey] || '14px';
        root.style.setProperty('--datamind-font-size', fontSizePx);

        // Clases de modo oscuro
        if (p.themeMode === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }

        // Actualizar fuentes de forma directa en el body si ya existe
        if (document.body) {
            document.body.style.fontFamily = fontCss;
            document.body.style.backgroundColor = p.themeMode === 'dark' ? '#0f172a' : p.bgNeutral;
        }
    }

    function savePreferences(prefs) {
        const merged = { ...loadPreferences(), ...prefs };
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(merged));
        } catch (e) {
            console.warn('DataMindTheme: No se pudo escribir en localStorage', e);
        }
        applyPreferences(merged);
        window.dispatchEvent(new CustomEvent('datamind:theme-changed', { detail: merged }));
        return merged;
    }

    // Ejecución inmediata Zero-FOUC en carga de script
    applyPreferences(loadPreferences());

    // Objeto Global DataMindTheme
    window.DataMindTheme = {
        PRESET_PALETTES,
        FONT_MAP,
        FONT_SIZES,
        DEFAULT_PREFS,
        loadPreferences,
        savePreferences,
        applyPreferences,

        getPreferences: function () {
            return loadPreferences();
        },

        getColors: function () {
            const p = loadPreferences();
            return {
                primary: p.primary,
                primaryDark: p.primaryDark,
                secondary: p.secondary,
                accentLight: p.accentLight,
                bgNeutral: p.bgNeutral,
                preset: p.preset
            };
        },

        applyPreset: function (presetKey) {
            const pal = PRESET_PALETTES[presetKey] || PRESET_PALETTES.ocean;
            return savePreferences({
                preset: presetKey,
                primary: pal.primary,
                primaryDark: pal.primaryDark,
                secondary: pal.secondary,
                accentLight: pal.accentLight,
                bgNeutral: pal.bgNeutral
            });
        },

        applyCustomColors: function (colorObj) {
            const current = loadPreferences();
            return savePreferences({
                preset: 'custom',
                primary: colorObj.primary || current.primary,
                primaryDark: colorObj.primaryDark || current.primaryDark,
                secondary: colorObj.secondary || current.secondary,
                accentLight: colorObj.accentLight || current.accentLight,
                bgNeutral: colorObj.bgNeutral || current.bgNeutral
            });
        },

        setFontFamily: function (fontKeyOrCss) {
            let fontCss = FONT_MAP[fontKeyOrCss];
            let fontKey = fontKeyOrCss;
            if (!fontCss) {
                fontCss = fontKeyOrCss;
                fontKey = 'custom';
            }
            return savePreferences({
                fontKey: fontKey,
                fontFamilyCss: fontCss
            });
        },

        setFontSize: function (sizeKey) {
            const px = FONT_SIZES[sizeKey] || '14px';
            return savePreferences({
                fontSizeKey: sizeKey,
                fontSizePx: px
            });
        },

        setThemeMode: function (mode) {
            return savePreferences({ themeMode: mode === 'dark' ? 'dark' : 'light' });
        },

        toggleThemeMode: function () {
            const cur = loadPreferences();
            return savePreferences({ themeMode: cur.themeMode === 'dark' ? 'light' : 'dark' });
        },

        resetDefaults: function () {
            return savePreferences({ ...DEFAULT_PREFS });
        },

        resetToDefaults: function () {
            return savePreferences({ ...DEFAULT_PREFS });
        },

        updateSubnavPosition: function (moduleName, contextDetail) {
            const modEl = document.getElementById('subnavActiveModule');
            const ctxEl = document.getElementById('subnavActiveContext');
            if (modEl && moduleName) modEl.textContent = moduleName;
            if (ctxEl && contextDetail !== undefined) ctxEl.textContent = contextDetail;
        }
    };

    // Re-aplicación en DOMContentLoaded y listeners globales
    document.addEventListener('DOMContentLoaded', function () {
        applyPreferences(loadPreferences());

        // Dropdown de perfil de usuario en Navbar
        const profileBtn = document.getElementById('btnUserProfileMenu');
        const profileDropdown = document.getElementById('userProfileDropdown');
        const quickThemeBtn = document.getElementById('btnQuickToggleTheme');

        if (profileBtn && profileDropdown) {
            profileBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', function (e) {
                if (!profileDropdown.contains(e.target) && e.target !== profileBtn) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }

        if (quickThemeBtn) {
            quickThemeBtn.addEventListener('click', function () {
                const updated = window.DataMindTheme.toggleThemeMode();
                const icon = quickThemeBtn.querySelector('i');
                if (icon) {
                    icon.className = updated.themeMode === 'dark' ? 'fa-solid fa-sun text-amber-400' : 'fa-solid fa-moon text-datamind-primary';
                }
            });
        }
    });
})();
