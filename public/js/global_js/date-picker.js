/**
 * DatePicker Global Handler
 * Maneja la lógica de los componentes DatePicker personalizados con Tailwind CSS.
 */
(function () {
    const DatePicker = {
        months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
        
        init() {
            document.addEventListener('click', (e) => this.handleOutsideClick(e));
            this.setupAll();
            document.addEventListener('spa-content-loaded', () => this.setupAll());
        },

        setupAll() {
            document.querySelectorAll('.date-picker-container:not([data-picker-init])').forEach(container => {
                this.setupPicker(container);
            });
        },

        setupPicker(container) {
            const trigger = container.querySelector('.date-picker-trigger');
            const dropdown = container.querySelector('.date-picker-dropdown');
            const hiddenInput = container.querySelector('.date-picker-input');
            const label = container.querySelector('.date-picker-label');
            const btnPrev = container.querySelector('.date-picker-prev');
            const btnNext = container.querySelector('.date-picker-next');
            const btnToday = container.querySelector('.date-picker-today');
            const btnClear = container.querySelector('.date-picker-clear');
            const daysGrid = container.querySelector('.date-picker-days');
            const monthYearLabel = container.querySelector('.date-picker-month-year');

            container.dataset.pickerInit = 'true';

            // Estado interno del picker
            let viewDate = new Date(); // Fecha que se está viendo en el calendario
            if (hiddenInput.value) {
                const parts = hiddenInput.value.split('-');
                if (parts.length === 3) viewDate = new Date(parts[0], parts[1] - 1, parts[2]);
            }

            const renderCalendar = () => {
                const year = viewDate.getFullYear();
                const month = viewDate.getMonth();
                monthYearLabel.textContent = `${this.months[month]} ${year}`;

                // Primer día del mes y total de días
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const today = new Date();

                daysGrid.innerHTML = '';

                // Espacios en blanco para el inicio de semana
                for (let i = 0; i < firstDay; i++) {
                    daysGrid.insertAdjacentHTML('beforeend', '<div></div>');
                }

                // Días del mes
                for (let day = 1; day <= daysInMonth; day++) {
                    const dateStr = `${year}-${(month + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                    const isSelected = hiddenInput.value === dateStr;
                    const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === day;

                    const dayBtn = document.createElement('button');
                    dayBtn.type = 'button';
                    dayBtn.className = `h-8 w-8 text-[11px] font-bold rounded-sm flex items-center justify-center transition-all ${
                        isSelected 
                        ? 'bg-primary dark:bg-accent text-white dark:text-gray-900 shadow-md' 
                        : isToday 
                            ? 'text-primary dark:text-accent bg-primary/10 dark:bg-accent/10 border border-primary/20' 
                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10'
                    }`;
                    dayBtn.textContent = day;
                    dayBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        this.selectDate(container, dateStr);
                        this.closeAll();
                    });
                    daysGrid.appendChild(dayBtn);
                }
            };

            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (trigger.disabled) return;
                const isOpen = !dropdown.classList.contains('hidden');
                this.closeAll();
                if (!isOpen) {
                    dropdown.classList.remove('hidden');
                    renderCalendar();
                }
            });

            btnPrev.addEventListener('click', (e) => {
                e.stopPropagation();
                viewDate.setMonth(viewDate.getMonth() - 1);
                renderCalendar();
            });

            btnNext.addEventListener('click', (e) => {
                e.stopPropagation();
                viewDate.setMonth(viewDate.getMonth() + 1);
                renderCalendar();
            });

            btnToday.addEventListener('click', (e) => {
                e.stopPropagation();
                const now = new Date();
                const dateStr = `${now.getFullYear()}-${(now.getMonth() + 1).toString().padStart(2, '0')}-${now.getDate().toString().padStart(2, '0')}`;
                this.selectDate(container, dateStr);
                this.closeAll();
            });

            btnClear.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectDate(container, '');
                this.closeAll();
            });

            // Sync from external changes
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                        this.updateDisplay(container);
                    }
                });
            });
            observer.observe(hiddenInput, { attributes: true });

            if (hiddenInput.value) this.updateDisplay(container);
        },

        selectDate(container, dateStr) {
            const hiddenInput = container.querySelector('.date-picker-input');
            hiddenInput.value = dateStr;
            this.updateDisplay(container);
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        },

        updateDisplay(container) {
            const hiddenInput = container.querySelector('.date-picker-input');
            const label = container.querySelector('.date-picker-label');
            const value = hiddenInput.value;
            
            if (value) {
                label.textContent = value;
                label.classList.remove('text-gray-400', 'dark:text-gray-500', 'font-medium');
            } else {
                const placeholder = container.querySelector('.date-picker-trigger').dataset.placeholder || 'Seleccionar fecha...';
                label.textContent = placeholder;
                label.classList.add('text-gray-400', 'dark:text-gray-500', 'font-medium');
            }
        },

        closeAll() {
            document.querySelectorAll('.date-picker-dropdown').forEach(d => d.classList.add('hidden'));
        },

        handleOutsideClick(e) {
            if (!e.target.closest('.date-picker-container')) {
                this.closeAll();
            }
        }
    };

    window.setDatePickerValue = function(id, value) {
        const input = document.getElementById(id);
        if (input && input.classList.contains('date-picker-input')) {
            const container = input.closest('.date-picker-container');
            if (container) DatePicker.selectDate(container, value);
        }
    };

    window.DatePicker = DatePicker;
    DatePicker.init();
})();
