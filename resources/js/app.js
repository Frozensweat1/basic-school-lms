import './bootstrap';
import Quill from 'quill';
import Swal from 'sweetalert2';
import 'quill/dist/quill.snow.css';
import 'sweetalert2/dist/sweetalert2.min.css';

window.Swal = Swal;

const dashboardChartSelector = '[data-dashboard-chart]';
let chartLibrary = null;
let chartImport = null;
let lmsShellAbortController = null;

function currentTheme() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

function syncThemeControls() {
    const isDark = currentTheme() === 'dark';
    const toggle = document.getElementById('theme-toggle');
    const metaThemeColor = document.querySelector('meta[name="theme-color"]');

    if (toggle) {
        toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        toggle.querySelector('[data-theme-icon="moon"]')?.classList.toggle('hidden', isDark);
        toggle.querySelector('[data-theme-icon="sun"]')?.classList.toggle('hidden', !isDark);
    }

    if (metaThemeColor) {
        metaThemeColor.dataset.lightColor ??= metaThemeColor.content;
        metaThemeColor.content = isDark ? '#0f172a' : metaThemeColor.dataset.lightColor;
    }
}

function setTheme(theme, persist = true) {
    const isDark = theme === 'dark';
    document.documentElement.classList.toggle('dark', isDark);

    if (persist) localStorage.setItem('lms-theme', theme);
    syncThemeControls();
    document.dispatchEvent(new CustomEvent('lms-theme-changed', { detail: { theme } }));
}

function initialiseLmsShell() {
    const sidebar = document.getElementById('lms-sidebar');
    const shell = document.getElementById('lms-content-shell');
    const backdrop = document.getElementById('sidebar-backdrop');
    const mobileToggle = document.getElementById('content-sidebar-toggle');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const themeToggle = document.getElementById('theme-toggle');
    const userMenuToggle = document.getElementById('user-menu-toggle');
    const userMenu = document.getElementById('user-menu');

    if (!sidebar || !shell || !backdrop) return;

    lmsShellAbortController?.abort();
    lmsShellAbortController = new AbortController();
    const { signal } = lmsShellAbortController;
    const desktopMedia = window.matchMedia('(min-width: 768px)');

    const updateContentOffset = (collapsed) => {
        shell.classList.remove('md:pl-72', 'md:pl-24');
        shell.classList.add(collapsed ? 'md:pl-24' : 'md:pl-72');
    };

    const setSidebarCollapsed = (collapsed) => {
        sidebar.classList.toggle('sidebar-collapsed', collapsed);
        sidebar.dataset.collapsed = collapsed ? 'true' : 'false';
        updateContentOffset(collapsed);

        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
            sidebarToggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
            sidebarToggle.title = collapsed ? 'Expand navigation' : 'Collapse navigation';
        }
    };

    const setMobileOpen = (open, restoreFocus = true) => {
        sidebar.classList.toggle('sidebar-mobile-open', open);
        sidebar.setAttribute('aria-hidden', open ? 'false' : 'true');
        backdrop.classList.toggle('hidden', !open);
        mobileToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        sidebarToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
        sidebarToggle?.setAttribute('aria-label', 'Close navigation');
        document.body.classList.toggle('lms-mobile-nav-open', open);

        if (open) {
            requestAnimationFrame(() => sidebarToggle?.focus({ preventScroll: true }));
        } else if (restoreFocus) {
            mobileToggle?.focus({ preventScroll: true });
        }
    };

    const syncViewport = () => {
        if (desktopMedia.matches) {
            sidebar.classList.remove('sidebar-mobile-open');
            sidebar.setAttribute('aria-hidden', 'false');
            backdrop.classList.add('hidden');
            mobileToggle?.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('lms-mobile-nav-open');
            setSidebarCollapsed(localStorage.getItem('lms-sidebar-collapsed') === '1');
        } else {
            sidebar.classList.remove('sidebar-collapsed');
            updateContentOffset(false);
            setMobileOpen(false, false);
        }
    };

    mobileToggle?.addEventListener('click', () => setMobileOpen(true), { signal });

    sidebarToggle?.addEventListener('click', () => {
        if (!desktopMedia.matches) {
            setMobileOpen(false);
            return;
        }

        const collapsed = !sidebar.classList.contains('sidebar-collapsed');
        setSidebarCollapsed(collapsed);
        localStorage.setItem('lms-sidebar-collapsed', collapsed ? '1' : '0');
    }, { signal });

    backdrop.addEventListener('click', () => setMobileOpen(false), { signal });
    sidebar.querySelectorAll('a[href]').forEach((link) => {
        link.addEventListener('click', () => {
            if (!desktopMedia.matches) setMobileOpen(false, false);
        }, { signal });
    });

    themeToggle?.addEventListener('click', () => {
        setTheme(currentTheme() === 'dark' ? 'light' : 'dark');
    }, { signal });

    if (userMenuToggle && userMenu) {
        const setUserMenuOpen = (open) => {
            userMenu.classList.toggle('hidden', !open);
            userMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        userMenuToggle.addEventListener('click', () => {
            setUserMenuOpen(userMenu.classList.contains('hidden'));
        }, { signal });

        document.addEventListener('click', (event) => {
            if (!userMenu.contains(event.target) && !userMenuToggle.contains(event.target)) {
                setUserMenuOpen(false);
            }
        }, { signal });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') return;

            if (!userMenu.classList.contains('hidden')) {
                setUserMenuOpen(false);
                userMenuToggle.focus();
            }

            if (!desktopMedia.matches && sidebar.classList.contains('sidebar-mobile-open')) {
                setMobileOpen(false);
            }
        }, { signal });
    }

    desktopMedia.addEventListener('change', syncViewport, { signal });
    syncThemeControls();
    syncViewport();
}

async function loadChartLibrary() {
    if (!chartImport) {
        chartImport = import('chart.js/auto').then(({ default: Chart }) => {
            const isDark = currentTheme() === 'dark';
            Chart.defaults.color = isDark ? '#cbd5e1' : '#64748b';
            Chart.defaults.borderColor = isDark ? 'rgba(100, 116, 139, 0.35)' : 'rgba(148, 163, 184, 0.2)';
            Chart.defaults.font.family = 'Instrument Sans, ui-sans-serif, system-ui, sans-serif';
            chartLibrary = Chart;

            return Chart;
        });
    }

    return chartImport;
}

function updateDashboardChartTheme(theme) {
    if (!chartLibrary) return;

    const isDark = theme === 'dark';
    const textColor = isDark ? '#cbd5e1' : '#64748b';
    const gridColor = isDark ? 'rgba(100, 116, 139, 0.35)' : 'rgba(148, 163, 184, 0.2)';
    chartLibrary.defaults.color = textColor;
    chartLibrary.defaults.borderColor = gridColor;

    Object.values(chartLibrary.instances).forEach((chart) => {
        if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = textColor;

        Object.values(chart.options.scales ?? {}).forEach((scale) => {
            scale.ticks ??= {};
            scale.grid ??= {};
            scale.ticks.color = textColor;
            scale.grid.color = gridColor;
        });

        chart.update('none');
    });
}

function chartCanvasesWithin(root) {
    if (!root) return [];

    const canvases = [];

    if (root.matches?.(dashboardChartSelector)) canvases.push(root);
    root.querySelectorAll?.(dashboardChartSelector).forEach((canvas) => canvases.push(canvas));

    return canvases;
}

async function renderDashboardCharts(root = document) {
    const canvases = chartCanvasesWithin(root);
    if (!canvases.length) return;

    const Chart = await loadChartLibrary();

    canvases.forEach((canvas) => {
        if (Chart.getChart(canvas)) return;

        const configElement = canvas.closest('[data-dashboard-chart-container]')
            ?.querySelector('[data-dashboard-chart-config]');

        if (!configElement) return;

        try {
            const config = JSON.parse(configElement.textContent);
            const suppliedOptions = config.options ?? {};
            delete config.meta;

            config.options = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'nearest' },
                animation: { duration: 450 },
                ...suppliedOptions,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, boxWidth: 8, padding: 18 },
                    },
                    tooltip: { padding: 12, displayColors: true },
                    ...(suppliedOptions.plugins ?? {}),
                },
            };

            new Chart(canvas, config);
        } catch (error) {
            console.error('Unable to render dashboard chart.', error);
        }
    });
}

let chartRenderFrame = null;

function scheduleDashboardChartRender(root = document) {
    if (chartRenderFrame) cancelAnimationFrame(chartRenderFrame);
    chartRenderFrame = requestAnimationFrame(() => {
        chartRenderFrame = null;
        void renderDashboardCharts(root);
    });
}

function initialiseDashboardCharts() {
    scheduleDashboardChartRender();

    const observer = new MutationObserver((mutations) => {
        let shouldRender = false;

        mutations.forEach((mutation) => {
            mutation.removedNodes.forEach((node) => {
                chartCanvasesWithin(node).forEach((canvas) => chartLibrary?.getChart(canvas)?.destroy());
            });

            if ([...mutation.addedNodes].some((node) => chartCanvasesWithin(node).length)) {
                shouldRender = true;
            }
        });

        if (shouldRender) scheduleDashboardChartRender();
    });

    observer.observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initialiseLmsShell();
        initialiseDashboardCharts();
    }, { once: true });
} else {
    initialiseLmsShell();
    initialiseDashboardCharts();
}

document.addEventListener('livewire:navigated', () => {
    initialiseLmsShell();
    scheduleDashboardChartRender();
});
document.addEventListener('lms-theme-changed', (event) => updateDashboardChartTheme(event.detail.theme));

window.richTextEditor = (value, options = {}) => ({
    value,
    editor: null,

    init() {
        this.editor = new Quill(this.$refs.editor, {
            theme: 'snow',
            placeholder: options.placeholder ?? 'Write lesson content…',
            modules: {
                toolbar: [
                    [{ header: [2, 3, 4, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ indent: '-1' }, { indent: '+1' }],
                    ['blockquote', 'code-block'],
                    ['link', 'image'],
                    ['clean'],
                ],
            },
        });

        this.setEditorContent(this.value);

        this.editor.on('text-change', () => {
            const content = this.editor.root.innerHTML === '<p><br></p>' ? '' : this.editor.root.innerHTML;

            if (content !== this.value) {
                this.value = content;
            }
        });
    },

    setEditorContent(content) {
        this.editor.clipboard.dangerouslyPasteHTML(content || '');
    },
});
