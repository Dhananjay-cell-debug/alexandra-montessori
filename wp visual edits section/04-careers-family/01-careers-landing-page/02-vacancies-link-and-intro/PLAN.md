# Careers landing — Vacancies link and intro plan

## 1. Intent and feel
Make this small invitation feel deliberate and friendly: the vacancies action is prominent but restrained, followed by a reassuring explanation for speculative applicants.

## 2. Current React/public evidence and live-canvas parity
Careers.jsx lines 30–54 render one white section with a centred Link to /careers/vacancies, arrow badge, and a two-sentence paragraph. The builder must edit this exact first content section in the live /careers iframe.

## 3. Exact editable elements and controls
Editable controls: button label; approved internal destination picker defaulting to /careers/vacancies; ArrowRight icon choice from the approved icon set; icon/badge size and colours; border, radius, gap, padding and hover colours; intro rich text with safe inline emphasis; paragraph width, typography and spacing; section background and padding; reveal on/off/delay.

## 4. Layers and reorder rules
Layers are Section > centred container > vacancies link > text row > arrow badge/icon, then intro paragraph. Link and paragraph may swap only through an explicit two-item order control; icon stays inside the link and the section cannot absorb form fields from the next region.

## 5. Responsive behavior
Allow per-device link padding, gap, font size, paragraph size/width and vertical spacing. On narrow screens the link may wrap its label but must remain a single accessible target; never position the arrow absolutely over text.

## 6. Data ownership and binding
Label, intro, icon and visual settings are careers-page shell content. The destination uses the visual builder's internal-link registry; it is not a Job record field.

## 7. Protected functional logic
Keep React Router navigation, a valid /careers/vacancies default and the button's real link semantics. URL editing is restricted to safe internal routes or validated https links; no click-script field is exposed.

## 8. Accessibility
Require non-empty link text, visible keyboard focus, 44px-equivalent target height, sufficient normal/hover/focus contrast, and aria-hidden treatment for a decorative arrow. Rich text sanitization preserves semantic phrasing.

## 9. Builder state previews
Preview default, hover, focus-visible, long-label and broken-link validation states on all three devices. This static section has no loading/error/empty public state.

## 10. Storage, publishing, and versioning
Store section key careers-vacancies-intro in the revisioned /careers design with content, order and responsive style tokens. Save relative internal URLs, stable icon names and sanitized rich text; retain draft/published snapshots and audit who changed the destination.

## 11. Planned implementation files (future only)
Add stable hooks and design-token reads to Careers.jsx; register careers-vacancies-intro fields in future class-am-vb-careers-design.php; add link picker/icon picker controls in assets/editor.js; add exact canvas focus/hover rules to assets/canvas.css; test unsafe URLs and public parity.

## 12. Acceptance checklist
- [ ] Button, arrow badge and paragraph match live /careers exactly.
- [ ] Internal destination remains navigable after save/publish.
- [ ] Keyboard focus and long mobile copy remain usable.
- [ ] Reordering cannot move the icon or content into another section.
- [ ] Revision restore recovers text, link and styles together.
