# Taalbeheer

Je intranet kan inhoud bevatten in **elke taal die Nextcloud ondersteunt**. Deze gids legt uit hoe een taal actief wordt, hoe je de **aanbevolen taal** kiest waarop gebruikers terugvallen, wat gebruikers zien als hun eigen taal geen inhoud heeft, en hoe je een taal toevoegt of verwijdert.

> Content-gedreven model (sinds 1.7.0): een taal is **actief zodra die een startpagina heeft** — er zijn geen aan/uit-vinkjes. Je voegt een taal toe (waarmee een lege startpagina wordt aangemaakt) of vult een bestaande, en die verschijnt automatisch voor gebruikers in die taal. Gebruikers krijgen de **aanbevolen taal** te zien als hun eigen taal geen inhoud heeft (sinds 1.8.2, [#75](https://github.com/nextcloud/IntraVox/issues/75)).

## Waar vind je het

1. Open **Beheerinstellingen** → **IntraVox**.
2. Ga naar de tab **Talen**.
3. De sectie **Intranettalen** toont de talen met inhoud, de aanbevolen taal, en knoppen om een taal toe te voegen of te verwijderen.

## Wat je ziet

- **Talen met inhoud** — een chip voor elke taal die echte, door redacteuren gemaakte inhoud heeft, met de UI-vertaaldekking (bijv. "UI 93%"). De aanbevolen taal is gemarkeerd. Dit is de actuele verzameling talen die je gebruikers daadwerkelijk kunnen bekijken.
- **Aanbevolen taal** — een dropdown om de fallback-taal te kiezen (zie hieronder). Die toont alleen talen met inhoud, plus Engels.
- **Een taal toevoegen** — kies een door Nextcloud ondersteunde taal en klik **Taal toevoegen** om er een lege startpagina voor aan te maken.

Een taal is niets wat je "aanzet" — die wordt actief op het moment dat er een startpagina is (of je 'm hier toevoegt, er demo-inhoud voor importeert, of een redacteur er een pagina in maakt).

## Wat gebruikers zien als hun taal geen inhoud heeft

De Nextcloud-**weergavetaal** van een gebruiker bepaalt welke inhoud die ziet. Als hun taal geen IntraVox-inhoud heeft, kiest IntraVox een fallback — de gebruiker blijft **niet** op een lege pagina achter. De volgorde is (sinds 1.8.2, [#75](https://github.com/nextcloud/IntraVox/issues/75)):

1. **De eigen taal van de gebruiker**, als die echte inhoud heeft (een door een redacteur gemaakte startpagina, niet slechts een automatisch gegenereerde placeholder).
2. **De aanbevolen taal** — de taal die je kiest in de dropdown **Aanbevolen taal** — als die inhoud heeft. Precies waar de instelling voor is: "welke taal ziet een gebruiker zonder eigen taal?"
3. **Engels (`en`)** — de universele fallback en de brontaal voor Transifex.
4. Alleen als **geen** van bovenstaande inhoud heeft, toont IntraVox de melding *"Nog geen inhoud in jouw taal"*, met de talen die wél inhoud hebben en een link naar de persoonlijke taalinstelling van de gebruiker.

Op een intranet met alleen Engelse inhoud ziet een gebruiker met weergavetaal Duits dus gewoon de Engelse pagina's — geen melding. Stel je de aanbevolen taal in op Nederlands en is er Nederlandse inhoud, dan zien diezelfde gebruikers het Nederlandse intranet.

> **De aanbevolen taal is alleen een weergave-fallback.** Een redacteur maakt en bewaart pagina's altijd in de **eigen** taal — een pagina aanmaken leidt de schrijfactie nooit om naar de aanbevolen taal.

Engels kan niet worden verwijderd: het is de gegarandeerde laatste fallback en de Transifex-brontaal. De dropdown **Aanbevolen taal** biedt alleen talen aan die echt inhoud hebben (plus Engels), zodat je de fallback niet op een lege taal kunt zetten.

## Een taal toevoegen

1. Kies bij **Een taal toevoegen** de taal.
2. Klik **Taal toevoegen**.

Wat er server-side gebeurt:

- Er wordt een inhoudsmap voor die taal aangemaakt als die nog niet bestaat, met een lege `home.json`-startpagina — zodat de taal meteen navigeerbaar is.
- De pagina-boom-caches worden geleegd zodat de taal direct voor de gebruikers verschijnt.
- De taal verschijnt bij de "Talen met inhoud"-chips en, als er gebundelde demo-inhoud voor is (Nederlands, Engels, Duits, Frans), in de Demo-inhoud-tabel.

Gebundelde demo-inhoud is alleen voor een paar talen op volledig-intranet-niveau beschikbaar (Nederlands, Engels, Duits, Frans). Voor elke andere taal begin je bij de lege startpagina en bouw je van daaruit op — pagina's maken, navigatie-items toevoegen, enzovoort.

## Een taal verwijderen

1. Zoek de taal-chip onder **Talen met inhoud**.
2. Klik op de **×** en bevestig.

Wat er gebeurt:

- De inhoudsmap van de taal (alle pagina's, navigatie, footer, reacties en media) gaat naar de Nextcloud-**prullenbak**, dus er gaat niets direct verloren — het is te herstellen vanuit de prullenbak in de Bestanden-app.
- De pagina-boom-caches worden geleegd zodat de taal meteen uit de weergave van gebruikers verdwijnt.

Twee talen zijn beschermd en kunnen niet worden verwijderd:

- **Engels** — de universele fallback en brontaal.
- **De huidige aanbevolen taal** — kies eerst een andere aanbevolen taal, dan kun je deze verwijderen.

## Effect op de licentietelling

De gratis-tier-limiet is **50 pagina's per taal**. Die telt per inhoudsmap, dus elke taal heeft z'n eigen budget van 50 pagina's. Een taal verwijderen verplaatst de pagina's naar de prullenbak, dus ze tellen niet meer mee; de map herstellen uit de prullenbak brengt zowel de pagina's als hun telling terug.

## Veelvoorkomende scenario's

### "We publiceren alleen in het Engels, maar onze gebruikers hebben verschillende weergavetalen"

Laat Engels als aanbevolen taal staan (de standaard). Elke gebruiker — ongeacht z'n Nextcloud-weergavetaal — ziet het Engelse intranet, zonder "wijzig je taal"-melding. Dit is de meest voorkomende opzet met één taal.

### "We zijn een Nederlandse organisatie; Duitse/Franse gebruikers moeten Nederlands zien"

Zet de **Aanbevolen taal** op Nederlands. Gebruikers wiens weergavetaal geen IntraVox-inhoud heeft (bijv. Duits, Frans) krijgen het Nederlandse intranet te zien in plaats van het Engelse.

### "We hebben Spaans geprobeerd, werkte niet"

Verwijder de Spaanse chip onder **Talen met inhoud**. De map gaat naar de prullenbak; geen dataverlies, en je kunt 'm later herstellen vanuit de prullenbak als je van gedachten verandert. (Is Spaans je aanbevolen taal, wissel dan eerst de aanbevolen taal.)

### "Een nieuwe Transifex-taal heeft goede dekking bereikt — laten we die aanbieden"

Voeg de taal toe onder **Een taal toevoegen**. Die krijgt een lege startpagina; gebruikers in die taal zien dan de IntraVox-interface in hun taal en de lege startpagina als beginpunt, en je kunt de pagina's verder opbouwen.

## Back-ups

- De aanbevolen-taal-instelling staat in `oc_appconfig.intravox.primary_language` — onderdeel van standaard Nextcloud-config-back-ups.
- Inhoudsmappen zitten in de IntraVox-GroupFolder — onderdeel van standaard data-back-ups.

Een back-up terugzetten herstelt zowel de aanbevolen taal als de inhoudsmappen, exact tot het moment van de back-up.

## Probleemoplossing

**"De weergavetaal van een gebruiker heeft inhoud, maar die ziet nog steeds de fallback-taal."**
Inhoud telt alleen als die echt is (door een redacteur gemaakt). Een net toegevoegde taal begint met een automatisch gegenereerde placeholder-startpagina, die **niet** als inhoud telt totdat een redacteur er een echte pagina in bewaart. Maak/bewerk een pagina in die taal en de fallback stopt.

**"Na het toevoegen van een taal verschijnt er geen startpagina."**
Kijk in het Nextcloud-log naar `[LanguageHomepageService]`-fouten. De meest voorkomende oorzaak is een verse installatie waar de IntraVox-GroupFolder nog niet is ingericht — de **Setup**-actie onder **Ondersteuning → Onderhoud** lost dit op.

**"Ik heb per ongeluk een taal verwijderd."**
De map staat in de Nextcloud-prullenbak. Herstel 'm vanuit de prullenbak in de **Bestanden**-app; de taal verschijnt terug met al z'n pagina's.

## Zie ook

- [Beheerinstellingen](settings.md) — de Talen-tab en de rest van het beheerpaneel.
