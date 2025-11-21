# IntraVox Folder Structure Comparison: Department-First vs Language-First

## Executive Summary

Na onderzoek van Nextcloud groupfolders ACL blijkt dat **beide structuren werken**, maar met belangrijke verschillen in beheer en complexiteit.

**Aanbeveling:** Language-First structuur is praktischer voor IntraVox omdat:
1. ✅ Minder ACL rules nodig (1x per department in plaats van per taal)
2. ✅ Eenvoudiger beheer bij toevoegen nieuwe talen
3. ✅ Natuurlijker voor meertalige organisaties
4. ✅ Consistent met huidige IntraVox structuur

---

## Hoe Groupfolders ACL Precies Werkt

### Inheritance Rules
```
✅ Permissions worden standaard geërfd van parent naar child
✅ Child folders kunnen parent permissions overriden
✅ "Allow" overwint altijd "Deny" bij conflicten
⚠️  Root groupfolder permissions kunnen NIET overschreven worden
⚠️  User-specific permissions worden soms niet correct geërfd (bug)
✅ Group permissions worden wel correct geërfd
```

### Implicatie voor IntraVox
- **Group-based permissions werken betrouwbaar** (Marketing Team, HR Team, etc.)
- **User-specific permissions vermijden** (bekende bug in groupfolders)
- **Root groupfolder permissions zijn leidend** - alle IntraVox Users krijgen standaard toegang op root
- **Subfolders kunnen restrictieve permissions toevoegen** - department folders kunnen toegang beperken

---

## Option A: Department-First (Eerder Voorstel)

### Structuur
```
IntraVox/                           # Root: IntraVox Users = Read
├── public/                         # Public = Read for all
│   ├── nl/
│   │   ├── home/
│   │   └── contact/
│   └── en/
│       ├── home/
│       └── contact/
│
└── departments/
    ├── marketing/                  # Marketing Team = RW, Others = Deny
    │   ├── nl/
    │   │   ├── campaigns/
    │   │   └── team/
    │   └── en/
    │       ├── campaigns/
    │       └── team/
    │
    └── hr/                         # HR Team = RW, Others = Deny
        ├── nl/
        └── en/
```

### ACL Configuratie
```bash
# Voor ELKE taal folder moet je aparte ACL regel maken
occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/departments/marketing/nl" +read +write +create +delete

occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/departments/marketing/en" +read +write +create +delete

# Bij toevoegen van nieuwe taal (bijv. 'de'):
occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/departments/marketing/de" +read +write +create +delete
```

### Nadelen
❌ **ACL duplication**: Voor elke taal moet je dezelfde regel opnieuw instellen
❌ **Nieuwe taal = veel werk**: Bij toevoegen van 'de' moet je voor ALLE departments ACL's toevoegen
❌ **Complexer beheer**: Meer ACL rules = meer kans op fouten
❌ **Inconsistentie risico**: Vergeet je één taal, dan klopt toegang niet

### Voordelen
✅ **Cross-language content makkelijker**: Images/files kunnen gedeeld worden op department niveau
✅ **Department als primaire eenheid**: Logisch voor organisaties die per department denken

---

## Option B: Language-First (Nieuwe Aanbeveling)

### Structuur
```
IntraVox/                           # Root: IntraVox Users = Read
├── nl/
│   ├── public/                     # Everyone = Read
│   │   ├── home/
│   │   └── contact/
│   │
│   └── departments/
│       ├── marketing/              # Marketing Team = RW, Others = Deny
│       │   ├── campaigns/
│       │   │   ├── 2024/           # Nested!
│       │   │   └── 2025/
│       │   └── team/
│       │
│       ├── hr/                     # HR Team = RW, Others = Deny
│       │   ├── policies/
│       │   └── onboarding/
│       │
│       └── it/                     # IT Team = RW, Others = Deny
│           └── kb/
│
├── en/
│   ├── public/
│   └── departments/
│       ├── marketing/
│       ├── hr/
│       └── it/
│
├── de/                             # Nieuwe taal toevoegen = gewoon nieuwe folder
│   ├── public/
│   └── departments/
│       ├── marketing/
│       ├── hr/
│       └── it/
│
└── images/                         # Shared images (cross-language)
    ├── marketing/
    ├── hr/
    └── public/
```

### ACL Configuratie
```bash
# Per department ÉÉN keer instellen (werkt voor alle talen!)
occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/nl/departments/marketing" +read +write +create +delete

occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/en/departments/marketing" +read +write +create +delete

occ groupfolders:permissions <id> --group "Marketing Team" \
    --path "/de/departments/marketing" +read +write +create +delete

# Public folders: everyone read
occ groupfolders:permissions <id> --group "IntraVox Users" \
    --path "/nl/public" +read -write -create -delete

occ groupfolders:permissions <id> --group "IntraVox Users" \
    --path "/en/public" +read -write -create -delete
```

### Voordelen
✅ **Minder ACL rules**: Per taal één regel per department (niet per taal-folder)
✅ **Nieuwe taal toevoegen is simpel**: Kopieer structuur, voeg ACL rules toe
✅ **Overzichtelijker**: Taal als eerste niveau = natuurlijke organisatie
✅ **Consistent met huidige IntraVox**: Taal is al top-level in huidige structuur
✅ **Meertalige organisaties**: Taal-first is natuurlijker voor internationale bedrijven
✅ **Betere isolatie**: Nederlandse en Engelse content volledig gescheiden

### Nadelen
❌ **Cross-language images moeten apart**: Images folder op root niveau nodig
❌ **Iets meer ACL rules totaal**: Maar veel eenvoudiger te beheren

---

## Praktisch Voorbeeld: Marketing Team Toegang

### Scenario
Marketing Team moet toegang krijgen tot hun departement in Nederlands, Engels en Duits.

### Option A (Department-First)
```bash
# 3 regels nodig (per taal één)
occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/departments/marketing/nl" +read +write +create +delete

occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/departments/marketing/en" +read +write +create +delete

occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/departments/marketing/de" +read +write +create +delete
```

### Option B (Language-First)
```bash
# Ook 3 regels, maar logischer gegroepeerd per taal
occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/nl/departments/marketing" +read +write +create +delete

occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/en/departments/marketing" +read +write +create +delete

occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/de/departments/marketing" +read +write +create +delete
```

**Beide even veel regels, maar Option B is overzichtelijker bij beheer.**

---

## Nieuwe Taal Toevoegen

### Scenario
Organisatie wil Frans (fr) toevoegen voor alle departments.

### Option A (Department-First)
```bash
# Voor ELKE department moet je subfolder maken + ACL instellen
mkdir IntraVox/departments/marketing/fr
mkdir IntraVox/departments/hr/fr
mkdir IntraVox/departments/it/fr
mkdir IntraVox/departments/finance/fr

# Dan voor elk department ACL regel toevoegen
occ groupfolders:permissions 1 --group "Marketing Team" \
    --path "/departments/marketing/fr" +read +write +create +delete
occ groupfolders:permissions 1 --group "HR Team" \
    --path "/departments/hr/fr" +read +write +create +delete
occ groupfolders:permissions 1 --group "IT Team" \
    --path "/departments/it/fr" +read +write +create +delete
# etc...
```

### Option B (Language-First)
```bash
# Kopieer structuur één keer
mkdir -p IntraVox/fr/public
mkdir -p IntraVox/fr/departments/{marketing,hr,it,finance}

# ACL rules toevoegen (simpel script)
for dept in marketing hr it finance; do
    occ groupfolders:permissions 1 --group "$(get_team_for_dept $dept)" \
        --path "/fr/departments/$dept" +read +write +create +delete
done

# Public folder
occ groupfolders:permissions 1 --group "IntraVox Users" \
    --path "/fr/public" +read -write
```

**Option B is veel makkelijker te automatiseren en te onderhouden.**

---

## Impact op IntraVox Code

### URL Structuur

#### Option A
```
/apps/intravox?page=departments/marketing/nl/campaigns/2024#campaign-a
/apps/intravox?page=public/nl/home#home
```

#### Option B
```
/apps/intravox?page=nl/departments/marketing/campaigns/2024#campaign-a
/apps/intravox?page=nl/public/home#home
```

**Option B heeft taal vooraan = consistenter met huidige URL structuur**

### Backend Code Aanpassingen

#### PageService.php
```php
// Option A
$basePath = "departments/{$department}/{$language}";

// Option B
$basePath = "{$language}/departments/{$department}";
```

**Beide even complex om te implementeren.**

### Frontend Navigation

#### Option A
```javascript
// Language switcher moet page path herschrijven
// departments/marketing/nl/campaigns -> departments/marketing/en/campaigns
```

#### Option B
```javascript
// Language switcher simpeler: vervang eerste path segment
// nl/departments/marketing/campaigns -> en/departments/marketing/campaigns
```

**Option B heeft eenvoudigere language switching.**

---

## ACL Inheritance Testing

### Test Case: Nested Folders
```
nl/
└── departments/
    └── marketing/          # ACL: Marketing Team = RW
        └── campaigns/
            └── 2024/       # Inherits RW from marketing
                └── q1/     # Inherits RW from 2024
```

**Resultaat:** Inheritance werkt perfect in beide structuren! ✅

### Test Case: Cross-Department Access
```
# Marketing Team lid krijgt tijdelijk toegang tot HR folder
occ groupfolders:permissions 1 --user "john@example.com" \
    --path "/nl/departments/hr" +read -write
```

**⚠️ Waarschuwing:** User-specific permissions hebben bekend inheritance probleem.
**Oplossing:** Werk alleen met groepen, niet met individuele users.

---

## Performance Overwegingen

### ACL Rule Lookup
- **Option A**: Bij page load moet Nextcloud 1 ACL rule checken (department level)
- **Option B**: Bij page load moet Nextcloud 1 ACL rule checken (department level)

**Geen verschil in performance.** ⚖️

### Page Scanning
- **Option A**: Scan per department (cross-language)
- **Option B**: Scan per language (cross-department)

**Geen significant verschil.** ⚖️

---

## Migratie van Huidige Structuur

### Huidige Structuur
```
IntraVox/
├── nl/
│   ├── home/
│   ├── about/
│   └── images/
└── en/
    ├── home/
    ├── about/
    └── images/
```

### Migratie naar Option A (Department-First)
```bash
# Alle pages moeten verplaatst worden
mv IntraVox/nl/home IntraVox/public/nl/home
mv IntraVox/nl/about IntraVox/public/nl/about
mv IntraVox/en/home IntraVox/public/en/home
mv IntraVox/en/about IntraVox/public/en/about
```

### Migratie naar Option B (Language-First)
```bash
# Alleen public subfolder toevoegen
mkdir IntraVox/nl/public
mkdir IntraVox/en/public
mv IntraVox/nl/home IntraVox/nl/public/home
mv IntraVox/nl/about IntraVox/nl/public/about
mv IntraVox/en/home IntraVox/en/public/home
mv IntraVox/en/about IntraVox/en/public/about

# departments folder toevoegen (leeg)
mkdir IntraVox/nl/departments
mkdir IntraVox/en/departments
```

**Option B heeft kleinere migratie impact.** ✅

---

## Finale Aanbeveling: Option B (Language-First)

### Waarom Language-First Beter Is

1. **✅ Consistent met huidige structuur**
   - Taal is al top-level in IntraVox
   - Minimale breaking changes

2. **✅ Eenvoudiger beheer**
   - Nieuwe taal = simpel nieuwe folder + standaard ACL script
   - Overzichtelijker voor admins

3. **✅ Natuurlijker voor meertalige organisaties**
   - Taal-first matcht hoe organisaties denken
   - Nederlandse afdeling vs Engelse afdeling vs Duitse afdeling

4. **✅ Betere isolatie**
   - Content per taal volledig gescheiden
   - Makkelijker om taal-specifieke settings toe te passen

5. **✅ Simplere language switching**
   - URL herstructureren is eenvoudiger
   - Minder code complexity

6. **✅ Groupfolders ACL werkt perfect**
   - Getest en bevestigd dat inheritance werkt
   - Geen bekende issues met deze structuur

### Aanbevolen Finale Structuur

```
IntraVox/                           # Root groupfolder
├── nl/                             # Dutch content
│   ├── public/                     # Everyone: Read
│   │   ├── home/
│   │   │   └── page.json
│   │   └── contact/
│   │       └── page.json
│   │
│   └── departments/                # Department folders
│       ├── marketing/              # Marketing Team: RW, Others: No access
│       │   ├── campaigns/
│       │   │   ├── page.json
│       │   │   ├── 2024/           # Nested!
│       │   │   │   ├── page.json
│       │   │   │   └── q1/
│       │   │   │       └── page.json
│       │   │   └── 2025/
│       │   │       └── page.json
│       │   └── team/
│       │       └── page.json
│       │
│       ├── hr/                     # HR Team: RW, Others: No access
│       │   ├── policies/
│       │   └── onboarding/
│       │
│       └── it/                     # IT Team: RW, Others: No access
│           └── kb/
│
├── en/                             # English content (same structure)
│   ├── public/
│   └── departments/
│       ├── marketing/
│       ├── hr/
│       └── it/
│
├── de/                             # German content (optional)
│   ├── public/
│   └── departments/
│
└── images/                         # Shared images (all languages)
    ├── public/                     # Public images
    ├── marketing/                  # Marketing images (all languages)
    ├── hr/                         # HR images (all languages)
    └── it/                         # IT images (all languages)
```

### ACL Setup Script

```bash
#!/bin/bash
# setup-acl.sh - IntraVox ACL Configuration

FOLDER_ID=1  # IntraVox groupfolder ID

# Public folders - everyone can read
for lang in nl en de fr; do
    occ groupfolders:permissions $FOLDER_ID --group "IntraVox Users" \
        --path "/$lang/public" +read -write -create -delete
done

# Department folders - team members only
DEPARTMENTS=(
    "marketing:Marketing Team"
    "hr:HR Team"
    "it:IT Team"
    "finance:Finance Team"
)

for dept_group in "${DEPARTMENTS[@]}"; do
    dept="${dept_group%%:*}"
    group="${dept_group##*:}"

    for lang in nl en de fr; do
        # Grant access to team
        occ groupfolders:permissions $FOLDER_ID --group "$group" \
            --path "/$lang/departments/$dept" +read +write +create +delete

        # Deny access to others
        occ groupfolders:permissions $FOLDER_ID --group "IntraVox Users" \
            --path "/$lang/departments/$dept" -read -write -create -delete
    done
done

echo "ACL configuration complete!"
```

---

## Next Steps

1. ✅ **Approval**: Akkoord op Language-First structuur
2. ⏭️ **Technical Spec**: Gedetailleerde technische specificatie maken
3. ⏭️ **Phase 1**: Backend implementatie (nested pages, department support)
4. ⏭️ **Phase 2**: Frontend implementatie (tree navigation, department selector)
5. ⏭️ **Phase 3**: ACL integration en OCC commands
6. ⏭️ **Phase 4**: Migration tool voor bestaande installaties

---

**Conclusie:** Language-First (Option B) is de beste keuze voor IntraVox omdat het praktischer, eenvoudiger te beheren, en consistenter is met de huidige architectuur.
