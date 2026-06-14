# IntraVox — Toegankelijkheid (WCAG 2.1 AA)

## Wettelijk kader

Nederlandse overheidsorganisaties zijn wettelijk verplicht hun websites en applicaties digitaal toegankelijk te maken. IntraVox voldoet aan de volgende standaarden:

| Wet / Standaard | Status | Vereiste |
|---|---|---|
| **Wet Digitale Overheid (WDO)** | Van kracht sinds 1 juli 2023 | WCAG 2.1 AA verplicht voor alle overheidssites, -intranetten en -apps |
| **EN 301 549** | Europese geharmoniseerde standaard | Bevat WCAG 2.1 A+AA als basis voor web-toegankelijkheid |
| **WCAG 2.1 niveau AA** | 50 succescriteria (30×A + 20×AA) | Alle 50 moeten worden gehaald |

Overheidsorganisaties zijn daarnaast verplicht:

- Een **toegankelijkheidsverklaring** te publiceren (Nederlands model van Digitoegankelijk.nl)
- Deze jaarlijks bij te werken
- Minstens elke **36 maanden** een volledige WCAG-audit te laten uitvoeren

## Compliance-status

**IntraVox voldoet aan ~47 van de 50 WCAG 2.1 AA-succescriteria.**

De 3 criteria met gedeeltelijke compliance (1.2.1, 1.2.2, 1.2.5) betreffen captions en audio-descriptions voor video-content. Dit hangt af van door gebruikers ingevoerde content — externe platforms zoals YouTube en Vimeo bieden dit native. De applicatie zelf creëert geen barrières voor deze criteria.

## Geïmplementeerde maatregelen

### Principe 1: Waarneembaar

| Criterium | Implementatie |
|---|---|
| **1.1.1 Niet-tekstuele content** | Alle afbeeldingen hebben `alt`-tekst. Icoon-only-knoppen hebben `aria-label`. Gebruikers kunnen alt-tekst instellen via de widget-editor. |
| **1.3.1 Info en relaties** | Semantische landmarks (`<header>`, `<main>`, `<nav>`, `<footer>`). Kop-hiërarchie (h1–h6). Form-labels via `for`/`id`. ARIA-rollen op tabs, combobox en carousel. |
| **1.3.2 Betekenisvolle volgorde** | DOM-volgorde komt overeen met visuele volgorde. |
| **1.3.4 Oriëntatie** | Responsive design, geen oriëntatie-lock. |
| **1.3.5 Identificeer input-doel** | `autocomplete` op wachtwoordveld. |
| **1.4.1 Gebruik van kleur** | Status-indicators (Concept/Gepubliceerd) gebruiken tekst + icoon, niet alleen kleur. |
| **1.4.2 Audio-controle** | Video's hebben native `controls`. Autoplay is altijd `muted`. |
| **1.4.3 Contrast (minimum)** | Alle kleuren via Nextcloud-thema-variabelen. Concept-badge-contrast bijgewerkt naar 5.5:1-ratio. |
| **1.4.4 Tekst groter** | Responsive design, tekst schaalt naar 200%+. |
| **1.4.10 Reflow** | 25+ componenten met responsive CSS. Grid valt terug naar één kolom op smalle schermen. |
| **1.4.11 Niet-tekstueel contrast** | UI-componenten via Nextcloud-thema-variabelen met voldoende contrast. |
| **1.4.13 Content bij hover of focus** | Tooltips verdwijnen niet onverwacht. |

### Principe 2: Bedienbaar

| Criterium | Implementatie |
|---|---|
| **2.1.1 Toetsenbord** | Alle interacties zijn met toetsenbord toegankelijk. 15+ toetsenbord-handlers (Enter, Escape, pijltjes, Ctrl+Enter). Feed-connection-cards in admin-instellingen zijn toetsenbord-navigeerbaar (`tabindex`, Enter/Space toggle). Feed-items hebben `focus-visible`-outline. |
| **2.1.2 Geen toetsenbord-val** | Escape sluit modals en dropdowns. NcModal heeft ingebouwde focus-trap. |
| **2.2.2 Pauze, stop, verberg** | Carousel-autoplay stopt met `prefers-reduced-motion`. Globale CSS vermindert alle animaties. |
| **2.3.1 Drie flashes** | Geen flikkerende content. |
| **2.4.1 Blokken overslaan** | Skip-to-content-link op zowel hoofdapp als publieke pagina's. |
| **2.4.3 Focus-volgorde** | DOM-volgorde = visuele volgorde. Geen `.blur()`-anti-patterns. |
| **2.4.5 Meerdere manieren** | Navigatiebalk, breadcrumb, zoekfunctie en pagina-tree. |
| **2.4.6 Koppen en labels** | Alle form-inputs hebben een geassocieerd label. Kop-hiërarchie is correct. |
| **2.4.7 Focus zichtbaar** | Globale `*:focus-visible`-outline (2px solid, 2px offset) via `main.css`. |
| **2.5.1 Pointer-gestures** | Drag-and-drop heeft knop-alternatieven. |

### Principe 3: Begrijpelijk

| Criterium | Implementatie |
|---|---|
| **3.1.1 Taal van pagina** | `document.documentElement.lang` wordt dynamisch bijgehouden via MutationObserver. |
| **3.2.3 Consistente navigatie** | Navigatie verschijnt op elke pagina in dezelfde volgorde. |
| **3.3.1 Fout-identificatie** | Wachtwoord-fouten met `role="alert"`. Toast-notificaties voor API-fouten. |
| **3.3.2 Labels of instructies** | 129 gelabelde inputs over 38 componenten. Placeholders als aanvullende hints. |
| **3.3.4 Fout-preventie** | Bevestigings-dialoog bij verwijderen van pagina's. |

### Principe 4: Robuust

| Criterium | Implementatie |
|---|---|
| **4.1.1 Parsing** | Geldig HTML via Vue-compiler. Geen dubbele ID's of ARIA-conflicten. |
| **4.1.2 Naam, rol, waarde** | ARIA-rollen op tabs (`role="tablist/tab/tabpanel"`), combobox (`role="combobox/listbox"`), carousel (`role="region"`). `aria-expanded` op dropdowns. |
| **4.1.3 Status-berichten** | `aria-live="polite"` op loading-states (inclusief feed-widget). `role="alert"` op foutmeldingen. `role="status"` op loading-spinners in admin-instellingen. |

## Technische details

### Bestanden met toegankelijkheids-maatregelen

| Bestand | Maatregelen |
|---------|-------------|
| `css/main.css` | Skip-link, visually-hidden, focus-visible, prefers-reduced-motion |
| `src/App.vue` | Landmarks, skip-link, aria-labels, live regions |
| `src/components/PublicPageView.vue` | Landmarks, skip-link, form-labels, live regions, validation |
| `src/components/WidgetEditor.vue` | 10+ label/input-associaties |
| `src/components/Navigation.vue` | `aria-haspopup`, `aria-expanded` op dropdowns |
| `src/components/PageTreeSelect.vue` | Combobox ARIA-patroon |
| `src/components/NewPageModal.vue` | Tab ARIA-patroon |
| `src/components/MediaPicker.vue` | Tab ARIA-patroon, vertaalde strings |
| `src/components/Breadcrumb.vue` | `aria-current="page"` |
| `src/components/news/NewsLayoutCarousel.vue` | Carousel ARIA, reduced motion, aria-live |
| `src/components/InlineTextEditor.vue` | aria-labels op toolbar-knoppen |
| `src/components/reactions/CommentSection.vue` | aria-labels op textareas |

### CSS toegankelijkheids-features

```css
/* Skip-link (verborgen tot focus) */
.skip-link { position: absolute; top: -100%; /* ... */ }
.skip-link:focus { top: 8px; }

/* Visueel verborgen maar leesbaar voor screenreaders */
.visually-hidden { position: absolute; width: 1px; height: 1px; /* ... */ }

/* Toetsenbord-focus-indicator */
*:focus-visible { outline: 2px solid var(--color-primary-element); outline-offset: 2px; }

/* Respecteer motion-voorkeur */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
```

## Testen

### Geautomatiseerd testen

1. **axe-core** browser-extensie op alle hoofdviews (homepage, edit-modus, modals, admin)
2. **Lighthouse** accessibility-audit (target: 90+ score)
3. Optioneel: voeg `eslint-plugin-vuejs-accessibility` toe aan het project

### Handmatig testen

1. **Toetsenbord-navigatie**: tab door de hele app zonder muis
2. **Screenreader**: test met VoiceOver (macOS) of NVDA (Windows)
3. **Zoom**: verifieer bij 200% en 400% dat geen content verloren gaat
4. **Kleurcontrast**: gebruik WAVE of axe DevTools

### Test-scenario's

- Bekijk een pagina en navigeer via breadcrumb
- Maak een nieuwe pagina via modal
- Bewerk een pagina met de widget-editor
- Zoeken
- Carousel-interactie (news-widget)
- Post een reactie/comment
- Publieke pagina met wachtwoord-bescherming

## Bekende beperkingen

| Onderwerp | Details |
|-----------|---------|
| **Video-captions (1.2.1, 1.2.2)** | Geen ingebouwde captioning-support. YouTube/Vimeo handelen dit af via hun eigen player. Lokale video's hebben geen captioning-feature. |
| **Audio-beschrijving (1.2.5)** | Hangt af van gebruikers-content. De app levert geen audio-description-track. |
| **Drag-and-drop-editor** | Drag-handvatten hebben aria-labels, maar een volledig toetsenbord-alternatief voor herordenen van widgets zou ideaal zijn. |

## Bronnen

- [Digitoegankelijk.nl](https://www.digitoegankelijk.nl) — Officieel Nederlands platform voor digitale toegankelijkheid
- [WCAG 2.1-specificatie](https://www.w3.org/TR/WCAG21/) — W3C Web Content Accessibility Guidelines
- [Wet Digitale Overheid](https://www.digitaleoverheid.nl/nieuws/wet-digitale-overheid-op-1-juli-2023-van-kracht/) — Wettelijke basis
- [EN 301 549](https://www.etsi.org/human-factors-accessibility/en-301-549-v3-the-harmonized-european-standard-for-ict-accessibility) — Europese geharmoniseerde standaard
