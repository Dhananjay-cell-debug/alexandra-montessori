# Blog archive — Year and month filter strip plan

## 1. Intent and feel
Make date filtering compact and obvious, giving parents a useful archive tool without allowing the control strip to overpower featured/current reading.

## 2. Current React/public evidence and live-canvas parity
Blogs.jsx renders a bordered sage-tinted section aria-label="Blog archive filters" containing Year and Month native selects. Options derive from archive.filters; Month is disabled until a Year is selected. updateFilter writes query parameters, clears month when year changes and always clears page. The canvas must run these exact select controls and URL-state logic.

## 3. Exact editable elements and controls
Expose accessible group label, All years/All months option labels, optional visible field labels if enabled only through a corresponding public JSX enhancement (disabled in exact-parity seed), strip background/border/padding/gap, select width/padding/radius/border/shadow/type, normal/hover/focus/disabled colours and responsive wrapping. Year/month option values remain read-only bindings.

## 4. Layers and reorder rules
Section > container row > year select > month select. Selects may swap only if dependency explanation remains clear; default order is locked Year then Month. Native option rows are not layer items and cannot be dragged or edited individually.

## 5. Responsive behavior
Preserve flex-wrap and min-w-32 defaults. Allow mobile full-width selects, bounded gap/padding and type size while retaining native zoom-safe font size and focus rings.

## 6. Data ownership and binding
Articles/archive endpoint owns available years and months. The visual design owns labels and control presentation only. Selected year/month live in URL search parameters, not the builder document.

## 7. Protected functional logic
Protect positive-integer parsing, month requiring year, year change clearing month, any filter change clearing page, setSearchParams navigation and data-derived option ordering. Visual edits cannot manufacture archive dates, disable URL persistence or submit invalid values.

## 8. Accessibility
Retain aria-labels, native keyboard behavior, visible focus, disabled semantics and sufficient contrast. If visible labels are later enabled, they must be programmatically associated. Do not replace native selects with inaccessible custom menus in this workstream.

## 9. Builder state previews
Preview All dates, year selected/month enabled, year+month selected, no available filters, disabled month, focus, long localized labels and mobile full width. Interactions use safe preview URL state and the real handlers without changing Article records.

## 10. Storage, publishing, and versioning
Store blogs-archive-filters shell only in /blogs revisions; exclude available/selected option data. Schema validates label lengths and style bounds. State selection is editor session data and never published.

## 11. Planned implementation files (future only)
Instrument the filter section/select slots in Blogs.jsx; add filter-shell schema to future class-am-vb-blog-design.php; build interaction/state inspector in assets/editor.js and exact native-state canvas CSS; test query clearing, disabled state and parity.

## 12. Acceptance checklist
- [ ] Canvas controls are the real archive selects.
- [ ] Year/month/page query behavior remains exact.
- [ ] Options come only from Articles archive data.
- [ ] Keyboard/focus/disabled states are usable on all devices.
- [ ] Revisions contain no query selections or date-option copies.
