<?php
/**
 * Exact-route registry and visual design storage for the public React site.
 *
 * Home keeps its established v3 option/contract. Every other registered route
 * receives an isolated v4 design document while global components continue to
 * resolve from the existing shared Home/global model.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class AM_VB_Site_Design {
	const SCHEMA_VERSION = 4;
	const META_KEY       = '_am_vb_exact_route_design';
	const SEED_OPTION    = 'am_vb_exact_route_seed_version';
	// 2: Testimonials, Privacy and the 404 joined the registry and need their
	//    documents seeded. Seeding skips any route that already has one.
	// 3: `about` was seeded by mistake and is trashed again on the way through.
	const SEED_VERSION   = 3;

	/**
	 * The section names below mirror the workstreams in `wp visual edits
	 * section`. They are presentation regions, not new public content records.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function registry() {
		$global_before = array(
			self::region('global-header', 'Header & navigation', 'Global component', 'Logo, primary navigation, nursery menu, Parent Info menu and availability action.', false),
		);
		$global_after = array(
			self::region('social-sidebar', 'Social links rail', 'Global component', 'Shared social platforms and destinations.', false),
			self::region('global-footer', 'Footer', 'Global component', 'Shared brand, contact, nursery, Ofsted, legal and disclosure content.', false),
			self::region('cookie-control', 'Cookie control', 'Global system component', 'Consent banner and preferences dialog.', false),
		);

		$pages = array(
			'home-poc' => self::page('Home page', '/', 'Home', array(), 'home'),
			// Privacy and the 404 are public routes like any other, and leaving
			// them out of this registry was the only reason a client could not
			// open them at all.
			//
			// `/about` is deliberately NOT here. The route exists in the React
			// bundle but it is not a page this client made or links to, so
			// listing it only put a page they do not recognise in their editor.
			// Do not add it back without asking.
			'testimonials' => self::page('Parent Testimonials', '/testimonials', 'Home', array(
				self::region('testimonials-header', 'Page header', 'Page section', 'Testimonials title and introduction.'),
				self::region('testimonials-collection', 'Testimonial cards', 'Bound collection', 'Cards resolve from published Testimonial records; design controls do not fork the quotes.'),
				self::region('testimonials-states', 'Empty & loading states', 'Conditional state', 'Safe states shown while or when no testimonials resolve.'),
			)),
			'privacy' => self::page('Privacy Policy', '/privacy', 'Parent Information', array(
				self::region('privacy-header', 'Page header', 'Page section', 'Policy title and last-updated line.'),
				self::region('privacy-body', 'Policy body', 'Page section', 'Policy headings and paragraphs. The wording is yours to change; nothing here is bound to a record.'),
			)),
			'not-found' => self::page('Page not found (404)', '/404-preview', 'Home', array(
				self::region('not-found-body', 'Not-found message', 'Page section', 'The message, reassurance and the way back that a visitor sees on a broken link.'),
			), 'template'),
			'nurseries' => self::page('Our Nurseries', '/nurseries', 'Our Nurseries', array(
				self::region('nurseries-introduction', 'Heading & introduction', 'Page section', 'The exact opening title, supporting copy and page spacing.'),
				self::region('nurseries-directory', 'Nursery directory', 'Bound collection', 'Cards are sourced from published Nursery records; design controls do not duplicate branch facts.'),
			)),
			'nurseries-hounslow' => self::nursery_page('Hounslow nursery', 'hounslow'),
			'nurseries-heston' => self::nursery_page('Heston nursery', 'heston'),
			'nurseries-hammersmith' => self::nursery_page('Hammersmith nursery', 'hammersmith', false),
			'curriculum' => self::page('Our Curriculum', '/curriculum', 'Curriculum', array(
				self::region('curriculum-philosophy', 'Our Philosophy', 'Page section', 'Heading, copy and approved image treatment.'),
				self::region('curriculum-eyfs', 'The EYFS', 'Page section', 'EYFS explanation, image and responsive composition.'),
				self::region('curriculum-montessori', 'The Montessori Approach', 'Page section', 'Montessori explanation, image and responsive composition.'),
				self::region('curriculum-together', 'Where They Meet', 'Page section', 'Closing comparison and supporting image.'),
			)),
			'careers' => self::page('Careers', '/careers', 'Careers', array(
				self::region('careers-header', 'Page header', 'Page section', 'Careers title and introduction.'),
				self::region('careers-vacancies-intro', 'Vacancies link & introduction', 'Page section', 'Current-vacancies action and supporting copy.'),
				self::region('careers-application', 'General application form', 'Protected form', 'Presentation is editable; field names, validation and submission routing are protected.'),
				self::region('careers-benefits', 'Why work with us', 'Page collection', 'Benefit cards and their presentation.'),
				self::region('careers-gallery', 'Gallery & modal', 'Page collection', 'Gallery images, crop, order and accessible modal presentation.'),
				self::region('careers-qualities', 'What we are looking for', 'Page collection', 'Candidate quality cards and supporting copy.'),
				self::region('careers-closing', 'Closing application band', 'Page section', 'Final reassurance and application action.'),
			)),
			'careers-vacancies' => self::page('Current Vacancies', '/careers/vacancies', 'Careers', array(
				self::region('vacancies-header', 'Page header', 'Page section', 'Archive title and introduction.'),
				self::region('vacancies-archive', 'Vacancies archive', 'Bound collection', 'Filters, count, location groups, cards and empty states remain bound to Job records.'),
			)),
			'careers-vacancy-template' => self::page('Vacancy detail template', '/careers/vacancies/preview', 'Careers', array(
				self::region('vacancy-detail-header', 'Dynamic page header', 'Bound template', 'The title and metadata resolve from the selected Job record.'),
				self::region('vacancy-detail-content', 'Role detail & application', 'Bound template', 'Role facts and application routing stay bound to Jobs.'),
				self::region('vacancy-detail-fallback', 'Closed / not-found fallback', 'Conditional state', 'Safe fallback shown when a role is unavailable.'),
			), 'template', '#^/careers/vacancies/[^/]+/?$#'),
			'careers-apply' => self::page('Apply', '/careers/apply', 'Careers', array(
				self::region('apply-header', 'Dynamic page header', 'Page section', 'General or selected-role application heading.'),
				self::region('apply-form', 'Application & return', 'Protected form', 'Presentation is editable while upload, validation and delivery remain protected.'),
			)),
			'events' => self::page('News & Events', '/events', 'Events', array(
				self::region('events-header', 'Page header', 'Page section', 'Archive heading and introduction.'),
				self::region('events-state', 'Events collection / empty state', 'Bound collection', 'Upcoming, past and no-event states resolve from Event records.'),
				self::region('events-closing', 'Visit CTA', 'Page section', 'Closing visit action and supporting text.'),
			)),
			'event-detail-template' => self::page('Event detail template', '/events/open-morning-hammersmith', 'Events', array(
				self::region('event-detail-header', 'Dynamic page header', 'Bound template', 'Title resolves from the selected Event record.'),
				self::region('event-detail-content', 'Event detail', 'Bound template', 'Date, time, location and description remain bound to the Event.'),
				self::region('event-detail-fallback', 'Not-found state', 'Conditional state', 'Safe fallback when an Event is unavailable.'),
			), 'template', '#^/events/[^/]+/?$#'),
			'contact' => self::page('Contact Us', '/contact', 'Contact', array(
				self::region('contact-header', 'Page header', 'Page section', 'Contact title and introduction.'),
				self::region('contact-directory', 'Nursery contact directory', 'Bound collection', 'Branch cards stay bound to Nursery records.'),
				self::region('contact-social', 'Social links row', 'Global binding', 'Platforms and destinations use the shared social records.'),
				self::region('contact-form-intro', 'Form introduction', 'Page section', 'Enquiry heading and guidance.'),
				self::region('contact-form', 'Enquiry form', 'Protected form', 'Labels and presentation are editable; routing, consent and validation stay protected.'),
			)),
			'contact-hounslow' => self::contact_page('Hounslow'),
			'contact-heston' => self::contact_page('Heston'),
			'contact-hammersmith' => self::contact_page('Hammersmith'),
			'check-availability' => self::page('Check Availability', '/check-availability', 'Admissions', array(
				self::region('availability-header', 'Page header', 'Page section', 'Admissions heading and introduction.'),
				self::region('availability-visual', 'Admissions visual card', 'Page media', 'Primary image, overlay and crop controls.'),
				self::region('availability-highlights', 'Admissions highlights', 'Page collection', 'Three supporting highlight cards.'),
				self::region('availability-form-header', 'Form header', 'Page section', 'Form heading and guidance.'),
				self::region('availability-parent-child', 'Parent & child details', 'Protected form', 'Parent name, Child name and Child age labels and appearance are editable; required rules, input identity and submission bindings are protected.'),
				self::region('availability-nursery', 'Preferred nursery', 'Protected form', 'Nursery choices remain bound; the selected nursery decides where the enquiry is delivered.'),
				self::region('availability-dates', 'Care period dates', 'Protected form', 'Date labels are editable; date rules are protected.'),
				self::region('availability-schedule', 'Schedule preferences', 'Protected form', 'Weekdays, sessions and time preferences keep their functional values.'),
				self::region('availability-submit', 'Requirements, consent & submit', 'Protected form', 'Requirements copy and presentation may change; consent/submission contract is protected.'),
				self::region('availability-states', 'Validation, sending & error states', 'Conditional state', 'Editor previews never publish fixture data.'),
				self::region('availability-success', 'Success state', 'Conditional state', 'Reference and send-another behaviour remains protected.'),
				self::region('availability-support', 'Branch contact support', 'Bound collection', 'Phone and branch facts remain bound to Nursery records.'),
			)),
			'fees' => self::page('Fees', '/fees', 'Parent Information', array(
				self::region('fees-header', 'Page header', 'Page section', 'Admissions and fees heading and introduction.'),
				self::region('fees-photo', 'Feature photo', 'Page media', 'Portrait image, crop and alternative text.'),
				self::region('fees-sheets', 'Nursery fee sheets', 'Bound collection', 'Downloads stay bound to each Nursery record.'),
				self::region('fees-calculator', 'Funding estimate callout', 'Page section', 'Calculator explanation and action.'),
			)),
			'fee-calculator' => self::page('Fee Calculator', '/fee-calculator', 'Parent Information', array(
				self::region('calculator-header', 'Page header', 'Page section', 'Estimator title and explanation.'),
				self::region('calculator-input', 'Input card', 'Protected calculator', 'Input presentation around protected estimator state.'),
				self::region('calculator-nursery', 'Nursery selector', 'Protected calculator', 'Options stay bound to Nurseries.'),
				self::region('calculator-days', 'Days per week', 'Protected calculator', 'Control labels are editable; allowed values and calculations are protected.'),
				self::region('calculator-hours', 'Funded hours', 'Protected calculator', 'Control labels are editable; eligibility math is protected.'),
				self::region('calculator-results', 'Results & summary cards', 'Protected calculator', 'Labels and design are editable; computed values are read-only bindings.'),
				self::region('calculator-disclaimer', 'Disclaimer & fee sheet', 'Bound action', 'Fee download resolves from the selected Nursery.'),
				self::region('calculator-states', 'Missing / incomplete states', 'Conditional state', 'Safe state previews do not alter calculation data.'),
			)),
			'funded-childcare' => self::page('Funded Childcare', '/funded-childcare', 'Parent Information', array(
				self::region('funding-header', 'Page header', 'Page section', 'Funding title and introduction.'),
				self::region('funding-offerings', 'Funding offerings', 'Page collection', 'Funding route cards and reviewed eligibility claims.'),
				self::region('funding-apply', 'How to apply', 'Page collection', 'Ordered application steps.'),
				self::region('funding-benefits', 'Why choose us', 'Page collection', 'Benefit cards and supporting copy.'),
				self::region('funding-resources', 'Fees & resources', 'Mixed bindings', 'Resource copy is local; fee PDFs remain Nursery bindings.'),
				self::region('funding-faq', 'Funding FAQs', 'Page collection', 'Accessible question and answer collection.'),
				self::region('funding-closing', 'Closing CTA', 'Page section', 'Final nursery-selection action.'),
			)),
			'blogs' => self::page('Blog', '/blogs', 'Blog', array(
				self::region('blogs-header', 'Page header', 'Page section', 'Archive title and introduction.'),
				self::region('blogs-filters', 'Year & month filters', 'Bound controls', 'Filter values resolve from Article dates.'),
				self::region('blogs-featured', 'Featured article', 'Bound record', 'The featured card stays bound to an Article.'),
				self::region('blogs-archive', 'Article archive & states', 'Bound collection', 'Cards, loading, error, empty and pagination states.'),
				self::region('blogs-closing', 'Question CTA', 'Page section', 'Closing contact action.'),
			)),
			'blog-detail-template' => self::page('Blog article template', '/blogs/independence-through-montessori-how-we-guide-our-toddlers-2', 'Blog', array(
				self::region('blog-loading', 'Loading state', 'Conditional state', 'Transient accessible loading state.'),
				self::region('blog-error', 'Error & retry state', 'Conditional state', 'Safe error copy and retry behaviour.'),
				self::region('blog-not-found', 'Not-found state', 'Conditional state', 'Safe missing-article fallback.'),
				self::region('blog-hero', 'Article hero & back link', 'Bound template', 'Title, date, category and image resolve from the Article.'),
				self::region('blog-content', 'Rich article content', 'Bound template', 'Sanitised Article HTML remains record-owned.'),
				self::region('blog-related', 'Related articles', 'Bound collection', 'Related cards resolve from Article records.'),
				self::region('blog-closing', 'Help CTA', 'Page section', 'Closing contact action.'),
			), 'template', '#^/blogs/[^/]+/?$#'),
			'food-hygiene-rating' => self::page('Food & Hygiene', '/food-hygiene-rating', 'Parent Information', array(
				self::region('hygiene-header', 'Page header', 'Page section', 'Food and hygiene title and introduction.'),
				self::region('hygiene-grid', 'Branch rating cards', 'Bound collection', 'One card per published Nursery record.'),
				self::region('hygiene-badges', 'Rating badge states', 'Protected facts', 'Scores and pending states remain bound to verified public data.'),
				self::region('hygiene-metadata', 'Rating metadata', 'Protected facts', 'Authority and inspection date remain Nursery facts.'),
				self::region('hygiene-record-action', 'Public record action', 'Bound action', 'Validated public-record destination.'),
				self::region('hygiene-states', 'Empty & future branch states', 'Conditional state', 'Simulator fixtures never publish.'),
			)),
		);

		foreach ($pages as $slug => $page) {
			$page['slug']     = $slug;
			$page['regions']  = array_merge($global_before, $page['regions'], $global_after);
			$pages[$slug]     = $page;
		}

		return $pages;
	}

	/** @return array<string,mixed> */
	private static function page($title, $route, $family, $regions, $type = 'page', $match = '') {
		return array(
			'title'   => $title,
			'route'   => $route,
			'family'  => $family,
			'navigation' => in_array($family, array('Parent Information', 'Blog'), true) ? 'menu' : 'navbar',
			'type'    => $type,
			'match'   => $match,
			'regions' => $regions,
		);
	}

	/** @return array<string,mixed> */
	private static function region($id, $label, $kind, $detail, $editable = true) {
		return compact('id', 'label', 'kind', 'detail', 'editable');
	}

	/** @return array<string,mixed> */
	private static function nursery_page($title, $slug, $meals = true) {
		$regions = array(
			self::region('nursery-hero', 'Hero', 'Nursery override', 'Branch image, overlay, title, strapline and booking action.'),
			self::region('nursery-welcome', 'Welcome', 'Nursery override', 'Branch introduction and primary supporting media.'),
			self::region('nursery-philosophy', 'Philosophy strip', 'Template + override', 'Shared structure with explicit branch overrides.'),
			self::region('nursery-gallery', 'Gallery', 'Nursery collection', 'Branch gallery records, order, crop and modal presentation.'),
			self::region('nursery-features', 'Features', 'Nursery collection', 'Branch feature records with presentation controls.'),
			self::region('nursery-meals', $meals ? 'Healthy meals' : 'Healthy meals (off)', 'Conditional nursery section', $meals ? 'Branch meal content and imagery.' : 'Intentionally disabled for Hammersmith; the editor explains the inherited off state.'),
			self::region('nursery-team', 'Meet the team', 'Nursery collection', 'Team members remain Nursery-owned records.'),
			self::region('nursery-partnership', 'Parent partnership', 'Nursery collection', 'Branch partnership cards and copy.'),
			self::region('nursery-contact', 'Contact information', 'Protected nursery facts', 'Address, phone, email and hours stay bound to the Nursery record.'),
			self::region('nursery-accreditations', 'Accreditations', 'Nursery collection', 'Branch trust marks and verified report links.'),
			self::region('nursery-testimonials', 'Testimonials', 'Bound collection', 'Testimonials resolve through their branch relationship.'),
			self::region('nursery-booking-modal', 'Booking modal', 'Protected modal', 'Accessible scheduling overlay with protected integration.'),
		);

		return self::page($title, '/nurseries/' . $slug, 'Our Nurseries', $regions, 'nursery');
	}

	/** @return array<string,mixed> */
	private static function contact_page($location) {
		$slug = strtolower($location);
		return self::page('Contact ' . $location, '/contact/' . $slug, 'Contact', array(
			self::region('contact-location-header', 'Page header', 'Nursery binding', 'Branch title and introduction.'),
			self::region('contact-location-form', 'Enquiry form & primary photo', 'Protected form + Nursery media', 'Branch routing stays protected; photo resolves from the Nursery record unless explicitly overridden.'),
			self::region('contact-location-details', 'Contact details card', 'Protected nursery facts', 'Address, phone, email and hours stay bound to the Nursery.'),
			self::region('contact-location-gallery', 'Location gallery card', 'Nursery collection', 'Branch images and presentation.'),
			self::region('contact-location-other', 'Other nurseries', 'Bound navigation', 'Links resolve from published Nursery records.'),
			self::region('contact-location-missing', 'Missing record redirect', 'Protected route state', 'Safe redirect when a branch record is unavailable.'),
		), 'nursery-contact');
	}

	/**
	 * Seed one protected visual document per exact route. The document owns the
	 * workflow/lock/revision shell; the exact-route design remains in post meta.
	 *
	 * @return void
	 */
	public static function maybe_seed_pages() {
		if ((int) get_option(self::SEED_OPTION, 0) >= self::SEED_VERSION) {
			return;
		}
		// `about` was briefly seeded in error. Take its document away again so
		// the client's page list does not carry a page they never made.
		$stray = AM_VB_Document::find('about');
		if ($stray instanceof WP_Post) {
			AM_VB_Document::trash((int) $stray->ID);
		}
		foreach (self::registry() as $slug => $page) {
			if (AM_VB_Document::find($slug)) {
				continue;
			}
			AM_VB_Document::create(
				$page['title'],
				$slug,
				array(
					'version'  => AM_VB_Document::SCHEMA_VERSION,
					'title'    => $page['title'],
					'sections' => array(),
				)
			);
		}
		update_option(self::SEED_OPTION, self::SEED_VERSION, false);
	}

	/** @return array<string,mixed>|null */
	public static function page_for_slug($slug) {
		$registry = self::registry();
		return isset($registry[$slug]) ? $registry[$slug] : null;
	}

	/** @return array<string,array<string,mixed>> */
	public static function editor_registry() {
		$output = self::registry();
		foreach ($output as $slug => $page) {
			$output[$slug]['canvasUrl'] = add_query_arg('am_visual_canvas', $slug, home_url($page['route']));
		}
		return $output;
	}

	/** @return array<string,mixed> */
	public static function defaults($slug) {
		$page = self::page_for_slug($slug);
		$model = array(
			'version'        => self::SCHEMA_VERSION,
			'pageSlug'       => sanitize_key($slug),
			'sections'       => array(),
			'elements'       => array(),
			'collections'    => array(),
			'customSections' => array(),
		);
		if (!$page) {
			return $model;
		}
		foreach ($page['regions'] as $region) {
			if (empty($region['editable'])) {
				continue;
			}
			$model['sections'][$region['id']] = array(
				'visible'         => true,
				'backgroundColor' => '',
				// Zero means "inherit the real site's responsive design". The
				// editor presents the site's effective width until a user chooses
				// an explicit override, keeping an untouched canvas pixel-faithful.
				'desktop' => array('paddingY' => 0, 'gap' => 0, 'contentWidth' => 0),
				'tablet'  => array('paddingY' => 0, 'gap' => 0, 'contentWidth' => 0),
				'mobile'  => array('paddingY' => 0, 'gap' => 0, 'contentWidth' => 0),
			);
		}
		return $model;
	}

	/** @return array<string,mixed> */
	public static function get($slug) {
		$post = AM_VB_Document::find($slug);
		$stored = $post ? get_post_meta($post->ID, self::META_KEY, true) : array();
		return self::sanitize($slug, is_array($stored) ? $stored : array());
	}

	/** @return array<string,mixed>|WP_Error */
	public static function save($slug, $input) {
		$page = self::page_for_slug($slug);
		$post = AM_VB_Document::find($slug);
		if (!$page || !$post || 'home-poc' === $slug) {
			return new WP_Error('am_vb_unknown_exact_route', 'That exact-route design is not available.', array('status' => 404));
		}
		$design = self::sanitize($slug, is_array($input) ? $input : array());
		update_post_meta($post->ID, self::META_KEY, $design);
		update_post_meta($post->ID, '_am_vb_last_editor', get_current_user_id());
		AM_VB_Audit::log('exact_route_design_saved', $post->ID, array('schema' => self::SCHEMA_VERSION, 'route' => $page['route']));
		return $design;
	}

	/** @return void */
	public static function register_routes() {
		register_rest_route(
			AM_VB_REST::NAMESPACE,
			'/page-design/(?P<slug>[a-z0-9-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array(__CLASS__, 'rest_get'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array(__CLASS__, 'rest_save'),
					'permission_callback' => array(__CLASS__, 'can_edit'),
					'args'                => array(
						'design'       => array('required' => true, 'type' => 'object'),
						'sessionTitle' => array('required' => false, 'type' => 'string'),
					),
				),
			)
		);
	}

	/** @return bool */
	public static function can_edit() {
		return current_user_can('edit_am_visual_pages');
	}

	/** @return WP_REST_Response|WP_Error */
	public static function rest_get($request) {
		$slug = sanitize_key($request['slug']);
		if ('home-poc' === $slug || !self::page_for_slug($slug)) {
			return new WP_Error('am_vb_unknown_exact_route', 'That exact-route design is not available.', array('status' => 404));
		}
		return rest_ensure_response(self::payload($slug));
	}

	/** @return WP_REST_Response|WP_Error */
	public static function rest_save($request) {
		$slug   = sanitize_key($request['slug']);
		$design = self::save($slug, (array) $request->get_param('design'));
		if (is_wp_error($design)) {
			return $design;
		}
		$payload = self::payload($slug, $design);
		$title   = trim((string) $request->get_param('sessionTitle'));
		if ($title) {
			$session = AM_VB_Saved_Sessions::capture($slug, $title, 'exact', $design);
			if (is_wp_error($session)) {
				return $session;
			}
			$payload['savedSession'] = $session;
		}
		return rest_ensure_response($payload);
	}

	/** @return array<string,mixed> */
	private static function payload($slug, $design = null) {
		return array(
			'design'   => is_array($design) ? $design : self::get($slug),
			'defaults' => self::defaults($slug),
			'page'     => self::page_for_slug($slug),
			'updated'  => current_time('c'),
		);
	}

	/**
	 * Share only the current route's document with the public React runtime.
	 *
	 * @return void
	 */
	public static function localize_frontend() {
		if (!wp_script_is('alexandra-app', 'enqueued')) {
			return;
		}
		$slug = self::resolve_request_slug();
		if (!$slug || 'home-poc' === $slug) {
			return;
		}
		$design  = self::get($slug);
		$preview = AM_VB_Saved_Sessions::frontend_snapshot($slug, 'exact');
		wp_localize_script(
			'alexandra-app',
			'amSiteVisual',
			array(
				'pageSlug' => $slug,
				'page'     => self::page_for_slug($slug),
				// Media resolves through the attachment ID at render time, so a
				// design written on another domain still shows its pictures.
				'design'   => AM_VB_Media::resolve_design(is_array($preview) ? $preview : $design),
			)
		);
		wp_enqueue_script(
			'am-vb-site-runtime',
			AM_VB_PLUGIN_URL . 'assets/site-runtime.js',
			// The shared applier must be parsed first; the runtime calls straight
			// into it while annotating, rather than restating any property itself.
			array('alexandra-app', 'am-vb-style-apply'),
			AM_VB_VERSION,
			true
		);
	}

	/** @return string */
	public static function resolve_request_slug() {
		$path = isset($_SERVER['REQUEST_URI']) ? (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '/';
		$path = '/' . trim($path ?: '/', '/');
		if ('/' !== $path) {
			$path = untrailingslashit($path);
		}
		$registry = self::registry();
		foreach ($registry as $slug => $page) {
			if ($path === untrailingslashit($page['route']) && 'template' !== $page['type']) {
				return $slug;
			}
		}
		foreach ($registry as $slug => $page) {
			if (!empty($page['match']) && preg_match($page['match'], $path)) {
				return $slug;
			}
		}
		return '';
	}

	/** @return array<string,mixed> */
	private static function sanitize($slug, $input) {
		$defaults = self::defaults($slug);
		$output   = $defaults;
		$sections = isset($input['sections']) && is_array($input['sections']) ? $input['sections'] : array();
		foreach ($defaults['sections'] as $id => $section_default) {
			$source = isset($sections[$id]) && is_array($sections[$id]) ? $sections[$id] : array();
			$output['sections'][$id]['visible'] = !isset($source['visible']) || (bool) $source['visible'];
			$colour = isset($source['backgroundColor']) ? sanitize_hex_color($source['backgroundColor']) : '';
			$output['sections'][$id]['backgroundColor'] = $colour ?: '';
			foreach (array('desktop', 'tablet', 'mobile') as $device) {
				$values = isset($source[$device]) && is_array($source[$device]) ? $source[$device] : array();
				$output['sections'][$id][$device] = array(
					'paddingY'     => self::number($values, 'paddingY', $section_default[$device]['paddingY'], 0, 200),
					'gap'          => self::number($values, 'gap', $section_default[$device]['gap'], 0, 120),
					'contentWidth' => self::number($values, 'contentWidth', $section_default[$device]['contentWidth'], 0, 1600),
				);
				$output['sections'][$id][$device] = AM_VB_Style_Schema::sanitize_section_device(
					$id,
					$values,
					$output['sections'][$id][$device]
				);
			}
			// The same section toolkit Home gets - radius, border, shadow, hover,
			// scroll animation - reaches this page through the shared schema rather
			// than being restated here.
			$output['sections'][$id] = AM_VB_Style_Schema::sanitize_section($id, $source, $output['sections'][$id]);
		}

		$elements = isset($input['elements']) && is_array($input['elements']) ? $input['elements'] : array();
		foreach (array_slice($elements, 0, 1200, true) as $key => $element) {
			if (!is_array($element)) {
				continue;
			}
			$key  = sanitize_key($key);
			$type = sanitize_key($element['type'] ?? 'text');
			if (!$key || !in_array($type, array('text', 'textarea', 'button', 'tabs', 'image', 'logo', 'video', 'frame', 'shape'), true)) {
				continue;
			}
			// Same media contract as Home: normalised to this site, with the
			// attachment ID kept so the design stays portable across domains.
			$media = AM_VB_Media::sanitize_pair($element);
			$clean = array(
				'type'            => $type,
				'label'           => sanitize_text_field($element['label'] ?? ucfirst($type)),
				'value'           => sanitize_textarea_field($element['value'] ?? ''),
				'href'            => self::safe_href($element['href'] ?? ''),
				'linkDescription' => sanitize_text_field($element['linkDescription'] ?? ''),
				'src'             => $media['src'],
				'srcId'           => $media['srcId'],
				'alt'             => sanitize_text_field($element['alt'] ?? ''),
				'positionX'       => self::number($element, 'positionX', 50, 0, 100),
				'positionY'       => self::number($element, 'positionY', 50, 0, 100),
			);
			foreach (array('desktop', 'tablet', 'mobile') as $device) {
				$values = isset($element[$device]) && is_array($element[$device]) ? $element[$device] : array();
				$clean[$device] = array(
					'scale'   => self::number($values, 'scale', 100, 40, 240),
					'offsetX' => self::number($values, 'offsetX', 0, -1200, 1200),
					'offsetY' => self::number($values, 'offsetY', 0, -1200, 1200),
				);
				// Everything past the three legacy transforms is whitelisted by the
				// shared schema, exactly as Home does. Without this the inspector
				// offered rotate, width, colour and the rest on every page, the
				// canvas rendered them, and the save silently threw them away.
				$clean[$device] = AM_VB_Style_Schema::sanitize_device($type, $values, $clean[$device]);
			}
			$clean = array_merge($clean, AM_VB_Style_Schema::sanitize_flat($type, $element));
			$output['elements'][$key] = $clean;
		}

		// The free-design section format is intentionally shared with Home. The
		// defensive shape below keeps arbitrary scripts/CSS out of page metadata.
		$custom = isset($input['customSections']) && is_array($input['customSections']) ? array_slice($input['customSections'], 0, 30) : array();
		$allowed_anchors = array_keys($defaults['sections']);
		foreach ($custom as $section) {
			if (!is_array($section)) {
				continue;
			}
			$id = sanitize_key($section['id'] ?? '');
			if (!$id) {
				continue;
			}
			$after = sanitize_key($section['after'] ?? '');
			if (!in_array($after, $allowed_anchors, true)) {
				$after = end($allowed_anchors) ?: '';
			}
			$clean_section = array(
				'id'              => $id,
				'name'            => sanitize_text_field($section['name'] ?? 'Custom section'),
				'visible'         => !isset($section['visible']) || (bool) $section['visible'],
				'after'           => $after,
				'backgroundColor' => sanitize_hex_color($section['backgroundColor'] ?? '') ?: '#f7faf5',
				'items'           => array(),
			);
			foreach (array('desktop', 'tablet', 'mobile') as $device) {
				$values = isset($section[$device]) && is_array($section[$device]) ? $section[$device] : array();
				$clean_section[$device] = array('height' => self::number($values, 'height', 420, 160, 1400));
			}
			foreach (array_slice((array) ($section['items'] ?? array()), 0, 80) as $item) {
				if (!is_array($item)) {
					continue;
				}
				$item_id   = sanitize_key($item['id'] ?? '');
				$item_type = sanitize_key($item['type'] ?? 'text');
				if (!$item_id || !in_array($item_type, array('text', 'button', 'tabs', 'image', 'logo', 'shape'), true)) {
					continue;
				}
				$clean_item = array(
					'id'              => $item_id,
					'key'             => sanitize_key($item['key'] ?? ('custom-' . $id . '-' . $item_id)),
					'type'            => $item_type,
					'name'            => sanitize_text_field($item['name'] ?? ucfirst($item_type)),
					'shape'           => in_array(($item['shape'] ?? ''), array('rectangle', 'circle', 'pill', 'line'), true) ? $item['shape'] : 'rectangle',
					'backgroundColor' => sanitize_hex_color($item['backgroundColor'] ?? '') ?: '#a9c6a2',
					'color'           => sanitize_hex_color($item['color'] ?? '') ?: '#20372a',
					'borderRadius'    => self::number($item, 'borderRadius', 0, 0, 999),
				);
				foreach (array('desktop', 'tablet', 'mobile') as $device) {
					$values = isset($item[$device]) && is_array($item[$device]) ? $item[$device] : array();
					$clean_item[$device] = array(
						'x'        => self::number($values, 'x', 24, -1200, 2400),
						'y'        => self::number($values, 'y', 24, -1200, 1800),
						'width'    => self::number($values, 'width', 320, 20, 1600),
						'height'   => self::number($values, 'height', 100, 10, 1400),
						'fontSize' => self::number($values, 'fontSize', 18, 8, 180),
					);
				}
				$clean_section['items'][] = $clean_item;
			}
			$output['customSections'][] = $clean_section;
		}

		return $output;
	}

	/** @return int */
	private static function number($source, $key, $fallback, $minimum, $maximum) {
		$value = isset($source[$key]) && is_numeric($source[$key]) ? (float) $source[$key] : (float) $fallback;
		return (int) round(max($minimum, min($maximum, $value)));
	}

	/** @return string */
	private static function safe_href($value) {
		$value = trim((string) $value);
		if ('' === $value || '#' === $value) {
			return $value;
		}
		if (0 === strpos($value, '/') && 0 !== strpos($value, '//')) {
			return preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
		}
		return esc_url_raw($value, array('http', 'https', 'mailto', 'tel'));
	}
}
