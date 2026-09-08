/**
 * Poll unread notification count and optional live dashboard refresh hooks.
 */
(function () {
    'use strict';
    function rootUrl() {
        var r = (typeof window.CMS_ROOT === 'string' && window.CMS_ROOT) ? window.CMS_ROOT : '';
        if (r) { return r; }
        try {
            var loc = window.location;
            return loc.origin + (loc.pathname || '').replace(/\/[^/]+\/?$/, '').replace(/\/$/, '');
        } catch (e) {
            return '';
        }
    }
    function pollUnread() {
        var el = document.getElementById('nav-unread-count');
        if (!el) return;
        var root = rootUrl();
        var u = (root && root.length ? (root + '/api/notifications/unread') : '/api/notifications/unread');
        fetch(u, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (typeof d.count !== 'number') return;
                if (d.count > 0) {
                    el.textContent = d.count;
                    el.classList.remove('d-none');
                } else {
                    el.textContent = '';
                    el.classList.add('d-none');
                }
            })
            .catch(function () { /* ignore */ });
    }
    if (document.getElementById('nav-unread-count')) {
        pollUnread();
        setInterval(pollUnread, (window.CMS_POLL_SEC || 15) * 1000);
    }
})();
