const action = arguments[0];
const input = arguments[1] || {};
const done = arguments[arguments.length - 1];

function componentName(element) {
    const named = element.getAttribute('wire:name');
    if (named) return named;
    try {
        return JSON.parse(element.getAttribute('wire:snapshot') || '{}').memo?.name || '';
    } catch {
        return '';
    }
}

function findComponent(pattern) {
    const element = [...document.querySelectorAll('[wire\\:id]')]
        .find(item => pattern.test(componentName(item)));
    return element && window.Livewire ? window.Livewire.find(element.getAttribute('wire:id')) : null;
}

function errorName(error) {
    const names = ['Error', 'TypeError', 'ReferenceError', 'SyntaxError', 'RangeError', 'URIError', 'EvalError'];
    return names.includes(error?.name) ? error.name : 'UnknownError';
}

function diagnosticUrl(value) {
    if (!value) return null;
    try {
        const url = new URL(value, location.href);
        const path = url.pathname.split('/').map(part => {
            let decoded;
            try { decoded = decodeURIComponent(part); } catch { return '[redactado]'; }
            return /@|[A-Za-z0-9_-]{32,}/.test(decoded) ? '[redactado]' : part;
        }).join('/');
        return url.origin + path;
    } catch {
        return null;
    }
}

function pageDiagnostics() {
    if (!window.__logisticaGeoDiagnostics) {
        const state = window.__logisticaGeoDiagnostics = {errors: []};
        window.addEventListener('error', event => {
            if (state.errors.length >= 10) return;
            const resource = event.target instanceof Element ? event.target : null;
            state.errors.push({
                kind: resource ? 'resource' : 'javascript',
                error_name: errorName(event.error),
                source: diagnosticUrl(event.filename || resource?.getAttribute('src') || resource?.getAttribute('href')),
                line: event.lineno || null,
            });
        }, true);
        window.addEventListener('unhandledrejection', event => {
            if (state.errors.length < 10) state.errors.push({kind: 'promise', error_name: errorName(event.reason)});
        });
    }
    const password = document.querySelector('input[type="password"]');
    const form = password?.closest('form');
    const email = form?.querySelector('input[type="email"], input[autocomplete="username"], input[name="email"], input[type="text"]');
    let title = document.title || '';
    for (const field of [email, password]) {
        if (field?.value) title = title.split(field.value).join('[redactado]');
    }
    title = title.replace(/[^\s<>]+@[^\s<>]+/g, '[correo]').replace(/[A-Za-z0-9_-]{32,}/g, '[redactado]').slice(0, 160);
    const elements = [...document.querySelectorAll('[wire\\:id]')];
    const names = elements.map(componentName);
    let ordersResolved = false;
    let ordersResolutionError = null;
    try { ordersResolved = Boolean(findComponent(/list[-_.]?orders$/i)); } catch (error) { ordersResolutionError = errorName(error); }
    const navigation = performance.getEntriesByType('navigation')[0];
    const ordersLinks = [...document.querySelectorAll('a[href]')].filter(link => {
        try {
            const url = new URL(link.getAttribute('href'), location.href);
            return url.origin === location.origin && url.pathname.replace(/\/+$/, '') === '/orders';
        } catch {
            return false;
        }
    });
    const binding = field => {
        const value = field && [...field.attributes].find(attribute => /^wire:model(?:\.|$)/.test(attribute.name))?.value;
        return typeof value === 'string' && /^[A-Za-z_.][A-Za-z0-9_.-]{0,100}$/.test(value) ? value : null;
    };
    return {
        url: diagnosticUrl(location.href),
        title,
        document_ready_state: document.readyState,
        navigation_http_status: Number(navigation?.responseStatus) || null,
        livewire_present: Boolean(window.Livewire),
        livewire_find_type: typeof window.Livewire?.find,
        livewire_script_present: [...document.scripts].some(script => /livewire/i.test(script.src)),
        component_count: elements.length,
        unnamed_component_count: names.filter(name => !name).length,
        component_names: [...new Set(names.filter(name => /^[A-Za-z0-9_.\\-]{1,180}$/.test(name)))].slice(0, 30),
        orders_name_found: names.some(name => /list[-_.]?orders$/i.test(name)),
        orders_component_resolved: ordersResolved,
        orders_resolution_error: ordersResolutionError,
        orders_link_present: ordersLinks.length > 0,
        orders_link_visible: ordersLinks.some(link => link.getClientRects().length > 0),
        login_form_present: Boolean(form),
        password_field_visible: Boolean(password?.getClientRects().length),
        email_field_found: Boolean(email),
        email_binding: binding(email),
        password_binding: binding(password),
        form_error_count: form?.querySelectorAll('[aria-invalid="true"], .fi-fo-field-wrp-error-message').length || 0,
        alert_count: document.querySelectorAll('[role="alert"]').length,
        tracking_cell_count: document.querySelectorAll('td.fi-ta-cell-tracking-number').length,
        empty_table_visible: Boolean(document.querySelector('.fi-ta-empty-state')),
        failed_script_resources: performance.getEntriesByType('resource')
            .filter(resource => resource.initiatorType === 'script' && resource.responseStatus >= 400)
            .slice(-10).map(resource => ({url: diagnosticUrl(resource.name), http_status: resource.responseStatus})),
        browser_errors: window.__logisticaGeoDiagnostics.errors,
    };
}

function collectLinks(text) {
    const state = window.__logisticaGeo;
    const decoder = document.createElement('textarea');
    const pattern = /\/filament\/exports\/(\d+)\/download\?[^"'\\\s<>]+/g;
    for (const match of text.matchAll(pattern)) {
        decoder.innerHTML = match[0].replace(/&amp;/g, '&').replace(/&amp;/g, '&');
        const link = decoder.value;
        // CSV y no Excel: Geo arma el Excel recién al descargarlo y, con decenas de miles de
        // paquetes, su servidor corta a los 60 s (504). El CSV lo envía por partes desde el inicio.
        if (/format=csv(?:&|$)/.test(link)) state.links[match[1]] = link;
    }
}

function findExportId(value) {
    if (!value || typeof value !== 'object') return null;
    for (const [key, child] of Object.entries(value)) {
        if (/^export_?id$/i.test(key) && /^\d+$/.test(String(child))) return String(child);
        const found = findExportId(child);
        if (found) return found;
    }
    return null;
}

function exportResponseErrors(component) {
    try {
        const snapshot = typeof component?.snapshot === 'string' ? JSON.parse(component.snapshot) : component?.snapshot;
        const errors = component?.errors || snapshot?.memo?.errors;
        return errors && typeof errors === 'object' ? Object.keys(errors).length : 0;
    } catch {
        return 0;
    }
}

function recognizeExportStart(components) {
    const effects = components.map(component => ({
        dispatches: component?.effects?.dispatches || [],
        returns: component?.effects?.returns || [],
    }));
    const decoder = document.createElement('textarea');
    decoder.innerHTML = JSON.stringify(effects).replace(/\\u([0-9a-f]{4})/gi, (_, code) => String.fromCharCode(parseInt(code, 16)));
    const text = decoder.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const rows = text.match(/procesaran\s+([\d.,]+)\s+filas/i);
    const state = window.__logisticaGeo;
    if (rows || /exportacion\s+iniciada/i.test(text)) state.started = true;
    if (rows) state.expectedRows = Number(rows[1].replace(/[^\d]/g, ''));
}

async function refreshNotifications() {
    const notifications = findComponent(/database[-_.]?notifications/i);
    if (notifications) {
        window.__logisticaGeo.notificationsLoaded = true;
        await notifications.$call('$refresh');
    }
    collectLinks(document.documentElement.innerHTML);
}

async function run() {
    if (action === 'diagnostics') {
        return pageDiagnostics();
    }

    if (action === 'ready') {
        return Boolean(window.Livewire && findComponent(/list[-_.]?orders$/i));
    }

    if (action === 'login') {
        const password = document.querySelector('input[type="password"]');
        const form = password?.closest('form');
        const email = form?.querySelector('input[type="email"], input[autocomplete="username"], input[name="email"], input[type="text"]');
        if (!form || !email || !password) return false;
        const element = form.closest('[wire\\:id]') || form.querySelector('[wire\\:id]');
        const component = element && window.Livewire ? window.Livewire.find(element.getAttribute('wire:id')) : null;
        for (const [field, value] of [[email, input.email], [password, input.password]]) {
            const binding = [...field.attributes].find(attribute => /^wire:model(?:\.|$)/.test(attribute.name));
            if (component && binding) await component.$set(binding.value, value, false);
            const setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
            setter.call(field, value);
            field.dispatchEvent(new Event('input', {bubbles: true}));
            field.dispatchEvent(new Event('change', {bubbles: true}));
        }
        setTimeout(() => form.requestSubmit(), 100);
        return true;
    }

    if (action === 'observe') {
        window.__logisticaGeo = {
            links: {}, notificationsLoaded: false, started: false, expectedRows: null, exportId: null,
            submitted: false, exportSubmitting: false, validationErrorCount: 0,
            responseObserved: false, responseHttpStatus: null, observerErrorCount: 0,
        };
        const originalFetch = window.fetch;
        window.fetch = async function (...args) {
            const exporting = window.__logisticaGeo.exportSubmitting;
            let ownIndex = -1;
            let bodyParsed = false;
            try {
                const rawBody = typeof args[1]?.body === 'string' ? args[1].body
                    : args[0] instanceof Request ? await args[0].clone().text() : null;
                if (rawBody !== null) {
                    const body = JSON.parse(rawBody);
                    bodyParsed = true;
                    ownIndex = (body.components || []).findIndex(component =>
                        (component.calls || []).some(call => call.method === 'callMountedAction')
                    );
                }
            } catch {
                if (exporting) window.__logisticaGeo.observerErrorCount++;
            }
            const ownRequest = exporting && (ownIndex !== -1 || !bodyParsed);
            const response = await originalFetch.apply(this, args);
            if (response.url.includes('/livewire')) {
                const state = window.__logisticaGeo;
                if (ownRequest) {
                    state.responseObserved = true;
                }
                if (exporting && ownIndex !== -1) {
                    state.responseHttpStatus = Math.max(state.responseHttpStatus || 0, response.status);
                }
                try {
                    const data = await response.clone().json();
                    const text = JSON.stringify(data);
                    collectLinks(text);
                    if (/DatabaseNotifications|database[-_.]notifications/i.test(text)) state.notificationsLoaded = true;
                    if (ownRequest) {
                        const components = Array.isArray(data.components) ? data.components : [data];
                        recognizeExportStart(components);
                        if (ownIndex !== -1) {
                            const ownResponse = components[ownIndex];
                            state.validationErrorCount = Math.max(state.validationErrorCount, exportResponseErrors(ownResponse));
                            state.exportId = findExportId(ownResponse?.effects) || state.exportId;
                        }
                    }
                } catch {
                    if (exporting) window.__logisticaGeo.observerErrorCount++;
                }
            }
            return response;
        };
        await refreshNotifications();
        return window.__logisticaGeo;
    }

    if (action === 'export') {
        const wire = findComponent(/list[-_.]?orders$/i);
        if (!wire) return false;
        const state = window.__logisticaGeo;
        state.started = false;
        state.submitted = false;
        state.expectedRows = null;
        state.exportId = null;
        state.validationErrorCount = 0;
        state.responseObserved = false;
        state.responseHttpStatus = null;
        state.observerErrorCount = 0;
        await wire.$call('mountAction', 'export-packages');
        await wire.$set('mountedActions.0.data.created_at_from', input.from + ' 00:00:00');
        await wire.$set('mountedActions.0.data.created_at_to', input.to + ' 00:00:00');
        const exportField = [...document.querySelectorAll('input, select, textarea')].find(field =>
            [...field.attributes].some(attribute => /created_at_from/.test(attribute.value))
        );
        const exportForm = exportField?.closest('form, [role="dialog"], .fi-modal-window');
        state.exportSubmitting = true;
        try {
            await wire.$call('callMountedAction');
            state.submitted = true;
        } finally {
            state.exportSubmitting = false;
        }
        const visibleErrors = [...(exportForm?.querySelectorAll('[aria-invalid="true"], .fi-fo-field-wrp-error-message') || [])]
            .filter(element => element.getClientRects().length > 0).length;
        state.validationErrorCount = Math.max(state.validationErrorCount, visibleErrors);
        return state;
    }

    if (action === 'poll') {
        await refreshNotifications();
        return window.__logisticaGeo;
    }

    return null;
}

run().then(value => done({ok: true, value})).catch(error => done({ok: false, error_name: errorName(error)}));
