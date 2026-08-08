<?php
/**
 * Plugin Name: Alexandra Cache Headers
 * Description: Guarantees dynamic HTML is never stored by an intermediary cache (Hostinger CDN, proxies, browsers).
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Why this exists (2026-07-25 incident).
 *
 * During the 2026-07-24 move to alexandramontessori.co.uk the domain was briefly
 * still served by the Hostinger AI placeholder site, whose .htaccess sets
 * `ExpiresDefault "access plus 1 weeks"`. The Hostinger CDN fetched `/` in that
 * window, stored the placeholder homepage for 7 days, and then kept replaying it
 * to visitors even though the origin was serving the correct site. The real site
 * sent no cache headers at all, so it never told the edge not to store it.
 *
 * The primary fix lives in the docroot .htaccess (AM-CACHE-POLICY block). This
 * mu-plugin is the belt-and-braces copy: WordPress rewrites .htaccess on permalink
 * flushes and some plugin installs, so the header must also be asserted from code
 * that cannot be clobbered that way.
 *
 * Every HTML response here is dynamic — window.amData is inlined per request — so
 * no front-end response is ever safe to store. Hashed SPA assets under
 * themes/alexandra-theme/dist/assets/ are static files that never reach PHP, so
 * they are unaffected by this.
 */
add_action(
	'send_headers',
	function () {
		if ( is_admin() || headers_sent() ) {
			return;
		}

		header( 'Cache-Control: no-cache, no-store, must-revalidate, max-age=0', true );
		header( 'Pragma: no-cache', true );

		// Diagnostic marker. The .htaccess AM-CACHE-POLICY block sets an identical
		// Cache-Control, so without this there is no way to tell from outside
		// whether this fallback is actually running. If a future response has the
		// no-store header but NOT this marker, the protection is coming from
		// .htaccess alone and this mu-plugin has stopped loading.
		header( 'X-AM-Cache-Guard: mu-plugin', true );
	},
	99
);
