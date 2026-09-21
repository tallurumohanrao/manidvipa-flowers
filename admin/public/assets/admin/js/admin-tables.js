(function (window, document) {
    'use strict';

    function cellText(cell) {
        if (!cell) return '';
        var input = cell.querySelector('input:not([type="checkbox"]), select, textarea');
        var value = input && input.value ? input.value : cell.textContent;
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function compareValues(a, b) {
        var left = String(a || '').trim();
        var right = String(b || '').trim();
        var leftNumber = left.replace(/[^0-9.-]/g, '');
        var rightNumber = right.replace(/[^0-9.-]/g, '');
        if (leftNumber && rightNumber && /^-?\d+(\.\d+)?$/.test(leftNumber) && /^-?\d+(\.\d+)?$/.test(rightNumber)) {
            return Number(leftNumber) - Number(rightNumber);
        }

        var leftDate = Date.parse(left);
        var rightDate = Date.parse(right);
        if (!Number.isNaN(leftDate) && !Number.isNaN(rightDate)) return leftDate - rightDate;
        return left.localeCompare(right, undefined, { numeric: true, sensitivity: 'base' });
    }

    function renderPagination(root, pages, current, onChange) {
        var nav = root.querySelector('.admin-table-client-pagination');
        if (!nav) {
            nav = document.createElement('nav');
            nav.className = 'admin-table-client-pagination';
            nav.setAttribute('aria-label', 'Table pages');
            root.appendChild(nav);
        }
        nav.innerHTML = '';
        if (pages <= 1) return;

        var makeButton = function (label, page, disabled, active) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm ' + (active ? 'btn-primary' : 'btn-outline-primary');
            button.textContent = label;
            button.disabled = disabled;
            if (active) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', function () { onChange(page); });
            nav.appendChild(button);
        };

        makeButton('Previous', current - 1, current <= 1, false);
        for (var page = 1; page <= pages; page += 1) makeButton(String(page), page, false, page === current);
        makeButton('Next', current + 1, current >= pages, false);
    }

    function initTable(table) {
        if (table.dataset.adminTableInitialized === 'true' || table.dataset.adminTable === 'off') return;
        if (table.closest('[data-admin-table="off"]') || table.closest('.voice-preview-table-wrap')) return;

        var body = table.tBodies && table.tBodies[0];
        var header = table.tHead && table.tHead.rows[0];
        if (!body || !header || !body.rows.length || !header.cells.length) return;

        table.dataset.adminTableInitialized = 'true';
        var rows = Array.prototype.slice.call(body.rows);
        var dragTable = table.dataset.adminTable === 'drag';
        var root = table.closest('.card-body') || table.parentElement;
        var hasServerPagination = !!(root && root.querySelector('.pagination'));
        var queryParams = new URLSearchParams(window.location.search);
        var initialQuery = queryParams.get('q') || '';
        var state = { query: initialQuery.toLowerCase().trim(), page: 1, pageSize: 10, sortIndex: null, direction: 'asc' };

        var toolbar = document.createElement('div');
        toolbar.className = 'admin-table-toolbar d-flex flex-wrap align-items-center justify-content-between';
        var options = document.createElement('div');
        options.className = 'admin-table-options d-flex align-items-center';
        var existingPageSize = root.querySelector('select[name="per_page"], select[name="perPage"]');
        if (!existingPageSize) {
            var pageLabel = document.createElement('label');
            pageLabel.className = 'mb-0 mr-2 text-muted small';
            pageLabel.textContent = 'Rows';
            var pageSize = document.createElement('select');
            pageSize.className = 'custom-select custom-select-sm';
            pageSize.setAttribute('aria-label', 'Rows per page');
            [10, 25, 50, 100].forEach(function (size) {
                var option = document.createElement('option');
                option.value = String(size);
                option.textContent = String(size);
                if (size === state.pageSize) option.selected = true;
                pageSize.appendChild(option);
            });
            pageSize.addEventListener('change', function () {
                state.pageSize = Number(pageSize.value) || 25;
                state.page = 1;
                if (hasServerPagination) {
                    var url = new URL(window.location.href);
                    var pageParameter = url.searchParams.has('perPage') ? 'perPage' : 'per_page';
                    url.searchParams.set(pageParameter, String(state.pageSize));
                    url.searchParams.delete('page');
                    window.location.assign(url.toString());
                } else {
                    render();
                }
            });
            options.appendChild(pageLabel);
            options.appendChild(pageSize);
        }

        var searchGroup = document.createElement('div');
        searchGroup.className = 'admin-table-search input-group input-group-sm';
        var search = document.createElement('input');
        search.type = 'search';
        search.className = 'form-control';
        search.placeholder = hasServerPagination ? 'Search this page' : 'Search records';
        search.value = initialQuery;
        search.setAttribute('aria-label', search.placeholder);
        searchGroup.appendChild(search);

        toolbar.appendChild(options);
        toolbar.appendChild(searchGroup);
        var tableWrap = table.closest('.table-responsive') || table;
        tableWrap.parentNode.insertBefore(toolbar, tableWrap);
        var footer = document.createElement('div');
        footer.className = 'admin-table-footer';
        tableWrap.parentNode.insertBefore(footer, tableWrap.nextSibling);

        var headers = Array.prototype.slice.call(header.cells);
        headers.forEach(function (cell, index) {
            if (dragTable) return;
            var label = cellText(cell).toLowerCase();
            if (index === 0 && (cell.querySelector('input[type="checkbox"]') || !label)) return;
            if (index === headers.length - 1 && /action|manage|operation/.test(label)) return;
            if (/^select$|^#?$/.test(label)) return;
            cell.classList.add('admin-table-sortable');
            cell.setAttribute('tabindex', '0');
            cell.setAttribute('role', 'button');
            cell.setAttribute('title', 'Sort by ' + cellText(cell));
            var sort = function () {
                if (state.sortIndex === index) state.direction = state.direction === 'asc' ? 'desc' : 'asc';
                else { state.sortIndex = index; state.direction = 'asc'; }
                state.page = 1;
                render();
            };
            cell.addEventListener('click', sort);
            cell.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); sort(); }
            });
        });

        search.addEventListener('input', function () {
            state.query = search.value.toLowerCase().trim();
            state.page = 1;
            render();
        });

        function render() {
            var filtered = rows.filter(function (row) {
                return !state.query || row.textContent.replace(/\s+/g, ' ').toLowerCase().indexOf(state.query) !== -1;
            });
            if (state.sortIndex !== null) {
                filtered.sort(function (rowA, rowB) {
                    var result = compareValues(cellText(rowA.cells[state.sortIndex]), cellText(rowB.cells[state.sortIndex]));
                    return state.direction === 'asc' ? result : -result;
                });
            }

            var visible = filtered;
            var pages = 1;
            if (!hasServerPagination) {
                pages = Math.max(1, Math.ceil(filtered.length / state.pageSize));
                if (state.page > pages) state.page = pages;
                var start = (state.page - 1) * state.pageSize;
                visible = filtered.slice(start, start + state.pageSize);
            }
            rows.forEach(function (row) { row.style.display = visible.indexOf(row) !== -1 ? '' : 'none'; });
            headers.forEach(function (cell) { cell.removeAttribute('aria-sort'); });
            if (state.sortIndex !== null && headers[state.sortIndex]) {
                headers[state.sortIndex].setAttribute('aria-sort', state.direction === 'asc' ? 'ascending' : 'descending');
            }
            if (!hasServerPagination) renderPagination(footer, pages, state.page, function (page) { state.page = page; render(); });
        }

        render();
    }

    function init() { document.querySelectorAll('table.table').forEach(initTable); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})(window, document);
