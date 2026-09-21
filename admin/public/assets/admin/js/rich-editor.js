(function (window, document) {
    'use strict';

    var counter = 0;
    var config = window.AdminRichEditorConfig || {};

    function normalizeUrl(url) {
        url = (url || '').trim();
        if (!url) {
            return '';
        }
        if (/^(https?:|mailto:|tel:|\/|#)/i.test(url)) {
            return url;
        }
        return 'https://' + url;
    }

    function createButton(options) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'admin-rich-editor__button';
        button.title = options.title;
        button.setAttribute('aria-label', options.title);
        button.innerHTML = options.html;
        return button;
    }

    function createSeparator() {
        var separator = document.createElement('span');
        separator.className = 'admin-rich-editor__separator';
        return separator;
    }

    function insertHtml(editable, html) {
        editable.focus();
        if (document.queryCommandSupported && document.queryCommandSupported('insertHTML')) {
            document.execCommand('insertHTML', false, html);
            return;
        }
        var selection = window.getSelection();
        if (!selection || !selection.rangeCount) {
            editable.insertAdjacentHTML('beforeend', html);
            return;
        }
        var range = selection.getRangeAt(0);
        range.deleteContents();
        var fragment = range.createContextualFragment(html);
        range.insertNode(fragment);
    }

    function extractUploadUrl(responseText) {
        var data;
        try {
            data = JSON.parse(responseText);
        } catch (error) {
            data = null;
        }
        if (data) {
            return data.url || data.location || (data.file && data.file.url) || '';
        }
        var match = responseText.match(/['"]url['"]\s*:\s*['"]([^'"]+)['"]/i);
        return match ? match[1] : '';
    }

    function initEditor(textarea) {
        if (!textarea || textarea.dataset.richEditorInitialized === '1') {
            return;
        }

        textarea.dataset.richEditorInitialized = '1';
        if (!textarea.id) {
            counter += 1;
            textarea.id = 'admin-rich-editor-' + counter;
        }

        var wrapper = document.createElement('div');
        wrapper.className = 'admin-rich-editor';
        var rowCount = Number(textarea.getAttribute('rows') || 0);
        if (rowCount > 0 && rowCount <= 5) {
            wrapper.className += ' is-compact';
        }

        var toolbar = document.createElement('div');
        toolbar.className = 'admin-rich-editor__toolbar';

        var editable = document.createElement('div');
        editable.className = 'admin-rich-editor__area';
        editable.contentEditable = textarea.disabled || textarea.readOnly ? 'false' : 'true';
        editable.innerHTML = textarea.value || '';

        var source = document.createElement('textarea');
        source.className = 'admin-rich-editor__source';
        source.value = textarea.value || '';
        source.disabled = textarea.disabled;
        source.readOnly = textarea.readOnly;

        function syncFromEditable() {
            textarea.value = editable.innerHTML.trim();
            source.value = textarea.value;
        }

        function syncFromSource() {
            textarea.value = source.value;
            editable.innerHTML = source.value;
        }

        function run(command, value) {
            editable.focus();
            document.execCommand(command, false, value || null);
            syncFromEditable();
        }

        function addCommandButton(options) {
            var button = createButton(options);
            button.addEventListener('click', function () {
                if (wrapper.classList.contains('is-source')) {
                    wrapper.classList.remove('is-source');
                    syncFromSource();
                }
                run(options.command, options.value);
            });
            toolbar.appendChild(button);
        }

        function addActionButton(options, callback) {
            var button = createButton(options);
            button.addEventListener('click', callback);
            toolbar.appendChild(button);
            return button;
        }

        addCommandButton({ title: 'Paragraph', html: '<strong>P</strong>', command: 'formatBlock', value: 'p' });
        addCommandButton({ title: 'Heading 2', html: '<strong>H2</strong>', command: 'formatBlock', value: 'h2' });
        addCommandButton({ title: 'Heading 3', html: '<strong>H3</strong>', command: 'formatBlock', value: 'h3' });
        toolbar.appendChild(createSeparator());
        addCommandButton({ title: 'Bold', html: '<i class="fas fa-bold"></i>', command: 'bold' });
        addCommandButton({ title: 'Italic', html: '<i class="fas fa-italic"></i>', command: 'italic' });
        addCommandButton({ title: 'Underline', html: '<i class="fas fa-underline"></i>', command: 'underline' });
        addCommandButton({ title: 'Strikethrough', html: '<i class="fas fa-strikethrough"></i>', command: 'strikeThrough' });
        toolbar.appendChild(createSeparator());
        addCommandButton({ title: 'Bulleted list', html: '<i class="fas fa-list-ul"></i>', command: 'insertUnorderedList' });
        addCommandButton({ title: 'Numbered list', html: '<i class="fas fa-list-ol"></i>', command: 'insertOrderedList' });
        toolbar.appendChild(createSeparator());
        addCommandButton({ title: 'Align left', html: '<i class="fas fa-align-left"></i>', command: 'justifyLeft' });
        addCommandButton({ title: 'Align center', html: '<i class="fas fa-align-center"></i>', command: 'justifyCenter' });
        addCommandButton({ title: 'Align right', html: '<i class="fas fa-align-right"></i>', command: 'justifyRight' });
        toolbar.appendChild(createSeparator());

        addActionButton({ title: 'Add link', html: '<i class="fas fa-link"></i>' }, function () {
            var url = normalizeUrl(window.prompt('Enter URL'));
            if (!url) {
                return;
            }
            run('createLink', url);
        });

        addActionButton({ title: 'Insert image URL', html: '<i class="fas fa-image"></i>' }, function () {
            var url = normalizeUrl(window.prompt('Enter image URL'));
            if (!url) {
                return;
            }
            insertHtml(editable, '<img src="' + url.replace(/"/g, '&quot;') + '" alt="">');
            syncFromEditable();
        });

        if (config.uploadUrl) {
            var fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            fileInput.style.display = 'none';
            wrapper.appendChild(fileInput);

            addActionButton({ title: 'Upload image', html: '<i class="fas fa-upload"></i>' }, function () {
                fileInput.value = '';
                fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                if (!fileInput.files || !fileInput.files[0]) {
                    return;
                }

                var body = new FormData();
                body.append('upload', fileInput.files[0]);
                if (config.csrfToken) {
                    body.append('_token', config.csrfToken);
                }

                fetch(config.uploadUrl, {
                    method: 'POST',
                    headers: config.csrfToken ? { 'X-CSRF-TOKEN': config.csrfToken, 'Accept': 'application/json' } : { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    body: body
                })
                    .then(function (response) {
                        return response.text().then(function (text) {
                            if (!response.ok) {
                                throw new Error(text || 'Upload failed');
                            }
                            return text;
                        });
                    })
                    .then(function (text) {
                        var url = extractUploadUrl(text);
                        if (!url) {
                            throw new Error('Upload response did not include an image URL.');
                        }
                        insertHtml(editable, '<img src="' + url.replace(/"/g, '&quot;') + '" alt="">');
                        syncFromEditable();
                    })
                    .catch(function (error) {
                        window.alert(error.message || 'Unable to upload image.');
                    });
            });
        }

        addActionButton({ title: 'Insert table', html: '<i class="fas fa-table"></i>' }, function () {
            var rows = Math.max(1, Math.min(10, parseInt(window.prompt('Rows', '2'), 10) || 2));
            var columns = Math.max(1, Math.min(8, parseInt(window.prompt('Columns', '2'), 10) || 2));
            var html = '<table><tbody>';
            for (var row = 0; row < rows; row += 1) {
                html += '<tr>';
                for (var column = 0; column < columns; column += 1) {
                    html += '<td>&nbsp;</td>';
                }
                html += '</tr>';
            }
            html += '</tbody></table>';
            insertHtml(editable, html);
            syncFromEditable();
        });

        addCommandButton({ title: 'Quote', html: '<i class="fas fa-quote-right"></i>', command: 'formatBlock', value: 'blockquote' });
        addCommandButton({ title: 'Horizontal line', html: '<i class="fas fa-minus"></i>', command: 'insertHorizontalRule' });
        addCommandButton({ title: 'Remove formatting', html: '<i class="fas fa-eraser"></i>', command: 'removeFormat' });

        var sourceButton = addActionButton({ title: 'HTML source', html: '<i class="fas fa-code"></i>' }, function () {
            if (wrapper.classList.contains('is-source')) {
                wrapper.classList.remove('is-source');
                sourceButton.classList.remove('is-active');
                syncFromSource();
            } else {
                syncFromEditable();
                wrapper.classList.add('is-source');
                sourceButton.classList.add('is-active');
                source.focus();
            }
        });

        if (textarea.disabled || textarea.readOnly) {
            toolbar.querySelectorAll('button').forEach(function (button) {
                button.disabled = true;
            });
        }

        editable.addEventListener('input', syncFromEditable);
        editable.addEventListener('blur', syncFromEditable);
        source.addEventListener('input', syncFromSource);
        source.addEventListener('blur', syncFromSource);

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrapper, textarea.nextSibling);
        wrapper.appendChild(toolbar);
        wrapper.appendChild(editable);
        wrapper.appendChild(source);

        if (textarea.form && textarea.form.dataset.richEditorSubmitBound !== '1') {
            textarea.form.dataset.richEditorSubmitBound = '1';
            textarea.form.addEventListener('submit', function () {
                document.querySelectorAll('.editor[data-rich-editor-initialized="1"]').forEach(function (field) {
                    var richEditor = field.nextElementSibling;
                    if (!richEditor || !richEditor.classList.contains('admin-rich-editor')) {
                        return;
                    }
                    var area = richEditor.querySelector('.admin-rich-editor__area');
                    var sourceField = richEditor.querySelector('.admin-rich-editor__source');
                    field.value = richEditor.classList.contains('is-source') ? sourceField.value : area.innerHTML.trim();
                });
            });
        }
    }

    window.AdminRichEditor = {
        initAll: function (selector) {
            document.querySelectorAll(selector || '.editor').forEach(initEditor);
        }
    };
})(window, document);
