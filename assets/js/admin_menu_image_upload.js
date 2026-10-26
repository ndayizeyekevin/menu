(function () {
    var form = document.getElementById('imageUploadForm');
    if (!form) return;

    var search = document.getElementById('menuSearch');
    var searchHelp = document.getElementById('menuSearchHelp');
    var selectedMenuId = document.getElementById('menu_id');
    var resultsList = document.getElementById('menuSearchResults');
    var fileInput = document.getElementById('menu_image');
    var currentPreview = document.getElementById('currentImagePreview');
    var currentEmpty = document.getElementById('currentImageEmpty');
    var currentCaption = document.getElementById('currentImageCaption');
    var newPreview = document.getElementById('newImagePreview');
    var newEmpty = document.getElementById('newImageEmpty');
    var newCaption = document.getElementById('newImageCaption');
    var replacementNote = document.getElementById('replacementNote');
    var submitButton = document.getElementById('uploadSubmit');
    var validation = document.getElementById('uploadValidation');
    var spinner = document.getElementById('spinnerOverlay');
    var allOptions = Array.from(document.querySelectorAll('#menuItemData [data-menu-id]')).map(function (item) {
        return { value: item.dataset.menuId, label: item.dataset.menuName, image: item.dataset.image || '' };
    });
    var objectUrl = '';
    var activeResult = -1;

    function closeResults() {
        resultsList.hidden = true;
        resultsList.replaceChildren();
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');
        activeResult = -1;
    }

    function chooseItem(item) {
        search.value = item.label;
        selectedMenuId.value = item.value;
        search.setCustomValidity('');
        searchHelp.textContent = 'Selected: ' + item.label + '.';
        closeResults();
        updateSelectedItem();
    }

    function renderOptions(query) {
        var normalized = query.trim().toLocaleLowerCase();
        selectedMenuId.value = '';
        search.setCustomValidity('');
        updateSelectedItem();

        if (normalized === '') {
            searchHelp.textContent = 'Type a name and choose a matching menu item.';
            closeResults();
            return [];
        }

        var matches = allOptions.filter(function (item) {
            return item.label.toLocaleLowerCase().includes(normalized);
        }).slice(0, 10);
        var totalMatches = allOptions.filter(function (item) {
            return item.label.toLocaleLowerCase().includes(normalized);
        }).length;

        resultsList.replaceChildren();
        if (matches.length === 0) {
            var emptyResult = document.createElement('li');
            emptyResult.className = 'menu-search-empty';
            emptyResult.textContent = 'No menu item matches “' + query.trim() + '”.';
            emptyResult.setAttribute('role', 'option');
            resultsList.append(emptyResult);
            searchHelp.textContent = 'No matching menu items. Check the spelling or try another name.';
        } else {
            matches.forEach(function (item, index) {
                var result = document.createElement('li');
                result.id = 'menu-search-result-' + index;
                result.className = 'menu-search-option';
                result.setAttribute('role', 'option');
                result.setAttribute('aria-selected', 'false');
                result.textContent = item.label;
                result.addEventListener('pointerdown', function (event) {
                    event.preventDefault();
                    chooseItem(item);
                });
                resultsList.append(result);
            });
            searchHelp.textContent = totalMatches > matches.length
                ? 'Showing the first ' + matches.length + ' of ' + totalMatches + ' matches. Use the arrow keys or choose an item.'
                : matches.length + (matches.length === 1 ? ' matching menu item. Use the arrow keys or choose it.' : ' matching menu items. Use the arrow keys or choose one.');
        }

        resultsList.hidden = false;
        search.setAttribute('aria-expanded', 'true');
        return matches;
    }

    function setActiveResult(matches, index) {
        var options = resultsList.querySelectorAll('[role="option"]');
        options.forEach(function (option) {
            option.setAttribute('aria-selected', 'false');
        });
        if (index < 0 || index >= matches.length) return;
        activeResult = index;
        options[index].setAttribute('aria-selected', 'true');
        search.setAttribute('aria-activedescendant', options[index].id);
        options[index].scrollIntoView({ block: 'nearest' });
    }

    function updateSelectedItem() {
        var selected = allOptions.find(function (item) { return item.value === selectedMenuId.value; });
        currentPreview.hidden = true;
        currentPreview.removeAttribute('src');
        currentEmpty.hidden = false;
        currentCaption.textContent = '';
        replacementNote.hidden = true;
        submitButton.textContent = 'Upload image';

        if (!selected) return;

        currentCaption.textContent = selected.label;
        if (selected.image) {
            currentPreview.src = selected.image;
            currentPreview.hidden = false;
            currentEmpty.hidden = true;
            currentPreview.onerror = function () {
                currentPreview.hidden = true;
                currentEmpty.hidden = false;
                currentEmpty.textContent = 'The current image could not be loaded.';
            };
            currentPreview.onload = function () {
                currentPreview.hidden = false;
                currentEmpty.hidden = true;
            };
            replacementNote.textContent = 'Uploading will replace the current image for ' + selected.label + '.';
            replacementNote.hidden = false;
            submitButton.textContent = 'Replace image';
        } else {
            currentEmpty.textContent = 'This item does not have an image yet.';
        }
    }

    var visibleMatches = [];
    search.addEventListener('input', function () {
        selectedMenuId.value = '';
        visibleMatches = renderOptions(search.value);
    });
    search.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown' && !resultsList.hidden) {
            event.preventDefault();
            setActiveResult(visibleMatches, Math.min(activeResult + 1, visibleMatches.length - 1));
        } else if (event.key === 'ArrowUp' && !resultsList.hidden) {
            event.preventDefault();
            setActiveResult(visibleMatches, Math.max(activeResult - 1, 0));
        } else if (event.key === 'Enter' && !resultsList.hidden) {
            if (activeResult >= 0 && visibleMatches[activeResult]) {
                event.preventDefault();
                chooseItem(visibleMatches[activeResult]);
            } else if (visibleMatches.length === 1) {
                event.preventDefault();
                chooseItem(visibleMatches[0]);
            }
        } else if (event.key === 'Escape' && !resultsList.hidden) {
            closeResults();
        }
    });
    search.addEventListener('blur', function () {
        window.setTimeout(closeResults, 120);
    });

    fileInput.addEventListener('change', function () {
        validation.hidden = true;
        validation.textContent = '';
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = '';
        newPreview.hidden = true;
        newPreview.removeAttribute('src');
        newEmpty.hidden = false;
        newCaption.textContent = '';

        var file = fileInput.files && fileInput.files[0];
        if (!file) return;

        var allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            validation.textContent = 'Choose a JPG, PNG, GIF, or WEBP image.';
            validation.hidden = false;
            fileInput.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            validation.textContent = 'This image is larger than 5 MB. Choose a smaller file.';
            validation.hidden = false;
            fileInput.value = '';
            return;
        }

        objectUrl = URL.createObjectURL(file);
        newPreview.src = objectUrl;
        newPreview.hidden = false;
        newEmpty.hidden = true;
        newCaption.textContent = file.name + ' · ' + (file.size / (1024 * 1024)).toFixed(2) + ' MB';
    });

    form.addEventListener('submit', function (event) {
        if (!selectedMenuId.value) {
            event.preventDefault();
            search.setCustomValidity('Type a menu name and choose an existing item from the suggestions.');
            search.reportValidity();
            searchHelp.textContent = 'Choose an existing menu item from the suggestions before uploading.';
            return;
        }
        if (spinner) spinner.classList.add('active');
        submitButton.disabled = true;
        submitButton.textContent = 'Uploading...';
    });

    if (selectedMenuId.value) {
        var initialSelection = allOptions.find(function (item) { return item.value === selectedMenuId.value; });
        if (initialSelection) {
            searchHelp.textContent = 'Selected: ' + initialSelection.label + '.';
            updateSelectedItem();
        }
    }
})();
