/**
 * SelectPicker Global Handler
 * Maneja la lógica de los componentes SelectPicker personalizados con Tailwind CSS.
 */
(function () {
    const SelectPicker = {
        init() {
            document.addEventListener('click', (e) => this.handleOutsideClick(e));
            
            // Inicializar todos los pickers presentes
            this.setupAll();

            // Re-inicializar cuando el contenido SPA cambie
            document.addEventListener('spa-content-loaded', () => this.setupAll());
        },

        setupAll() {
            document.querySelectorAll('.select-picker-container:not([data-picker-init])').forEach(container => {
                this.setupPicker(container);
            });
        },

        setupPicker(container) {
            const trigger = container.querySelector('.select-picker-trigger');
            const dropdown = container.querySelector('.select-picker-dropdown');
            const searchInput = container.querySelector('.select-picker-search');
            const optionsList = container.querySelector('.select-picker-options');
            const options = container.querySelectorAll('.select-picker-option');
            const hiddenInput = container.querySelector('.select-picker-input');
            const label = container.querySelector('.select-picker-label');
            const icon = trigger.querySelector('.fa-chevron-down');

            container.dataset.pickerInit = 'true';

            // Toggle Dropdown
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (trigger.disabled) return;
                
                const isOpen = !dropdown.classList.contains('hidden');
                this.closeAll();
                
                if (!isOpen) {
                    dropdown.classList.remove('hidden');
                    icon.classList.add('rotate-180');
                    searchInput.value = '';
                    this.filterOptions(container, '');
                    setTimeout(() => searchInput.focus(), 10);
                }
            });

            // Search Filter
            searchInput.addEventListener('input', (e) => {
                this.filterOptions(container, e.target.value);
            });

            // Option Selection
            options.forEach(opt => {
                opt.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectOption(container, opt);
                });
            });

            // Custom Option Selection
            const customOpt = container.querySelector('.select-picker-custom-option');
            if (customOpt) {
                customOpt.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const text = customOpt.querySelector('.select-picker-custom-text').textContent;
                    this.selectCustom(container, text);
                });
            }

            // Handle Dynamic Value Setting (from JS)
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                        this.updateDisplayFromValue(container);
                    }
                });
            });
            observer.observe(hiddenInput, { attributes: true });
            
            // Initial sync if value exists
            if (hiddenInput.value) {
                this.updateDisplayFromValue(container);
            }
        },

        selectOption(container, opt) {
            const hiddenInput = container.querySelector('.select-picker-input');
            const label = container.querySelector('.select-picker-label');
            const options = container.querySelectorAll('.select-picker-option');

            hiddenInput.value = opt.dataset.value;
            hiddenInput.dataset.isCustom = 'false';
            label.textContent = opt.dataset.label;
            label.classList.remove('text-gray-400', 'dark:text-gray-500', 'font-medium');

            options.forEach(o => {
                const check = o.querySelector('.fa-check');
                if (check) check.remove();
            });
            opt.insertAdjacentHTML('beforeend', '<i class="fas fa-check text-[10px] text-primary dark:text-accent absolute right-3 top-1/2 -translate-y-1/2"></i>');

            this.closeAll();
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        },

        selectCustom(container, text) {
            const hiddenInput = container.querySelector('.select-picker-input');
            const label = container.querySelector('.select-picker-label');
            const options = container.querySelectorAll('.select-picker-option');

            hiddenInput.value = text;
            hiddenInput.dataset.isCustom = 'true';
            label.textContent = text;
            label.classList.remove('text-gray-400', 'dark:text-gray-500', 'font-medium');

            options.forEach(o => {
                const check = o.querySelector('.fa-check');
                if (check) check.remove();
            });

            this.closeAll();
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        },

        updateDisplayFromValue(container) {
            const hiddenInput = container.querySelector('.select-picker-input');
            const label = container.querySelector('.select-picker-label');
            const value = hiddenInput.value;
            const option = container.querySelector(`.select-picker-option[data-value="${value}"]`);

            if (option) {
                label.textContent = option.dataset.label;
                label.classList.remove('text-gray-400', 'dark:text-gray-500', 'font-medium');
            } else if (!value) {
                const placeholder = container.querySelector('.select-picker-trigger').dataset.placeholder || 'Seleccionar...';
                label.textContent = placeholder;
                label.classList.add('text-gray-400', 'dark:text-gray-500', 'font-medium');
            }
        },

        filterOptions(container, query) {
            const q = query.toLowerCase().trim();
            const options = container.querySelectorAll('.select-picker-option');
            const noResults = container.querySelector('.select-picker-no-results');
            let found = 0;

            options.forEach(opt => {
                const label = opt.dataset.label.toLowerCase();
                const search = opt.dataset.search ? opt.dataset.search.toLowerCase() : '';
                const isHiddenByFilter = opt.classList.contains('picker-hidden-by-filter');
                
                if (!isHiddenByFilter && (label.includes(q) || search.includes(q))) {
                    opt.classList.remove('hidden');
                    found++;
                } else {
                    opt.classList.add('hidden');
                }
            });

            const customOpt = container.querySelector('.select-picker-custom-option');
            if (customOpt) {
                if (q.length > 0 && found === 0) {
                    customOpt.classList.remove('hidden');
                    customOpt.querySelector('.select-picker-custom-text').textContent = query;
                } else {
                    customOpt.classList.add('hidden');
                }
            }

            if (found === 0 && (!customOpt || customOpt.classList.contains('hidden'))) noResults.classList.remove('hidden');
            else noResults.classList.add('hidden');
        },

        closeAll() {
            document.querySelectorAll('.select-picker-dropdown').forEach(d => d.classList.add('hidden'));
            document.querySelectorAll('.fa-chevron-down.rotate-180').forEach(i => i.classList.remove('rotate-180'));
        },

        handleOutsideClick(e) {
            if (!e.target.closest('.select-picker-container')) {
                this.closeAll();
            }
        }
    };

    // Exponer helper para actualizar desde afuera
    window.setPickerValue = function(id, value) {
        const input = document.getElementById(id);
        if (input && input.classList.contains('select-picker-input')) {
            input.value = value;
            // Forzar el observer o llamar directo
            const container = input.closest('.select-picker-container');
            if (container) {
                const option = container.querySelector(`.select-picker-option[data-value="${value}"]`);
                const label = container.querySelector('.select-picker-label');
                if (option) {
                    label.textContent = option.dataset.label;
                    label.classList.remove('text-gray-400', 'dark:text-gray-500', 'font-medium');
                } else {
                    label.textContent = 'Seleccionar...';
                    label.classList.add('text-gray-400', 'dark:text-gray-500', 'font-medium');
                }
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    };

    window.SelectPicker = SelectPicker;
    SelectPicker.init();
})();
