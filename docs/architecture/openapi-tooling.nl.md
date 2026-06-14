# OpenAPI-tooling-gids

Maak gebruik van IntraVox' OpenAPI-specificatie met moderne developer-tools.

## Inhoudsopgave

- [Wat is OpenAPI?](#wat-is-openapi)
- [Toegang tot de spec](#toegang-tot-de-spec)
- [Swagger UI](#swagger-ui)
- [Postman-integratie](#postman-integratie)
- [Code-generatie](#code-generatie)
- [API-test-tools](#api-test-tools)
- [Volgende stappen](#volgende-stappen)

## Wat is OpenAPI?

OpenAPI (voorheen Swagger) is een industrie-standaard-specificatie om REST-API's te beschrijven. IntraVox levert een volledige OpenAPI-3.1-specificatie die het volgende mogelijk maakt:

- **Interactieve API-exploration** — test endpoints direct in Swagger UI
- **Geautomatiseerde client-generatie** — genereer type-safe clients in 50+ talen
- **API-testen** — importeer in Postman, Insomnia of geautomatiseerde test-tools
- **Auto-completion** — IDE-ondersteuning via gegenereerde clients
- **Documentatie** — zelf-documenterende API met voorbeelden en schema's

**Voordelen:**

- ✅ Ontdek alle beschikbare endpoints zonder source code te lezen
- ✅ Zie request-/response-schema's met validatie-regels
- ✅ Probeer API-calls direct in de browser (Swagger UI)
- ✅ Genereer client-libraries automatisch (geen handmatige HTTP-code)
- ✅ Houd API-contracten in sync tussen frontend en backend

## Toegang tot de spec

De OpenAPI-spec is beschikbaar op:

- **Bestand-pad:** `IntraVox/openapi.json` (in repository-root)
- **GitHub-URL:** https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json
- **Lokale server:** `https://your-nextcloud.com/apps/intravox/openapi.json` (vereist authenticatie)

**Huidige versie:** 0.9.17
**Formaat:** OpenAPI 3.1.0 (JSON)

**Snelle weergave:**

```bash
# Spec downloaden
curl -O https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json

# Spec valideren
npx @apidevtools/swagger-cli validate openapi.json
```

## Swagger UI

Swagger UI biedt een interactieve interface om de IntraVox-API te verkennen en testen.

### Optie 1: online Swagger-Editor

**Geschikt voor:** snelle exploratie zonder lokale setup

1. Ga naar https://editor.swagger.io
2. File → Import URL
3. Voer in: `https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json`
4. Verken endpoints in het rechter-paneel

**Endpoints uitproberen:**

1. Klik op "Authorize" (rechtsboven)
2. Voer je Nextcloud-credentials in:
   - Username: `your-username`
   - Password: `your-app-password` (uit Nextcloud-beveiligings-instellingen)
3. Selecteer een endpoint → "Try it out"
4. Vul parameters in → "Execute"
5. Bekijk response direct in de browser

**Voordelen:**

- ✅ Geen installatie vereist
- ✅ Werkt op elk apparaat
- ✅ Altijd up-to-date UI

**Nadelen:**

- ❌ Vereist internet-verbinding
- ❌ Deelt URL met editor.swagger.io-servers

### Optie 2: lokale Swagger UI (Docker)

**Geschikt voor:** offline development, privacy

**Start Swagger UI:**

```bash
# IntraVox-repo clonen (of openapi.json downloaden)
git clone https://github.com/nextcloud/IntraVox.git
cd IntraVox

# Swagger UI draaien in Docker
docker run -p 8080:8080 \
  -e SWAGGER_JSON=/openapi.json \
  -v $(pwd)/openapi.json:/openapi.json \
  swaggerapi/swagger-ui

# Open browser
open http://localhost:8080
```

**Swagger UI stoppen:**

```bash
# Druk Ctrl+C in terminal
# Of vind container-ID en stop
docker ps
docker stop <container-id>
```

**Voordelen:**

- ✅ Werkt offline
- ✅ Geen data verlaat je machine
- ✅ Snelle response-tijden

**Nadelen:**

- ❌ Vereist Docker-installatie
- ❌ Handmatige update van openapi.json nodig

### Optie 3: Swagger UI in Nextcloud-app (toekomst)

> **Let op:** native Swagger-UI-integratie in IntraVox-beheerinstellingen is gepland voor een toekomstige release. Hiermee kunnen beheerders API-documentatie direct vanuit Nextcloud benaderen zonder externe tools.

## Postman-integratie

Postman is een populair API-development-platform dat OpenAPI-import ondersteunt.

### Import OpenAPI-spec

1. Open Postman
2. Klik op "Import" (linksboven)
3. Selecteer "Link"-tab
4. Voer URL in: `https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json`
5. Import Type: "OpenAPI 3.1"
6. Klik op "Import"

**Resultaat:** collection met alle IntraVox-endpoints georganiseerd per tag (Pages, Templates, Media, enz.)

### Environment configureren

Maak een Postman-environment voor herbruikbare variabelen:

1. Environments → Create Environment
2. Naam: "IntraVox Production" (of "IntraVox Dev")
3. Voeg variabelen toe:

| Variabele | Initial Value | Current Value |
|-----------|---------------|---------------|
| `baseUrl` | `https://your-nextcloud.com` | (idem) |
| `username` | `your-username` | (idem) |
| `appPassword` | `xxxx-xxxx-xxxx-xxxx-xxxx` | (geheim houden) |

4. Sla environment op
5. Selecteer environment uit dropdown (rechtsboven)

### Authorization-setup

**Collection-niveau auth (geldt voor alle requests):**

1. Selecteer "IntraVox API"-collection
2. Authorization-tab
3. Type: "Basic Auth"
   - Username: `{{username}}`
   - Password: `{{appPassword}}`
4. Sla op

**Test authenticatie:**

1. Templates → List all templates (GET)
2. Send
3. Zou 200 met template-lijst terug moeten geven

### Requests organiseren

**Maak mappen voor workflows:**

```
IntraVox-API-collection
├── 📁 Setup (templates oplijsten, permissies ophalen)
├── 📁 Pagina's aanmaken (vanuit templates, blanco pagina's)
├── 📁 Media-management (upload, delete)
└── 📁 Testing (error-cases, edge-cases)
```

**Gebruik variabelen in requests:**

```json
{
  "templateId": "{{templateId}}",
  "pageTitle": "{{pageTitle}}",
  "parentPath": "{{parentPath}}"
}
```

### Voorbeelden opslaan

Na succesvolle requests, sla responses op als voorbeelden:

1. Stuur request
2. Klik op "Save Response" → "Save as example"
3. Naam: "Succes — HR-afdeling-pagina"

**Voordelen:**

- Documentatie voor je team
- Referentie voor verwachte responses
- Regressie-test-baseline

### Collection exporteren

Deel met je team:

1. Collection → … menu → Export
2. Format: "Collection v2.1"
3. Sla op als `intravox-api-collection.json`
4. Commit naar je repository

## Code-generatie

Genereer type-safe client-libraries vanuit de OpenAPI-spec.

### OpenAPI-Generator-CLI

**Install:**

```bash
npm install -g @openapitools/openapi-generator-cli
```

**Verifieer installatie:**

```bash
openapi-generator-cli version
```

### JavaScript-client genereren

```bash
openapi-generator-cli generate \
  -i https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  -g javascript \
  -o ./intravox-client-js \
  --additional-properties=projectName=intravox-client,usePromises=true
```

**Gegenereerde structuur:**

```
intravox-client-js/
├── docs/               # API-documentatie
├── src/
│   ├── api/            # API-classes (PagesApi, TemplatesApi, enz.)
│   ├── model/          # Datamodellen (Page, Template, enz.)
│   └── ApiClient.js    # HTTP-client
├── package.json
└── README.md
```

**Installeer gegenereerde client:**

```bash
cd intravox-client-js
npm install
npm link  # Globaal beschikbaar maken
```

**Gebruik-voorbeeld:**

```javascript
const IntraVox = require('intravox-client');

// Client configureren
const client = IntraVox.ApiClient.instance;
client.basePath = 'https://your-nc.com/apps/intravox';
client.authentications['BasicAuth'].username = 'user';
client.authentications['BasicAuth'].password = 'app-password';

// Templates-API gebruiken
const templatesApi = new IntraVox.TemplatesApi();

// Templates oplijsten
templatesApi.listTemplates((error, data) => {
  if (error) {
    console.error(error);
  } else {
    console.log('Templates:', data.templates);
  }
});

// Pagina maken vanuit template (met promises)
templatesApi.createPageFromTemplate({
  templateCreateFromRequest: {
    templateId: 'department',
    pageTitle: 'HR-afdeling',
    parentPath: '/teams'
  }
}).then(data => {
  console.log('Pagina aangemaakt:', data.page.id);
}).catch(error => {
  console.error('Error:', error);
});
```

### Python-client genereren

```bash
openapi-generator-cli generate \
  -i https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  -g python \
  -o ./intravox-client-py \
  --additional-properties=packageName=intravox_client,projectName=intravox-client
```

**Installeer gegenereerde client:**

```bash
cd intravox-client-py
pip install -e .
```

**Gebruik-voorbeeld:**

```python
import intravox_client
from intravox_client.rest import ApiException

# Client configureren
configuration = intravox_client.Configuration()
configuration.host = 'https://your-nc.com/apps/intravox'
configuration.username = 'user'
configuration.password = 'app-password'

# Templates-API gebruiken
with intravox_client.ApiClient(configuration) as api_client:
    templates_api = intravox_client.TemplatesApi(api_client)

    try:
        # Templates oplijsten
        templates = templates_api.list_templates()
        print(f'Gevonden {len(templates.templates)} templates')

        # Pagina maken vanuit template
        request = intravox_client.TemplateCreateFromRequest(
            template_id='department',
            page_title='HR-afdeling',
            parent_path='/teams'
        )
        page = templates_api.create_page_from_template(request)
        print(f'Pagina aangemaakt: {page.page.id}')

    except ApiException as e:
        print(f'API-error: {e.status} - {e.reason}')
```

### TypeScript-client genereren

**Geschikt voor:** frontend-applicaties met type-safety

```bash
openapi-generator-cli generate \
  -i https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  -g typescript-fetch \
  -o ./intravox-client-ts \
  --additional-properties=npmName=@your-org/intravox-client,supportsES6=true
```

**Gebruik-voorbeeld:**

```typescript
import { Configuration, TemplatesApi } from '@your-org/intravox-client';

// Client configureren
const config = new Configuration({
  basePath: 'https://your-nc.com/apps/intravox',
  username: 'user',
  password: 'app-password'
});

const templatesApi = new TemplatesApi(config);

// Templates oplijsten (met TypeScript-types)
const templates = await templatesApi.listTemplates();
templates.templates.forEach(template => {
  console.log(`${template.id}: ${template.name} (${template.complexity})`);
});

// Pagina maken vanuit template (type-safe)
const page = await templatesApi.createPageFromTemplate({
  templateCreateFromRequest: {
    templateId: 'department',
    pageTitle: 'HR-afdeling',
    parentPath: '/teams'  // TypeScript valideert dat dit optioneel is
  }
});

console.log(`Aangemaakt: ${page.page.id}`);
```

**Voordelen van de TypeScript-client:**

- ✅ Auto-completion in VS Code
- ✅ Type-checking tijdens compile
- ✅ Inline-documentatie uit OpenAPI-spec
- ✅ Refactoring-veiligheid

### Andere ondersteunde talen

OpenAPI Generator ondersteunt 50+ talen:

**Populaire opties:**

- `php` — PHP met Guzzle
- `go` — Go-client
- `java` — Java met OkHttp
- `ruby` — Ruby-client
- `rust` — Rust-client
- `csharp` — C#/.NET-client
- `kotlin` — Kotlin-client
- `swift5` — Swift-5-client

**Lijst alle generators op:**

```bash
openapi-generator-cli list
```

**Genereer voor elke taal:**

```bash
openapi-generator-cli generate \
  -i https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  -g <generator-name> \
  -o ./output-directory
```

## API-test-tools

### Bruno (open-source Postman-alternatief)

**Geschikt voor:** Git-friendly API-testing

1. Download Bruno: https://www.usebruno.com
2. Maak een nieuwe collection aan
3. Import → OpenAPI Spec
4. Browse naar `openapi.json`
5. Configureer environment-variabelen

**Voordelen ten opzichte van Postman:**

- ✅ Slaat collections op als bestanden (Git-friendly)
- ✅ Geen cloud-sync vereist (privacy)
- ✅ Offline-first design
- ✅ Snel en lichtgewicht

**Environment-bestand (`.env`):**

```env
BASE_URL=https://your-nextcloud.com
USERNAME=your-username
APP_PASSWORD=xxxx-xxxx-xxxx-xxxx
```

### Insomnia

**Geschikt voor:** mooie UI met GraphQL-ondersteuning

1. Download Insomnia: https://insomnia.rest
2. Import Data → From URL
3. Voer in: `https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json`
4. Format: "OpenAPI 3"
5. Configureer base-environment

**Environment-setup:**

```json
{
  "base_url": "https://your-nextcloud.com/apps/intravox",
  "username": "your-username",
  "app_password": "xxxx-xxxx-xxxx-xxxx"
}
```

### Schemathesis (geautomatiseerd API-testen)

**Geschikt voor:** edge-cases en schema-violations vinden

Test je API-implementatie tegen de OpenAPI-spec:

**Install:**

```bash
pip install schemathesis
```

**Tests draaien:**

```bash
# Test alle endpoints
schemathesis run \
  https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  --base-url https://your-nc.com/apps/intravox \
  --auth user:app-password \
  --hypothesis-max-examples=50

# Test specifiek endpoint
schemathesis run \
  https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  --base-url https://your-nc.com/apps/intravox \
  --auth user:app-password \
  --endpoint /api/templates

# Test met custom checks
schemathesis run openapi.json \
  --base-url https://localhost/apps/intravox \
  --auth user:password \
  --checks all \
  --hypothesis-max-examples=100
```

**Wat het test:**

- ✅ Response matched met schema (correcte types, verplichte velden)
- ✅ HTTP-statuscodes zijn geldig
- ✅ Verplichte parameters worden afgedwongen
- ✅ Edge-cases (lege strings, max integers, speciale tekens)
- ✅ Content-Type-headers correct
- ✅ Geen 500-errors op geldige input

**Voorbeeld-output:**

```
GET /api/templates .                                            [PASSED]
GET /api/templates/{id} .                                       [PASSED]
POST /api/pages/from-template .                                 [PASSED]
POST /api/templates F                                           [FAILED]

FAILURES:
POST /api/templates
  - Schema-violation: response mist 'templateId'-veld
```

### Dredd (API-Blueprint-testing)

**Geschikt voor:** contract-testing (spec vs implementatie)

```bash
# Install
npm install -g dredd

# Converteer OpenAPI naar API Blueprint (indien nodig)
# Of gebruik dredd-openapi-generator

# Tests draaien
dredd openapi.json https://your-nc.com/apps/intravox \
  --user user:app-password
```

### HTTPie (cURL-alternatief)

**Geschikt voor:** handmatig testen met mooie output

```bash
# Install
brew install httpie  # macOS
pip install httpie   # Andere platforms

# Templates oplijsten
http GET https://your-nc.com/apps/intravox/api/templates \
  -a username:app-password

# Pagina maken vanuit template
http POST https://your-nc.com/apps/intravox/api/pages/from-template \
  -a username:app-password \
  templateId=department \
  pageTitle="HR-afdeling" \
  parentPath=/teams

# Pretty-printed JSON-output
http --pretty=all GET https://your-nc.com/apps/intravox/api/templates \
  -a username:app-password
```

**Voordelen ten opzichte van cURL:**

- ✅ Syntax-highlighting
- ✅ Simpelere syntax (geen -H-flags voor JSON)
- ✅ Pretty-printed output by default
- ✅ Session-ondersteuning

## Volgende stappen

**Quickstart:**

- [template-api-quickstart.md](template-api-quickstart.md) — begin in 5 minuten met de template-API

**API-referentie:**

- [api-reference.md](api-reference.md) — complete endpoint-documentatie

**Developer-gids:**

- [api-development.md](api-development.md) — bijdragen aan de IntraVox-API en onderhoud van de OpenAPI-spec

**Geavanceerde onderwerpen:**

- **CI/CD-integratie:** draai Schemathesis-tests in GitHub Actions
- **Mock-servers:** gebruik Prism om de IntraVox-API te mocken voor frontend-development
- **Contract-testing:** zorg dat frontend en backend in sync blijven met Pact
- **API-gateway:** proxy de IntraVox-API via Kong of Nginx

**Resources:**

- OpenAPI-specificatie: https://spec.openapis.org/oas/v3.1.0
- OpenAPI Generator: https://openapi-generator.tech
- Swagger-tools: https://swagger.io/tools/
- Postman-learning: https://learning.postman.com
