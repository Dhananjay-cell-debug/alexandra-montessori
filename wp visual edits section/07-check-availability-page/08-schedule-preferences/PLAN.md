# Schedule preferences plan

## Intent and feel

This section should let a family express a rough weekly pattern quickly, while making clear that days, session and times are preferences—not an automatic booking or eligibility decision.

## Exact current JSX/public evidence

- `Required days of the week` visually labels seven real buttons Mon–Sun; none is programmatically required.
- Each day button uses `aria-pressed`, toggles membership in `days`, and switches between sage-filled and outlined styles.
- `Preferred session` select offers blank, Morning, Afternoon, Full Day and Other; it is optional.
- Other reveals `Please describe the session you need` with placeholder `e.g. school pick-up only`; current code does not require non-empty Other detail.
- Any nonblank session except Full Day reveals optional start/end time inputs; Full Day hides times.
- API `days` uses selection order via `days.join(', ')`, while the human-readable `sessions` summary reorders days by Mon–Sun and adds session/times/end date.

## Editable controls

- Group labels/help copy and display labels for weekday/session options while retaining protected machine values.
- Chip radius, padding, gap, active/inactive/hover/focus tokens and wrapping.
- Select/input labels/placeholders and conditional-field spacing/reveal.
- Preview blank, multiple nonchronological selections, Morning/Afternoon/Full Day/Other, blank Other detail, partial times and mobile wrapping.
- Functional requiredness or option additions belong to an authorized rules panel, not cosmetic editing.

## Layers, reordering and dragging

Protected flow is weekday group -> session/times grid. Weekday buttons stay chronological visually; client drag-reorder is disabled because machine/readable order must remain comprehensible. Conditional Other/times remain owned children of session selection and cannot be detached. Whole groups may move only through schema-aware form ordering.

## Desktop, tablet and mobile

Weekday chips flex-wrap at all widths. Session/times use one column then `sm:grid-cols-2`; conditional fields occupy natural grid positions. Test 320px, 400% zoom, all days active and browser time inputs. Wrapping must not visually imply a different weekday order.

## Data ownership and bindings

Machine arrays `WEEK_DAYS` and `SESSION_OPTIONS`, state transitions and summary builder are functional code/rule ownership. Page schema owns copy/presentation. Runtime preferences submit to `days`, `sessionType`, `startTime`, `endTime`, `sessions`; none enters design revisions.

## Protected behavior

- Preserve weekday/session machine values, `aria-pressed` and Full Day time-hiding rule.
- Do not label days/session/Other/times as required unless programmatic validation changes.
- Display-label changes cannot alter submitted values.
- Summary behavior must be tested explicitly: chronological human summary versus current selection-order `days` field.
- No arbitrary scripts/actions on chips.

## Accessibility

Day controls are real buttons with pressed state, visible focus and adequate target size. Session and time inputs retain labels. Conditional fields follow their controller in DOM order, are announced appropriately and respect reduced motion. Help text explains preference semantics.

## State previews and failure handling

Preview no days/session, one/all days, out-of-order clicks, every session option, blank Other, one-sided time and Full Day transition. Invalid option values fall back to blank rather than enter payload. Clearing/changing session must have an explicit stale-time policy in the planned implementation; current code hides but retains time state until submission excludes it for Full Day.

## Storage and versioning

Store display labels/style separately from a versioned schedule rules contract. Stable option IDs preserve payload meaning. Rules changes require regression fixtures for summary and API fields. Runtime selections stay session-local and out of content revisions.

## Planned implementation files (future only)

- `src/components/availability/SchedulePreferences.jsx`
- `src/lib/availabilitySchedule.js`
- `src/pages/Availability.jsx`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-schedule.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-schedule.php`

## Current public section -> exact WP canvas parity

| Current public behavior | Exact WP canvas requirement |
| --- | --- |
| Mon–Sun optional `aria-pressed` chips | Same order, pressed styling and toggle behavior |
| Optional Morning/Afternoon/Full Day/Other select | Same labels, values and blank option |
| Other reveals optional text field | Same current conditional and non-required behavior |
| Nonblank non-Full-Day reveals optional times | Same exact visibility rule |
| Human summary orders days; `days` payload uses click order | Preview/tests surface this exact current distinction |

## Acceptance checklist

- [ ] Default options, copy and conditional rules match current JSX.
- [ ] Pressed state and machine values remain synchronized.
- [ ] No optional control is falsely presented as required.
- [ ] Full Day hides times and payload behavior remains correct.
- [ ] Mobile wrap preserves chronological comprehension.
- [ ] Summary and raw payload ordering have explicit regression tests.

