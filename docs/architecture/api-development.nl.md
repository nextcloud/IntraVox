# API-development-gids

Gids voor IntraVox-contributors om nieuwe API-endpoints toe te voegen en OpenAPI-documentatie te onderhouden.

## Inhoudsopgave

- [Architectuur-overzicht](#architectuur-overzicht)
- [Een nieuw endpoint toevoegen](#een-nieuw-endpoint-toevoegen)
- [OpenAPI-spec bijwerken](#openapi-spec-bijwerken)
- [Schema-best-practices](#schema-best-practices)
- [Validatie-checklist](#validatie-checklist)
- [Case study: template-endpoints](#case-study-template-endpoints)
- [Resources](#resources)

## Architectuur-overzicht

De IntraVox-API volgt een gelaagd architectuur-patroon:

```
HTTP-request
    ↓
Router (appinfo/routes.php)
    ↓
Controller (lib/Controller/ApiController.php)
    ↓
Service-laag (lib/Service/PageService.php, enz.)
    ↓
Data-access (filesystem via Nextcloud-API)
    ↓
HTTP-response (JSONResponse)
```

### Componenten

**Router (`appinfo/routes.php`):**

- Mapt URL-patronen naar controller-methoden
- Definieert HTTP-verbs (GET, POST, PUT, DELETE)
- Groepeert gerelateerde endpoints

**Controller (`lib/Controller/ApiController.php`):**

- Verwerkt HTTP-zaken (request-parsing, auth-checks)
- Valideert input-parameters
- Delegeert business-logica naar services
- Formatteert responses als JSON
- Vangt exceptions op en geeft passende HTTP-statuscodes terug

**Service-laag (`lib/Service/`):**

- Bevat business-logica
- Praat met de Nextcloud-filesystem-API
- Herbruikbaar over controllers, CLI-commando's en background jobs heen
- Gooit exceptions bij fouten (controller vangt ze op)

**OpenAPI-spec (`openapi.json`):**

- Single source of truth voor API-contracten
- Documenteert alle endpoints, parameters en responses
- Gebruikt door Swagger UI, Postman, code-generators
- Moet in sync blijven met de daadwerkelijke implementatie

### Design-principes

1. **Thin controllers** — delegeer naar services, geen business-logica
2. **Herbruikbare services** — gebruikt door API, CLI, cron-jobs
3. **OpenAPI als contract** — de spec definieert wat endpoints horen te doen
4. **Consistente error-afhandeling** — alle errors geven JSON terug met `error`- en `errorId`-velden
5. **Semantic versioning** — breaking changes vereisen een nieuwe API-versie

## Een nieuw endpoint toevoegen

Volg deze stappen om een nieuw API-endpoint aan IntraVox toe te voegen.

### Stap 1: route definiëren

**Bestand:** `appinfo/routes.php`

Voeg de route toe aan de `'routes'`-array:

```php
[
    'name' => 'api#myNewEndpoint',
    'url' => '/api/my-resource',
    'verb' => 'POST'
]
```

**Naam-conventies:**

- Route-naam: `api#{camelCase}` (bv. `api#createPageFromTemplate`)
- URL-pad: `/api/{kebab-case}` (bv. `/api/pages/from-template`)
- HTTP-verb: GET (lees), POST (aanmaken), PUT (bijwerken), DELETE (verwijderen)

**Voorbeelden:**

```php
// Resources oplijsten
['name' => 'api#listResources', 'url' => '/api/resources', 'verb' => 'GET']

// Enkele resource ophalen
['name' => 'api#getResource', 'url' => '/api/resources/{id}', 'verb' => 'GET']

// Resource aanmaken
['name' => 'api#createResource', 'url' => '/api/resources', 'verb' => 'POST']

// Resource bijwerken
['name' => 'api#updateResource', 'url' => '/api/resources/{id}', 'verb' => 'PUT']

// Resource verwijderen
['name' => 'api#deleteResource', 'url' => '/api/resources/{id}', 'verb' => 'DELETE']
```

### Stap 2: controller-methode implementeren

**Bestand:** `lib/Controller/ApiController.php`

Voeg een publieke methode toe die de route-naam matched:

```php
/**
 * Maak een nieuwe resource aan
 *
 * @NoAdminRequired
 * @param string $param Verplichte parameter
 * @param string|null $optionalParam Optionele parameter
 * @return JSONResponse
 */
public function myNewEndpoint(string $param, ?string $optionalParam = null): JSONResponse {
    try {
        // 1. Input valideren
        if (empty($param)) {
            return new JSONResponse([
                'error' => 'Parameter "param" is verplicht'
            ], 400);
        }

        // 2. Permissies checken (indien nodig)
        if (!$this->permissionService->canWrite($this->userId, '/some/path')) {
            return new JSONResponse([
                'error' => 'Toegang geweigerd'
            ], 403);
        }

        // 3. Delegeren naar service
        $result = $this->pageService->doSomething($param, $optionalParam);

        // 4. Success-response teruggeven
        return new JSONResponse([
            'success' => true,
            'data' => $result
        ], 200);

    } catch (\InvalidArgumentException $e) {
        // 5. Validatie-errors afhandelen
        return new JSONResponse([
            'error' => $e->getMessage()
        ], 400);

    } catch (\Exception $e) {
        // 6. Onverwachte errors afhandelen
        $errorId = bin2hex(random_bytes(4));
        $this->logger->error('myNewEndpoint failed', [
            'errorId' => $errorId,
            'exception' => $e->getMessage(),
            'param' => $param,
            'userId' => $this->userId
        ]);

        return new JSONResponse([
            'error' => 'Interne server-fout',
            'errorId' => $errorId
        ], 500);
    }
}
```

**Annotaties:**

- `@NoAdminRequired` — sta niet-admin-gebruikers toe (default: alleen admin)
- `@NoCSRFRequired` — sla CSRF-check over (alleen voor read-only GET-endpoints)
- `@PublicPage` — sta anonieme toegang toe (voorzichtig gebruiken)

**Response-conventies:**

- Succes: `{'success': true, 'data': ...}` met 200 (of 201 bij aanmaak)
- Client-error: `{'error': 'message'}` met 400/403/404
- Server-error: `{'error': 'message', 'errorId': 'abc123'}` met 500

### Stap 3: service-logica implementeren

**Bestand:** `lib/Service/PageService.php` (of nieuwe service maken)

Voeg een business-logica-methode toe:

```php
/**
 * Doe iets met een resource
 *
 * @param string $param Parameter-beschrijving
 * @param string|null $optionalParam Optionele parameter
 * @return array Resultaat-data
 * @throws \InvalidArgumentException als validatie faalt
 * @throws \Exception als operatie faalt
 */
public function doSomething(string $param, ?string $optionalParam = null): array {
    // 1. Business-regels valideren
    if (strlen($param) < 3) {
        throw new \InvalidArgumentException('Parameter moet minstens 3 tekens zijn');
    }

    // 2. Data ophalen (filesystem, database)
    $folder = $this->rootFolder->getUserFolder($this->userId);
    $file = $folder->get('/IntraVox/' . $param);

    // 3. Business-logica uitvoeren
    $content = $file->getContent();
    $processed = $this->processContent($content);

    // 4. Resultaat teruggeven
    return [
        'id' => $param,
        'content' => $processed,
        'optional' => $optionalParam
    ];
}

/**
 * Helper-methode voor verwerking
 *
 * @param string $content
 * @return string
 */
private function processContent(string $content): string {
    // Verwerkings-logica hier
    return strtoupper($content);
}
```

**Service-verantwoordelijkheden:**

- Business-logica en validatie
- Data-access (bestanden, database)
- Data-transformatie
- Gooi exceptions bij errors (controller vangt op)
- Geen HTTP-zaken (geen JSONResponse, geen statuscodes)

### Stap 4: OpenAPI-spec bijwerken

**Bestand:** `openapi.json`

Voeg endpoint-documentatie toe. Zie sectie [OpenAPI-spec bijwerken](#openapi-spec-bijwerken).

### Stap 5: testen

Zie sectie [Validatie-checklist](#validatie-checklist).

## OpenAPI-spec bijwerken

### Path-entry toevoegen

**Locatie:** `openapi.json` → `paths`-object

```json
"/api/my-resource": {
  "post": {
    "operationId": "my-new-endpoint",
    "summary": "Maak een nieuwe resource aan",
    "description": "Maakt een nieuwe resource aan met de gegeven parameters. Vereist schrijfrechten op de doel-map.",
    "tags": ["Resources"],
    "requestBody": {
      "required": true,
      "content": {
        "application/json": {
          "schema": {
            "$ref": "#/components/schemas/ResourceCreateRequest"
          },
          "example": {
            "param": "my-value",
            "optionalParam": "optional-value"
          }
        }
      }
    },
    "responses": {
      "200": {
        "description": "Resource succesvol aangemaakt",
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/ResourceResponse"
            }
          }
        }
      },
      "400": {
        "description": "Ongeldige parameters",
        "content": {
          "application/json": {
            "schema": {
              "type": "object",
              "properties": {
                "error": { "type": "string" }
              }
            },
            "example": {
              "error": "Parameter \"param\" is verplicht"
            }
          }
        }
      },
      "401": {
        "$ref": "#/components/responses/Unauthorized"
      },
      "403": {
        "$ref": "#/components/responses/Forbidden"
      }
    }
  }
}
```

### Schema-definities toevoegen

**Locatie:** `openapi.json` → `components` → `schemas`

```json
"ResourceCreateRequest": {
  "type": "object",
  "required": ["param"],
  "properties": {
    "param": {
      "type": "string",
      "description": "Verplichte parameter (minimum 3 tekens)",
      "minLength": 3
    },
    "optionalParam": {
      "type": "string",
      "nullable": true,
      "description": "Optionele parameter voor aanvullende configuratie"
    }
  }
},
"ResourceResponse": {
  "type": "object",
  "properties": {
    "success": {
      "type": "boolean"
    },
    "data": {
      "type": "object",
      "properties": {
        "id": {
          "type": "string",
          "description": "Resource-identifier"
        },
        "content": {
          "type": "string",
          "description": "Verwerkte content"
        },
        "optional": {
          "type": "string",
          "nullable": true,
          "description": "Optionele parameter-waarde"
        }
      }
    }
  }
}
```

### Tag toevoegen (bij nieuwe categorie)

**Locatie:** `openapi.json` → `tags`-array

```json
{
  "name": "Resources",
  "description": "Resource-management-endpoints voor aanmaken, lezen, bijwerken en verwijderen van resources"
}
```

## Schema-best-practices

### Naam-conventies

| Element | Conventie | Voorbeeld | Waarom |
|---------|-----------|-----------|--------|
| operationId | kebab-case | `create-page-from-template` | URL-friendly, leesbaar |
| Schema-naam | PascalCase | `TemplateCreateFromRequest` | Matched met class-naming |
| Tag-naam | PascalCase, enkelvoud | `Templates` (niet `Template`) | Consistentie |
| Property-naam | camelCase | `pageTitle`, `parentPath` | JavaScript-conventie |

**Schema-naam-patterns:**

- Request-schema's: `{Resource}{Action}Request`
  - `TemplateCreateFromRequest` — pagina maken vanuit template
  - `TemplateSaveRequest` — pagina opslaan als template
  - `PageUpdateRequest` — pagina bijwerken
- Response-schema's: `{Resource}` of `{Resource}Response`
  - `Page` — volledig pagina-object
  - `TemplatePreview` — template-lijst-item (lichtgewicht)
  - `ErrorResponse` — error-details
- Suffixen:
  - `Request` — input-data voor POST/PUT
  - `Response` — output-data (optioneel als gelijk aan resource)
  - `Preview` — lichtgewicht-versie voor lijsten

### Verplichte velden

Specificeer altijd een `required`-array voor object-schema's:

```json
{
  "type": "object",
  "required": ["field1", "field2"],
  "properties": {
    "field1": { "type": "string" },
    "field2": { "type": "integer" },
    "field3": { "type": "string", "nullable": true }
  }
}
```

**Regels:**

- Lijst alleen écht verplichte velden (validatie faalt zonder ze)
- Optionele velden: weglaten uit `required`-array
- Nullable velden: voeg `"nullable": true` toe
- Markeer nooit alle velden als verplicht als sommige optioneel zijn

### Beschrijvingen

Schrijf duidelijke, actionable beschrijvingen:

**Slecht:**

```json
{
  "templateId": {
    "type": "string",
    "description": "Het template-ID"
  }
}
```

**Goed:**

```json
{
  "templateId": {
    "type": "string",
    "description": "Template-map-naam (bv. 'department', 'event'). Gebruik GET /api/templates om beschikbare IDs op te lijsten."
  }
}
```

**Richtlijnen:**

- Leg formaat/validatie uit (bv. "ISO-8601-datum-string", "UUID v4")
- Geef voorbeelden in de beschrijving
- Link naar gerelateerde endpoints indien behulpzaam
- Verduidelijk beperkingen (min/max-lengte, regex-patronen, enum-waarden)
- Leg business-betekenis uit, niet alleen technisch type

### Validatie-beperkingen

Gebruik OpenAPI-validatie-keywords:

```json
{
  "pageTitle": {
    "type": "string",
    "description": "Pagina-titel",
    "minLength": 1,
    "maxLength": 255
  },
  "columnCount": {
    "type": "integer",
    "description": "Aantal kolommen",
    "minimum": 1,
    "maximum": 5
  },
  "complexity": {
    "type": "string",
    "description": "Template-complexiteit-niveau",
    "enum": ["simple", "medium", "advanced"]
  },
  "email": {
    "type": "string",
    "description": "Gebruikers-e-mailadres",
    "format": "email"
  },
  "url": {
    "type": "string",
    "description": "Externe URL",
    "format": "uri"
  }
}
```

**Beschikbare beperkingen:**

- `minLength`, `maxLength` — string-lengte
- `minimum`, `maximum` — getallen-range
- `enum` — beperkte set waarden
- `pattern` — regex-validatie
- `format` — standaard-formaten (email, uri, date-time, uuid)

### Voorbeelden

Lever realistische, copy-paste-ready voorbeelden:

**Enkel voorbeeld:**

```json
"example": {
  "templateId": "department",
  "pageTitle": "HR-afdeling",
  "parentPath": "/teams"
}
```

**Meerdere voorbeelden (beter):**

```json
"examples": {
  "department": {
    "summary": "HR-afdeling-pagina maken",
    "value": {
      "templateId": "department",
      "pageTitle": "HR-afdeling",
      "parentPath": "/teams"
    }
  },
  "event": {
    "summary": "Bedrijfs-event-pagina maken (geen parent)",
    "value": {
      "templateId": "event",
      "pageTitle": "Jaarcongres 2026"
    }
  }
}
```

**Voordelen:**

- Swagger UI toont dropdown met voorbeelden
- Code-generators gebruiken voorbeelden voor tests
- Ontwikkelaars kunnen werkende waarden copy-pasten
- Documenteert verschillende use-cases

### Error-responses

Gebruik herbruikbare error-response-schema's:

**Herbruikbare errors (al gedefinieerd in IntraVox):**

```json
"responses": {
  "400": { "$ref": "#/components/responses/BadRequest" },
  "401": { "$ref": "#/components/responses/Unauthorized" },
  "403": { "$ref": "#/components/responses/Forbidden" },
  "404": { "$ref": "#/components/responses/NotFound" }
}
```

**Custom error met voorbeeld:**

```json
"400": {
  "description": "Ongeldige template-ID of ontbrekende verplichte parameters",
  "content": {
    "application/json": {
      "schema": {
        "type": "object",
        "properties": {
          "error": {
            "type": "string",
            "description": "Mensvriendelijke foutmelding"
          }
        }
      },
      "examples": {
        "missing-param": {
          "summary": "Verplichte parameter ontbreekt",
          "value": {
            "error": "Verplichte parameter ontbreekt: templateId"
          }
        },
        "invalid-template": {
          "summary": "Ongeldige template-ID",
          "value": {
            "error": "Template niet gevonden: invalid-id"
          }
        }
      }
    }
  }
}
```

## Validatie-checklist

Voltooi deze checklist voordat je je API-wijzigingen merget:

### 1. OpenAPI-spec-validatie

**Installeer validator:**

```bash
npm install -g @apidevtools/swagger-cli
```

**Valideer spec:**

```bash
cd /path/to/IntraVox
swagger-cli validate openapi.json
```

**Verwachte output:**

```
openapi.json is valid
```

**Veelvoorkomende errors en fixes:**

| Error | Oorzaak | Fix |
|-------|---------|-----|
| Missing required field | Schema mist `required`-array | Voeg verplichte velden-lijst toe |
| Invalid $ref | Pad bestaat niet | Check schema-naam-spelling |
| Duplicate operationId | Twee endpoints met dezelfde ID | Gebruik unieke kebab-case-IDs |
| Missing response schema | Response heeft geen schema | Voeg schema toe of gebruik bestaande |

### 2. Handmatig testen (cURL)

**Test succes-case:**

```bash
curl -X POST \
  -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{"param":"valid-value"}' \
  https://localhost/apps/intravox/api/my-resource
```

**Test error-cases:**

```bash
# Ontbrekende parameter
curl -X POST \
  -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{}' \
  https://localhost/apps/intravox/api/my-resource

# Ongeldige parameter
curl -X POST \
  -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{"param":"ab"}' \
  https://localhost/apps/intravox/api/my-resource

# Ongeauthoriseerd (verkeerd wachtwoord)
curl -X POST \
  -u "user:wrong-password" \
  -H "Content-Type: application/json" \
  -d '{"param":"valid"}' \
  https://localhost/apps/intravox/api/my-resource
```

**Verifieer:**

- ✅ Response matched met OpenAPI-schema
- ✅ HTTP-statuscodes correct (200, 201, 400, 401, 403, 404, 500)
- ✅ Foutmeldingen zijn behulpzaam (niet alleen "Bad Request")
- ✅ Authenticatie werkt (401 bij verkeerde credentials)
- ✅ Autorisatie werkt (403 bij geen permissie)

### 3. Postman-import-test

1. Open Postman
2. Import → Link → `https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json`
3. Vind je nieuwe endpoint in de collection
4. Stuur request met geldige data
5. Verifieer dat response matched met schema
6. Probeer ongeldige data (zou 400 moeten geven)

### 4. Code-generatie-test

**Genereer TypeScript-client:**

```bash
npx @openapitools/openapi-generator-cli generate \
  -i openapi.json \
  -g typescript-fetch \
  -o /tmp/intravox-test-client
```

**Check voor:**

- ✅ Geen generatie-errors
- ✅ Method-namen kloppen (check `/tmp/intravox-test-client/apis/`)
- ✅ Type-definities accuraat
- ✅ Voorbeelden opgenomen in docs

**Inspecteer gegenereerde bestanden:**

```bash
# Lijst gegenereerde API-methodes
ls /tmp/intravox-test-client/apis/

# Check je nieuwe endpoint-methode
grep -A 10 "myNewEndpoint" /tmp/intravox-test-client/apis/*.ts
```

### 5. Documentatie-review

**Self-review-checklist:**

- [ ] `operationId` volgt kebab-case-conventie
- [ ] Summary is bondig (<70 tekens)
- [ ] Description legt uit wat, waarom en wanneer te gebruiken
- [ ] Alle parameters hebben beschrijvingen
- [ ] Voorbeelden zijn realistisch en copy-paste-ready
- [ ] Error-responses gedocumenteerd met voorbeelden
- [ ] Schema-namen volgen PascalCase
- [ ] Required-velden-array is accuraat
- [ ] Tag bestaat en is passend
- [ ] Geen typo's in beschrijvingen

**Peer-review:**

- Vraag een collega om de OpenAPI-docs zonder context te lezen
- Kunnen ze begrijpen wat het endpoint doet?
- Zijn voorbeelden duidelijk genoeg om direct te gebruiken?

## Case study: template-endpoints

De template-endpoints toegevoegd in v0.9.17 demonstreren best-practices. Laten we ze analyseren.

### Route-definitie

**Bestand:** `appinfo/routes.php:127-132`

```php
// Templates oplijsten
['name' => 'api#listTemplates', 'url' => '/api/templates', 'verb' => 'GET'],

// Template-details ophalen
['name' => 'api#getTemplate', 'url' => '/api/templates/{id}', 'verb' => 'GET'],

// Pagina maken vanuit template
['name' => 'api#createPageFromTemplate', 'url' => '/api/pages/from-template', 'verb' => 'POST'],

// Pagina opslaan als template
['name' => 'api#saveAsTemplate', 'url' => '/api/templates', 'verb' => 'POST'],

// Template verwijderen
['name' => 'api#deleteTemplate', 'url' => '/api/templates/{id}', 'verb' => 'DELETE'],
```

**Design-beslissingen:**

- ✅ RESTful URL-structuur (`/api/templates` voor de collection)
- ✅ Consistente naamgeving (`listTemplates`, `getTemplate`, enz.)
- ✅ Speciale case: `/api/pages/from-template` (maakt pagina's, geen templates)
- ✅ Standaard-HTTP-verbs (GET lijst/details, POST aanmaken, DELETE verwijderen)

### Controller-implementatie

**Bestand:** `lib/Controller/ApiController.php:3156-3183`

Voorbeeld: `createPageFromTemplate()`

```php
/**
 * Pagina maken vanuit template
 *
 * @NoAdminRequired
 * @param string $templateId Template-ID
 * @param string $pageTitle Pagina-titel
 * @param string|null $parentPath Parent-map-pad
 * @return JSONResponse
 */
public function createPageFromTemplate(
    string $templateId,
    string $pageTitle,
    ?string $parentPath = null
): JSONResponse {
    try {
        $page = $this->pageService->createPageFromTemplate(
            $templateId,
            $pageTitle,
            $parentPath,
            $this->userId
        );

        return new JSONResponse([
            'success' => true,
            'page' => $page
        ], 201); // 201 Created voor nieuwe resource

    } catch (\Exception $e) {
        return new JSONResponse([
            'error' => $e->getMessage()
        ], 400);
    }
}
```

**Kern-patterns:**

- ✅ Thin controller (delegeert direct naar service)
- ✅ Type-hints voor alle parameters
- ✅ Nullable parameter met default-waarde (`?string $parentPath = null`)
- ✅ 201-status voor resource-aanmaak (niet alleen 200)
- ✅ Generieke exception-handling (service gooit specifieke errors)
- ✅ Huidige user-ID geïnjecteerd (`$this->userId`)

### OpenAPI-schema's

**TemplatePreview** (voor list-endpoint):

```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "string",
      "description": "Template-identifier (map-naam)"
    },
    "name": {
      "type": "string",
      "description": "Template-weergavenaam"
    },
    "columnCount": {
      "type": "integer",
      "minimum": 1,
      "maximum": 5
    },
    "complexity": {
      "type": "string",
      "enum": ["simple", "medium", "advanced"]
    }
  }
}
```

**TemplateCreateFromRequest** (voor create-endpoint):

```json
{
  "type": "object",
  "required": ["templateId", "pageTitle"],
  "properties": {
    "templateId": {
      "type": "string",
      "description": "Template-map-naam (bv. 'department', 'event')"
    },
    "pageTitle": {
      "type": "string",
      "description": "Titel voor de nieuwe pagina"
    },
    "parentPath": {
      "type": "string",
      "nullable": true,
      "description": "Optioneel parent-map-pad (bv. '/teams')"
    }
  }
}
```

**Design-beslissingen:**

- ✅ Aparte schema's voor verschillende use-cases (list vs create)
- ✅ Enums voor validatie (`complexity`)
- ✅ Min/max-beperkingen (`columnCount` 1-5)
- ✅ Expliciete nullability (`parentPath`)
- ✅ Beschrijvende beschrijvingen met voorbeelden
- ✅ Duidelijke verplichte velden (`templateId`, `pageTitle`)

### Voorbeelden

Meerdere voorbeelden voor verschillende use-cases:

```json
"examples": {
  "department": {
    "summary": "HR-afdeling-pagina maken",
    "value": {
      "templateId": "department",
      "pageTitle": "HR-afdeling",
      "parentPath": "/teams"
    }
  },
  "event": {
    "summary": "Bedrijfs-event-pagina maken",
    "value": {
      "templateId": "event",
      "pageTitle": "Jaarcongres 2026"
    }
  }
}
```

**Waarom meerdere voorbeelden:**

- ✅ Toont verschillende templates
- ✅ Demonstreert optionele parameter (met/zonder `parentPath`)
- ✅ Helpt ontwikkelaars het juiste template te kiezen

### Commits

Zie CHANGELOG v0.9.17:

- Template-endpoint-implementatie (controller + service)
- OpenAPI-spec-update (schema's + endpoints)
- Versie-bump naar 0.9.17

## Resources

**OpenAPI-specificatie:**

- Officiële spec: https://spec.openapis.org/oas/v3.1.0
- Voorbeelden: https://github.com/OAI/OpenAPI-Specification/tree/main/examples

**Tools:**

- Swagger Editor: https://editor.swagger.io (online validator)
- OpenAPI Generator: https://openapi-generator.tech (client-generatie)
- swagger-cli: https://github.com/APIDevTools/swagger-cli (validatie)

**Nextcloud-development:**

- App-development: https://docs.nextcloud.com/server/latest/developer_manual/
- Coding-standards: https://docs.nextcloud.com/server/latest/developer_manual/basics/coding_standard.html
- API-guidelines: https://docs.nextcloud.com/server/latest/developer_manual/basics/controllers.html

**IntraVox-docs:**

- Quickstart: [template-api-quickstart.md](template-api-quickstart.md)
- OpenAPI-tooling: [openapi-tooling.md](openapi-tooling.md)
- API-referentie: [api-reference.md](api-reference.md)

**Leer-resources:**

- OpenAPI-tutorial: https://oai.github.io/Documentation/
- RESTful-API-design: https://restfulapi.net/
- JSON-schema: https://json-schema.org/learn/
