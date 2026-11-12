<?php
/**
 * The site's pages, as an arrangement the operator owns.
 *
 * The seed below is the site that exists today - five sections in the menu,
 * plus the pages that are reachable but deliberately not in it. After the
 * first activation the code never writes to this list again: order, visibility
 * and any page added later all live in the `ldrn_ws_pages` option, so the
 * arrangement survives plugin updates.
 *
 * Nothing is ever destroyed. Hiding a page removes it from the public
 * navigation and from the site's own route table; the record and its copy stay
 * exactly where they were, and Restore puts it back.
 */

defined('ABSPATH') || exit;

const LDRN_WS_PAGES_OPTION   = 'ldrn_ws_pages';
const LDRN_WS_HISTORY_OPTION = 'ldrn_ws_pages_history';
const LDRN_WS_HISTORY_DEPTH  = 40;

/**
 * The site as it stands.
 *
 * group: primary - in the menu (Let's talk is the header's pill button)
 *        other   - reachable at its own URL, kept out of the menu
 */
function ldrn_ws_default_pages() {
    return array(
        array('id'=>'home', 'label'=>'Home', 'route'=>'/', 'group'=>'primary', 'visible'=>true, 'lede'=>'Home'),
        array('id'=>'settings', 'label'=>'Our Nursery', 'route'=>'/our-nursery', 'group'=>'primary', 'visible'=>true, 'lede'=>'Our Nursery'),
        array('id'=>'settings-hounslow', 'label'=>'Hounslow', 'route'=>'/our-nursery/hounslow', 'group'=>'other', 'visible'=>true, 'lede'=>'Hounslow'),
        array('id'=>'about', 'label'=>'About', 'route'=>'/about', 'group'=>'other', 'visible'=>true, 'lede'=>'About'),
        array('id'=>'about-ofsted', 'label'=>'Ofsted', 'route'=>'/about/ofsted', 'group'=>'other', 'visible'=>true, 'lede'=>'Ofsted'),
        array('id'=>'curriculum', 'label'=>'Our Curriculum', 'route'=>'/curriculum', 'group'=>'primary', 'visible'=>true, 'lede'=>'Our Curriculum'),
        array('id'=>'events', 'label'=>'Events', 'route'=>'/events', 'group'=>'primary', 'visible'=>true, 'lede'=>'Events'),
        array('id'=>'fees', 'label'=>'Fees', 'route'=>'/fees', 'group'=>'other', 'visible'=>true, 'lede'=>'Fees'),
        array('id'=>'fee-calculator', 'label'=>'Fee Calculator', 'route'=>'/fee-calculator', 'group'=>'other', 'visible'=>true, 'lede'=>'Fee Calculator'),
        array('id'=>'funded-childcare', 'label'=>'Funded Childcare', 'route'=>'/funded-childcare', 'group'=>'other', 'visible'=>true, 'lede'=>'Funded Childcare'),
        array('id'=>'blogs', 'label'=>'Blog', 'route'=>'/blogs', 'group'=>'other', 'visible'=>true, 'lede'=>'Blog'),
        array('id'=>'food-hygiene-rating', 'label'=>'Food Hygiene Rating', 'route'=>'/food-hygiene-rating', 'group'=>'other', 'visible'=>true, 'lede'=>'Food Hygiene Rating'),
        array('id'=>'admissions', 'label'=>'Admissions', 'route'=>'/admissions', 'group'=>'other', 'visible'=>true, 'lede'=>'Admissions'),
        array('id'=>'book-a-visit', 'label'=>'Book a Visit', 'route'=>'/book-a-visit', 'group'=>'other', 'visible'=>true, 'lede'=>'Book a Visit'),
        array('id'=>'check-availability', 'label'=>'Check Availability', 'route'=>'/check-availability', 'group'=>'other', 'visible'=>true, 'lede'=>'Check Availability'),
        array('id'=>'careers', 'label'=>'Careers', 'route'=>'/careers', 'group'=>'primary', 'visible'=>true, 'lede'=>'Careers'),
        array('id'=>'careers-vacancies', 'label'=>'Vacancies', 'route'=>'/careers/vacancies', 'group'=>'other', 'visible'=>true, 'lede'=>'Vacancies'),
        array('id'=>'careers-apply', 'label'=>'Apply', 'route'=>'/careers/apply', 'group'=>'other', 'visible'=>true, 'lede'=>'Apply'),
        array('id'=>'testimonials', 'label'=>'Parent Stories', 'route'=>'/testimonials', 'group'=>'other', 'visible'=>true, 'lede'=>'Parent Stories'),
        array('id'=>'contact', 'label'=>'Contact Us', 'route'=>'/contact', 'group'=>'primary', 'visible'=>true, 'lede'=>'Contact Us'),
        array('id'=>'privacy', 'label'=>'Privacy', 'route'=>'/privacy', 'group'=>'other', 'visible'=>true, 'lede'=>'Privacy'),
    );
}

function ldrn_ws_seed_pages() {
    if (false !== get_option(LDRN_WS_PAGES_OPTION, false)) {
        return;
    }
    $pages = ldrn_ws_default_pages();
    update_option(LDRN_WS_PAGES_OPTION, $pages, false);
    update_option(LDRN_WS_HISTORY_OPTION, array(
        'stack' => array($pages),
        'index' => 0,
    ), false);
}

/** Every page, in the operator's order, hidden ones included. */
function ldrn_ws_pages() {
    $pages = get_option(LDRN_WS_PAGES_OPTION, null);
    if (!is_array($pages) || !$pages) {
        ldrn_ws_seed_pages();
        $pages = get_option(LDRN_WS_PAGES_OPTION, ldrn_ws_default_pages());
    }
    return array_values(array_map('ldrn_ws_normalise_page', $pages));
}

function ldrn_ws_normalise_page($page) {
    $page  = (array) $page;
    $group = isset($page['group']) ? $page['group'] : 'other';
    return array(
        'id'      => sanitize_key(isset($page['id']) ? $page['id'] : ''),
        'label'   => sanitize_text_field(isset($page['label']) ? $page['label'] : ''),
        'route'   => '/' . ltrim(sanitize_text_field(isset($page['route']) ? $page['route'] : '/'), '/'),
        'group'   => in_array($group, array('primary', 'other'), true) ? $group : 'other',
        'visible' => !empty($page['visible']),
        'lede'    => sanitize_text_field(isset($page['lede']) ? $page['lede'] : ''),
    );
}

function ldrn_ws_page($id) {
    foreach (ldrn_ws_pages() as $page) {
        if ($page['id'] === $id) {
            return $page;
        }
    }
    return null;
}

/** The pages in a given group, in order. */
function ldrn_ws_pages_in($group, $visible_only = false) {
    return array_values(array_filter(ldrn_ws_pages(), function ($page) use ($group, $visible_only) {
        if ($page['group'] !== $group) {
            return false;
        }
        return !$visible_only || $page['visible'];
    }));
}

/** What the public site should render as its navigation, in order. */
function ldrn_ws_public_navigation() {
    $out = array();
    foreach (ldrn_ws_pages() as $page) {
        if (!$page['visible'] || 'primary' !== $page['group']) {
            continue;
        }
        $out[] = array(
            'id'    => $page['id'],
            'label' => $page['label'],
            'route' => $page['route'],
        );
    }
    return $out;
}

/* -------------------------------------------------------------------------
 * Writing - every change goes through here, so every change is undoable.
 * ---------------------------------------------------------------------- */

function ldrn_ws_history() {
    $history = get_option(LDRN_WS_HISTORY_OPTION, null);
    if (!is_array($history) || !isset($history['stack'][0])) {
        $history = array('stack' => array(ldrn_ws_pages()), 'index' => 0);
    }
    $history['index'] = max(0, min((int) $history['index'], count($history['stack']) - 1));
    return $history;
}

/**
 * Commit a new arrangement.
 *
 * Anything the operator had undone past is discarded first - the same way a
 * text editor drops the redo tail once you type. The stack is capped so a long
 * afternoon of rearranging cannot grow the options table without bound.
 */
function ldrn_ws_commit_pages(array $pages) {
    $pages = array_values(array_map('ldrn_ws_normalise_page', $pages));
    update_option(LDRN_WS_PAGES_OPTION, $pages, false);

    $history = ldrn_ws_history();
    $stack   = array_slice($history['stack'], 0, $history['index'] + 1);
    $stack[] = $pages;

    if (count($stack) > LDRN_WS_HISTORY_DEPTH) {
        $stack = array_slice($stack, count($stack) - LDRN_WS_HISTORY_DEPTH);
    }

    update_option(LDRN_WS_HISTORY_OPTION, array(
        'stack' => array_values($stack),
        'index' => count($stack) - 1,
    ), false);

    return $pages;
}

function ldrn_ws_can_undo() {
    $history = ldrn_ws_history();
    return $history['index'] > 0;
}

function ldrn_ws_can_redo() {
    $history = ldrn_ws_history();
    return $history['index'] < count($history['stack']) - 1;
}

function ldrn_ws_step_history($direction) {
    $history = ldrn_ws_history();
    $target  = $history['index'] + ($direction > 0 ? 1 : -1);
    if ($target < 0 || $target > count($history['stack']) - 1) {
        return false;
    }
    $history['index'] = $target;
    update_option(LDRN_WS_HISTORY_OPTION, $history, false);
    update_option(LDRN_WS_PAGES_OPTION, $history['stack'][$target], false);
    return true;
}

/* -------------------------------------------------------------------------
 * The operations the Site Settings screen offers.
 * ---------------------------------------------------------------------- */

/** Reorder to exactly the ids given; anything unnamed keeps its place at the end. */
function ldrn_ws_reorder(array $ordered_ids) {
    $pages = ldrn_ws_pages();
    $by_id = array();
    foreach ($pages as $page) {
        $by_id[$page['id']] = $page;
    }
    $next = array();
    foreach ($ordered_ids as $id) {
        $id = sanitize_key($id);
        if (isset($by_id[$id])) {
            $next[] = $by_id[$id];
            unset($by_id[$id]);
        }
    }
    foreach ($by_id as $leftover) {
        $next[] = $leftover;
    }
    return ldrn_ws_commit_pages($next);
}

/** Hide or restore. Never deletes - the record and its copy are untouched. */
function ldrn_ws_set_visibility($id, $visible) {
    $pages = ldrn_ws_pages();
    foreach ($pages as &$page) {
        if ($page['id'] === $id) {
            $page['visible'] = (bool) $visible;
        }
    }
    unset($page);
    return ldrn_ws_commit_pages($pages);
}

/** Move a page between the menu and the reachable-but-unlisted group. */
function ldrn_ws_set_group($id, $group) {
    $group = in_array($group, array('primary', 'other'), true) ? $group : 'other';
    $pages = ldrn_ws_pages();
    foreach ($pages as &$page) {
        if ($page['id'] === $id) {
            $page['group'] = $group;
        }
    }
    unset($page);
    return ldrn_ws_commit_pages($pages);
}

/** A page the client adds later. Lands at the end of its group, visible. */
function ldrn_ws_add_page($label, $route, $group = 'primary', $lede = '') {
    $label = sanitize_text_field($label);
    $route = '/' . ltrim(sanitize_text_field($route), '/');
    if ('' === $label) {
        return new WP_Error('ldrn_ws_no_label', 'A page needs a name.');
    }

    $pages = ldrn_ws_pages();
    $base  = sanitize_key(str_replace('/', '-', trim($route, '/')));
    if ('' === $base) {
        $base = sanitize_key($label);
    }
    $id    = $base;
    $n     = 2;
    $taken = wp_list_pluck($pages, 'id');
    while (in_array($id, $taken, true)) {
        $id = $base . '-' . $n;
        $n++;
    }

    $pages[] = array(
        'id'      => $id,
        'label'   => $label,
        'route'   => $route,
        'group'   => $group,
        'visible' => true,
        'lede'    => '' !== $lede ? sanitize_text_field($lede) : 'Added from Site Settings.',
    );
    ldrn_ws_commit_pages($pages);
    return $id;
}

function ldrn_ws_rename_page($id, $label, $route, $lede) {
    $pages = ldrn_ws_pages();
    foreach ($pages as &$page) {
        if ($page['id'] !== $id) {
            continue;
        }
        if ('' !== trim((string) $label)) {
            $page['label'] = sanitize_text_field($label);
        }
        if ('' !== trim((string) $route)) {
            $page['route'] = '/' . ltrim(sanitize_text_field($route), '/');
        }
        $page['lede'] = sanitize_text_field($lede);
    }
    unset($page);
    return ldrn_ws_commit_pages($pages);
}
