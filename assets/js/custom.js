function showToast(message, type = 'success') {

    const toast = document.getElementById('commonToast');
    const header = document.getElementById('commonToastHeader');
    const title = document.getElementById('commonToastTitle');
    const body = document.getElementById('commonToastBody');
    const icon = document.getElementById('commonToastIcon');

    // Remove old classes
    header.classList.remove(
        'bg-success',
        'bg-danger',
        'bg-warning',
        'bg-info'
    );

    // Set toast based on type
    switch (type) {

        case 'success':
            header.classList.add('bg-success');
            title.textContent = 'Success';
            icon.setAttribute('data-lucide', 'check-circle');
            break;

        case 'error':
            header.classList.add('bg-danger');
            title.textContent = 'Error';
            icon.setAttribute('data-lucide', 'x-circle');
            break;

        case 'warning':
            header.classList.add('bg-warning');
            title.textContent = 'Warning';
            icon.setAttribute('data-lucide', 'alert-triangle');
            break;

        case 'info':
            header.classList.add('bg-info');
            title.textContent = 'Information';
            icon.setAttribute('data-lucide', 'info');
            break;
    }

    body.textContent = message;

    // Recreate Lucide icon
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Show Bootstrap Toast
    const bsToast = bootstrap.Toast.getOrCreateInstance(toast, {
        delay: 3500
    });

    bsToast.show();
}

/**
 * Live Refresh Follow-up Notifications in Header without page reload
 */
function refreshFollowupNotifications(callback) {
    const siteUrl = (typeof SITE_URL !== 'undefined') ? SITE_URL : '/';
    const ajaxUrl = siteUrl + 'include/notifications-ajax.php';

    if (typeof jQuery !== 'undefined') {
        jQuery.ajax({
            url: ajaxUrl,
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.status) {
                    const badge = document.getElementById('headerNotifBadge');
                    const pendingBadge = document.getElementById('headerNotifPendingBadge');
                    const listContainer = document.getElementById('headerNotifList');

                    if (badge) {
                        if (res.total_pending > 0) {
                            badge.textContent = res.badge_text || res.total_pending;
                            badge.classList.remove('d-none');
                        } else {
                            badge.textContent = '0';
                            badge.classList.add('d-none');
                        }
                    }

                    if (pendingBadge) {
                        pendingBadge.textContent = res.pending_label || (res.total_pending + ' Pending');
                    }

                    if (listContainer && res.html !== undefined) {
                        listContainer.innerHTML = res.html;
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }
                }
                if (typeof callback === 'function') {
                    callback(res);
                }
            },
            error: function () {
                if (typeof callback === 'function') {
                    callback(null);
                }
            }
        });
    } else {
        fetch(ajaxUrl)
            .then(response => response.json())
            .then(res => {
                if (res && res.status) {
                    const badge = document.getElementById('headerNotifBadge');
                    const pendingBadge = document.getElementById('headerNotifPendingBadge');
                    const listContainer = document.getElementById('headerNotifList');

                    if (badge) {
                        if (res.total_pending > 0) {
                            badge.textContent = res.badge_text || res.total_pending;
                            badge.classList.remove('d-none');
                        } else {
                            badge.textContent = '0';
                            badge.classList.add('d-none');
                        }
                    }

                    if (pendingBadge) {
                        pendingBadge.textContent = res.pending_label || (res.total_pending + ' Pending');
                    }

                    if (listContainer && res.html !== undefined) {
                        listContainer.innerHTML = res.html;
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }
                }
                if (typeof callback === 'function') {
                    callback(res);
                }
            })
            .catch(() => {
                if (typeof callback === 'function') {
                    callback(null);
                }
            });
    }
}

/**
 * Header Menu Search & Real-time Live Clock
 */
document.addEventListener('DOMContentLoaded', function () {
    // 1. Header Real-time Clock
    const clockEl = document.getElementById('headerLiveClock');
    const dateEl = document.getElementById('headerLiveDate');

    function updateHeaderTime() {
        const now = new Date();

        if (clockEl) {
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // 0 becomes 12
            const strHours = String(hours).padStart(2, '0');
            clockEl.textContent = `${strHours}:${minutes}:${seconds} ${ampm}`;
        }

        if (dateEl) {
            const day = String(now.getDate()).padStart(2, '0');
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            dateEl.textContent = `${day} ${month} ${year}`;
        }
    }

    if (clockEl || dateEl) {
        updateHeaderTime();
        setInterval(updateHeaderTime, 1000);
    }

    // 2. Header Menu Search (triggers after typing 3 or more characters)
    const searchInput = document.getElementById('headerMenuSearchInput');
    const searchDropdown = document.getElementById('headerSearchResults');
    const clearBtn = document.getElementById('headerSearchClearBtn');

    if (!searchInput || !searchDropdown) return;

    const menus = Array.isArray(window.HEADER_SEARCH_MENUS) ? window.HEADER_SEARCH_MENUS : [];

    function highlightMatch(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<mark class="p-0 bg-warning-subtle text-dark fw-bold">$1</mark>');
    }

    function renderSearchResults(query) {
        const trimmed = query.trim().toLowerCase();

        // Must be at least 3 characters
        if (trimmed.length < 3) {
            searchDropdown.style.display = 'none';
            searchDropdown.innerHTML = '';
            if (clearBtn) {
                clearBtn.style.display = trimmed.length > 0 ? 'flex' : 'none';
            }
            return;
        }

        if (clearBtn) clearBtn.style.display = 'flex';

        // Filter menus matching query in either name or parent section
        const matches = menus.filter(function (item) {
            const nameMatch = item.name && item.name.toLowerCase().includes(trimmed);
            const parentMatch = item.parent && item.parent.toLowerCase().includes(trimmed);
            return nameMatch || parentMatch;
        });

        if (matches.length === 0) {
            searchDropdown.innerHTML = `
                <div class="p-3 text-center text-muted">
                    <i data-lucide="search-x" class="fs-24 d-block mx-auto mb-1 opacity-50"></i>
                    <span class="small">No menus found matching "<strong>${escapeHtml(trimmed)}</strong>"</span>
                </div>
            `;
            searchDropdown.style.display = 'block';
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }

        let html = '<div class="header-search-results-list list-group list-group-flush">';
        matches.forEach(function (item, idx) {
            const iconName = item.icon || 'arrow-right';
            const highlightedName = highlightMatch(escapeHtml(item.name), trimmed);
            const parentBadge = item.parent ? `<span class="badge bg-light text-muted border ms-auto small">${escapeHtml(item.parent)}</span>` : '';

            html += `
                <a href="${item.route}" class="list-group-item list-group-item-action d-flex align-items-center py-2 px-3 gap-2 border-0 rounded-2 ${idx === 0 ? 'active-item' : ''}">
                    <div class="search-item-icon avatar avatar-xs rounded bg-primary-subtle text-primary d-flex align-items-center justify-content-center">
                        <i data-lucide="${iconName}" class="fs-14"></i>
                    </div>
                    <div class="search-item-info flex-grow-1 text-truncate">
                        <span class="fw-medium text-dark d-block text-truncate">${highlightedName}</span>
                    </div>
                </a>
            `;
        });
        html += '</div>';

        searchDropdown.innerHTML = html;
        searchDropdown.style.display = 'block';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function escapeHtml(string) {
        if (!string) return '';
        return String(string)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    searchInput.addEventListener('input', function () {
        renderSearchResults(this.value);
    });

    searchInput.addEventListener('focus', function () {
        if (this.value.trim().length >= 3) {
            renderSearchResults(this.value);
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            searchDropdown.style.display = 'none';
            searchDropdown.innerHTML = '';
            clearBtn.style.display = 'none';
            searchInput.focus();
        });
    }

    // Keyboard navigation (Escape to close, Enter to navigate first item, Arrow Up/Down)
    searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            searchDropdown.style.display = 'none';
            return;
        }

        const items = searchDropdown.querySelectorAll('.list-group-item');
        if (items.length === 0 || searchDropdown.style.display === 'none') return;

        let activeIdx = -1;
        items.forEach((item, i) => {
            if (item.classList.contains('bg-light') || item.classList.contains('active-item')) {
                activeIdx = i;
            }
        });

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            const nextIdx = activeIdx < items.length - 1 ? activeIdx + 1 : 0;
            items.forEach(el => el.classList.remove('bg-light', 'active-item'));
            items[nextIdx].classList.add('bg-light', 'active-item');
            items[nextIdx].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const prevIdx = activeIdx > 0 ? activeIdx - 1 : items.length - 1;
            items.forEach(el => el.classList.remove('bg-light', 'active-item'));
            items[prevIdx].classList.add('bg-light', 'active-item');
            items[prevIdx].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            if (activeIdx >= 0 && items[activeIdx]) {
                e.preventDefault();
                items[activeIdx].click();
            } else if (items[0]) {
                e.preventDefault();
                items[0].click();
            }
        }
    });

    // Close dropdown on click outside
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
            searchDropdown.style.display = 'none';
        }
    });

    // 3. Dynamic Theme Color Extraction from Active Header Logo
    applyThemeFromLogo();

    // 4. Auto-refresh notifications when bell dropdown is clicked
    const notifDropdownEl = document.getElementById('headerNotificationDropdown');
    if (notifDropdownEl) {
        notifDropdownEl.addEventListener('show.bs.dropdown', function () {
            refreshFollowupNotifications();
        });
    }

    // 5. Periodic background refresh (every 30 seconds)
    setInterval(function () {
        refreshFollowupNotifications();
    }, 30000);
});

/**
 * Extract Dominant Brand Color from Header Logo and dynamically apply to theme
 * Automatically detects new logo on refresh when Superadmin or Company changes logo.
 */
function applyThemeFromLogo() {
    const logoImg = document.querySelector('header .logo');
    if (!logoImg) return;

    // Use current logo src as unique cache key (auto-invalidates when logo changes)
    const logoSrc = logoImg.getAttribute('src') || logoImg.src;
    if (!logoSrc) return;

    const cacheKey = 'armor_logo_color_' + btoa(encodeURIComponent(logoSrc)).slice(0, 32);

    function applyColor(rgb, hex) {
        if (!rgb || !hex) return;
        const rgbStr = `${rgb.r}, ${rgb.g}, ${rgb.b}`;
        const hoverHex = `rgb(${Math.round(rgb.r * 0.85)}, ${Math.round(rgb.g * 0.85)}, ${Math.round(rgb.b * 0.85)})`;
        const activeHex = `rgb(${Math.round(rgb.r * 0.75)}, ${Math.round(rgb.g * 0.75)}, ${Math.round(rgb.b * 0.75)})`;

        let styleTag = document.getElementById('dynamic-logo-theme-vars');
        if (!styleTag) {
            styleTag = document.createElement('style');
            styleTag.id = 'dynamic-logo-theme-vars';
            document.head.appendChild(styleTag);
        }

        styleTag.innerHTML = `
            :root,
            [data-bs-theme=light] {
                --bs-primary: ${hex} !important;
                --bs-primary-rgb: ${rgbStr} !important;
                --bs-link-color: ${hex} !important;
                --bs-link-hover-color: ${hoverHex} !important;
                --bs-primary-bg-subtle: rgba(${rgbStr}, 0.12) !important;
                --bs-primary-border-subtle: rgba(${rgbStr}, 0.35) !important;
                --bs-focus-ring-color: rgba(${rgbStr}, 0.25) !important;
            }
            [data-bs-theme=dark] {
                --bs-primary: ${hex} !important;
                --bs-primary-rgb: ${rgbStr} !important;
                --bs-link-color: ${hex} !important;
                --bs-primary-bg-subtle: rgba(${rgbStr}, 0.2) !important;
                --bs-primary-border-subtle: rgba(${rgbStr}, 0.45) !important;
            }
            .btn-primary {
                --bs-btn-bg: ${hex} !important;
                --bs-btn-border-color: ${hex} !important;
                --bs-btn-hover-bg: ${hoverHex} !important;
                --bs-btn-hover-border-color: ${hoverHex} !important;
                --bs-btn-active-bg: ${activeHex} !important;
                --bs-btn-active-border-color: ${activeHex} !important;
                --bs-btn-disabled-bg: ${hex} !important;
                --bs-btn-disabled-border-color: ${hex} !important;
            }
            .btn-outline-primary {
                --bs-btn-color: ${hex} !important;
                --bs-btn-border-color: ${hex} !important;
                --bs-btn-hover-bg: ${hex} !important;
                --bs-btn-hover-border-color: ${hex} !important;
                --bs-btn-active-bg: ${hex} !important;
                --bs-btn-active-border-color: ${hex} !important;
            }
            .text-primary {
                color: ${hex} !important;
            }
            .bg-primary {
                --bs-bg-opacity: 1;
                background-color: rgba(${rgbStr}, var(--bs-bg-opacity, 1)) !important;
            }
            .border-primary {
                border-color: ${hex} !important;
            }
            .horizontal-menu .nav-item .nav-link.active,
            .horizontal-menu .nav-item .nav-link[aria-expanded=true] {
                color: #fff !important;
                background-color: ${hex} !important;
            }
            .horizontal-menu .nav-item .nav-link.active .nav-text,
            .horizontal-menu .nav-item .nav-link[aria-expanded=true] .nav-text {
                color: #fff !important;
            }
            .horizontal-menu .nav-item .nav-link.active i,
            .horizontal-menu .nav-item .nav-link.active [data-lucide],
            .horizontal-menu .nav-item .nav-link[aria-expanded=true] i,
            .horizontal-menu .nav-item .nav-link[aria-expanded=true] [data-lucide] {
                color: #fff !important;
            }
            .form-check-input:checked {
                background-color: ${hex} !important;
                border-color: ${hex} !important;
            }
            .form-check-input:focus {
                border-color: ${hex} !important;
                box-shadow: 0 0 0 0.25rem rgba(${rgbStr}, 0.25) !important;
            }
            .page-item.active .page-link {
                background-color: ${hex} !important;
                border-color: ${hex} !important;
            }
            .module-card.selected-highlight {
                background-color: ${hex} !important;
                border-color: ${hex} !important;
                box-shadow: 0 3px 8px rgba(${rgbStr}, 0.4) !important;
            }
        `;

        document.documentElement.style.setProperty('--bs-primary', hex);
        document.documentElement.style.setProperty('--bs-primary-rgb', rgbStr);
    }

    // Check localStorage cache for instantaneous apply without delay
    try {
        const cached = localStorage.getItem(cacheKey);
        if (cached) {
            const parsed = JSON.parse(cached);
            if (parsed && parsed.hex && parsed.rgb) {
                applyColor(parsed.rgb, parsed.hex);
            }
        }
    } catch (e) {}

    function getDominantColorFromImg(img) {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            const size = 64;
            canvas.width = size;
            canvas.height = size;

            ctx.drawImage(img, 0, 0, size, size);
            const imgData = ctx.getImageData(0, 0, size, size).data;

            const colorCounts = {};
            let maxCount = 0;
            let dominantRgb = null;

            for (let i = 0; i < imgData.length; i += 4) {
                const r = imgData[i];
                const g = imgData[i + 1];
                const b = imgData[i + 2];
                const a = imgData[i + 3];

                // Skip transparent, near-white (>235), and near-black (<25)
                if (a < 120) continue;
                if (r > 235 && g > 235 && b > 235) continue;
                if (r < 25 && g < 25 && b < 25) continue;

                // Group color shades (step of 16)
                const qR = Math.round(r / 16) * 16;
                const qG = Math.round(g / 16) * 16;
                const qB = Math.round(b / 16) * 16;
                const key = `${qR},${qG},${qB}`;

                colorCounts[key] = (colorCounts[key] || 0) + 1;
                if (colorCounts[key] > maxCount) {
                    maxCount = colorCounts[key];
                    dominantRgb = { r: qR, g: qG, b: qB };
                }
            }

            return dominantRgb;
        } catch (err) {
            return null;
        }
    }

    function processLogo() {
        const rgb = getDominantColorFromImg(logoImg);
        if (rgb) {
            const hex = '#' + ((1 << 24) + (rgb.r << 16) + (rgb.g << 8) + rgb.b).toString(16).slice(1);
            applyColor(rgb, hex);
            try {
                const colorData = JSON.stringify({ rgb: rgb, hex: hex });
                localStorage.setItem(cacheKey, colorData);
                localStorage.setItem('armor_last_logo_color', colorData);
            } catch (e) {}
        }
    }

    if (logoImg.complete && logoImg.naturalHeight !== 0) {
        processLogo();
    } else {
        logoImg.addEventListener('load', processLogo);
    }
}