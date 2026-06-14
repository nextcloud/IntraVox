# Template-API-quickstart

Begin in 5 minuten met de IntraVox-Template-API.

## Inhoudsopgave

- [Authenticatie](#authenticatie)
- [Quick reference](#quick-reference)
- [Use case 1: templates oplijsten](#use-case-1-templates-oplijsten)
- [Use case 2: pagina maken vanuit template](#use-case-2-pagina-maken-vanuit-template)
- [Use case 3: custom template opslaan](#use-case-3-custom-template-opslaan)
- [Foutafhandeling](#foutafhandeling)
- [Code-voorbeelden](#code-voorbeelden)
- [Volgende stappen](#volgende-stappen)

## Authenticatie

De IntraVox-API gebruikt HTTP Basic Authentication met Nextcloud-app-wachtwoorden.

**App-wachtwoord aanmaken:**

1. Nextcloud-instellingen → Beveiliging → Apparaten & sessies
2. Maak een nieuw app-wachtwoord aan
3. Kopieer het gegenereerde token

**Gebruik in API-calls:**

```bash
curl -u "username:app-password-token" \
  https://your-nextcloud.com/apps/intravox/api/templates
```

> **Let op:** gebruik nooit je echte Nextcloud-wachtwoord voor API-calls. Gebruik altijd app-specifieke wachtwoorden voor beveiliging.

## Quick reference

| Endpoint | Methode | Doel | Auth vereist |
|----------|---------|------|---------------|
| `/api/templates` | GET | Lijst alle beschikbare templates | Ja |
| `/api/templates/{id}` | GET | Template-details ophalen | Ja |
| `/api/pages/from-template` | POST | **Pagina maken vanuit template** | Ja |
| `/api/templates` | POST | Pagina opslaan als custom template | Ja |
| `/api/templates/{id}` | DELETE | Custom template verwijderen | Ja |

**Ingebouwde templates:** department, event, knowledge-base, landing-page, news-article, news-hub, project

Volledige API-referentie: [api-reference.md](api-reference.md)

## Use case 1: templates oplijsten

**Doel:** alle beschikbare templates met metadata ophalen

**Request:**

```bash
curl -X GET \
  -u "username:app-password" \
  https://your-nextcloud.com/apps/intravox/api/templates
```

**Response (200 OK):**

```json
{
  "templates": [
    {
      "id": "department",
      "name": "Department",
      "description": "Team-informatie, services en resources",
      "columnCount": 3,
      "widgetCount": 12,
      "widgetTypes": ["heading", "text", "image", "links"],
      "complexity": "medium",
      "hasHeaderRow": true,
      "hasSidebars": false
    },
    {
      "id": "event",
      "name": "Event",
      "description": "Event-planning, sprekers en registratie",
      "columnCount": 3,
      "widgetCount": 15,
      "widgetTypes": ["heading", "text", "image", "video", "links"],
      "complexity": "advanced",
      "hasHeaderRow": true,
      "hasSidebars": true
    }
  ]
}
```

**Wat je krijgt:**

- `id` — template-identifier (gebruik dit om pagina's aan te maken)
- `name` — weergavenaam
- `description` — doel van het template
- `complexity` — simple, medium of advanced
- `columnCount` — aantal kolommen in layout
- `widgetCount` — totaal aantal widgets in template
- `widgetTypes` — types widgets gebruikt
- `hasHeaderRow` — of het template een header-rij heeft
- `hasSidebars` — of het template side-columns heeft

## Use case 2: pagina maken vanuit template

**Doel:** een nieuwe "HR-afdeling"-pagina maken op basis van het "department"-template

**Request:**

```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "templateId": "department",
    "pageTitle": "HR-afdeling",
    "parentPath": "/teams"
  }' \
  https://your-nextcloud.com/apps/intravox/api/pages/from-template
```

**Request-parameters:**

- `templateId` (**verplicht**) — template-ID uit de lijst (bv. "department", "event")
- `pageTitle` (**verplicht**) — titel voor je nieuwe pagina
- `parentPath` (optioneel) — parent-map-pad (bv. "/teams", "/departments")

**Response (201 Created):**

```json
{
  "success": true,
  "page": {
    "id": "hr-afdeling",
    "title": "HR-afdeling",
    "path": "/teams/hr-afdeling",
    "uniqueId": "a3f8e9c2-4d5b-6f7e-8a9b-0c1d2e3f4a5b",
    "layout": {
      "columns": 3,
      "hasHeaderRow": true,
      "hasSidebars": false
    },
    "widgets": [
      {
        "type": "heading",
        "content": "Welkom bij de HR-afdeling"
      }
    ]
  }
}
```

**Wat er gebeurt:**

1. Template-layout en -widgets worden gekloond naar de nieuwe pagina
2. Nieuwe pagina wordt aangemaakt in de gespecificeerde parent-map (of root indien weggelaten)
3. Media-bestanden worden gekopieerd uit de template-directory
4. Unique-page-ID wordt gegenereerd voor permalinks
5. Pagina is direct toegankelijk in de IntraVox-UI

**Meer voorbeelden:**

Event-pagina maken zonder parent-map:

```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "templateId": "event",
    "pageTitle": "Jaarcongres 2026"
  }' \
  https://your-nextcloud.com/apps/intravox/api/pages/from-template
```

Knowledge-base-pagina maken in docs-map:

```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "templateId": "knowledge-base",
    "pageTitle": "API-documentatie",
    "parentPath": "/docs"
  }' \
  https://your-nextcloud.com/apps/intravox/api/pages/from-template
```

## Use case 3: custom template opslaan

**Doel:** je bestaande pagina opslaan als herbruikbaar template

**Request:**

```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "pageId": "mijn-custom-pagina",
    "name": "Mijn-project-template",
    "description": "Custom layout voor project-pagina's"
  }' \
  https://your-nextcloud.com/apps/intravox/api/templates
```

**Request-parameters:**

- `pageId` (**verplicht**) — unique ID van bestaande pagina om als template op te slaan
- `name` (**verplicht**) — template-naam (getoond in template-picker)
- `description` (optioneel) — template-beschrijving

**Response (200 OK):**

```json
{
  "success": true,
  "templateId": "mijn-project-template"
}
```

**Custom template verwijderen:**

```bash
curl -X DELETE \
  -u "username:app-password" \
  https://your-nextcloud.com/apps/intravox/api/templates/mijn-project-template
```

> **Let op:** ingebouwde templates (department, event, enz.) kunnen niet verwijderd worden. Alleen custom templates kunnen verwijderd worden.

## Foutafhandeling

**Veelvoorkomende fouten:**

| HTTP-code | Fout | Oorzaak | Oplossing |
|-----------|------|---------|-----------|
| 401 | Unauthorized | Ongeldige credentials | Check username/app-wachtwoord |
| 400 | Invalid template ID | Template bestaat niet | Gebruik ID uit `GET /api/templates` |
| 400 | Missing required parameter | `templateId` of `pageTitle` niet meegegeven | Vul alle verplichte velden in |
| 403 | Forbidden | Geen schrijfrechten in map | Check GroupFolder-ACL-permissies |
| 404 | Not Found | Pagina of template niet gevonden | Verifieer dat pagina-/template-ID bestaat |

**Voorbeeld-error-response:**

```json
{
  "error": "Template niet gevonden: invalid-id",
  "errorId": "6f8a4c2b"
}
```

Gebruik `errorId` bij het rapporteren van issues aan support voor makkelijkere debugging.

**Validatie-errors:**

```json
{
  "error": "Verplichte parameter ontbreekt: templateId"
}
```

## Code-voorbeelden

### JavaScript (Fetch-API)

```javascript
// Templates oplijsten
async function listTemplates() {
  const response = await fetch(
    'https://your-nc.com/apps/intravox/api/templates',
    {
      method: 'GET',
      headers: {
        'Authorization': 'Basic ' + btoa('username:app-password')
      }
    }
  );

  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
  }

  const data = await response.json();
  console.log('Templates:', data.templates);
  return data.templates;
}

// Pagina maken vanuit template
async function createPageFromTemplate(templateId, pageTitle, parentPath = null) {
  const body = {
    templateId,
    pageTitle
  };

  if (parentPath) {
    body.parentPath = parentPath;
  }

  const response = await fetch(
    'https://your-nc.com/apps/intravox/api/pages/from-template',
    {
      method: 'POST',
      headers: {
        'Authorization': 'Basic ' + btoa('username:app-password'),
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(body)
    }
  );

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.error || 'Pagina aanmaken mislukt');
  }

  const data = await response.json();
  console.log('Pagina aangemaakt:', data.page.id);
  return data.page;
}

// Gebruik
try {
  const templates = await listTemplates();
  const page = await createPageFromTemplate('department', 'HR-afdeling', '/teams');
  console.log('Gelukt! Pagina-URL:', `/apps/intravox#${page.path}`);
} catch (error) {
  console.error('Error:', error.message);
}
```

### Python (requests)

```python
import requests
from requests.auth import HTTPBasicAuth

# Configuratie
BASE_URL = 'https://your-nc.com/apps/intravox/api'
auth = HTTPBasicAuth('username', 'app-password')

# Templates oplijsten
def list_templates():
    response = requests.get(f'{BASE_URL}/templates', auth=auth)
    response.raise_for_status()
    templates = response.json()['templates']
    print(f'Gevonden {len(templates)} templates')
    return templates

# Pagina maken vanuit template
def create_page_from_template(template_id, page_title, parent_path=None):
    payload = {
        'templateId': template_id,
        'pageTitle': page_title
    }

    if parent_path:
        payload['parentPath'] = parent_path

    response = requests.post(
        f'{BASE_URL}/pages/from-template',
        auth=auth,
        json=payload
    )
    response.raise_for_status()
    page = response.json()['page']
    print(f'Pagina aangemaakt: {page["id"]}')
    return page

# Pagina opslaan als template
def save_as_template(page_id, name, description=None):
    payload = {
        'pageId': page_id,
        'name': name
    }

    if description:
        payload['description'] = description

    response = requests.post(
        f'{BASE_URL}/templates',
        auth=auth,
        json=payload
    )
    response.raise_for_status()
    template_id = response.json()['templateId']
    print(f'Template opgeslagen: {template_id}')
    return template_id

# Gebruik
try:
    templates = list_templates()
    page = create_page_from_template('department', 'HR-afdeling', '/teams')
    print(f'Gelukt! Pagina-pad: {page["path"]}')
except requests.exceptions.HTTPError as e:
    print(f'HTTP-error: {e.response.status_code}')
    print(f'Foutmelding: {e.response.json().get("error")}')
except Exception as e:
    print(f'Error: {e}')
```

### PHP (Guzzle)

```php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Configuratie
$client = new Client([
    'base_uri' => 'https://your-nc.com/apps/intravox/api/',
    'auth' => ['username', 'app-password']
]);

// Templates oplijsten
function listTemplates($client) {
    try {
        $response = $client->get('templates');
        $data = json_decode($response->getBody(), true);
        $templates = $data['templates'];
        echo "Gevonden " . count($templates) . " templates\n";
        return $templates;
    } catch (RequestException $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return [];
    }
}

// Pagina maken vanuit template
function createPageFromTemplate($client, $templateId, $pageTitle, $parentPath = null) {
    $payload = [
        'templateId' => $templateId,
        'pageTitle' => $pageTitle
    ];

    if ($parentPath) {
        $payload['parentPath'] = $parentPath;
    }

    try {
        $response = $client->post('pages/from-template', [
            'json' => $payload
        ]);
        $page = json_decode($response->getBody(), true)['page'];
        echo "Pagina aangemaakt: {$page['id']}\n";
        return $page;
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $error = json_decode($e->getResponse()->getBody(), true);
            echo "Error: " . ($error['error'] ?? 'Onbekende fout') . "\n";
        }
        return null;
    }
}

// Pagina opslaan als template
function saveAsTemplate($client, $pageId, $name, $description = null) {
    $payload = [
        'pageId' => $pageId,
        'name' => $name
    ];

    if ($description) {
        $payload['description'] = $description;
    }

    try {
        $response = $client->post('templates', [
            'json' => $payload
        ]);
        $templateId = json_decode($response->getBody(), true)['templateId'];
        echo "Template opgeslagen: $templateId\n";
        return $templateId;
    } catch (RequestException $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return null;
    }
}

// Gebruik
$templates = listTemplates($client);
$page = createPageFromTemplate($client, 'department', 'HR-afdeling', '/teams');
if ($page) {
    echo "Gelukt! Pagina-pad: {$page['path']}\n";
}
```

### cURL (shell-script)

```bash
#!/bin/bash

# Configuratie
BASE_URL="https://your-nextcloud.com/apps/intravox/api"
AUTH="username:app-password"

# Templates oplijsten
list_templates() {
    echo "Templates ophalen..."
    curl -s -u "$AUTH" "$BASE_URL/templates" | jq '.'
}

# Pagina maken vanuit template
create_page_from_template() {
    local template_id=$1
    local page_title=$2
    local parent_path=${3:-}

    local payload='{"templateId":"'$template_id'","pageTitle":"'$page_title'"'

    if [ -n "$parent_path" ]; then
        payload+=', "parentPath":"'$parent_path'"'
    fi

    payload+='}'

    echo "Pagina '$page_title' maken vanuit template '$template_id'..."
    curl -s -X POST \
        -u "$AUTH" \
        -H "Content-Type: application/json" \
        -d "$payload" \
        "$BASE_URL/pages/from-template" | jq '.'
}

# Pagina opslaan als template
save_as_template() {
    local page_id=$1
    local name=$2
    local description=${3:-}

    local payload='{"pageId":"'$page_id'","name":"'$name'"'

    if [ -n "$description" ]; then
        payload+=', "description":"'$description'"'
    fi

    payload+='}'

    echo "Pagina '$page_id' opslaan als template '$name'..."
    curl -s -X POST \
        -u "$AUTH" \
        -H "Content-Type: application/json" \
        -d "$payload" \
        "$BASE_URL/templates" | jq '.'
}

# Gebruik-voorbeelden
list_templates
create_page_from_template "department" "HR-afdeling" "/teams"
# save_as_template "my-page-id" "Mijn-custom-template" "Custom project-layout"
```

## Volgende stappen

**Verken verder:**

- **Volledige API-referentie:** [api-reference.md](api-reference.md) — complete endpoint-documentatie met alle parameters en responses
- **Template-features:** [TEMPLATES.md](../user/templates.md) — overzicht van het template-systeem en ingebouwde templates
- **OpenAPI-tooling:** [openapi-tooling.md](openapi-tooling.md) — gebruik Swagger UI, Postman en code-generators met de IntraVox-API
- **Autorisatie:** [authorization.md](../admin/authorization.md) — GroupFolder-permissies en toegangscontrole

**Geavanceerde onderwerpen:**

- **Bulk-operaties:** programmatisch meerdere pagina's maken vanuit templates
- **Custom workflows:** integreer template-creatie in je CI/CD-pipelines
- **Webhooks:** trigger pagina-creatie vanuit externe events
- **Media-management:** upload en beheer template-media-bestanden via WebDAV

**Hulp nodig?**

- GitHub Issues: https://github.com/nextcloud/IntraVox/issues
- Nextcloud-community: https://help.nextcloud.com
