# SiBAJA Design Contract

This contract extracts the current authenticated application UI. It is not a redesign. New visual work should preserve the existing Bootstrap 5, Public Sans, white-surface, Lampung-blue administration dashboard language unless a future task explicitly changes the direction.

## 1. Visual Principles

- Authenticated pages feel like a compact public-sector reporting console: dense navigation, white content surfaces, blue institutional identity, and direct data access over decoration.
- The application shell uses a white sticky navbar with a blue logo lockup, subtle shadows, and Bootstrap cards/tables/forms for predictable administrative workflows.
- Visual hierarchy comes from surface elevation, blue active states, bold labels, and compact spacing rather than large hero treatments or ornamental motion.
- Existing public/login/front pages use separate layouts and are outside this authenticated header contract.

## 2. Extracted Tokens

### Color

| Role | Token | Value | Extracted from | Usage |
| --- | --- | --- | --- | --- |
| Brand primary | `--sibaja-brand-primary` | `#2b529a` | logo box, navbar-blue, hero strip | SiBAJA identity blocks and primary hover accents |
| Brand deep | `--sibaja-brand-deep` | `#0f2c74` | active nav, footer clock boxes | Active/selected emphasis and compact status text |
| Brand navy active | `--sibaja-brand-active` | `#131d6a` | mobile navbar active state | Dark active backing in compressed nav states |
| Surface primary | `--sibaja-surface-primary` | `#ffffff` | body, navbar, dropdowns, cards | Main page, nav, cards, dropdown menus |
| Surface soft | `--sibaja-surface-soft` | `#F5F8FD` | hero/page backgrounds | Light page sections and soft panels |
| Surface muted | `--sibaja-surface-muted` | `#f1f1f1` | dropdown hover | Hover fill and low-emphasis panel areas |
| Text primary | `--sibaja-text-primary` | `#1C1C1C` | hero/card headings | Headings and important table/card values |
| Text secondary | `--sibaja-text-secondary` | `#666666` | navbar links | Default navigation and secondary labels |
| Border default | `--sibaja-border-default` | `#dddddd` | dropdown menu borders | Dropdown and light card separation |
| Semantic success | `--sibaja-success` | `#28a745` / Bootstrap `success` | report cards, buttons | Positive totals and export/action buttons |
| Semantic info | `--sibaja-info` | `#17a2b8` / Bootstrap `info` | summary cards | Informational totals |
| Semantic warning | `--sibaja-warning` | `#ffc107` / Bootstrap `warning` | summary cards | Warning/caution values |
| Semantic danger | `--sibaja-danger` | `#dc3545` / Bootstrap `danger` | summary cards, logout | Destructive and urgent values |

### Typography

| Role | Token | Value | Usage |
| --- | --- | --- | --- |
| Primary font | `--sibaja-font-primary` | `'Public Sans', sans-serif` | Authenticated layout, logo text, navbar, default body |
| H1/dashboard | `--sibaja-type-h1` | Bootstrap `h1` / `1.75rem-2rem` observed | Dashboard/page titles |
| H2/page | `--sibaja-type-h2` | Bootstrap `h4` / `1.5rem` observed | Report page headings |
| H3/card | `--sibaja-type-card-title` | `1rem` to `1.125rem`, `600-700` | Card headers and section labels |
| Body | `--sibaja-type-body` | `1rem`, weight `400` | Default text and table content |
| Small/meta | `--sibaja-type-small` | `0.875rem` to `0.94rem` | Navbar links, filters, table cells |
| Caption | `--sibaja-type-caption` | `0.75rem` to `0.8125rem` | Dense labels and metadata |

### Spacing, Radius, Shadow

| Role | Token | Value | Usage |
| --- | --- | --- | --- |
| Space 1 | `--sibaja-space-1` | `0.25rem` / `4px` | Dropdown inner padding, tight offsets |
| Space 2 | `--sibaja-space-2` | `0.5rem` / `8px` | Icon-label gap, dropdown item padding |
| Space 3 | `--sibaja-space-3` | `0.75rem` / `12px` | Compact clusters and form rhythm |
| Space 4 | `--sibaja-space-4` | `1rem` / `16px` | Standard card/filter padding |
| Space 5 | `--sibaja-space-5` | `1.25rem` / `20px` | Section and form group separation |
| Space 6 | `--sibaja-space-6` | `1.5rem` / `24px` | Page/card grouping |
| Radius small | `--sibaja-radius-sm` | `0.25rem` / `4px` | Mobile active nav blocks, form fields |
| Radius default | `--sibaja-radius-md` | `0.375rem` / `6px` | Buttons, dropdown items, cards in pages |
| Radius large | `--sibaja-radius-lg` | `0.5rem` / `8px` | Dropdown menus, filter/card surfaces |
| Shadow nav | `--sibaja-shadow-nav` | `0 2px 6px rgba(0, 0, 0, 0.08)` | Sticky navbar |
| Shadow logo | `--sibaja-shadow-logo` | `0 3px 8px rgba(0, 0, 0, 0.2)` | Blue logo lockup |
| Shadow dropdown | `--sibaja-shadow-dropdown` | `0 0.5rem 1rem rgba(0, 0, 0, 0.15)` | Dropdown menus |
| Shadow card | `--sibaja-shadow-card` | `0 2px 8px rgba(0, 0, 0, 0.05)` | Data cards/filter/table wrappers |

## 3. Navbar Component Anatomy And States

- Structure: `nav.navbar.navbar-expand-lg.navbar-light.shadow-sm.sticky-top` contains a `container-fluid px-4`, blue `sibaja-logo-box`, Bootstrap toggler, `#mainNavbar`, primary `.navbar-nav.ms-4.me-auto`, page-specific filter cluster, sync metadata indicator, and right-aligned user dropdown.
- Logo lockup: blue surface `#2b529a`, white text, 50px logo on desktop, divider line, compact subtitle, and a subtle shadow.
- Primary navigation: `.nav-link` defaults to secondary gray text, `0.94rem`, weight 500, compact horizontal margins. Hover changes text to brand primary. Active state uses brand deep, weight 600/700, and a short underline or dark backing in compressed navbar contexts.
- Dropdowns: white menu surface, 220px minimum width, `0.5rem` radius, light border, dropdown shadow, rounded items, `0.2s` caret rotation, and muted hover fill. Nested submenus open by hover/focus on desktop and become visible static lists in mobile.
- Page filters: injected through `@yield('navbar-extra')` after the main menu, commonly Bootstrap `form-control-sm`, `form-select-sm`, Select2, `d-flex`, `flex-wrap`, and narrow fixed/min widths for year/satker filters.
- User dropdown: right edge Bootstrap icon-only trigger with `bi-person-circle`, `1.4rem`, and a small logout menu.
- API sync indicator: compact non-interactive metadata cluster placed after page-specific filters and before the user dropdown. It uses a Bootstrap icon with `aria-hidden="true"`, visible label `Sinkronisasi API terakhir`, a semantic `<time>` when a value exists, and the empty state `Belum pernah disinkronkan`.

## 4. Responsive Behavior

- Desktop `>=1157px`: navbar collapse is forced visible; menu, filters, sync indicator, and user dropdown stay in a single compact header row where possible.
- Intermediate `992px-1156px`: shared CSS forces the lg navbar into an absolute collapsed panel; nav links become stacked full-width rows when opened.
- Mobile and tablet `<=991.98px`: Bootstrap collapse expands as a vertical column. `.navbar-nav` becomes full width, links are stacked, dropdown shadows are removed, submenus become static, and active states avoid underline overflow.
- At `<=768px`, logo lockup becomes full width, the logo shrinks to 40px, navigation text tightens to `0.85rem`, and page content/card/table wrappers guard against horizontal overflow.
- The API sync indicator must wrap to a readable full-width row inside the expanded mobile navbar at `<=991.98px` and remain visually subordinate to navigation on desktop.

## 5. Accessibility Constraints

- HTML language remains `id`; visible labels are Indonesian and deterministic.
- Navigation uses Bootstrap semantic `nav`, links, dropdown buttons, and `aria-expanded` values. New metadata must not add keyboard traps or JavaScript-only behavior.
- Decorative icons use `aria-hidden="true"`. Icon-only controls need a visible or programmatic name.
- The API sync indicator must expose the full meaning through visible text plus `title`/ARIA semantics; compact layout cannot rely on icon alone.
- Datetime values use semantic `<time datetime="ISO-8601">` when available. Empty state remains text, not a blank icon.
- Text contrast should preserve WCAG AA against white surfaces, using brand deep or default body text for important metadata.
- Focus and hover states for existing interactive elements are preserved; the sync indicator is non-interactive and should not enter the tab order.

## 6. Accepted Debt

| Item | Location | Why accepted | Owner / Exit |
| --- | --- | --- | --- |
| Oversized shared stylesheet | `public/css/sibaja.css` | Existing 1115 LOC stylesheet contains repeated tokens and breakpoint overrides; current task explicitly forbids growing or restructuring it. | Future design-system consolidation can migrate repeated values to root variables. |
| Inline and page-scoped visual values | Multiple Blade views | Existing pages use inline styles, Bootstrap utilities, and local `<style>` blocks; current task is extraction plus one scoped header addition, not a cleanup. | Gradually replace during page-specific refactors. |
| Legacy navbar partial divergence | `resources/views/layouts/partials/user-navbar.blade.php` | Legacy partial has a blue navbar variant and older collapse attributes; current authenticated app shell uses `layouts/user.blade.php`. | Remove or align only when legacy partial usage is audited. |
| Breakpoint overlap | `public/css/sibaja.css` navbar media rules | Existing `1156px` and `991.98px` navbar rules overlap Bootstrap lg behavior; current task preserves behavior exactly. | Normalize breakpoints in a dedicated navbar refactor. |
