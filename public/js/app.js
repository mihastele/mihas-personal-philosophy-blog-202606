(function() {
    'use strict';

    var searchToggle = document.querySelector('.nav-search-toggle');
    var searchBar = document.getElementById('searchBar');

    if (searchToggle && searchBar) {
        searchToggle.addEventListener('click', function(e) {
            e.preventDefault();
            var isVisible = searchBar.style.display !== 'none';
            searchBar.style.display = isVisible ? 'none' : 'block';
            if (!isVisible) {
                searchBar.querySelector('.search-input').focus();
            }
        });
    }

    var searchQuery = new URLSearchParams(window.location.search).get('search');
    if (searchQuery && searchBar) {
        searchBar.style.display = 'block';
    }

    window.switchEditorTab = function(tab, btn) {
        var tabs = document.querySelectorAll('.editor-tab');
        tabs.forEach(function(t) { t.classList.remove('active'); });
        btn.classList.add('active');

        var textarea = document.getElementById('content');
        var preview = document.getElementById('editorPreview');
        var toolbar = document.getElementById('editorToolbar');

        if (tab === 'preview') {
            textarea.style.display = 'none';
            if (toolbar) toolbar.style.display = 'none';
            preview.classList.add('active');
            preview.innerHTML = renderMarkdown(textarea.value);
        } else {
            textarea.style.display = 'block';
            if (toolbar) toolbar.style.display = 'flex';
            preview.classList.remove('active');
        }
    };

    window.insertMarkdown = function(type) {
        var textarea = document.getElementById('content');
        if (!textarea) return;

        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var text = textarea.value;
        var selected = text.substring(start, end);
        var insert = '';
        var cursorOffset = 0;

        switch (type) {
            case 'bold':
                insert = '**' + (selected || 'bold text') + '**';
                cursorOffset = selected ? insert.length : 2;
                break;
            case 'italic':
                insert = '*' + (selected || 'italic text') + '*';
                cursorOffset = selected ? insert.length : 1;
                break;
            case 'strike':
                insert = '~~' + (selected || 'strikethrough') + '~~';
                cursorOffset = selected ? insert.length : 2;
                break;
            case 'h2':
                insert = '\n## ' + (selected || 'Heading');
                cursorOffset = insert.length;
                break;
            case 'h3':
                insert = '\n### ' + (selected || 'Heading');
                cursorOffset = insert.length;
                break;
            case 'quote':
                insert = '\n> ' + (selected || 'Quote text');
                cursorOffset = insert.length;
                break;
            case 'link':
                insert = '[' + (selected || 'link text') + '](url)';
                cursorOffset = insert.length - 4;
                break;
            case 'ul':
                insert = '\n- ' + (selected || 'List item');
                cursorOffset = insert.length;
                break;
            case 'ol':
                insert = '\n1. ' + (selected || 'List item');
                cursorOffset = insert.length;
                break;
            case 'hr':
                insert = '\n\n---\n\n';
                cursorOffset = insert.length;
                break;
            case 'code':
                if (selected && selected.indexOf('\n') !== -1) {
                    insert = '\n```\n' + selected + '\n```\n';
                } else {
                    insert = '`' + (selected || 'code') + '`';
                }
                cursorOffset = selected ? insert.length : 1;
                break;
        }

        textarea.value = text.substring(0, start) + insert + text.substring(end);
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = start + cursorOffset;
    };

    window.openImageModal = function() {
        var modal = document.getElementById('imageModal');
        if (modal) modal.classList.add('active');
    };

    window.closeImageModal = function() {
        var modal = document.getElementById('imageModal');
        if (modal) modal.classList.remove('active');
        var input = document.getElementById('imageUploadInput');
        if (input) input.value = '';
        var status = document.getElementById('imageUploadStatus');
        if (status) status.innerHTML = '';
        var alt = document.getElementById('imageAltText');
        if (alt) alt.value = '';
    };

    window.insertImage = function() {
        var fileInput = document.getElementById('imageUploadInput');
        var altText = document.getElementById('imageAltText').value || 'image';
        var statusEl = document.getElementById('imageUploadStatus');

        if (!fileInput.files[0]) {
            statusEl.innerHTML = '<div class="alert alert-error">Please select an image.</div>';
            return;
        }

        var formData = new FormData();
        formData.append('image', fileInput.files[0]);
        formData.append('action', 'upload_image');
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        statusEl.innerHTML = '<p style="color: var(--color-text-muted);">Uploading...</p>';

        fetch('/admin/editor.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var textarea = document.getElementById('content');
                var pos = textarea.selectionStart;
                var text = textarea.value;
                var imgMd = '\n![' + altText + '](' + data.url + ')\n';
                textarea.value = text.substring(0, pos) + imgMd + text.substring(pos);
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = pos + imgMd.length;
                closeImageModal();
            } else {
                statusEl.innerHTML = '<div class="alert alert-error">' + (data.error || 'Upload failed.') + '</div>';
            }
        })
        .catch(function() {
            statusEl.innerHTML = '<div class="alert alert-error">Upload failed. Please try again.</div>';
        });
    };

    window.uploadCover = function(input) {
        if (!input.files[0]) return;

        var formData = new FormData();
        formData.append('image', input.files[0]);
        formData.append('action', 'upload_image');
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        fetch('/admin/editor.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                var area = document.getElementById('coverUploadArea');
                area.innerHTML = '<div class="cover-preview" id="coverPreview">' +
                    '<img src="' + data.url + '" alt="Cover">' +
                    '<button type="button" class="remove-cover" onclick="removeCover()">&times;</button>' +
                    '</div>' +
                    '<input type="hidden" name="cover_image" id="coverImageInput" value="' + data.filename + '">';
            } else {
                alert(data.error || 'Upload failed.');
            }
        })
        .catch(function() {
            alert('Upload failed. Please try again.');
        });
    };

    window.removeCover = function() {
        var area = document.getElementById('coverUploadArea');
        area.innerHTML = '<div class="cover-upload" id="coverUpload">' +
            '<p style="color: var(--color-text-muted); font-size: 0.85rem;">Click or drop to upload cover image</p>' +
            '<input type="file" accept="image/jpeg,image/png,image/gif,image/webp" onchange="uploadCover(this)">' +
            '</div>' +
            '<input type="hidden" name="cover_image" id="coverImageInput" value="">';
    };

    function renderMarkdown(md) {
        var html = md;

        html = html.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

        html = html.replace(/^### (.+)$/gm, '<h3>$1</h3>');
        html = html.replace(/^## (.+)$/gm, '<h2>$1</h2>');
        html = html.replace(/^# (.+)$/gm, '<h1>$1</h1>');

        html = html.replace(/^&gt; (.+)$/gm, '<blockquote>$1</blockquote>');

        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');
        html = html.replace(/~~(.+?)~~/g, '<del>$1</del>');
        html = html.replace(/`(.+?)`/g, '<code>$1</code>');

        html = html.replace(/!\[(.*?)\]\((.*?)\)/g, '<img src="$2" alt="$1">');
        html = html.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

        html = html.replace(/^---$/gm, '<hr>');

        html = html.replace(/```\n?([\s\S]*?)```/g, '<pre><code>$1</code></pre>');

        var lines = html.split('\n');
        var result = [];
        var inList = false;
        var listType = '';

        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];

            var ulMatch = line.match(/^- (.+)$/);
            var olMatch = line.match(/^\d+\. (.+)$/);

            if (ulMatch) {
                if (!inList || listType !== 'ul') {
                    if (inList) result.push('</' + listType + '>');
                    result.push('<ul>');
                    inList = true;
                    listType = 'ul';
                }
                result.push('<li>' + ulMatch[1] + '</li>');
            } else if (olMatch) {
                if (!inList || listType !== 'ol') {
                    if (inList) result.push('</' + listType + '>');
                    result.push('<ol>');
                    inList = true;
                    listType = 'ol';
                }
                result.push('<li>' + olMatch[1] + '</li>');
            } else {
                if (inList) {
                    result.push('</' + listType + '>');
                    inList = false;
                    listType = '';
                }
                if (line.trim() === '') {
                    result.push('');
                } else if (line.indexOf('<h') !== 0 && line.indexOf('<blockquote') !== 0 && line.indexOf('<hr') !== 0 && line.indexOf('<pre') !== 0 && line.indexOf('<img') !== 0) {
                    result.push('<p>' + line + '</p>');
                } else {
                    result.push(line);
                }
            }
        }
        if (inList) result.push('</' + listType + '>');

        return result.join('\n');
    }

    var imageModal = document.getElementById('imageModal');
    if (imageModal) {
        imageModal.addEventListener('click', function(e) {
            if (e.target === imageModal) {
                closeImageModal();
            }
        });
    }
})();
