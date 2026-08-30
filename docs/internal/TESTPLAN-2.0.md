# Testplan IntraVox 2.0

Handmatig testplan voor de 2.0-release. **Doel: de UI-paden afdekken die de
geautomatiseerde tests niet bereiken.**

## Resterende ronde — 30-40 minuten (stand 14 aug)

Het meeste van dit plan is inmiddels afgedekt: **A, I, J** volledig server-side
(beide accounts byte-identiek; lege-index-gedrag mét twee gevonden-en-gefixte
bugklassen; reindex), **K** mechanisch (échte 1.9.7→2.0.0-upgrade nagespeeld op
de gevulde database), **B1-B3, C, D, E1-E2, M1-M8/M10** via de screenshotsessies
en dev-verificaties, **H1-H4** via de 1.9.8-fixes + unit tests. Wat rest is
onderstaande gerichte browserronde. Console open (F12) gedurende alles.

**Vooraf:** hard verversen (Cmd+Shift+R), profieltaal **Nederlands**.

### T1 — Nederlandse teksten + de plural-proef (5 min, hoogste bewijswaarde)

De plural-vertalingen zijn handmatig geleverd (de bot kan ze niet); een
formatfout valt **stil** terug op Engels — alleen een browser toont dit.

1. Open een Engelse pagina → melding boven de content in het **Nederlands**
   ("Deze pagina is in English." + "Lees in …")
2. ⓘ → tab Vertalingen: alle labels Nederlands
3. **Enkelvoud-plural:** subpagina (bv. en/about/customers) → kies Français →
   oranje melding "**1 bovenliggende pagina bestaat** nog niet…"
4. **Meervoud-plural:** sub-súbpagina (diepte 3) → Français → "**2 bovenliggende
   pagina's bestaan** nog niet…" — Engels hier = formatfout, direct melden
5. Editor: verwijder de laatste rij van een (test)pagina → melding "Dit is de
   laatste rij…" in het Nederlands

### T2 — F: eentalige installatie (5 min, release-blocking, gecoördineerd)

Zeg "**start F**": de andere taalmappen worden server-side tijdelijk weggezet.
Dan: F1 geen vertaal-item in het …-menu · F2 geen tab Vertalingen · F3 geen
taalmelding, nergens. Zeg "**klaar**" voor herstel.

### T3 — G: gelijktijdig bewerken (5 min)

Twee browsers (of normaal + privévenster), Rik + admin, zelfde pagina:
G2 save in browser 1 lukt · G3 save in browser 2 → **foutmelding**, geen stille
overschrijving · G4 herladen + opnieuw → lukt · G5 tweemaal achtereen opslaan in
één sessie → beide lukken.

### T4 — B: linkgedrag (4 min)

B4 dezelfde pagina-link 2× plakken in dezelfde tab → werkt beide keren ·
B5 `#page-bestaatniet` → homepage **mét** Nederlandse foutmelding ·
B6 publieke share-link in anoniem venster → werkt als voorheen.

### T5 — E: lezer zonder schrijfrechten (4 min)

Log in als `test` of `Wieke` (IntraVox Users): E5 taalmelding zichtbaar, géén
bewerk-UI · E3 Engelse pagina zonder NL-versie → melding zónder knop ·
E4 pagina in eigen taal → géén melding.

### T6 — M9: MetaVox uit en aan (3 min, gecoördineerd)

Zeg "**start M9**": MetaVox wordt uitgeschakeld → herlaad: geen tab, geen
menu-item, rest werkt → melden → weer ingeschakeld → tab terug zonder herstart.

### T7 — naveegwerk (5 min)

Ctrl+K op een paginatitel → resultaat **zonder sjablonen** · pagina met
subpagina's verwijderen → weg uit boom, daarna zoeken → geen treffers (H5/H6) ·
`/apps/intravox/` zonder hash → homepage met `#page-…`, verversen → blijft
(A4/A5).

**Rapportage:** per blok ✓/✗; bij ✗ account + URL + console. Blokkerend:
T1, T2, en elke stille console-fout.

---

## Waarom dit plan bestaat

De 491 unit-tests zijn allemaal PHP met mocks. Tijdens de bouw van 2.0 zijn
**vier** bugs door handmatig klikken gevonden die server-side onzichtbaar waren:

| Bug | Waarom de tests hem misten |
|---|---|
| Paden per gebruiker opgeslagen → leeg intranet | Alle tests draaiden als één gebruiker |
| Homepage resolveerde naar alfabetisch eerste pagina | Volgorde-afhankelijkheid tussen componenten |
| `Translate page` deed niets | `v-if` + `:open` samen op NcDialog |
| Taalnamen toonden `Deutsch (Persönlich: Du)` | Nooit visueel bekeken |

Dat patroon is de reden voor dit plan: **test met minstens twee accounts, in de
browser, en kijk naar wat er staat.**

---

## Voorbereiding

**Omgeving:** dev.rikdekker.nl (of een verse install met demo-data)

**Nodig:**
- Twee accounts: één admin, één gewone gebruiker met schrijfrechten
- Minstens twee talen met content (dev heeft `en`, `fr`, `nl`)
- Eén account met een **regionale locale** (`nl_NL`, niet `nl`) — daar zat een
  echte bug in zoeken

**Vooraf:**
```bash
occ intravox:reindex          # index vullen
occ intravox:reindex --dry-run # moet hetzelfde aantal melden
```

Ververs de browser hard (**Cmd+Shift+R**) — oude bundels in de cache hebben
tijdens de bouw meermaals verwarring gegeven.

---

## A. Basis — werkt het intranet überhaupt

Deze sectie eerst, en met **beide accounts**. Hier zaten de ergste regressies.

| # | Stap | Verwacht |
|---|---|---|
| A1 | Open `/apps/intravox/` als **admin** | Homepage, geen welkomscherm |
| A2 | Open `/apps/intravox/` als **gewone gebruiker** | Zelfde aantal pagina's als admin |
| A3 | Vergelijk de zijbalk-boom tussen beide accounts | Identiek (afgezien van rechten) |
| A4 | Open `/apps/intravox/` zonder hash | URL wordt `#page-<id>` van de homepage |
| A5 | Ververs die pagina | Zelfde pagina blijft staan |

> **A2 is de belangrijkste test van dit plan.** De ernstigste bug van 2.0 was
> dat alles werkte voor het account dat toevallig de index vulde, en niemand
> anders. Eén account testen bewijst niets.

---

## B. Gedeelde links — het oorspronkelijke probleem

De aanleiding voor dit hele traject: een redacteur deelt een link in een
campagne, en de ontvanger krijgt iets anders te zien.

**Opzet:** zet je profieltaal op **Engels**. Vraag een link op naar een
**Nederlandse** pagina (kopieer uit de adresbalk terwijl je die pagina bekijkt).

| # | Stap | Verwacht |
|---|---|---|
| B1 | Plak die link in een nieuw tabblad | De **Nederlandse** pagina opent |
| B2 | Kijk naar de adresbalk | URL is **onveranderd** — niet herschreven |
| B3 | Kijk boven de content | Melding "Deze pagina is in het Nederlands." |
| B4 | Plak dezelfde link nogmaals in dezelfde tab | Werkt weer (niet naar home) |
| B5 | Verzin een niet-bestaande id: `#page-bestaatniet` | Homepage **mét** foutmelding |
| B6 | Open een publieke share-link naar dezelfde pagina | Werkt zoals voorheen |

> **B2 en B4** waren allebei kapot: de URL werd stil overschreven, waardoor de
> link uit de geschiedenis verdween en opnieuw plakken niet hielp.

---

## C. Vertaling maken — de redacteursflow

| # | Stap | Verwacht |
|---|---|---|
| C1 | Open een pagina → `…`-menu | "Pagina vertalen" staat er, naast "Pagina kopiëren" |
| C2 | Klik erop | Dialoog opent **direct** |
| C3 | Bekijk de talenlijst | Alleen talen waarin de pagina nog **niet** bestaat |
| C4 | Kijk naar de taalnamen | `English`, `Nederlands` — **niet** `English (US)` of `Deutsch (Persönlich: Du)` |
| C5 | Kies een taal → Aanmaken | Nieuwe pagina opent, inhoud gekopieerd |
| C5b | Vertaal een pagina **met afbeeldingen** | Alle afbeeldingen zichtbaar op de vertaling (geen 404's — check ook het netwerktabblad) |
| C6 | Kijk naar de status-badge | **Concept**, niet gepubliceerd |
| C7 | Open het `…`-menu op de nieuwe pagina | "Pagina vertalen" biedt de brontaal niet meer aan |
| C8 | Wijzig iets in de vertaling en sla op | Bronpagina blijft **onveranderd** |

> **C8 is een ontwerpkeuze om te bevestigen:** de kopie is een startpunt, geen
> spiegel. Als het bewerken van de vertaling de bron verandert, is er iets mis.

---

## D. Vertalingen beheren — de zijbalk

| # | Stap | Verwacht |
|---|---|---|
| D1 | Open ⓘ → tab "Vertalingen" | Tab bestaat |
| D2 | Bekijk een gekoppelde pagina | Andere taalversies met titel en taal |
| D3 | Klik op een vertaling in de lijst | Navigeert daarheen |
| D4 | Koppel een bestaande pagina in een andere taal | Verschijnt in de lijst |
| D5 | Probeer een pagina in **dezelfde** taal te koppelen | Staat niet in de keuzelijst |
| D6 | Ontkoppel | Lijst leeg; de **andere** pagina blijft bestaan |
| D7 | Open die andere pagina | Is ook ontkoppeld (symmetrisch), maar bestaat nog |

---

## E. Lezerservaring

**Opzet:** twee gekoppelde pagina's (NL + EN), profieltaal **Nederlands**.

| # | Stap | Verwacht |
|---|---|---|
| E1 | Open de **Engelse** pagina | Melding + knop "Lees in het Nederlands" |
| E2 | Klik die knop | Nederlandse versie opent |
| E3 | Open een Engelse pagina **zonder** NL-versie | Alleen melding, **geen** knop |
| E4 | Open een pagina in je eigen taal | **Geen** melding |
| E5 | Log in als lezer **zonder** schrijfrechten | Melding zichtbaar, geen bewerk-UI |

> **E5:** vóór 2.0 was de taalbadge alleen zichtbaar voor redacteuren — precies
> de groep die hem niet nodig had.

---

## F. Eentalige installatie

**Belangrijk:** de meeste klanten zijn eentalig en mogen niets van dit alles
zien. Test op een install met **één** taalmap, of verwijder tijdelijk de andere.

| # | Stap | Verwacht |
|---|---|---|
| F1 | `…`-menu | **Geen** "Pagina vertalen" |
| F2 | ⓘ-zijbalk | **Geen** tab "Vertalingen" |
| F3 | Open een willekeurige pagina | **Geen** taalmelding |

---

## G. Gelijktijdig bewerken

**Opzet:** twee browsers, twee accounts, dezelfde pagina.

| # | Stap | Verwacht |
|---|---|---|
| G1 | Open in beide, bewerk in beide | — |
| G2 | Sla op in browser 1 | Lukt |
| G3 | Sla daarna op in browser 2 | **Foutmelding**, geen stille overschrijving |
| G4 | Herlaad browser 2 en sla opnieuw op | Lukt |
| G5 | Bewerk en sla **tweemaal achter elkaar** op in één sessie | Beide keren gelukt |

> **G3** verving stille datavernietiging. **G5** controleert dat je geen
> conflict met jezelf krijgt na de eerste opslag.

---

## H. Verplaatsen en kopiëren

| # | Stap | Verwacht |
|---|---|---|
| H1 | Versleep een pagina in de boom binnen één taal | Werkt |
| H2 | Sleep een pagina naar de root | Blijft in de **eigen** taal |
| H3 | Bulk-verplaats meerdere pagina's | Alle geslaagd, of nette melding per pagina |
| H4 | Kopieer een root-pagina | Kopie in **dezelfde** taal als het origineel |
| H5 | Verwijder een pagina met subpagina's | Verdwijnt volledig uit de boom |
| H6 | Zoek daarna op die paginanaam | Geen resultaten |

> **H2** was een datavernietigende bug: een verplaatsing naar root verhuisde de
> pagina met subtree naar de taalmap van de *gebruiker*.

---

## I. Zoeken

| # | Stap | Verwacht |
|---|---|---|
| I1 | Zoek (Ctrl+K) op een paginatitel | Pagina staat erbij |
| I2 | Zoek op tekst **in** een pagina | Wordt gevonden |
| I3 | Herhaal met een account op `nl_NL` (regionale locale) | Zelfde resultaten |

> **I3** was kapot: de index kreeg `nl_NL` terwijl er `nl` in staat, dus het
> snelle pad gaf voor die gebruikers altijd nul.

---

## J. Index en herstel

| # | Stap | Verwacht |
|---|---|---|
| J1 | `occ intravox:reindex --dry-run` | Aantallen per taal, geen wijziging |
| J2 | `occ intravox:reindex` | Zelfde aantallen |
| J3 | Leeg de indextabel handmatig, herlaad het intranet | **Alles blijft werken** (trager) |
| J4 | Draai reindex opnieuw | Weer gevuld |
| J5 | Maak een pagina aan, kijk in de index | Rij toegevoegd |
| J6 | Verplaats die pagina, kijk in de index | `path` bijgewerkt |

```sql
SELECT language, COUNT(*) FROM oc_intravox_page_index GROUP BY language;
SELECT COUNT(*) total, COUNT(DISTINCT unique_id) uniek FROM oc_intravox_page_index;
SELECT path FROM oc_intravox_page_index LIMIT 3;  -- moet relatief zijn: en/about
```

> **J3 is de belangrijkste**: de index is een cache. Een lege of kapotte index
> mag nooit tot ontbrekende pagina's leiden, alleen tot tragere resolutie.

---

## K. Upgrade vanaf 1.9.x

Test op een **kopie** van een bestaande installatie, niet op productie.

| # | Stap | Verwacht |
|---|---|---|
| K1 | Upgrade zonder reindex te draaien | Intranet werkt volledig |
| K2 | Controleer de nieuwe kolom | `translation_group` bestaat, alles NULL |
| K3 | Open bestaande pagina's | Geen vertalingen, geen fouten |
| K4 | Draai `occ intravox:reindex` | Vult de index |
| K5 | Bewerk een pagina die vóór de upgrade bestond | Slaat normaal op |

---

## L. Toegankelijkheid

| # | Stap | Verwacht |
|---|---|---|
| L1 | Open een anderstalige pagina, inspecteer `<main>` | Heeft `lang="en"` (of de paginataal) |
| L2 | Open een pagina in je eigen taal | **Geen** `lang`-attribuut |
| L3 | Screenreader op een anderstalige pagina | Juiste uitspraak |

---

## M. MetaVox-integratie

**Waarom deze sectie bestaat:** de oude integratie leende een Files-app-hook die
Nextcloud 34 verwijderde — de tab was leeg zonder foutmelding. De nieuwe rendert
de velden zelf uit MetaVox' API. Tijdens de bouw zaten hier drie stille bugs
(ontbrekende props, cache die het veld opslokte, initial state op één van vijf
routes), dus: **klikken, niet aannemen.**

**Opzet:** MetaVox ≥ 1.1.1 geïnstalleerd, minstens één groupfolder met
toegewezen velden (dev: groupfolder 1, 5 velden).

| # | Stap | Verwacht |
|---|---|---|
| M1 | Open een pagina → ⓘ | **Vier** tabs: Details · MetaVox · Vertalingen · Versies |
| M2 | `…`-menu | **MetaVox**-item in de INFO-groep, opent de tab direct |
| M3 | Open de MetaVox-tab | Zelfde velden en invoertypes als hetzelfde bestand in de **Files**-app |
| M4 | Bekijk het formulier ongewijzigd | **Geen** Opslaan-knop zichtbaar (verschijnt pas bij een wijziging — zoals in Files) |
| M5 | Wijzig een veld → Opslaan | Bevestiging; herladen → waarde staat er nog |
| M6 | Zelfde bestand in de Files-app | Zelfde waarde zichtbaar |
| M7 | Onbekend veldtype (dev: "Primary driver" [person]) | "Unknown field type: person" — zelfde tekst als in Files |
| M8 | Kopieer of vertaal een pagina → MetaVox-tab | Velden **leeg** (eigen fileId), comments/reacties ook leeg |
| M9 | `occ app:disable metavox` → herlaad | Geen tab, geen menu-item, rest werkt; daarna weer enablen → terug zonder herstart |
| M10 | Netwerktabblad bij openen zijbalk | Geen `/api/metavox/status`-call (het veld lift mee op de paginarespons) |

> **M9 is de belangrijkste**: beschikbaarheid komt via initial state op élke
> route (directe URL, taal-URL, share). Test M1 daarom ook eens via een direct
> `#page-…`-adres in een nieuw tabblad, niet alleen via navigatie.

**NC32-restrisico (bewust, genoteerd):** de oude script-injectie is verwijderd;
op NC32 bestond die route nog. Het nieuwe paneel is versie-onafhankelijk, maar
er is geen NC32-omgeving om dat aan te tonen.

---

## N. Pagina-editor: rijen, kolommen en widgets

**Waarom deze sectie bestaat:** `PageEditor.vue` is in 2.0 *niet* gewijzigd —
het bestand is byte-identiek aan v1.9.7. Maar de laag eronder wél: `App.vue`
groeide met ruim 400 regels en de opslagroute is herbouwd rond de page-index en
conflictdetectie (`PageConflictException`). De editor zit daar direct op. Een
ongewijzigde editor op een herbouwde opslagroute is precies het gat waar de 491
unit-tests niets zeggen: die zijn allemaal server-side met mocks.

Aanleiding is daarnaast issue #94: een gebruiker verwijderde de laatste rij en
kwam op een leeg canvas terecht waar de knoppen *Linkerkolom* / *Rechterkolom* /
*Koprij* nog wél stonden, maar het hoofdraster niet meer terug te krijgen was.
N5–N7 dekken die fix af.

| # | Stap | Verwacht |
|---|---|---|
| N1 | Nieuwe pagina → bewerken → widget toevoegen → opslaan | Widget staat er na herladen |
| N2 | Rij toevoegen via **Rij invoegen** onder een bestaande rij | Nieuwe rij op de juiste plek, 1 kolom |
| N3 | Zet een rij op 3 kolommen, vul alle drie → terug naar 1 kolom | **Geen** widget verdwenen; alles staat in de overgebleven kolom, in volgorde |
| N4 | Rij dupliceren met widgets erin | Kopie met eigen widget-id's; bewerken van de kopie raakt het origineel niet |
| N5 | Verwijder de **laatste** rij van een pagina | Bevestiging meldt expliciet dat dit de laatste rij is en dat je erna een nieuwe kunt toevoegen |
| N6 | Bevestig → leeg canvas | Knop **Rij invoegen** staat naast Linkerkolom/Rechterkolom |
| N7 | Klik **Rij invoegen** op het lege canvas → widget toevoegen → opslaan | Pagina is normaal bruikbaar; herladen toont de widget |
| N8 | Rijen slepen (drag handle) → opslaan → herladen | Volgorde blijft staan |
| N9 | Zijkolom toevoegen, vullen, verwijderen, opnieuw toevoegen | Nieuwe zijkolom is leeg; pagina blijft opslaanbaar |
| N10 | Koprij toevoegen en verwijderen | Idem, geen resten in de opgeslagen JSON |
| N11 | Structurele wijziging (rij weg) → **Annuleren** i.p.v. opslaan | Pagina staat er weer volledig; **niets** opgeslagen |
| N12 | Twee browsers, beide in bewerkmodus op dezelfde pagina, beide een rij wijzigen → beide opslaan | Tweede opslag wordt **geweigerd** (conflict), niet stil overschreven — zie ook G |
| N13 | Na een structurele wijziging + opslaan: ⓘ → Versies → herstel de vorige versie | Oude indeling terug, inclusief rijen en kolommen |

> **N3, N11 en N13 zijn de belangrijkste.** N3 omdat kolomreductie widgets
> verplaatst in plaats van verwijdert — dat mag niet stilletjes misgaan. N11 en
> N13 zijn de enige twee vangnetten die er zijn: er is **geen undo** in de
> editor (Ctrl+Z werkt alleen binnen één tekstwidget).

**Bewust niet gedekt:** de generieke bevestigingsteksten zeggen niet hóéveel
widgets een actie weggooit, en `Annuleren` vraagt zelf niets. Beide zijn bekend
en staan los van 2.0.

---

## Niet gedekt — bewust

Zaken die dit plan **niet** aantoont, en waarvoor apart werk nodig is:

- **Schaal.** De snelheidswinst is gemeten op een synthetische fixture van 9.000
  pagina's, niet op echte infrastructuur. Vóór een klant met duizenden pagina's:
  genereer een realistische dataset in een Team Folder en meet.
- **Zoeken bij schaal.** `searchPages()` leest nog steeds elke pagina per query.
- **Verouderde vertalingen.** Bewust buiten v1: er is geen markering wanneer een
  bron wijzigt na het vertalen.
- **Per-taal rechten.** Bestaan niet; wie mag bewerken, mag alle talen bewerken.

---

## Verslaglegging

Noteer per sectie: geslaagd / gefaald + wat je zag. Bij een fout:

1. Welk **account** en welke **profieltaal**
2. **URL** op het moment van de fout
3. Browserconsole (**F12**) — de meeste 2.0-bugs waren stil in de UI maar
   zichtbaar in de console
4. `occ log:tail` of `data/nextcloud.log`

**Blokkerend voor release:** elke fout in **A**, **B**, **F**, **J3** of **K**;
plus **M1/M5** wanneer MetaVox geïnstalleerd is (een lege of niet-opslaande
metadata-tab was precies de klacht die dit werk startte).
Dat zijn de secties waar een fout betekent dat het intranet niet werkt, dat
gedeelde links kapot zijn, dat eentalige klanten last hebben, of dat een
upgrade schade doet.
