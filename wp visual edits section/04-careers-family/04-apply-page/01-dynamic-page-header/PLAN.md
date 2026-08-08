# Apply page — Dynamic page header plan

## 1. Intent and feel
Clearly distinguish a general application from a role-specific application while keeping the header restrained and consistent with the rest of the site.

## 2. Current React/public evidence and live-canvas parity
Apply.jsx reads ?job=, finds a matching job in window.amData.jobs, and sends “Apply for this role” to PageHeader when found or “Apply” otherwise. Seo title is either “Apply - {job.title}” or “Apply”; canonical path is /careers/apply. The canvas must execute that exact route/query behavior.

## 3. Exact editable elements and controls
Expose general title and role-specific title pattern; heading typography/colour/alignment; section background/padding/container; SEO general title, role title pattern, description and social-image fallback. Bound job.title appears as a locked token in SEO preview, not in the visible role-specific h1.

## 4. Layers and reorder rules
Section > container > conditional h1. Header is pinned first and cannot duplicate. General/role-specific strings are state variants of one layer, not two simultaneous headings.

## 5. Responsive behavior
Mirror PageHeader defaults with per-device type/padding. Stress-test long localized role-specific labels and keep natural height on mobile.

## 6. Data ownership and binding
Header patterns belong to the /careers/apply shell; Jobs owns job.title and id. Query selection is preview context only and is never serialized into the design.

## 7. Protected functional logic
Protect ?job lookup, general fallback for unknown/closed records, one h1 and canonical route. Styling cannot make a missing Job appear valid or change the selected application context.

## 8. Accessibility
Maintain single h1, AA contrast, readable wrap and state preview labelling. Avoid embedding ambiguous role data in editable strings.

## 9. Builder state previews
Preview General application, Valid selected Job and Unknown/closed job query, plus long copy and all devices. These are the actual synchronous states.

## 10. Storage, publishing, and versioning
Store apply-header state strings/styles/SEO patterns in the revisioned /careers/apply document; exclude the selected Job. Validate required pattern tokens and publish one atomic shell for both URL variants.

## 11. Planned implementation files (future only)
Add stable hooks and pattern reads to Apply.jsx/PageHeader.jsx; define apply-header schema in future class-am-vb-careers-design.php; add query-state fixture selector to assets/editor.js and canvas CSS; test both valid/invalid query variants.

## 12. Acceptance checklist
- [ ] General and role-specific canvas states match their public URLs.
- [ ] One h1 and route canonical remain correct.
- [ ] Selected Job data is read-only and not saved.
- [ ] Unknown job falls back safely.
- [ ] Responsive type and revisions work.
