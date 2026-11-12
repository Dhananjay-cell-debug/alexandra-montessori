<?php
/**
 * The Site Editor - Framer's three panels, inside WordPress.
 *
 *   left    Pages · Layers · Assets
 *   centre  the real site, at Desktop 1440 / Tablet 834 / Phone 390
 *   right   Content · Text · Size · Layout · Styles for the selected layer
 *
 * One design document holds every edit made here. It is saved as a draft
 * while you work (Framer's autosave) and only reaches visitors on Publish.
 * Every publish is kept, so any earlier version can be brought back.
 *
 * Shape of the document:
 *
 *   array(
 *     'nodes' => array(
 *       'm:/about>3.1' => array(
 *         'text' => "Two lines\nof heading",      // content, every breakpoint
 *         'src'  => 'http://…/pic.jpg', 'alt' => '…', 'href' => '/lets-talk',
 *         's'    => array(                        // styles, per breakpoint
 *           'd' => array('font-size' => '64px', 'color' => '#6F4A2E'),
 *           't' => array('font-size' => '48px'),
 *           'p' => array('display'   => 'none'),
 *         ),
 *       ),
 *     ),
 *   )
 *
 * The key is both the storage key and the address of the element - see the
 * header of assets/studio-runtime.js. It never carries a raw selector, so the
 * only thing checked here is its shape.
 *
 * Nothing about this touches the INU source (F:/INU media/inu-cinematic) or
 * the Vercel deploy: the theme prints the runtime into the page WordPress
 * serves, and nowhere else.
 */

defined('ABSPATH') || exit;

const LDRN_WS_STUDIO_DRAFT    = 'ldrn_ws_studio_draft';
const LDRN_WS_STUDIO_LIVE     = 'ldrn_ws_studio_live';
const LDRN_WS_STUDIO_VERSIONS = 'ldrn_ws_studio_versions';
const LDRN_WS_STUDIO_META     = 'ldrn_ws_studio_meta';
const LDRN_WS_STUDIO_KEEP     = 30;
const LDRN_WS_STUDIO_MAX_NODES = 4000;

/* -------------------------------------------------------------------------
 * Validation - the only gate between the editor and every visitor's page.
 * ---------------------------------------------------------------------- */

function ldrn_ws_studio_props() {
    return array(
        'color', 'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing',
        'fill','stroke','stroke-width',
        'text-align', 'text-transform', 'font-style', 'text-decoration-line',
        'width', 'height', 'min-height', 'max-width', 'display', 'flex-direction',
        'justify-content', 'align-items', 'flex-wrap', 'gap', 'align-self', 'order',
        'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'opacity', 'background-color', 'border-radius', 'border-width', 'border-style',
        'border-color', 'box-shadow', 'overflow', 'object-fit', 'object-position',
        'grid-template-columns', 'grid-column', 'aspect-ratio', 'position',
        'top', 'right', 'bottom', 'left', 'z-index', 'background-image',
        'max-height', 'min-width', 'flex', 'text-wrap', 'justify-self',
        'border-top-width', 'border-right-width', 'border-bottom-width', 'border-left-width',
    );
}

/** Tags the Assets panel may put on a page. Nothing that runs code. */
function ldrn_ws_studio_tags() {
    return array(
        'div', 'section', 'header', 'footer', 'article', 'aside', 'figure', 'figcaption',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'small', 'strong', 'em',
        'a', 'img', 'ul', 'ol', 'li', 'blockquote', 'hr', 'iframe',
        'svg', 'g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon',
    );
}

/** The only places an embedded frame may point: a Google map or a YouTube video. */
function ldrn_ws_studio_embed_ok($url) {
    return (bool) preg_match('#^https://(www\.google\.com/maps/embed\?|maps\.google\.com/maps\?|www\.youtube-nocookie\.com/embed/|www\.youtube\.com/embed/)[A-Za-z0-9_\-.%&=?+,/!:@;]*$#D', (string) $url);
}

/**
 * Pages the INU build draws itself (see ldrn-workspace-shell's route table).
 * Everything else is made in the Site Editor. /contact is the same page as
 * /lets-talk, and the asset folders are the build's own addresses, so none
 * of them can be taken by a new page either.
 */
function ldrn_ws_studio_builtin_route($route) {
    return in_array($route, wp_list_pluck(ldrn_ws_default_pages(), 'route'), true)
        || preg_match('#^/(our-nursery|events|blogs|careers/vacancies)/[^/]+$#', $route)
        || preg_match('#^/(wp-|assets|fonts|wp-content|wp-json)#', $route);
}

/**
 * w: m: t: address the site's own elements (see studio-runtime.js);
 * n:<id> addresses something inserted from the Assets panel.
 */
function ldrn_ws_studio_valid_key($key) {
    if (!is_string($key)) {
        return false;
    }
    $key=preg_replace('/~label[1-9][0-9]?$/D','',$key);
    return (bool) preg_match('#^(w:[A-Za-z0-9._-]{1,100}|[me]:/[A-Za-z0-9/_-]{0,100}|t)(>\d{1,3}(\.\d{1,3}){0,40})?$#D', $key)
        || (bool) preg_match('#^n:[a-z0-9]{4,16}$#D', $key);
}

function ldrn_ws_studio_clean_value($prop, $value) {
    if (!is_scalar($value)) {
        return '';
    }
    $value = trim((string) $value);
    if ('' === $value) {
        return '';
    }
    if ('font-family' === $prop) {
        return in_array($value, array('display', 'mark', 'sans'), true) ? $value : '';
    }
    if (strlen($value) > 160 || !preg_match('#^[A-Za-z0-9\#%.,()\s+\-/]*$#D', $value)) {
        return '';
    }
    if (preg_match('/url|expression|var\(/i', $value)) {
        return '';
    }
    if ('position' === $prop && !in_array($value, array('static', 'relative', 'absolute', 'sticky'), true)) {
        return '';
    }
    // A background may be a gradient and nothing else - never a picture from
    // somewhere else, which is what url() would allow.
    if ('background-image' === $prop && 'none' !== $value && !preg_match('/^(linear|radial)-gradient\(/', $value)) {
        return '';
    }
    return $value;
}

function ldrn_ws_studio_clean_url($url, $link = false) {
    $url = trim((string) $url);
    if ('' === $url) {
        return '';
    }
    if ('#' === $url[0]) {
        return preg_match('#^\#[A-Za-z0-9/_?=&.-]*$#D', $url) ? $url : '';
    }
    if ('/' === $url[0] && (strlen($url) < 2 || '/' !== $url[1])) {
        return esc_url_raw($url) ? $url : '';
    }
    $protocols = $link ? array('http', 'https', 'mailto', 'tel') : array('http', 'https');
    return esc_url_raw($url, $protocols);
}

/** Take whatever the browser sent and keep only what is allowed to exist. */
/** The inline tags a styled run of text may keep. */
function ldrn_ws_studio_rich_tags() {
    return array('em', 'strong', 'b', 'i', 'u', 's', 'span', 'small', 'sup', 'sub', 'mark');
}

/**
 * Words with their inline styling kept: a list of runs, each a string, a
 * line break, or an inline element (a whitelisted tag, its classes, its
 * own runs). Nothing else survives - no attributes but class and the
 * site's own data-ldrn-ws name, no tags outside the list, no angle brackets.
 */
function ldrn_ws_studio_clean_rich($runs, $depth, &$budget) {
    $out = array();
    if (!is_array($runs) || $depth > 4) {
        return $out;
    }
    foreach ($runs as $run) {
        if ($budget <= 0 || count($out) >= 200) {
            break;
        }
        if (is_string($run)) {
            $text = str_replace(array('<', '>', "\0"), '', wp_check_invalid_utf8($run));
            $text = mb_substr($text, 0, $budget);
            $budget -= mb_strlen($text);
            if ('' !== $text) {
                $out[] = $text;
            }
        } elseif (is_array($run) && !empty($run['br'])) {
            $out[] = array('br' => 1);
        } elseif (is_array($run) && isset($run['tag']) && in_array($run['tag'], ldrn_ws_studio_rich_tags(), true)) {
            $item = array('tag' => $run['tag']);
            if (!empty($run['cls']) && is_string($run['cls'])) {
                $cls = trim(preg_replace('#[^A-Za-z0-9 _:/\-\[\].%!]#', '', $run['cls']));
                if ('' !== $cls) {
                    $item['cls'] = mb_substr($cls, 0, 300);
                }
            }
            if (!empty($run['ww']) && is_string($run['ww']) && preg_match('#^[A-Za-z0-9._-]{1,100}$#D', $run['ww'])) {
                $item['ww'] = $run['ww'];
            }
            $item['kids'] = ldrn_ws_studio_clean_rich(isset($run['kids']) ? $run['kids'] : array(), $depth + 1, $budget);
            $out[] = $item;
        }
    }
    return $out;
}

function ldrn_ws_studio_clean_doc($raw) {
    $clean = array('nodes' => array());
    if (!is_array($raw)) {
        return $clean;
    }
    $props = ldrn_ws_studio_props();
    $count = 0;
    $nodes = isset($raw['nodes']) && is_array($raw['nodes']) ? $raw['nodes'] : array();

    foreach ($nodes as $key => $node) {
        if ($count >= LDRN_WS_STUDIO_MAX_NODES) {
            break;
        }
        if (!ldrn_ws_studio_valid_key($key) || !is_array($node)) {
            continue;
        }
        $out = array();

        if (isset($node['text']) && is_string($node['text'])) {
            $out['text'] = mb_substr(sanitize_textarea_field($node['text']), 0, 5000);
        }
        if (isset($node['label']) && is_string($node['label'])) $out['label'] = mb_substr(sanitize_textarea_field($node['label']), 0, 5000);
        if (isset($node['placeholder']) && is_string($node['placeholder'])) $out['placeholder'] = mb_substr(sanitize_text_field($node['placeholder']), 0, 300);
        if (isset($node['newTab'])) $out['newTab'] = (bool)$node['newTab'];
        if(isset($node['options']) && is_array($node['options'])) {
            $out['options']=array();
            foreach(array_slice($node['options'],0,100) as $option) {
                if(!is_array($option) || !isset($option['value'],$option['label']))continue;
                $out['options'][]=array('value'=>mb_substr(sanitize_text_field($option['value']),0,200),'label'=>mb_substr(sanitize_text_field($option['label']),0,300));
            }
        }
        // What the edit was made on (see resolveKeys() in the runtime).
        $fingerprint = null;
        if (isset($node['f']) && is_string($node['f']) && preg_match('#^[A-Za-z0-9._:/\[\]%!|\#~-]{1,240}$#D', $node['f'])) {
            $fingerprint = $node['f'];
        }
        if (isset($node['rich']) && is_array($node['rich'])) {
            $budget = 5000;
            $rich = ldrn_ws_studio_clean_rich($node['rich'], 0, $budget);
            if ($rich) {
                $out['rich'] = $rich;
                unset($out['text']);
            }
        }
        if (!empty($node['src'])) {
            $src = ldrn_ws_studio_clean_url($node['src']);
            if ($src) {
                $out['src'] = $src;
            }
        }
        if (isset($node['alt']) && is_string($node['alt'])) {
            $out['alt'] = mb_substr(sanitize_text_field($node['alt']), 0, 300);
        }
        if (isset($node['href']) && $node['href'] === '') $out['href'] = '';
        if (!empty($node['href'])) {
            $href = ldrn_ws_studio_clean_url($node['href'], true);
            if ($href) {
                $out['href'] = $href;
            }
        }
        if (!empty($node['s']) && is_array($node['s'])) {
            foreach (array('d', 't', 'p') as $bp) {
                if (empty($node['s'][$bp]) || !is_array($node['s'][$bp])) {
                    continue;
                }
                foreach ($node['s'][$bp] as $prop => $value) {
                    if (!in_array($prop, $props, true)) {
                        continue;
                    }
                    $value = ldrn_ws_studio_clean_value($prop, $value);
                    if ('' !== $value) {
                        $out['s'][$bp][$prop] = $value;
                    }
                }
            }
        }

        if ($out) {
            if (null !== $fingerprint) {
                $out['f'] = $fingerprint;
            }
            $clean['nodes'][$key] = $out;
            $count++;
        }
    }

    if (!empty($raw['blocks']) && is_array($raw['blocks'])) {
        $budget = 3000;
        foreach ($raw['blocks'] as $id => $block) {
            if (!is_array($block) || !preg_match('#^[a-z0-9]{4,16}$#D', (string) $id)) {
                continue;
            }
            $at  = isset($block['at']) ? (string) $block['at'] : '';
            $pos = isset($block['pos']) ? (string) $block['pos'] : '';
            if (!ldrn_ws_studio_valid_key($at) || !in_array($pos, array('after', 'before', 'append', 'prepend'), true)) {
                continue;
            }
            $tree = ldrn_ws_studio_clean_tree(isset($block['tree']) ? $block['tree'] : null, 0, $budget);
            // A block's id and its root element's id are one and the same -
            // it is how the runtime recognises the block already on the page.
            if (!$tree || $tree['id'] !== (string) $id) {
                continue;
            }
            $clean['blocks'][(string) $id] = array('at' => $at, 'pos' => $pos, 'tree' => $tree);
            // What it was placed on (see resolveKeys() in the runtime).
            if (isset($block['af']) && is_string($block['af']) && preg_match('#^[A-Za-z0-9._:/\[\]%!|\#~-]{1,240}$#D', $block['af'])) {
                $clean['blocks'][(string) $id]['af'] = $block['af'];
            }
            if (count($clean['blocks']) >= 500) {
                break;
            }
        }
    }

    // A page-specific layer may be organised under another section in the
    // editor's compact stack. Addresses remain validated, never raw CSS.
    if (!empty($raw['groups']) && is_array($raw['groups'])) {
        $clean['groups'] = array();
        foreach ($raw['groups'] as $key => $group) {
            if (count($clean['groups']) >= 1000) break;
            if (!ldrn_ws_studio_valid_key($key) || !ldrn_ws_studio_valid_key($group) || $key === $group) continue;
            if (!preg_match('#^m:([^>]+)>\d{1,3}$#D', $group, $to)) continue;
            if (!preg_match('#^n:[a-z0-9]{4,16}$#D', $key) && (!preg_match('#^m:([^>]+)>#', $key, $from) || $from[1] !== $to[1])) continue;
            $clean['groups'][$key] = $group;
        }
    }

    // Explicit layer order is presentation metadata. Only validated layer
    // addresses are retained; it never becomes a CSS selector or property.
    if (!empty($raw['layerOrder']) && is_array($raw['layerOrder'])) {
        $clean['layerOrder'] = array();
        foreach ($raw['layerOrder'] as $group => $keys) {
            if (count($clean['layerOrder']) >= 100) break;
            if (!is_array($keys)) continue;
            if ($group !== 'page' && !ldrn_ws_studio_valid_key($group)) continue;
            $valid = array();
            foreach ($keys as $key) {
                if (count($valid) >= 2000) break;
                if (is_string($key) && ldrn_ws_studio_valid_key($key) && !in_array($key, $valid, true)) $valid[] = $key;
            }
            if ($valid) $clean['layerOrder'][$group] = $valid;
        }
    }

    return $clean;
}

/** One inserted element and everything inside it, reduced to what is allowed. */
function ldrn_ws_studio_clean_tree($node, $depth, &$budget, $in_svg = false) {
    if (!is_array($node) || $depth > 14 || $budget <= 0) {
        return null;
    }
    $id  = isset($node['id']) ? (string) $node['id'] : '';
    $tag = isset($node['tag']) ? strtolower((string) $node['tag']) : '';
    if (!preg_match('#^[a-z0-9]{4,16}$#D', $id) || !in_array($tag, ldrn_ws_studio_tags(), true)) {
        return null;
    }
    $shapes = array('g', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon');
    if (($in_svg && !in_array($tag, $shapes, true)) || (!$in_svg && in_array($tag, $shapes, true))) return null;
    $budget--;
    $out = array('id' => $id, 'tag' => $tag);
    if ('svg' === $tag || $in_svg) {
        foreach ((array)($node['attrs'] ?? array()) as $attr => $value) {
            if (!is_scalar($value)) continue;
            $value = trim((string)$value);
            if (in_array($attr, array('viewBox','width','height','x','y','x1','x2','y1','y2','cx','cy','r','rx','ry','stroke-width','stroke-dasharray','opacity'), true)
                && preg_match('/^[0-9eE.,%\s+\-]{1,200}$/D', $value)) $out['attrs'][$attr] = $value;
            elseif (in_array($attr, array('d','points','transform'), true)
                && preg_match('/^[A-Za-z0-9.,()\s+\-]{1,4000}$/D', $value)) $out['attrs'][$attr] = $value;
            elseif (in_array($attr, array('fill','stroke'), true)
                && preg_match('/^(none|currentColor|transparent|#[a-fA-F0-9]{3,8}|[a-zA-Z]{1,30})$/D', $value)) $out['attrs'][$attr] = $value;
            elseif (in_array($attr, array('stroke-linecap','stroke-linejoin','fill-rule','clip-rule','aria-hidden'), true)
                && preg_match('/^[a-z]{1,15}$/D', $value)) $out['attrs'][$attr] = $value;
        }
    }
    if ('a' === $tag && isset($node['newTab'])) $out['newTab'] = (bool)$node['newTab'];
    if (in_array($node['collection'] ?? '', array('events','articles','gallery','testimonials','jobs'), true)) {
        $out['collection'] = $node['collection'];
    }

    if (isset($node['text']) && is_string($node['text']) && '' !== $node['text']) {
        $out['text'] = mb_substr(sanitize_textarea_field($node['text']), 0, 5000);
    }
    // What the Layers panel calls it ("Hero - Split").
    if (!empty($node['name']) && is_string($node['name'])) {
        $out['name'] = mb_substr(sanitize_text_field($node['name']), 0, 80);
    }
    // Class names only matter for a duplicated section of the site's own
    // design, which keeps the build's classes so it looks the same.
    if (!empty($node['cls']) && is_string($node['cls'])) {
        $cls = trim(preg_replace('#[^A-Za-z0-9 _:\-\[\]./%\#()!,]#', '', $node['cls']));
        if ('' !== $cls) {
            $out['cls'] = mb_substr($cls, 0, 600);
        }
    }
    if ('img' === $tag && !empty($node['src'])) {
        $src = ldrn_ws_studio_clean_url($node['src']);
        if ($src) {
            $out['src'] = $src;
        }
    }
    if ('img' === $tag && isset($node['alt']) && is_string($node['alt'])) {
        $out['alt'] = mb_substr(sanitize_text_field($node['alt']), 0, 300);
    }
    if ('a' === $tag && !empty($node['href'])) {
        $href = ldrn_ws_studio_clean_url($node['href'], true);
        if ($href) {
            $out['href'] = $href;
        }
    }
    if ('iframe' === $tag) {
        $src = isset($node['src']) ? trim((string) $node['src']) : '';
        if (!ldrn_ws_studio_embed_ok($src)) {
            return null;
        }
        $out['src'] = $src;
    }
    if (!empty($node['kids']) && is_array($node['kids']) && !in_array($tag, array('img', 'hr', 'iframe'), true)) {
        foreach ($node['kids'] as $kid) {
            $kid = ldrn_ws_studio_clean_tree($kid, $depth + 1, $budget, 'svg' === $tag || $in_svg);
            if ($kid) {
                $out['kids'][] = $kid;
            }
        }
    }
    return $out;
}

/* -------------------------------------------------------------------------
 * Storage
 * ---------------------------------------------------------------------- */

function ldrn_ws_studio_empty() {
    return array('nodes' => array());
}

function ldrn_ws_studio_live() {
    $doc = get_option(LDRN_WS_STUDIO_LIVE, null);
    return is_array($doc) && isset($doc['nodes']) ? $doc : ldrn_ws_studio_empty();
}

function ldrn_ws_studio_draft() {
    $doc = get_option(LDRN_WS_STUDIO_DRAFT, null);
    return is_array($doc) && isset($doc['nodes']) ? $doc : ldrn_ws_studio_live();
}

function ldrn_ws_studio_versions() {
    $list = get_option(LDRN_WS_STUDIO_VERSIONS, array());
    return is_array($list) ? $list : array();
}

function ldrn_ws_studio_same($a, $b) {
    return wp_json_encode($a) === wp_json_encode($b);
}

function ldrn_ws_studio_meta() {
    $meta = get_option(LDRN_WS_STUDIO_META, array());
    return is_array($meta) ? $meta : array();
}

/**
 * The draft's revision. It changes whenever the draft does - an autosave, a
 * publish, a discard, a restored version or a restored session - so an
 * editor can tell that the draft it is about to save over is no longer the
 * one it opened.
 */
function ldrn_ws_studio_rev() {
    return (string) get_option('ldrn_ws_studio_rev', '');
}

function ldrn_ws_studio_bump_rev() {
    update_option('ldrn_ws_studio_rev', wp_generate_password(12, false, false), false);
}
add_action('update_option_' . LDRN_WS_STUDIO_DRAFT, 'ldrn_ws_studio_bump_rev');
add_action('add_option_' . LDRN_WS_STUDIO_DRAFT, 'ldrn_ws_studio_bump_rev');
add_action('delete_option_' . LDRN_WS_STUDIO_DRAFT, 'ldrn_ws_studio_bump_rev');
add_action('update_option_' . LDRN_WS_PAGES_OPTION, 'ldrn_ws_studio_bump_rev');
add_action('update_option_ldrn_ws_pending_site','ldrn_ws_studio_bump_rev');
add_action('add_option_ldrn_ws_pending_site','ldrn_ws_studio_bump_rev');
add_action('delete_option_ldrn_ws_pending_site','ldrn_ws_studio_bump_rev');

/**
 * Refuse to write over a draft that moved on since this editor last saw it,
 * unless the person has said to. Answers with what is there now, so the
 * editor can offer to load it.
 */
function ldrn_ws_studio_check_rev() {
    if (!ldrn_ws_access_admin() && (!isset($_POST['rev']) || !empty($_POST['force']))) wp_send_json_error(array('message'=>'Reload the Site Editor before saving. Restricted edits require the current draft revision.'),409);
    if (!isset($_POST['rev']) || !empty($_POST['force'])) {
        return;
    }
    $base = sanitize_text_field(wp_unslash($_POST['rev']));
    if ($base === ldrn_ws_studio_rev()) {
        return;
    }
    wp_send_json_error(array(
        'conflict' => true,
        'message'  => 'The draft was changed somewhere else since this editor opened it.',
        'doc'      => ldrn_ws_studio_draft(),
        'status'   => ldrn_ws_studio_status(),
    ), 409);
}

function ldrn_ws_studio_status() {
    $meta = ldrn_ws_studio_meta();
    return array(
        'rev'         => ldrn_ws_studio_rev(),
        'unpublished' => (bool)get_option('ldrn_ws_pending_site') || !ldrn_ws_studio_same(ldrn_ws_studio_draft(), ldrn_ws_studio_live()) || !ldrn_ws_studio_same(ldrn_ws_pages(), get_option('ldrn_ws_published_pages',ldrn_ws_default_pages())),
        'publishedAt' => isset($meta['published_at']) ? (int) $meta['published_at'] : 0,
        'publishedBy' => isset($meta['published_by']) ? (string) $meta['published_by'] : '',
        'versions'    => array_map(function ($v) {
            return array(
                'id'    => $v['id'],
                'time'  => (int) $v['time'],
                'user'  => $v['user'],
                'count' => count($v['doc']['nodes']),
            );
        }, array_reverse(ldrn_ws_studio_versions())),
    );
}

/* -------------------------------------------------------------------------
 * What the theme prints into the page
 * ---------------------------------------------------------------------- */

/** Is this request the Site Editor's own canvas, asking for the draft? */
function ldrn_ws_studio_is_canvas_request() {
    return isset($_GET['ldrn_ws_studio']) && is_user_logged_in() && ldrn_ws_access_can_edit() && ldrn_ws_access_route('/' . trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'));
}

/** Added to `window.__LDRN_WORKSPACE__` by ldrn-workspace-shell. */
function ldrn_ws_studio_payload() {
    $canvas = ldrn_ws_studio_is_canvas_request();
    return array(
        'doc'   => $canvas ? ldrn_ws_studio_draft() : ldrn_ws_studio_live(),
        'edit'  => $canvas,
        'scoped' => $canvas && !ldrn_ws_access_admin(),
        'restNonce' => $canvas ? wp_create_nonce('wp_rest') : '',
        'pages' => $canvas ? ldrn_ws_studio_custom_pages(false) : ldrn_ws_published_custom_pages(),
    );
}

/** The runtime itself, printed straight after the bridge. */
function ldrn_ws_studio_head_markup() {
    $build=ldrn_ws_request_build();
    if($build && !ldrn_ws_studio_is_canvas_request())return '<script id="ldrn-studio-runtime" src="'.esc_url(ldrn_ws_build_url($build).'/runtime.js').'"></script><script src="'.esc_url(ldrn_ws_build_url($build).'/asset-map.js').'"></script>';
    $file = LDRN_WS_DIR . 'assets/studio-runtime.js';
    $ver  = is_readable($file) ? filemtime($file) : LDRN_WS_VERSION;
    $name = ldrn_ws_studio_is_canvas_request() ? 'studio-runtime.js' : ldrn_ws_studio_live_runtime();
    return '<script id="ldrn-studio-runtime" src="' . esc_url(LDRN_WS_URL . 'assets/' . $name . '?ver=' . $ver) . '"></script>';
}

/**
 * The runtime without its editor half - what every visitor downloads. Cut
 * from studio-runtime.js at the line that starts the editor-only code and
 * rebuilt whenever that file changes, so there is one source to edit.
 */
function ldrn_ws_studio_live_runtime() {
    $src = LDRN_WS_DIR . 'assets/studio-runtime.js';
    $out = LDRN_WS_DIR . 'assets/studio-runtime.live.js';
    if (!is_readable($src)) {
        return 'studio-runtime.js';
    }
    if (!is_readable($out) || filemtime($out) < filemtime($src)) {
        $code = file_get_contents($src);
        // Whatever the line endings: the comment that opens the editor half.
        $mark = strpos($code, "Everything below runs only inside the Site Editor's canvas.");
        $cut  = false === $mark ? false : strrpos(substr($code, 0, $mark), '/* ====');
        if (false === $cut) {
            return 'studio-runtime.js';
        }
        $live = "/* Generated from studio-runtime.js by ldrn_ws_studio_live_runtime() - edit that file, not this one. */\n"
              . substr($code, 0, $cut) . "})();\n";
        if (false === @file_put_contents($out, $live)) {
            return 'studio-runtime.js';
        }
        @touch($out, filemtime($src));
    }
    return 'studio-runtime.live.js';
}

/* -------------------------------------------------------------------------
 * Pages made in the Site Editor
 *
 * They live in the same page directory as every other page (pages.php), so
 * Site Settings, the menu and the SEO screen all see them. Their content is
 * nothing but inserted blocks in the design document, on their own route.
 * ---------------------------------------------------------------------- */

/** Every page, as the editor's Pages panel needs it. */
function ldrn_ws_studio_pages_for_editor() {
    $out = array();
    foreach (ldrn_ws_pages() as $page) {
        $out[] = array(
            'id'      => $page['id'],
            'label'   => $page['label'],
            'route'   => $page['route'],
            'menu'    => 'primary' === $page['group'],
            'visible' => $page['visible'],
            'custom'  => !ldrn_ws_studio_builtin_route($page['route']),
        );
    }
    return ldrn_ws_access_editor_pages(apply_filters('ldrn_ws_editor_pages', $out));
}

/** Pages the build does not know about - the runtime draws them instead of its 404. */
function ldrn_ws_studio_custom_pages($visible_only = false) {
    $out = array();
    foreach (ldrn_ws_pages() as $page) {
        if (ldrn_ws_studio_builtin_route($page['route']) || ($visible_only && !$page['visible'])) {
            continue;
        }
        $out[] = array('route' => $page['route'], 'label' => $page['label']);
    }
    return $out;
}

/**
 * One level only (`/journal`, never `/journal/2026`): INU pages load their
 * styles and pictures by relative path, which only resolves from a
 * one-level address.
 */
function ldrn_ws_studio_clean_slug($slug) {
    $slug = strtolower(trim((string) $slug));
    $slug = preg_replace('#[^a-z0-9-]+#', '-', $slug);
    return trim(preg_replace('#-+#', '-', $slug), '-');
}

add_action('wp_ajax_ldrn_ws_studio_page_add', function () {
    ldrn_ws_studio_guard();
    ldrn_ws_studio_check_rev();
    $label = isset($_POST['label']) ? sanitize_text_field(wp_unslash($_POST['label'])) : '';
    $slug  = ldrn_ws_studio_clean_slug(isset($_POST['slug']) && '' !== $_POST['slug'] ? wp_unslash($_POST['slug']) : $label);
    if ('' === $label || '' === $slug) {
        wp_send_json_error(array('message' => 'A new page needs a name.'), 400);
    }
    $route = '/' . $slug;
    if (ldrn_ws_studio_builtin_route($route) || in_array($route, wp_list_pluck(ldrn_ws_pages(), 'route'), true)) {
        wp_send_json_error(array('message' => 'There is already a page at ' . $route . '.'), 400);
    }
    // Out of the menu until someone decides it is ready - a menu link to a
    // page whose content is still an unpublished draft would lead nowhere.
    $group = !empty($_POST['menu']) ? 'primary' : 'other';
    $id = ldrn_ws_add_page($label, $route, $group, 'Made in the Site Editor.');
    if (is_wp_error($id)) {
        wp_send_json_error(array('message' => $id->get_error_message()), 400);
    }
    wp_send_json_success(array('pages' => ldrn_ws_studio_pages_for_editor(), 'route' => $route, 'status'=>ldrn_ws_studio_status()));
});

add_action('wp_ajax_ldrn_ws_studio_page_update', function () {
    ldrn_ws_studio_guard();
    ldrn_ws_studio_check_rev();
    $id   = isset($_POST['id']) ? sanitize_key(wp_unslash($_POST['id'])) : '';
    $page = ldrn_ws_page($id);
    if (!$page && function_exists('ldrn_ws_native_page') && preg_match('/^record-([1-9][0-9]*)$/D', $id, $match)) {
        $native = ldrn_ws_native_page((int)$match[1]);
        if ($native && !in_array($native['route'], wp_list_pluck(ldrn_ws_pages(), 'route'), true)) {
            $page = array('id'=>$id, 'label'=>$native['label'], 'route'=>$native['route'], 'group'=>'other', 'visible'=>true, 'lede'=>$native['label']);
            ldrn_ws_commit_pages(array_merge(ldrn_ws_pages(), array($page)));
        }
    }
    if (!$page) {
        wp_send_json_error(array('message' => 'That page no longer exists.'), 404);
    }
    $label = isset($_POST['label']) && '' !== trim((string) $_POST['label']) ? sanitize_text_field(wp_unslash($_POST['label'])) : $page['label'];
    $route = $page['route'];
    if (isset($_POST['slug']) && !ldrn_ws_studio_builtin_route($page['route'])) {
        $slug = ldrn_ws_studio_clean_slug(wp_unslash($_POST['slug']));
        $next = '/' . $slug;
        if ('' === $slug || ldrn_ws_studio_builtin_route($next)) {
            wp_send_json_error(array('message' => 'That address is not available.'), 400);
        }
        foreach (ldrn_ws_pages() as $other) {
            if ($other['id'] !== $id && $other['route'] === $next) {
                wp_send_json_error(array('message' => 'There is already a page at ' . $next . '.'), 400);
            }
        }
        $route = $next;
    }
    ldrn_ws_rename_page($id, $label, $route, $page['lede']);
    if (isset($_POST['menu'])) {
        ldrn_ws_set_group($id, '1' === $_POST['menu'] ? 'primary' : 'other');
    }
    if (isset($_POST['visible'])) ldrn_ws_set_visibility($id, '1' === $_POST['visible']);
    wp_send_json_success(array('pages' => ldrn_ws_studio_pages_for_editor(), 'route' => $route, 'from' => $page['route'], 'status'=>ldrn_ws_studio_status()));
});

add_action('wp_ajax_ldrn_ws_studio_page_delete', function () {
    ldrn_ws_studio_guard();
    ldrn_ws_studio_check_rev();
    $id   = isset($_POST['id']) ? sanitize_key(wp_unslash($_POST['id'])) : '';
    $page = ldrn_ws_page($id);
    if (!$page || ldrn_ws_studio_builtin_route($page['route'])) {
        wp_send_json_error(array('message' => 'Only pages made in the Site Editor can be deleted. Built-in pages can be hidden in Site Settings.'), 400);
    }
    // Through the directory's own commit, so Site Settings' Undo can bring it back.
    ldrn_ws_commit_pages(array_values(array_filter(ldrn_ws_pages(), function ($p) use ($id) {
        return $p['id'] !== $id;
    })));
    wp_send_json_success(array('pages' => ldrn_ws_studio_pages_for_editor(), 'route' => $page['route'], 'status'=>ldrn_ws_studio_status()));
});

/* -------------------------------------------------------------------------
 * AJAX
 * ---------------------------------------------------------------------- */

function ldrn_ws_studio_guard() {
    check_ajax_referer('ldrn_ws_studio', 'nonce');
    $action = sanitize_key($_REQUEST['action'] ?? '');
    if (!ldrn_ws_access_admin() && !in_array($action, array('ldrn_ws_studio_save','ldrn_ws_studio_publish'), true)) wp_send_json_error(array('message'=>'Only an Administrator can change site navigation, saved sections or restore the whole site.'),403);
    if (!ldrn_ws_access_can_edit()) {
        wp_send_json_error(array('message' => 'You are not allowed to edit the site.'), 403);
    }
}

// A fresh security token for an editor that has been open a long time, or
// whose login was renewed in another tab - the one it was opened with
// belongs to the old login. Like WordPress's own wp_ajax_rest_nonce(), it is
// given only to someone logged in, here only to someone who may edit.
add_action('wp_ajax_ldrn_ws_studio_nonce', function () {
    if (!ldrn_ws_access_can_edit()) {
        wp_send_json_error(array('message' => 'You are not allowed to edit the site.'), 403);
    }
    nocache_headers();
    wp_send_json_success(array('nonce' => wp_create_nonce('ldrn_ws_studio'), 'restNonce' => wp_create_nonce('wp_rest')));
});

function ldrn_ws_studio_posted_doc() {
    $raw = isset($_POST['doc']) ? wp_unslash($_POST['doc']) : '';
    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        wp_send_json_error(array('message' => 'The editor sent something unreadable. Nothing was saved.'), 400);
    }
    $doc = ldrn_ws_studio_clean_doc($decoded);
    ldrn_ws_access_require_doc($doc, ldrn_ws_studio_draft());
    return $doc;
}

add_action('wp_ajax_ldrn_ws_studio_save', function () {
    ldrn_ws_studio_guard();
    ldrn_ws_studio_check_rev();
    $doc = ldrn_ws_studio_posted_doc();
    update_option(LDRN_WS_STUDIO_DRAFT, $doc, false);
    wp_send_json_success(array('doc' => $doc, 'status' => ldrn_ws_studio_status()));
});

add_action('wp_ajax_ldrn_ws_studio_publish', function () {
    ldrn_ws_studio_guard();
    ldrn_ws_studio_check_rev();
    // Publish what the screen is showing, not whatever the last autosave was.
    $doc = isset($_POST['doc']) ? ldrn_ws_studio_posted_doc() : ldrn_ws_studio_draft();
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    ldrn_ws_access_publish_guard($doc);
    $result=ldrn_ws_publish_document($doc,$name);
    if(is_wp_error($result))wp_send_json_error(array('message'=>$result->get_error_message()),409);
    wp_send_json_success(array('doc' => $doc, 'status' => ldrn_ws_studio_status()));
});

/** A saved website and its content publish together, or all DB changes roll back. */
function ldrn_ws_publish_document($doc,$name) {
    global $wpdb;
    if (!ldrn_ws_access_admin()) {
        $permission = ldrn_ws_access_validate_doc($doc, ldrn_ws_studio_draft());
        if (is_wp_error($permission)) return $permission;
        if (get_option('ldrn_ws_pending_site') || ldrn_ws_pages() != ldrn_ws_public_pages() || is_wp_error(ldrn_ws_access_validate_doc($doc,ldrn_ws_studio_live()))) return new WP_Error('page_access','An Administrator must resolve unpublished changes outside your allocated pages first.');
    }
    // Recovery must exist before a complete saved website is published again.
    if(empty($GLOBALS['ldrn_ws_restoring_session']) && get_option('ldrn_ws_pending_site') && !ldrn_ws_capture_session('Before publishing restored website'))return new WP_Error('recovery','The recovery copy could not be saved. Nothing was published.');
    foreach(array($wpdb->posts,$wpdb->postmeta,$wpdb->options,$wpdb->term_relationships) as $table) {
        $engine=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));
        if(strtolower((string)$engine)!=='innodb')return new WP_Error('transaction','Transactional WordPress tables are required to publish safely.');
    }
    $outer=!empty($GLOBALS['ldrn_ws_external_transaction']);
    $wpdb->query($outer ? 'SAVEPOINT ldrn_workspace_publish' : 'START TRANSACTION');
    try {
    $restore=ldrn_ws_apply_pending_site();
    if(is_wp_error($restore))throw new RuntimeException($restore->get_error_message());
    update_option(LDRN_WS_STUDIO_DRAFT, $doc, false);
    update_option(LDRN_WS_STUDIO_LIVE, $doc, false);

    $user = wp_get_current_user();
    $versions = ldrn_ws_studio_versions();
    $versions[] = array(
        'id'   => wp_generate_password(10, false, false),
        'time' => time(),
        'user' => $user ? $user->display_name : '',
        'doc'  => $doc,
    );
    if (count($versions) > LDRN_WS_STUDIO_KEEP) {
        $versions = array_slice($versions, -LDRN_WS_STUDIO_KEEP);
    }
    update_option(LDRN_WS_STUDIO_VERSIONS, array_values($versions), false);
    update_option(LDRN_WS_STUDIO_META, array(
        'published_at' => time(),
        'published_by' => $user ? $user->display_name : '',
    ), false);

    // Every publish is also a saved session of the whole site (sessions.php).
    do_action('ldrn_ws_studio_published', $name);
    $wpdb->query($outer ? 'RELEASE SAVEPOINT ldrn_workspace_publish' : 'COMMIT');
    return true;
    } catch(Throwable $error) {
        $wpdb->query($outer ? 'ROLLBACK TO SAVEPOINT ldrn_workspace_publish' : 'ROLLBACK');
        wp_cache_flush();
        return new WP_Error('publish_failed',$error->getMessage());
    }
}

/** Bring an old version back into the draft. It is live only once published. */
add_action('wp_ajax_ldrn_ws_studio_restore', function () {
    ldrn_ws_studio_guard();
    ldrn_ws_studio_check_rev();
    $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
    foreach (ldrn_ws_studio_versions() as $version) {
        if ($version['id'] === $id) {
            delete_option('ldrn_ws_pending_site');
            update_option(LDRN_WS_STUDIO_DRAFT, $version['doc'], false);
            wp_send_json_success(array('doc' => $version['doc'], 'status' => ldrn_ws_studio_status()));
        }
    }
    wp_send_json_error(array('message' => 'That version is no longer kept.'), 404);
});

/** Throw away everything since the last publish. */
add_action('wp_ajax_ldrn_ws_studio_discard', function () {
    ldrn_ws_studio_guard();
    ldrn_ws_studio_check_rev();
    $live = ldrn_ws_studio_live();
    delete_option('ldrn_ws_pending_site');
    ldrn_ws_commit_pages(ldrn_ws_public_pages());
    update_option(LDRN_WS_STUDIO_DRAFT, $live, false);
    wp_send_json_success(array('doc' => $live, 'pages' => ldrn_ws_studio_pages_for_editor(), 'status' => ldrn_ws_studio_status()));
});

/* -------------------------------------------------------------------------
 * Assets - the site's own pictures plus the media library.
 * ---------------------------------------------------------------------- */

/**
 * Images the build ships with, at the web-root paths the site uses them from.
 *
 * The INU build exports every picture at several widths with a content hash
 * in the name (`project-lodha-1200.0adb40b4b5.webp`), and older exports stay
 * on disk. So the list is read from what the current pages actually use, and
 * each picture appears once - at its largest width up to 1700px, which is
 * sharp on any layer without shipping a 4K plate into a card.
 */
function ldrn_ws_studio_site_images() { return ldrn_ws_images(); }

/* -------------------------------------------------------------------------
 * The screen
 * ---------------------------------------------------------------------- */

add_action('admin_menu', function () {
    add_menu_page(
        'Site Editor',
        'Site Editor',
        'edit_ldrn_site_pages',
        'ldrn-studio',
        'ldrn_ws_studio_render',
        'dashicons-art',
        2.9
    );
}, 9);

add_filter('admin_body_class', function ($classes) {
    if (isset($_GET['page']) && 'ldrn-studio' === $_GET['page']) {
        $classes .= ' ldrn-studio-screen';
    }
    return $classes;
});

add_action('admin_enqueue_scripts', function () {
    if (!isset($_GET['page']) || 'ldrn-studio' !== $_GET['page']) {
        return;
    }
    $css = LDRN_WS_DIR . 'assets/studio.css';
    $js  = LDRN_WS_DIR . 'assets/studio.js';

    wp_enqueue_style('ldrn-studio', LDRN_WS_URL . 'assets/studio.css', array(), filemtime($css));
    // INU's own three faces are self-hosted by the build, so template previews
    // in the Assets panel use the very same files the site does.
    wp_add_inline_style('ldrn-studio', ldrn_ws_site_font_faces());
    wp_enqueue_media();
    $lib = LDRN_WS_DIR . 'assets/studio-library.js';
    wp_enqueue_script('ldrn-studio-library', LDRN_WS_URL . 'assets/studio-library.js', array(), filemtime($lib), true);
    wp_enqueue_script('ldrn-studio', LDRN_WS_URL . 'assets/studio.js', array('media-views', 'ldrn-studio-library'), filemtime($js), true);

    $pages = ldrn_ws_studio_pages_for_editor();
    $user = wp_get_current_user();

    wp_localize_script('ldrn-studio', 'ldrnStudio', array(
        'ajaxUrl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('ldrn_ws_studio'),
        'restUrl'  => esc_url_raw(rest_url('wp/v2/media')),
        'restNonce'=> wp_create_nonce('wp_rest'),
        'base'     => home_url('/'),
        'exit'     => admin_url('admin.php?page=' . LDRN_WS_MENU),
        'sessionsUrl' => admin_url('admin.php?page=ldrn-sessions'),
        'siteUrl'  => home_url('/'),
        'pages'    => $pages,
        // Opened from a page in the directory: start the canvas on that page.
        'start'    => ldrn_ws_studio_start_route($pages),
        'draft'    => ldrn_ws_studio_draft(),
        'status'   => ldrn_ws_studio_status(),
        'palette'  => array_values(array_unique(array_merge(array_keys(ldrn_ws_colour_swatches()), array_values(ldrn_ws_palette())))),
        'images'   => ldrn_ws_studio_site_images(),
        'savedTemplates' => ldrn_ws_templates(),
        'contentLinks' => ldrn_ws_access_admin() ? array(
            array('label'=>'Articles','url'=>admin_url('edit.php')),
            array('label'=>'Events','url'=>admin_url('edit.php?post_type=ldrn_event')),
            array('label'=>'Nursery & gallery','url'=>admin_url('edit.php?post_type=ldrn_setting')),
            array('label'=>'Parent stories','url'=>admin_url('edit.php?post_type=ldrn_testimonial')),
            array('label'=>'Vacancies','url'=>admin_url('edit.php?post_type=ldrn_job')),
        ) : array(),
        'scoped' => !ldrn_ws_access_admin(),
        'allowedRoutes' => ldrn_ws_access_routes(),
        'userId' => get_current_user_id(),
        'user'     => $user ? $user->display_name : '',
    ));
});

/** The page the canvas opens on - one the directory knows, or Home. */
function ldrn_ws_studio_start_route($pages) {
    $want = isset($_GET['route']) ? '/' . ltrim(sanitize_text_field(wp_unslash($_GET['route'])), '/') : '/';
    return in_array($want, wp_list_pluck($pages, 'route'), true) ? $want : ($pages[0]['route'] ?? '/');
}

function ldrn_ws_studio_render() {
    if (!ldrn_ws_access_can_edit()) {
        wp_die(esc_html__('You are not allowed to view this.', 'ldrn-workspace'));
    }
    // Everything is drawn by studio.js; this is only the mount point and a
    // plain message for the moment before it runs.
    echo '<div id="ldrn-studio" class="ldrn-studio"><p class="ldrn-studio-boot">Opening the Site Editor…</p></div>';
}
