document.addEventListener('DOMContentLoaded', () => {
    // Determine the current "section" of the page (first path segment)
    const getSection = (path) => {
        // / or /?... -> 'history'
        // /meters -> 'meters'
        // /login -> 'login' etc.
        const p = path.split('?')[0];
        if (p === '/' || p === '/history') return 'history';
        const parts = p.split('/').filter(Boolean);
        return parts[0] || 'history';
    };

    // SPA Link Handling
    const handleNavigation = (e) => {
        const link = e.target.closest('A');
        if (link && link.getAttribute('href')?.startsWith('/') && !link.hasAttribute('target')) {
            const href = link.getAttribute('href');
            if (href === '/logout') return;

            e.preventDefault();

            // Fix Bug #5: only allow partial update when navigating within the same section
            const currentSection = getSection(window.location.pathname);
            const targetSection = getSection(href);
            const allowPartial = currentSection === 'history' && targetSection === 'history';

            loadPage(href, allowPartial);
        }
    };

    // SPA Form Handling
    const handleFormSubmit = (e) => {
        const form = e.target;
        const action = form.getAttribute('action') || window.location.pathname;
        const method = (form.getAttribute('method') || 'GET').toUpperCase();

        if (action.startsWith('/') || action === '/' || action === '') {
            e.preventDefault();

            const formData = new FormData(form);
            const url = action || window.location.pathname;

            if (method === 'GET') {
                const params = new URLSearchParams(formData);
                const finalUrl = url.split('?')[0] + '?' + params.toString();
                const currentSection = getSection(window.location.pathname);
                const targetSection = getSection(url);
                const allowPartial = currentSection === 'history' && targetSection === 'history';
                loadPage(finalUrl, allowPartial);
            } else {
                submitFormAjax(url, method, formData);
            }
        }
    };

    // Re-execute <script> tags injected via innerHTML (browsers don't run them automatically)
    const executeScripts = (container) => {
        container.querySelectorAll('script').forEach(oldScript => {
            const newScript = document.createElement('script');
            [...oldScript.attributes].forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    };

    const loadPage = async (url, allowPartial = false) => {
        const contentArea = document.querySelector('#main-content');
        let resultsArea = document.querySelector('#history-results');

        if (!contentArea) return;

        try {
            console.log(`SPA: ${url} | partial=${allowPartial}`);

            const usePartial = allowPartial && !!resultsArea;
            const targetArea = usePartial ? resultsArea : contentArea;
            targetArea.style.opacity = '0.5';

            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const html = await response.text();

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newResults = doc.querySelector('#history-results');

            if (usePartial && newResults) {
                resultsArea.innerHTML = newResults.innerHTML;
                executeScripts(resultsArea);
            } else {
                contentArea.innerHTML = html;
                executeScripts(contentArea);
                window.scrollTo(0, 0);
            }

            window.history.pushState({}, '', url);
        } catch (err) {
            console.error('SPA: Failed to load page', err);
        } finally {
            if (contentArea) contentArea.style.opacity = '1';
            resultsArea = document.querySelector('#history-results');
            if (resultsArea) resultsArea.style.opacity = '1';
        }
    };

    const submitFormAjax = async (url, method, formData) => {
        const contentArea = document.querySelector('#main-content');
        try {
            if (contentArea) contentArea.style.opacity = '0.5';
            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            // If response is JSON (API call from meters modal), don't update DOM
            const contentType = response.headers.get('Content-Type') || '';
            if (contentType.includes('application/json')) {
                const json = await response.json();
                // Re-dispatch a custom event so page scripts can react
                document.dispatchEvent(new CustomEvent('spa:json-response', { detail: json }));
                return json;
            }

            if (response.redirected) {
                loadPage(response.url);
                return;
            }

            const html = await response.text();
            if (contentArea) {
                contentArea.innerHTML = html;
                executeScripts(contentArea);
            }
            window.history.pushState({}, '', url);
            window.scrollTo(0, 0);
        } catch (err) {
            console.error('SPA: Form submission failed', err);
        } finally {
            if (contentArea) contentArea.style.opacity = '1';
        }
    };

    document.body.addEventListener('click', handleNavigation);
    document.body.addEventListener('submit', handleFormSubmit);

    window.onpopstate = () => {
        loadPage(window.location.pathname + window.location.search);
    };

    // Expose loadPage globally so view-level scripts (meters/index.php) can call it
    window.loadPage = loadPage;

    // Toggle Inline Row
    window.toggleRow = (id) => {
        const detailRow = document.getElementById(`detail-${id}`);
        if (!detailRow) return;

        const isVisible = detailRow.style.display === 'table-row';
        if (!isVisible) {
            detailRow.style.display = 'table-row';
            const content = detailRow.querySelector('.detail-content');
            if (content) content.classList.add('fade-in');
        } else {
            detailRow.style.display = 'none';
        }
    };

    // Toggle All Details
    window.toggleAllDetails = () => {
        const details = document.querySelectorAll('.detail-row');
        const btn = document.getElementById('btn-toggle-all');
        let anyVisible = false;

        details.forEach(row => {
            if (row.style.display === 'table-row') anyVisible = true;
        });

        if (anyVisible) {
            details.forEach(row => row.style.display = 'none');
            if (btn) btn.textContent = 'Hiện tất cả chi tiết';
        } else {
            details.forEach(row => {
                row.style.display = 'table-row';
                const content = row.querySelector('.detail-content');
                if (content) content.classList.add('fade-in');
            });
            if (btn) btn.textContent = 'Ẩn tất cả chi tiết';
        }
    };
});
