<?php
/**
 * Media that survives the journey from the editor to the live page.
 *
 * A design is data, and data outlives the hostname it was written on. Storing a
 * bare absolute URL means the moment the design is copied to another site - a
 * local machine to production, or one domain to another - every image points at
 * a host the visitor cannot reach, and the page silently renders a placeholder
 * instead. That is exactly how an image uploaded in the builder reached
 * production as `http://alexandra-montessori.local/...` and disappeared.
 *
 * Two rules fix that class of failure for good:
 *
 *   1. On SAVE, a media URL is normalised to this site. An uploads path from a
 *      foreign host is rehomed here; a foreign URL that is not ours is refused
 *      rather than stored and rendered as a broken image later.
 *   2. On SAVE we also record the attachment ID, and on RENDER the ID wins.
 *      An ID is portable in a way a URL never is - `wp_get_attachment_url()`
 *      always answers with the current site's domain and the current file.
 *
 * Together these mean a design can be moved between sites and the pictures
 * still resolve, and an editor cannot store a picture the site cannot serve.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Media {
	/** Media may only ever be fetched over these. No data:, no javascript:. */
	const PROTOCOLS = array('http', 'https');

	/**
	 * The host this site serves from.
	 *
	 * @return string
	 */
	public static function site_host() {
		return strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
	}

	/**
	 * Normalise a media URL so it belongs to this site, or refuse it.
	 *
	 * @param string $value Raw URL from the editor.
	 * @return string Normalised URL, or '' when it cannot be trusted.
	 */
	public static function normalise_src($value) {
		$value = trim((string) $value);
		if ('' === $value) {
			return '';
		}

		// Protocol-relative URLs resolve against the current scheme; make that
		// explicit before parsing so the host check below cannot be skipped.
		if (0 === strpos($value, '//')) {
			$value = (is_ssl() ? 'https:' : 'http:') . $value;
		}

		$clean = esc_url_raw($value, self::PROTOCOLS);
		if ('' === $clean) {
			return '';
		}

		$host = strtolower((string) wp_parse_url($clean, PHP_URL_HOST));

		// A site-relative path has no host and is already ours.
		if ('' === $host) {
			return $clean;
		}
		if ($host === self::site_host()) {
			return $clean;
		}

		// A foreign host is only ever acceptable when it is plainly the same
		// uploads file on a different domain - the local/staging/production
		// case. Rehome it. Anything else is refused: hotlinking someone else's
		// server on a nursery's website is both a privacy leak and a picture
		// that breaks the day they delete it.
		$path = (string) wp_parse_url($clean, PHP_URL_PATH);
		if ('' !== $path && false !== strpos($path, '/wp-content/uploads/')) {
			$rehomed = home_url(substr($path, strpos($path, '/wp-content/uploads/')));
			return esc_url_raw($rehomed, self::PROTOCOLS);
		}

		return '';
	}

	/**
	 * The attachment ID behind one of our own URLs, when there is one.
	 *
	 * @param string $url Normalised URL.
	 * @return int 0 when the URL is not a known attachment.
	 */
	public static function attachment_id($url) {
		$url = trim((string) $url);
		if ('' === $url) {
			return 0;
		}
		// Size suffixes (-600x800) belong to generated files, not attachments,
		// so ask about the original before giving up.
		$id = (int) attachment_url_to_postid($url);
		if ($id) {
			return $id;
		}
		$stripped = preg_replace('#-\d+x\d+(\.[A-Za-z0-9]+)$#', '$1', $url);
		if (is_string($stripped) && $stripped !== $url) {
			return (int) attachment_url_to_postid($stripped);
		}

		return 0;
	}

	/**
	 * Sanitise a media pair for storage: a normalised URL plus its ID.
	 *
	 * @param array<string,mixed> $source Raw element from the request.
	 * @return array{src:string,srcId:int}
	 */
	public static function sanitize_pair($source) {
		$src = self::normalise_src(isset($source['src']) ? $source['src'] : '');

		// Trust a supplied ID only after confirming it is a real attachment on
		// this site; an ID from another install would resolve to whatever
		// happens to occupy that ID here.
		$id = isset($source['srcId']) ? absint($source['srcId']) : 0;
		if ($id && 'attachment' !== get_post_type($id)) {
			$id = 0;
		}
		if (!$id) {
			$id = self::attachment_id($src);
		}
		// A stored ID with no URL is still renderable, so keep the ID even when
		// the URL was refused - that is the portable half of the pair.
		if ($id && '' === $src) {
			$src = (string) wp_get_attachment_url($id);
			$src = self::normalise_src($src);
		}

		return array('src' => $src, 'srcId' => $id);
	}

	/**
	 * Resolve a stored pair to the URL this site should render right now.
	 *
	 * The ID wins whenever it resolves, which is what makes a design portable:
	 * move the site to a new domain and every picture follows automatically.
	 *
	 * @param string $src Stored URL.
	 * @param int    $id  Stored attachment ID.
	 * @return string
	 */
	public static function resolve($src, $id = 0) {
		$id = absint($id);
		if ($id) {
			$from_id = wp_get_attachment_url($id);
			if (is_string($from_id) && '' !== $from_id) {
				return $from_id;
			}
		}

		return self::normalise_src($src);
	}

	/**
	 * Walk a design and resolve every media pair for rendering.
	 *
	 * @param array<string,mixed> $design Design model.
	 * @return array<string,mixed>
	 */
	public static function resolve_design($design) {
		if (!is_array($design)) {
			return $design;
		}
		foreach ($design as $key => $value) {
			if (!is_array($value)) {
				continue;
			}
			if (isset($value['src']) || isset($value['srcId'])) {
				$attachment_id = isset($value['srcId']) ? absint($value['srcId']) : 0;
				$resolved = self::resolve(
					isset($value['src']) ? $value['src'] : '',
					$attachment_id
				);
				$design[$key]['src'] = $resolved;
				if ($attachment_id && function_exists('wp_get_attachment_image_srcset')) {
					$srcset = wp_get_attachment_image_srcset($attachment_id, 'large');
					if (is_string($srcset) && '' !== $srcset) {
						$design[$key]['srcSet'] = $srcset;
					}
				}
			}
			$design[$key] = self::resolve_design($design[$key]);
		}

		return $design;
	}

	/**
	 * Report every media reference in a design that this site cannot serve.
	 *
	 * Used by the repair script and by anything that wants to warn an editor
	 * before a broken picture reaches a visitor.
	 *
	 * @param array<string,mixed> $design Design model.
	 * @param string              $label  Context label for the report.
	 * @return array<int,array<string,string>>
	 */
	public static function audit_design($design, $label = '') {
		$problems = array();
		if (!is_array($design)) {
			return $problems;
		}

		foreach ($design as $key => $value) {
			if (!is_array($value)) {
				continue;
			}
			$path = '' === $label ? (string) $key : $label . '/' . $key;

			if (isset($value['src']) && is_string($value['src']) && '' !== trim($value['src'])) {
				$src   = trim($value['src']);
				$host  = strtolower((string) wp_parse_url($src, PHP_URL_HOST));
				$id    = isset($value['srcId']) ? absint($value['srcId']) : 0;
				$issue = '';

				if ('' !== $host && $host !== self::site_host()) {
					$issue = 'points at a foreign host (' . $host . ')';
				} elseif (!$id && !self::attachment_id($src)) {
					$local = self::local_path($src);
					if ('' !== $local && !file_exists($local)) {
						$issue = 'file is missing on disk';
					}
				}

				if ('' !== $issue) {
					$problems[] = array('path' => $path, 'src' => $src, 'issue' => $issue);
				}
			}

			$problems = array_merge($problems, self::audit_design($value, $path));
		}

		return $problems;
	}

	/**
	 * Map one of our own URLs back to a path on disk.
	 *
	 * @param string $url URL.
	 * @return string '' when the URL is not under this site's uploads.
	 */
	private static function local_path($url) {
		$uploads = wp_upload_dir();
		if (!empty($uploads['error'])) {
			return '';
		}
		$baseurl = trailingslashit((string) $uploads['baseurl']);
		$basedir = trailingslashit((string) $uploads['basedir']);
		if (0 !== strpos($url, $baseurl)) {
			return '';
		}

		return $basedir . substr($url, strlen($baseurl));
	}
}
