function initializeSearchSuggestions() {
    document.querySelectorAll('form[data-search-suggestions]').forEach((form, formIndex) => {
        const input = form.querySelector('input[name="search"]');
        const menu = form.querySelector('[data-suggestions-menu]');
        const productIdInput = form.querySelector('input[name="suggested_product_id"]');
        const categorySelect = form.querySelector('select[name="category_id"]');
        if (!input || !menu) return;

        menu.id = `search-suggestions-${formIndex}`;
        menu.setAttribute('role', 'listbox');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-controls', menu.id);
        input.setAttribute('aria-expanded', 'false');

        let debounceTimer;
        let requestNumber = 0;
        let activeRequest;

        const closeMenu = () => {
            menu.classList.add('hidden');
            input.setAttribute('aria-expanded', 'false');
        };

        const chooseSuggestion = (suggestion) => {
            input.value = suggestion.value;
            if (productIdInput) productIdInput.value = suggestion.product_id || '';

            if (suggestion.category_id && categorySelect) {
                categorySelect.value = suggestion.category_id;
                input.value = '';
                if (productIdInput) productIdInput.value = '';
            } else if (suggestion.product_id && categorySelect) {
                categorySelect.value = '';
            }

            closeMenu();
            form.requestSubmit();
        };

        const renderSuggestions = (suggestions) => {
            menu.replaceChildren();
            const typedQuery = input.value.trim();
            const choices = typedQuery
                ? [{ kind: 'Search', label: `Search for “${typedQuery}”`, detail: 'Use exactly what you typed', value: typedQuery }, ...suggestions]
                : suggestions;

            choices.forEach((suggestion, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex w-full items-start justify-between gap-3 border-b border-gray-100 px-3 py-2 text-left hover:bg-blue-50 focus:bg-blue-50 focus:outline-none';
                button.setAttribute('role', 'option');

                const label = document.createElement('span');
                label.className = 'text-sm font-medium text-gray-800';
                label.textContent = suggestion.label;

                const detail = document.createElement('span');
                detail.className = 'ml-auto shrink-0 text-right text-xs text-gray-500';
                detail.textContent = suggestion.detail || suggestion.kind || '';

                button.append(label, detail);
                button.addEventListener('click', () => chooseSuggestion(suggestion));
                button.addEventListener('keydown', (event) => {
                    const options = [...menu.querySelectorAll('button[role="option"]')];
                    const next = event.key === 'ArrowDown' ? options[index + 1]
                        : event.key === 'ArrowUp' ? options[index - 1]
                            : null;
                    if (next) {
                        event.preventDefault();
                        next.focus();
                    } else if (event.key === 'ArrowUp' && index === 0) {
                        event.preventDefault();
                        input.focus();
                    }
                });
                menu.appendChild(button);
            });

            menu.classList.remove('hidden');
            input.setAttribute('aria-expanded', 'true');
        };

        const loadSuggestions = async () => {
            const query = input.value.trim();
            if (!query) {
                closeMenu();
                return;
            }

            const currentRequest = ++requestNumber;
            activeRequest?.abort();
            activeRequest = new AbortController();
            const url = new URL(form.dataset.suggestionsUrl, window.location.origin);
            url.searchParams.set('q', query);

            try {
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    signal: activeRequest.signal,
                });
                if (!response.ok) throw new Error('Suggestions could not be loaded.');
                const suggestions = await response.json();
                if (currentRequest === requestNumber) renderSuggestions(suggestions);
            } catch (error) {
                if (error.name !== 'AbortError' && currentRequest === requestNumber) closeMenu();
            }
        };

        input.addEventListener('input', () => {
            if (productIdInput) productIdInput.value = '';
            requestNumber++;
            activeRequest?.abort();
            clearTimeout(debounceTimer);
            if (!input.value.trim()) {
                closeMenu();
                return;
            }
            debounceTimer = setTimeout(loadSuggestions, 180);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' && !menu.classList.contains('hidden')) {
                const firstOption = menu.querySelector('button[role="option"]');
                if (firstOption) {
                    event.preventDefault();
                    firstOption.focus();
                }
            } else if (event.key === 'Escape') {
                closeMenu();
            }
        });

        input.addEventListener('focus', () => {
            if (input.value.trim()) loadSuggestions();
        });
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('form[data-search-suggestions]').forEach((form) => {
            if (!form.contains(event.target)) {
                const input = form.querySelector('input[name="search"]');
                const menu = form.querySelector('[data-suggestions-menu]');
                menu?.classList.add('hidden');
                input?.setAttribute('aria-expanded', 'false');
            }
        });
    });
}

initializeSearchSuggestions();
