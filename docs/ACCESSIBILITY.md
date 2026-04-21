# IntraVox — Accessibility (WCAG 2.1 AA)

## Legal framework

Dutch government organizations are legally required to make their websites and applications digitally accessible. IntraVox complies with the following standards:

| Law / Standard | Status | Requirement |
|---|---|---|
| **Wet Digitale Overheid (WDO)** | In effect since July 1, 2023 | WCAG 2.1 AA mandatory for all government websites, intranets and apps |
| **EN 301 549** | European harmonized standard | Contains WCAG 2.1 A+AA as baseline for web accessibility |
| **WCAG 2.1 Level AA** | 50 success criteria (30×A + 20×AA) | All 50 must be met |

Government organizations are additionally required to:
- Publish an **accessibility statement** (Dutch model from Digitoegankelijk.nl)
- Update it annually
- Conduct a full WCAG audit at least every **36 months**

## Compliance status

**IntraVox meets ~47 of the 50 WCAG 2.1 AA success criteria.**

The 3 criteria with partial compliance (1.2.1, 1.2.2, 1.2.5) concern captions and audio descriptions for video content. This depends on user-submitted content — external platforms like YouTube and Vimeo provide this natively. The application itself does not create barriers for these criteria.

## Implemented measures

### Principle 1: Perceivable

| Criterion | Implementation |
|---|---|
| **1.1.1 Non-text content** | All images have `alt` text. Icon-only buttons have `aria-label`. Users can set alt text via the widget editor. |
| **1.3.1 Info and relationships** | Semantic landmarks (`<header>`, `<main>`, `<nav>`, `<footer>`). Heading hierarchy (h1-h6). Form labels associated via `for`/`id`. ARIA roles on tabs, combobox and carousel. |
| **1.3.2 Meaningful sequence** | DOM order matches visual order. |
| **1.3.4 Orientation** | Responsive design, no orientation lock. |
| **1.3.5 Identify input purpose** | `autocomplete` on password field. |
| **1.4.1 Use of color** | Status indicators (Draft/Published) use text + icon, not color alone. |
| **1.4.2 Audio control** | Videos have native `controls`. Autoplay is always `muted`. |
| **1.4.3 Contrast (minimum)** | All colors via Nextcloud theme variables. Draft badge contrast updated to 5.5:1 ratio. |
| **1.4.4 Resize text** | Responsive design, text scales to 200%+. |
| **1.4.10 Reflow** | 25+ components with responsive CSS. Grid falls back to single column on narrow screens. |
| **1.4.11 Non-text contrast** | UI components via Nextcloud theme variables with sufficient contrast. |
| **1.4.13 Content on hover or focus** | Tooltips do not disappear unexpectedly. |

### Principle 2: Operable

| Criterion | Implementation |
|---|---|
| **2.1.1 Keyboard** | All interactions are keyboard-accessible. 15+ keyboard handlers (Enter, Escape, arrow keys, Ctrl+Enter). Feed connection cards in admin settings are keyboard-navigable (`tabindex`, Enter/Space toggle). Feed items have `focus-visible` outline. |
| **2.1.2 No keyboard trap** | Escape closes modals and dropdowns. NcModal has built-in focus trap. |
| **2.2.2 Pause, stop, hide** | Carousel autoplay stops with `prefers-reduced-motion`. Global CSS reduces all animations. |
| **2.3.1 Three flashes** | No flashing content. |
| **2.4.1 Bypass blocks** | Skip-to-content link on both the main app and public pages. |
| **2.4.3 Focus order** | DOM order = visual order. No `.blur()` anti-patterns. |
| **2.4.5 Multiple ways** | Navigation bar, breadcrumb, search and page tree. |
| **2.4.6 Headings and labels** | All form inputs have an associated label. Heading hierarchy is correct. |
| **2.4.7 Focus visible** | Global `*:focus-visible` outline (2px solid, 2px offset) via `main.css`. |
| **2.5.1 Pointer gestures** | Drag-and-drop has button alternatives. |

### Principle 3: Understandable

| Criterion | Implementation |
|---|---|
| **3.1.1 Language of page** | `document.documentElement.lang` is dynamically tracked via MutationObserver. |
| **3.2.3 Consistent navigation** | Navigation appears on every page in the same order. |
| **3.3.1 Error identification** | Password errors with `role="alert"`. Toast notifications for API errors. |
| **3.3.2 Labels or instructions** | 129 labeled inputs across 38 components. Placeholders as supplementary hints. |
| **3.3.4 Error prevention** | Confirmation dialog when deleting pages. |

### Principle 4: Robust

| Criterion | Implementation |
|---|---|
| **4.1.1 Parsing** | Valid HTML via Vue compiler. No duplicate IDs or ARIA conflicts. |
| **4.1.2 Name, role, value** | ARIA roles on tabs (`role="tablist/tab/tabpanel"`), combobox (`role="combobox/listbox"`), carousel (`role="region"`). `aria-expanded` on dropdowns. |
| **4.1.3 Status messages** | `aria-live="polite"` on loading states (including Feed widget). `role="alert"` on error messages. `role="status"` on loading spinners in admin settings. |

## Technical details

### Files with accessibility measures

| File | Measures |
|---|---|
| `css/main.css` | Skip link, visually-hidden, focus-visible, prefers-reduced-motion |
| `src/App.vue` | Landmarks, skip link, aria-labels, live regions |
| `src/components/PublicPageView.vue` | Landmarks, skip link, form labels, live regions, validation |
| `src/components/WidgetEditor.vue` | 10+ label/input associations |
| `src/components/Navigation.vue` | `aria-haspopup`, `aria-expanded` on dropdowns |
| `src/components/PageTreeSelect.vue` | Combobox ARIA pattern |
| `src/components/NewPageModal.vue` | Tab ARIA pattern |
| `src/components/MediaPicker.vue` | Tab ARIA pattern, translated strings |
| `src/components/Breadcrumb.vue` | `aria-current="page"` |
| `src/components/news/NewsLayoutCarousel.vue` | Carousel ARIA, reduced motion, aria-live |
| `src/components/InlineTextEditor.vue` | aria-labels on toolbar buttons |
| `src/components/reactions/CommentSection.vue` | aria-labels on textareas |

### CSS accessibility features

```css
/* Skip link (hidden until focused) */
.skip-link { position: absolute; top: -100%; /* ... */ }
.skip-link:focus { top: 8px; }

/* Visually hidden but readable by screen readers */
.visually-hidden { position: absolute; width: 1px; height: 1px; /* ... */ }

/* Keyboard focus indicator */
*:focus-visible { outline: 2px solid var(--color-primary-element); outline-offset: 2px; }

/* Respect motion preference */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```

## Testing

### Automated testing

1. **axe-core** browser extension on all main views (homepage, edit mode, modals, admin)
2. **Lighthouse** accessibility audit (target: 90+ score)
3. Optional: add `eslint-plugin-vuejs-accessibility` to the project

### Manual testing

1. **Keyboard navigation**: Tab through the entire app without a mouse
2. **Screen reader**: Test with VoiceOver (macOS) or NVDA (Windows)
3. **Zoom**: Verify at 200% and 400% that no content is lost
4. **Color contrast**: Use WAVE or axe DevTools

### Test scenarios

- View a page and navigate via breadcrumb
- Create a new page via modal
- Edit a page with the widget editor
- Search
- Carousel interaction (news widget)
- Post a reaction/comment
- Public page with password protection

## Known limitations

| Topic | Details |
|---|---|
| **Video captions (1.2.1, 1.2.2)** | No built-in captioning support. YouTube/Vimeo handle this via their own player. Local videos have no captioning feature. |
| **Audio description (1.2.5)** | Depends on user content. The app does not provide an audio description track. |
| **Drag-and-drop editor** | Drag handles have aria-labels, but a full keyboard alternative for reordering widgets would be ideal. |

## References

- [Digitoegankelijk.nl](https://www.digitoegankelijk.nl) — Official Dutch platform for digital accessibility
- [WCAG 2.1 specification](https://www.w3.org/TR/WCAG21/) — W3C Web Content Accessibility Guidelines
- [Wet Digitale Overheid](https://www.digitaleoverheid.nl/nieuws/wet-digitale-overheid-op-1-juli-2023-van-kracht/) — Legal basis
- [EN 301 549](https://www.etsi.org/human-factors-accessibility/en-301-549-v3-the-harmonized-european-standard-for-ict-accessibility) — European harmonized standard
