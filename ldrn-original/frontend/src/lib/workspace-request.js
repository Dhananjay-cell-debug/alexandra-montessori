/** Authenticated editor previews may read archived content; visitor requests cannot. */
export function workspaceRequest(url, options = {}) {
  const studio = window.__LDRN_WORKSPACE__?.studio;
  if (!studio?.edit || !studio.restNonce) return fetch(url, options);
  const target = new URL(url, window.location.origin);
  if (target.origin !== window.location.origin) throw new Error('Editor previews require this website’s API.');
  target.searchParams.set('ldrn_ws_studio', '1');
  return fetch(target, {
    ...options,
    headers: { ...options.headers, 'X-WP-Nonce': studio.restNonce },
  });
}
