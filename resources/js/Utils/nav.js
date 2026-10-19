/**
 * Navigation helpers shared by the layouts.
 * Uses the global Ziggy `route()` helper (window.route, set in app.js).
 */

function ziggy() {
    const fn = typeof globalThis !== 'undefined' ? globalThis.route : undefined;
    return typeof fn === 'function' ? fn : null;
}

/** true when a named route exists in the Ziggy route list. */
export function routeHas(name) {
    const route = ziggy();
    if (!route || !name) return false;
    try {
        return route().has(name);
    } catch {
        return false;
    }
}

/** Relative path ("/student/courses") for a parameterless named route, or null. */
export function routePath(name, params = undefined) {
    const route = ziggy();
    if (!route || !routeHas(name)) return null;
    try {
        return route(name, params, false);
    } catch {
        return null;
    }
}

/** Strip query string / hash and trailing slash. */
export function cleanPath(url) {
    if (!url) return '/';
    let path = String(url).split('#')[0].split('?')[0];
    if (/^https?:\/\//i.test(path)) {
        try {
            path = new URL(path).pathname;
        } catch {
            /* keep as is */
        }
    }
    if (path.length > 1) path = path.replace(/\/+$/, '');
    return path || '/';
}

/**
 * Is `href` the current page or one of its children?
 * `exact` → only the same path counts (use for dashboards).
 */
export function isActivePath(currentUrl, href, exact = false) {
    if (!href) return false;
    const current = cleanPath(currentUrl);
    const target = cleanPath(href);
    if (current === target) return true;
    if (exact || target === '/') return false;
    return current.startsWith(target + '/');
}

/**
 * Turn nav definitions into renderable items: drops items whose route is
 * missing, resolves `href`, keeps the rest of the fields.
 * Item shape: { label, route: string | string[], icon, exact?, match?: string[] (extra path prefixes), badge? }
 */
export function resolveNavItems(items, has = routeHas, path = routePath) {
    return (items || [])
        .map((item) => {
            if (!item) return null;
            if (item.href) return { ...item };
            // `route` may be a list of candidate names (first existing wins) — for routes still being built
            const names = Array.isArray(item.route) ? item.route : [item.route];
            const name = names.find((n) => has(n));
            return name ? { ...item, route: name, href: path(name) } : null;
        })
        .filter((item) => item && !!item.href);
}

/** Same as resolveNavItems for grouped sections; empty sections are dropped. */
export function resolveNavSections(sections, has = routeHas, path = routePath) {
    return (sections || [])
        .map((section) => ({ ...section, items: resolveNavItems(section.items, has, path) }))
        .filter((section) => section.items.length > 0);
}

/** Active check for a resolved item (href + optional extra `match` prefixes). */
export function isItemActive(currentUrl, item) {
    if (!item) return false;
    if (isActivePath(currentUrl, item.href, item.exact)) return true;
    return (item.match || []).some((prefix) => isActivePath(currentUrl, prefix));
}
