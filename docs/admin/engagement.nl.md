# Engagement-beheer-gids

Configuratie-gids voor IntraVox' engagement-features (reacties en comments).

**Doelgroep**: intranet-beheerders en content-managers

**Gerelateerde documentatie:**

- [Engagement-gebruikersgids](../user/engagement.md) — eind-gebruikers-documentatie
- [Engagement-architectuur](../features/engagement-architecture.md) — technische details
- [Beheer-instellingen](settings.md) — overige beheer-instellingen

---

## Overzicht

IntraVox biedt twee controle-niveaus voor engagement-features:

1. **Globale instellingen** — master-schakelaars voor het hele intranet (alleen beheerder)
2. **Pagina-instellingen** — per-pagina-overrides (editors met schrijfrechten)

![Admin engagement-instellingen](../screenshots/Engagement-Adminsettings.png)

*De Engagement-tab in IntraVox-beheer-instellingen*

---

## Globale instellingen

### Globale instellingen openen

1. Log in als Nextcloud-beheerder
2. Ga naar **Instellingen** (tandwiel-icoon rechtsboven)
3. Selecteer **IntraVox** in de Beheer-sectie
4. Klik op de **Engagement**-tab

### Beschikbare opties

#### Pagina-reacties

| Instelling | Standaard | Beschrijving |
|------------|-----------|--------------|
| Reacties op pagina's toestaan | Aan | Gebruikers kunnen emoji-reacties op pagina's toevoegen |

Bij uitschakelen verschijnen geen reactie-knoppen op pagina's.

#### Comments

| Instelling | Standaard | Beschrijving |
|------------|-----------|--------------|
| Comments toestaan | Aan | Gebruikers kunnen comments op pagina's plaatsen |
| Reacties op comments toestaan | Aan | Gebruikers kunnen op individuele comments reageren |

Let op: "Reacties op comments toestaan" verschijnt alleen wanneer "Comments toestaan" aan staat.

### Hoe globale instellingen werken

Globale instellingen zijn **master-schakelaars**:

- **Globaal aan**: feature is standaard beschikbaar, maar kan per pagina uitgezet
- **Globaal uit**: feature is overal uit, pagina-instellingen kunnen niet overrulen

```
Globaal: AAN + Pagina: (overerven) = Feature AAN
Globaal: AAN + Pagina: UIT         = Feature UIT
Globaal: UIT + Pagina: (anything)  = Feature UIT (kan niet aan)
```

---

## Pagina-instellingen

Editors met schrijfrechten op een pagina kunnen engagement-features voor specifieke pagina's uitzetten.

### Pagina-instellingen openen

1. Open de pagina die je wilt configureren
2. Klik **Bewerken** om edit-modus te openen
3. Klik op de **Instellingen**-knop (tandwiel) in de toolbar
4. Vind de **Engagement**-sectie

![Pagina engagement-instellingen](../screenshots/Engagement-Pagesettings.png)

*Pagina-specifieke engagement-instellingen in de Pagina-instellingen-modaal*

### Beschikbare opties

Elke instelling heeft twee states:

| State | Beschrijving |
|-------|--------------|
| Globale instelling gebruiken | Volg de globale instelling van de beheerder |
| Uitgeschakeld | Zet deze feature alleen voor deze pagina uit |

Let op: pagina-instellingen kunnen features alleen **uitzetten**. Is een feature globaal uit, dan toont de pagina-instelling "Globaal uitgeschakeld" en kan niet worden gewijzigd.

### Instellings-logica

| Globale instelling | Pagina-instelling | Resultaat |
|---------------------|---------------------|-----------|
| Aan | Globaal gebruiken | Aan |
| Aan | Uit | Uit |
| Uit | (vergrendeld) | Uit |

---

## Veelvoorkomende scenario's

### Scenario 1: standaard intranet

**Doel**: engagement overal standaard aan

**Configuratie**:

- Globaal: alle features AAN
- Pagina's: "Globaal gebruiken"

### Scenario 2: comments uit op beleidspagina's

**Doel**: reacties toestaan, maar geen comments op officiële documenten

**Configuratie**:

- Globaal: alle features AAN
- Beleidspagina's: zet "Comments toestaan" op Uit

### Scenario 3: read-only intranet

**Doel**: geen gebruikers-interactie, alleen content-consumptie

**Configuratie**:

- Globaal: alle features UIT
- Pagina's: geen wijzigingen nodig (globaal uit)

### Scenario 4: aankondigings-pagina's

**Doel**: reacties toestaan, geen discussies

**Configuratie**:

- Globaal: alle features AAN
- Aankondigings-pagina's: comments uitzetten

---

## Best practices

### Voor beheerders

1. **Begin met defaults** — schakel initieel alle features in
2. **Monitor gebruik** — check of gebruikers de features benutten
3. **Communiceer wijzigingen** — informeer gebruikers bij globaal uitschakelen
4. **Gebruik pagina-overrides** — geef voorkeur aan pagina-niveau-uitschakelen boven globaal

### Voor content-managers

| Content-type | Reacties | Comments | Rationale |
|--------------|----------|----------|-----------|
| Nieuws-artikelen | Aan | Aan | Engagement aanmoedigen |
| Beleidsdocumenten | Uit | Uit | Officieel, geen discussie nodig |
| Aankondigingen | Aan | Uit | Erkenning zonder debat |
| Knowledge base | Aan | Aan | Q&A en feedback waardevol |
| Archief-pagina's | Aan | Uit | Historisch, geen nieuwe discussie |

---

## Problemen oplossen

### Gebruikers melden ontbrekende features

1. Check dat globale instellingen aan staan
2. Check pagina-specifieke instellingen
3. Verifieer dat gebruikers leesrechten hebben op de pagina
4. Check browser-console op JavaScript-fouten

### Kan instellingen niet wijzigen

**Admin-instellingen:**

- Verifieer dat je Nextcloud-beheerder-rechten hebt

**Pagina-instellingen:**

- Verifieer dat je bewerk-rechten (schrijven) op de pagina hebt
- Check of feature globaal uit staat (toont "Globaal uitgeschakeld")

### Instellingen hebben geen effect

1. Vraag gebruikers de pagina te verversen (`Ctrl+F5`)
2. Wis Nextcloud-cache: `sudo -u www-data php occ maintenance:repair`
3. Check Nextcloud-logs op fouten

---

## API-referentie

Voor automatisering of integratie:

### Huidige instellingen ophalen

```
GET /apps/intravox/api/settings/engagement
```

Response:

```json
{
  "allowPageReactions": true,
  "allowComments": true,
  "allowCommentReactions": true
}
```

### Instellingen bijwerken (alleen admin)

```
PUT /apps/intravox/api/settings/engagement
Content-Type: application/json

{
  "allowPageReactions": true,
  "allowComments": true,
  "allowCommentReactions": true
}
```

---

## Opslag-details

### Globale instellingen

Opgeslagen in Nextcloud's `oc_appconfig`-tabel:

| App | Key | Waarde |
|-----|-----|--------|
| intravox | allowPageReactions | "1" of "0" |
| intravox | allowComments | "1" of "0" |
| intravox | allowCommentReactions | "1" of "0" |

### Pagina-instellingen

Opgeslagen in het JSON-bestand van elke pagina in het `settings`-object:

```json
{
  "settings": {
    "allowReactions": null,
    "allowComments": false,
    "allowCommentReactions": null
  }
}
```

Waarden: `null` (overerven), `false` (uitgeschakeld)
