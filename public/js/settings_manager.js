/**
 * DataMind Studio - Gestor de Configuración y Personalización Visual
 * Maneja eventos de paletas, pickers de color, tipografía y tamaño de texto.
 */
document.addEventListener('DOMContentLoaded', function () {
    if (!window.DataMindTheme) return;

    // Sincronizar selectores y pickers con el estado actual
    function syncPickers() {
        const prefs = window.DataMindTheme.getPreferences();
        $('#pickerPrimary').val(prefs.primary);
        $('#pickerPrimaryDark').val(prefs.primaryDark);
        $('#pickerSecondary').val(prefs.secondary);
        $('#pickerAccentLight').val(prefs.accentLight);
        $('#pickerBgNeutral').val(prefs.bgNeutral);

        // Selector de fuente
        $('#fontFamilySelector').val(prefs.fontKey || 'inter');

        // Escala de tamaño
        const sizeKey = prefs.fontSizeKey || 'base';
        $('.btn-font-size').removeClass('border-datamind-primary bg-datamind-neutral font-extrabold').addClass('border-slate-200');
        $(`.btn-font-size[data-size="${sizeKey}"]`).removeClass('border-slate-200').addClass('border-datamind-primary bg-datamind-neutral font-extrabold');

        // Paleta activa
        $('.palette-preset-card').removeClass('border-datamind-primary bg-datamind-neutral').addClass('border-slate-200 bg-white');
        if (prefs.preset && prefs.preset !== 'custom') {
            $(`.palette-preset-card[data-preset="${prefs.preset}"]`).removeClass('border-slate-200 bg-white').addClass('border-datamind-primary bg-datamind-neutral');
        }
    }

    syncPickers();

    // Clic en tarjetas de paleta predefinida
    $('.palette-preset-card').on('click', function () {
        const presetKey = $(this).data('preset');
        window.DataMindTheme.applyPreset(presetKey);
        syncPickers();
        if (window.toastr) {
            toastr.success(`Paleta aplicada: ${presetKey.toUpperCase()}`, 'DataMind Themes');
        }
    });

    // Selectores individuales de color en tiempo real
    $('#pickerPrimary, #pickerPrimaryDark, #pickerSecondary, #pickerAccentLight, #pickerBgNeutral').on('input change', function () {
        window.DataMindTheme.applyCustomColors({
            primary: $('#pickerPrimary').val(),
            primaryDark: $('#pickerPrimaryDark').val(),
            secondary: $('#pickerSecondary').val(),
            accentLight: $('#pickerAccentLight').val(),
            bgNeutral: $('#pickerBgNeutral').val(),
        });
    });

    // Selector de tipografía
    $('#fontFamilySelector').on('change', function () {
        window.DataMindTheme.setFontFamily($(this).val());
        if (window.toastr) {
            toastr.info('Tipografía actualizada', 'DataMind Themes');
        }
    });

    // Escala de tamaño de texto
    $('.btn-font-size').on('click', function () {
        const size = $(this).data('size');
        window.DataMindTheme.setFontSize(size);
        $('.btn-font-size').removeClass('border-datamind-primary bg-datamind-neutral font-extrabold').addClass('border-slate-200');
        $(this).removeClass('border-slate-200').addClass('border-datamind-primary bg-datamind-neutral font-extrabold');
        if (window.toastr) {
            toastr.info(`Tamaño de texto: ${size}`, 'DataMind Themes');
        }
    });

    // Restaurar valores por defecto
    $('#btnResetDefaultTheme').on('click', function () {
        window.DataMindTheme.resetDefaults();
        syncPickers();
        if (window.toastr) {
            toastr.success('Tema restaurado a valores originales', 'DataMind Themes');
        }
    });
});
