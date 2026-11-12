<?php
/** Administrator-managed page allocations for the current Site Editor. */
defined('ABSPATH') || exit;
const LDRN_WS_USER_PAGES_META = '_ldrn_ws_allowed_pages';

function ldrn_ws_access_admin($user = null) {
    $user = $user ?: wp_get_current_user();
    return $user && in_array('administrator', (array)$user->roles, true);
}
function ldrn_ws_access_catalog() {
    // All pages, before the current user's editor-list filter.
    return apply_filters('ldrn_ws_editor_pages', array_map(function($p) {
        return array('id'=>$p['id'], 'route'=>$p['route'], 'label'=>$p['label']);
    }, ldrn_ws_pages()));
}
function ldrn_ws_access_routes($user = null) {
    $user = $user ?: wp_get_current_user();
    $pages = ldrn_ws_access_catalog();
    if (ldrn_ws_access_admin($user)) return array_column($pages, 'route');
    if (!$user || !defined('LDRN_CONTENT_MANAGER_ROLE') || !in_array(LDRN_CONTENT_MANAGER_ROLE, (array)$user->roles, true)) return array();
    $ids = get_user_meta($user->ID, LDRN_WS_USER_PAGES_META, true);
    if (!is_array($ids)) return array();
    return array_values(array_column(array_filter($pages, function($p) use ($ids) { return in_array($p['id'], $ids, true); }), 'route'));
}
function ldrn_ws_access_can_edit($user = null) { return ldrn_ws_access_admin($user) || (bool)ldrn_ws_access_routes($user); }
function ldrn_ws_access_route($route) { return ldrn_ws_access_admin() || in_array($route, ldrn_ws_access_routes(), true); }
function ldrn_ws_access_editor_pages($pages) {
    if (ldrn_ws_access_admin()) return $pages;
    $routes = ldrn_ws_access_routes();
    return array_values(array_filter($pages, function($p) use ($routes) { return in_array($p['route'], $routes, true); }));
}
add_filter('user_has_cap', function($caps, $required, $args, $user) {
    if (in_array('edit_ldrn_site_pages', $required, true)) $caps['edit_ldrn_site_pages'] = ldrn_ws_access_can_edit($user);
    return $caps;
}, 20, 4);

function ldrn_ws_access_fields($user = null) {
    if (!current_user_can('promote_users')) return;
    $user = $user instanceof WP_User ? $user : null;
    $selected = $user ? (array)get_user_meta($user->ID, LDRN_WS_USER_PAGES_META, true) : array();
    $pages = ldrn_ws_access_catalog();
    wp_nonce_field('ldrn_ws_save_page_access', 'ldrn_ws_page_access_nonce');
    ?>
    <div id="ldrn-site-editor-allocation" style="max-width:1100px;margin:24px 0">
      <h2>Site Editor pages</h2>
      <p><strong>Choose the pages this Editor can design</strong><span style="float:right">Administrator always has every page</span></p>
      <p class="description">An Editor can open, save and publish only the pages ticked here. Shared navigation and whole-site restores stay with the Administrator. Viewer has no Site Editor access.</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(185px,1fr));gap:10px;margin:16px 0">
      <?php foreach ($pages as $page): ?>
        <label style="display:flex;align-items:center;gap:8px;padding:12px;background:white;border:1px solid #c3c4c7;border-radius:6px">
          <input type="checkbox" name="ldrn_ws_allowed_pages[]" value="<?php echo esc_attr($page['id']); ?>" <?php checked(in_array($page['id'], $selected, true)); ?>><?php echo esc_html($page['label']); ?>
        </label>
      <?php endforeach; ?>
      </div>
      <span id="ldrn-site-editor-count"></span>
      <div style="float:right"><button type="button" class="button" data-pages-select="all">Select all</button> <button type="button" class="button" data-pages-select="none">Clear</button></div>
      <p class="description">Leaving every page clear removes Site Editor access without deleting the account.</p>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      var panel=document.getElementById('ldrn-site-editor-allocation'), role=document.querySelector('select[name="role"]');
      if(!panel || !role)return;
      var boxes=Array.from(panel.querySelectorAll('input[type="checkbox"]'));
      function sync(){
        var editor=role.value===<?php echo wp_json_encode(LDRN_CONTENT_MANAGER_ROLE); ?>;
        boxes.forEach(function(box){box.disabled=!editor;});
        document.getElementById('ldrn-site-editor-count').textContent=role.value==='administrator'?'All pages available':editor?boxes.filter(function(b){return b.checked;}).length+' pages selected':'Viewer has no Site Editor access';
        panel.querySelectorAll('button').forEach(function(b){b.disabled=!editor;});
      }
      panel.querySelectorAll('[data-pages-select]').forEach(function(b){b.addEventListener('click',function(){boxes.forEach(function(box){box.checked=b.dataset.pagesSelect==='all';});sync();});});
      boxes.forEach(function(b){b.addEventListener('change',sync);});role.addEventListener('change',sync);sync();
    });
    </script>
    <?php
}
add_action('user_new_form', 'ldrn_ws_access_fields');
add_action('edit_user_profile', 'ldrn_ws_access_fields');
add_action('user_profile_update_errors', function($errors) {
    if (!current_user_can('promote_users') || ($_POST['role'] ?? '') !== LDRN_CONTENT_MANAGER_ROLE) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ldrn_ws_page_access_nonce'] ?? '')), 'ldrn_ws_save_page_access')) $errors->add('ldrn_ws_access_nonce','Site Editor allocation could not be verified. Reload and try again.');
}, 11);
function ldrn_ws_access_save($id) {
    if (!current_user_can('promote_user', $id) && !current_user_can('create_users')) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ldrn_ws_page_access_nonce'] ?? '')), 'ldrn_ws_save_page_access')) return;
    $ids = array_column(ldrn_ws_access_catalog(), 'id');
    $selected = array_map('sanitize_key', (array)($_POST['ldrn_ws_allowed_pages'] ?? array()));
    update_user_meta($id, LDRN_WS_USER_PAGES_META, array_values(array_intersect($ids, $selected)));
}
add_action('user_register', 'ldrn_ws_access_save', 30);
add_action('profile_update', 'ldrn_ws_access_save', 30);

/** Resolve every inserted descendant to the route of its owning block. */
function ldrn_ws_access_key_route($key, $doc, $depth = 0) {
    if ($depth > 16 || !is_string($key)) return null;
    if (preg_match('#^[me]:(/[^>~]*)#', $key, $m)) return $m[1];
    if (str_starts_with($key, 'w:')) {
        $map = array('home'=>'/', 'settings'=>'/our-nursery', 'about'=>'/about', 'ofsted'=>'/about/ofsted', 'curriculum'=>'/curriculum', 'events'=>'/events', 'fees'=>'/fees', 'fee-calculator'=>'/fee-calculator', 'funded-childcare'=>'/funded-childcare', 'blogs'=>'/blogs', 'article-card'=>'/blogs', 'food-hygiene-rating'=>'/food-hygiene-rating', 'admissions'=>'/admissions', 'book-avisit'=>'/book-a-visit', 'availability'=>'/check-availability', 'careers'=>'/careers', 'vacancies'=>'/careers/vacancies', 'apply'=>'/careers/apply', 'testimonials'=>'/testimonials', 'contact'=>'/contact', 'privacy'=>'/privacy');
        if (preg_match('#^w:(setting-detail|event-detail|article)\.([a-z0-9_-]+)\.#', $key, $m)) return array('setting-detail'=>'/our-nursery/', 'event-detail'=>'/events/', 'article'=>'/blogs/')[$m[1]].$m[2];
        if (preg_match('#^w:([a-z0-9-]+)\.#', $key, $m)) return $map[$m[1]] ?? null;
        return null;
    }
    if (!preg_match('#^n:([a-z0-9]{4,16})(?:[>~]|$)#', $key, $m)) return null;
    $id = $m[1]; $found = array();
    $contains = function($tree) use (&$contains, $id) {
        if (!is_array($tree)) return false;
        if (($tree['id'] ?? '') === $id) return true;
        foreach (($tree['kids'] ?? array()) as $child) if ($contains($child)) return true;
        return false;
    };
    foreach (($doc['blocks'] ?? array()) as $block) if ($contains($block['tree'] ?? array())) {
        $route = ldrn_ws_access_key_route($block['at'] ?? '', $doc, $depth+1);
        if ($route === null) return null;
        $found[] = $route;
    }
    return count($found) === 1 ? $found[0] : null;
}
function ldrn_ws_access_key_allowed($key, $doc) {
    $route = ldrn_ws_access_key_route($key, $doc);
    return $route !== null && in_array($route, ldrn_ws_access_routes(), true);
}
/** A full-document save cannot add/change/delete anything outside allocation. */
function ldrn_ws_access_validate_doc($next, $base) {
    if (ldrn_ws_access_admin()) return true;
    if (!ldrn_ws_access_can_edit()) return new WP_Error('page_access','No Site Editor pages are allocated to this account.');
    foreach (array('nodes','blocks','groups','layerOrder') as $bucket) {
        $old = (array)($base[$bucket] ?? array()); $new = (array)($next[$bucket] ?? array());
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            if (array_key_exists($key,$old) && array_key_exists($key,$new) && $old[$key] == $new[$key]) continue;
            foreach (array(array($old,$base), array($new,$next)) as $side) {
                list($items,$doc) = $side;
                if (!array_key_exists($key,$items)) continue;
                $value = $items[$key];
                if ($bucket === 'blocks') $ok = ldrn_ws_access_key_allowed($value['at'] ?? '',$doc);
                elseif ($bucket === 'groups') $ok = ldrn_ws_access_key_allowed($key,$doc) && ldrn_ws_access_key_allowed($value,$doc) && ldrn_ws_access_key_route($key,$doc) === ldrn_ws_access_key_route($value,$doc);
                elseif ($bucket === 'layerOrder') {
                    $ok = $key === 'page' || ldrn_ws_access_key_allowed($key,$doc);
                    foreach ((array)$value as $child) $ok = $ok && ldrn_ws_access_key_allowed($child,$doc);
                } else $ok = ldrn_ws_access_key_allowed($key,$doc);
                if (!$ok) return new WP_Error('page_access','This change affects an unallocated page or shared site layout. Nothing was saved.');
            }
        }
    }
    // Reject duplicate/reused inserted IDs even when no explicit node override exists.
    $collect = function($doc) {
        $ids=array();
        $walk=function($tree) use (&$walk,&$ids) {
            if (!is_array($tree)) return;
            if (!empty($tree['id'])) $ids[]='n:'.$tree['id'];
            foreach (($tree['kids'] ?? array()) as $child) $walk($child);
        };
        foreach (($doc['blocks'] ?? array()) as $block) $walk($block['tree'] ?? array());
        return $ids;
    };
    $oldids=$collect($base);$newids=$collect($next);
    if (count($newids)!==count(array_unique($newids))) return new WP_Error('page_access','Inserted layer IDs must be unique. Nothing was saved.');
    foreach (array_intersect($oldids,$newids) as $key) {
        $from=ldrn_ws_access_key_route($key,$base);$to=ldrn_ws_access_key_route($key,$next);
        if ($from !== $to && ($from === null || !in_array($from,ldrn_ws_access_routes(),true))) return new WP_Error('page_access','An inserted layer belongs to another page. Nothing was saved.');
    }
    return true;
}
function ldrn_ws_access_require_doc($doc, $base) {
    $result=ldrn_ws_access_validate_doc($doc,$base);
    if (is_wp_error($result)) wp_send_json_error(array('message'=>$result->get_error_message()),403);
}
function ldrn_ws_access_publish_guard($doc) {
    if (ldrn_ws_access_admin()) return;
    // A full-site publish must never release another operator's pending work.
    if (get_option('ldrn_ws_pending_site') || ldrn_ws_pages() != ldrn_ws_public_pages() || is_wp_error(ldrn_ws_access_validate_doc($doc,ldrn_ws_studio_live()))) wp_send_json_error(array('message'=>'There are unpublished changes outside your allocated pages. Ask an Administrator to publish or resolve those changes first.'),409);
}
add_action('admin_init', function() {
    if (($_GET['page'] ?? '') === 'ldrn-studio' && !ldrn_ws_access_can_edit()) wp_die('No Site Editor pages are allocated to this account.','Site Editor access',array('response'=>403));
}, -1);
add_action('template_redirect', function() {
    if (!isset($_GET['ldrn_ws_studio'])) return;
    $route='/' . trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH),'/');
    if (!ldrn_ws_access_can_edit() || !ldrn_ws_access_route($route)) wp_die('This page is not allocated to your Site Editor.','Page not allocated',array('response'=>403));
    nocache_headers();
}, -1);
