(function () {
    var form = document.getElementById('menuSearchForm');
    var input = document.getElementById('menuSearch');
    var results = document.getElementById('menuResults');
    var status = document.getElementById('menuSearchStatus');
    var clearLink = document.getElementById('clearMenuSearch');

    if (!form || !input || !results || !status || !clearLink) {
        return;
    }

    var timer;
    var controller;

    function addCell(row, text) {
        var cell = document.createElement('td');
        cell.textContent = text;
        row.appendChild(cell);
    }

    function renderItems(items) {
        var fragment = document.createDocumentFragment();

        if (items.length === 0) {
            var emptyRow = document.createElement('tr');
            var emptyCell = document.createElement('td');
            emptyCell.colSpan = 5;
            emptyCell.className = 'muted';
            emptyCell.textContent = 'No menu items found.';
            emptyRow.appendChild(emptyCell);
            fragment.appendChild(emptyRow);
        }

        items.forEach(function (item) {
            var row = document.createElement('tr');
            addCell(row, item.name);
            addCell(row, item.description);
            addCell(row, item.price + ' RWF');
            addCell(row, item.has_image ? 'Available' : 'None');

            var actionCell = document.createElement('td');
            var editLink = document.createElement('a');
            editLink.className = 'button';
            editLink.href = 'menu_edit.php?id=' + encodeURIComponent(item.id);
            editLink.textContent = 'Edit';
            actionCell.appendChild(editLink);
            row.appendChild(actionCell);
            fragment.appendChild(row);
        });

        results.replaceChildren(fragment);
    }

    async function searchMenu() {
        var query = input.value.trim();
        clearLink.hidden = query === '';
        if (controller) {
            controller.abort();
        }
        controller = new AbortController();
        status.textContent = 'Searching...';

        var url = new URL(window.location.href);
        if (query) {
            url.searchParams.set('q', query);
        } else {
            url.searchParams.delete('q');
        }
        window.history.replaceState({}, '', url);

        try {
            var response = await fetch('menu_search.php?q=' + encodeURIComponent(query), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal
            });
            if (!response.ok) {
                throw new Error('Search request failed with HTTP ' + response.status);
            }

            var data = await response.json();
            if (!Array.isArray(data.items) || typeof data.total !== 'number') {
                throw new Error('The search response was invalid.');
            }

            renderItems(data.items);
            status.textContent = data.total > data.items.length
                ? 'Showing ' + data.items.length + ' of ' + data.total + ' items. Refine your search to see fewer results.'
                : data.total + (data.total === 1 ? ' item found.' : ' items found.');
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            console.error(error);
            status.textContent = 'Search failed. Please try again.';
        }
    }

    input.addEventListener('input', function () {
        window.clearTimeout(timer);
        timer = window.setTimeout(searchMenu, 250);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        window.clearTimeout(timer);
        searchMenu();
    });

    clearLink.addEventListener('click', function (event) {
        event.preventDefault();
        input.value = '';
        window.clearTimeout(timer);
        searchMenu();
        input.focus();
    });
})();
