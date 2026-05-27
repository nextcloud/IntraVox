# OpenAPI Tooling Guide

Leverage IntraVox's OpenAPI specification with modern developer tools.

## Table of Contents
- [What is OpenAPI?](#what-is-openapi)
- [Accessing the Spec](#accessing-the-spec)
- [Swagger UI](#swagger-ui)
- [Postman Integration](#postman-integration)
- [Code Generation](#code-generation)
- [API Testing Tools](#api-testing-tools)
- [Next Steps](#next-steps)

## What is OpenAPI?

OpenAPI (formerly Swagger) is an industry-standard specification for describing REST APIs. IntraVox provides a complete OpenAPI 3.1 specification that enables:

- **Interactive API exploration** - Test endpoints directly in Swagger UI
- **Automated client generation** - Generate type-safe clients in 50+ languages
- **API testing** - Import into Postman, Insomnia, or automated testing tools
- **Auto-completion** - IDE support via generated clients
- **Documentation** - Self-documenting API with examples and schemas

**Benefits:**
- ✅ Discover all available endpoints without reading source code
- ✅ See request/response schemas with validation rules
- ✅ Try out API calls directly in browser (Swagger UI)
- ✅ Generate client libraries automatically (no manual HTTP code)
- ✅ Keep API contracts in sync between frontend and backend

## Accessing the Spec

The OpenAPI spec is available at:

- **File path:** `IntraVox/openapi.json` (in repository root)
- **GitHub URL:** https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json
- **Local server:** `https://your-nextcloud.com/apps/intravox/openapi.json` (requires authentication)

**Current version:** 0.9.17
**Format:** OpenAPI 3.1.0 (JSON)

**Quick view:**
```bash
# Download spec
curl -O https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json

# Validate spec
npx @apidevtools/swagger-cli validate openapi.json
```

## Swagger UI

Swagger UI provides an interactive interface to explore and test the IntraVox API.

### Option 1: Online Swagger Editor

**Best for:** Quick exploration without local setup

1. Go to https://editor.swagger.io
2. File → Import URL
3. Enter: `https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json`
4. Explore endpoints in the right panel

**Try out endpoints:**
1. Click "Authorize" button (top right)
2. Enter your Nextcloud credentials:
   - Username: `your-username`
   - Password: `your-app-password` (from Nextcloud security settings)
3. Select an endpoint → "Try it out"
4. Fill in parameters → "Execute"
5. View response directly in browser

**Pros:**
- ✅ No installation required
- ✅ Works on any device
- ✅ Always up-to-date UI

**Cons:**
- ❌ Requires internet connection
- ❌ Shares URL with editor.swagger.io servers

### Option 2: Local Swagger UI (Docker)

**Best for:** Offline development, privacy

**Start Swagger UI:**
```bash
# Clone IntraVox repo (or download openapi.json)
git clone https://github.com/nextcloud/IntraVox.git
cd IntraVox

# Run Swagger UI in Docker
docker run -p 8080:8080 \
  -e SWAGGER_JSON=/openapi.json \
  -v $(pwd)/openapi.json:/openapi.json \
  swaggerapi/swagger-ui

# Open browser
open http://localhost:8080
```

**Stop Swagger UI:**
```bash
# Press Ctrl+C in terminal
# Or find container ID and stop
docker ps
docker stop <container-id>
```

**Pros:**
- ✅ Works offline
- ✅ No data leaves your machine
- ✅ Fast response times

**Cons:**
- ❌ Requires Docker installation
- ❌ Need to manually update openapi.json

### Option 3: Swagger UI in Nextcloud App (Future)

> **Note:** Native Swagger UI integration in IntraVox admin settings is planned for a future release. This will allow admins to access API documentation directly from Nextcloud without external tools.

## Postman Integration

Postman is a popular API development platform that supports OpenAPI import.

### Import OpenAPI Spec

1. Open Postman
2. Click "Import" button (top left)
3. Select "Link" tab
4. Enter URL: `https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json`
5. Import Type: "OpenAPI 3.1"
6. Click "Import"

**Result:** Collection with all IntraVox endpoints organized by tags (Pages, Templates, Media, etc.)

### Configure Environment

Create a Postman environment for reusable variables:

1. Environments → Create Environment
2. Name: "IntraVox Production" (or "IntraVox Dev")
3. Add variables:

| Variable | Initial Value | Current Value |
|----------|---------------|---------------|
| `baseUrl` | `https://your-nextcloud.com` | (same) |
| `username` | `your-username` | (same) |
| `appPassword` | `xxxx-xxxx-xxxx-xxxx-xxxx` | (keep secret) |

4. Save environment
5. Select environment from dropdown (top right)

### Authorization Setup

**Collection-level auth (applies to all requests):**

1. Select "IntraVox API" collection
2. Authorization tab
3. Type: "Basic Auth"
   - Username: `{{username}}`
   - Password: `{{appPassword}}`
4. Save

**Test authentication:**
1. Templates → List all templates (GET)
2. Send
3. Should return 200 with template list

### Organize Requests

**Create folders for workflows:**
```
IntraVox API Collection
├── 📁 Setup (list templates, get permissions)
├── 📁 Create Pages (from templates, blank pages)
├── 📁 Media Management (upload, delete)
└── 📁 Testing (error cases, edge cases)
```

**Use variables in requests:**
```json
{
  "templateId": "{{templateId}}",
  "pageTitle": "{{pageTitle}}",
  "parentPath": "{{parentPath}}"
}
```

### Save Examples

After successful requests, save responses as examples:

1. Send request
2. Click "Save Response" → "Save as example"
3. Name: "Success - HR Department page"

**Benefits:**
- Documentation for your team
- Reference for expected responses
- Regression testing baseline

### Export Collection

Share with your team:

1. Collection → ... menu → Export
2. Format: "Collection v2.1"
3. Save as `intravox-api-collection.json`
4. Commit to your repository

## Code Generation

Generate type-safe client libraries from the OpenAPI spec.

### OpenAPI Generator CLI

**Install:**
```bash
npm install -g @openapitools/openapi-generator-cli
```

**Verify installation:**
```bash
openapi-generator-cli version
```

### Generate JavaScript Client

```bash
openapi-generator-cli generate \
  -i https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  -g javascript \
  -o ./intravox-client-js \
  --additional-properties=projectName=intravox-client,usePromises=true
```

**Generated structure:**
```
intravox-client-js/
├── docs/               # API documentation
├── src/
│   ├── api/            # API classes (PagesApi, TemplatesApi, etc.)
│   ├── model/          # Data models (Page, Template, etc.)
│   └── ApiClient.js    # HTTP client
├── package.json
└── README.md
```

**Install generated client:**
```bash
cd intravox-client-js
npm install
npm link  # Make available globally
```

**Usage example:**
```javascript
const IntraVox = require('intravox-client');

// Configure client
const client = IntraVox.ApiClient.instance;
client.basePath = 'https://your-nc.com/apps/intravox';
client.authentications['BasicAuth'].username = 'user';
client.authentications['BasicAuth'].password = 'app-password';

// Use Templates API
const templatesApi = new IntraVox.TemplatesApi();

// List templates
templatesApi.listTemplates((error, data) => {
  if (error) {
    console.error(error);
  } else {
    console.log('Templates:', data.templates);
  }
});

// Create page from template (with promises)
templatesApi.createPageFromTemplate({
  templateCreateFromRequest: {
    templateId: 'department',
    pageTitle: 'HR Department',
    parentPath: '/teams'
  }
}).then(data => {
  console.log('Created page:', data.page.id);
}).catch(error => {
  console.error('Error:', error);
});
```

### Generate Python Client

```bash
openapi-generator-cli generate \
  -i https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  -g python \
  -o ./intravox-client-py \
  --additional-properties=packageName=intravox_client,projectName=intravox-client
```

**Install generated client:**
```bash
cd intravox-client-py
pip install -e .
```

**Usage example:**
```python
import intravox_client
from intravox_client.rest import ApiException

# Configure client
configuration = intravox_client.Configuration()
configuration.host = 'https://your-nc.com/apps/intravox'
configuration.username = 'user'
configuration.password = 'app-password'

# Use Templates API
with intravox_client.ApiClient(configuration) as api_client:
    templates_api = intravox_client.TemplatesApi(api_client)

    try:
        # List templates
        templates = templates_api.list_templates()
        print(f'Found {len(templates.templates)} templates')

        # Create page from template
        request = intravox_client.TemplateCreateFromRequest(
            template_id='department',
            page_title='HR Department',
            parent_path='/teams'
        )
        page = templates_api.create_page_from_template(request)
        print(f'Created page: {page.page.id}')

    except ApiException as e:
        print(f'API Error: {e.status} - {e.reason}')
```

### Generate TypeScript Client

**Best for:** Frontend applications with type safety

```bash
openapi-generator-cli generate \
  -i https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  -g typescript-fetch \
  -o ./intravox-client-ts \
  --additional-properties=npmName=@your-org/intravox-client,supportsES6=true
```

**Usage example:**
```typescript
import { Configuration, TemplatesApi } from '@your-org/intravox-client';

// Configure client
const config = new Configuration({
  basePath: 'https://your-nc.com/apps/intravox',
  username: 'user',
  password: 'app-password'
});

const templatesApi = new TemplatesApi(config);

// List templates (with TypeScript types)
const templates = await templatesApi.listTemplates();
templates.templates.forEach(template => {
  console.log(`${template.id}: ${template.name} (${template.complexity})`);
});

// Create page from template (type-safe)
const page = await templatesApi.createPageFromTemplate({
  templateCreateFromRequest: {
    templateId: 'department',
    pageTitle: 'HR Department',
    parentPath: '/teams'  // TypeScript will validate this is optional
  }
});

console.log(`Created: ${page.page.id}`);
```

**Benefits of TypeScript client:**
- ✅ Auto-completion in VS Code
- ✅ Type checking at compile time
- ✅ Inline documentation from OpenAPI spec
- ✅ Refactoring safety

### Other Supported Languages

OpenAPI Generator supports 50+ languages:

**Popular options:**
- `php` - PHP with Guzzle
- `go` - Go client
- `java` - Java with OkHttp
- `ruby` - Ruby client
- `rust` - Rust client
- `csharp` - C#/.NET client
- `kotlin` - Kotlin client
- `swift5` - Swift 5 client

**List all generators:**
```bash
openapi-generator-cli list
```

**Generate for any language:**
```bash
openapi-generator-cli generate \
  -i https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  -g <generator-name> \
  -o ./output-directory
```

## API Testing Tools

### Bruno (Open-source Postman Alternative)

**Best for:** Git-friendly API testing

1. Download Bruno: https://www.usebruno.com
2. Create new collection
3. Import → OpenAPI Spec
4. Browse to `openapi.json`
5. Configure environment variables

**Advantages over Postman:**
- ✅ Stores collections as files (Git-friendly)
- ✅ No cloud sync required (privacy)
- ✅ Offline-first design
- ✅ Fast and lightweight

**Environment file (`.env`):**
```env
BASE_URL=https://your-nextcloud.com
USERNAME=your-username
APP_PASSWORD=xxxx-xxxx-xxxx-xxxx
```

### Insomnia

**Best for:** Beautiful UI with GraphQL support

1. Download Insomnia: https://insomnia.rest
2. Import Data → From URL
3. Enter: `https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json`
4. Format: "OpenAPI 3"
5. Configure base environment

**Environment setup:**
```json
{
  "base_url": "https://your-nextcloud.com/apps/intravox",
  "username": "your-username",
  "app_password": "xxxx-xxxx-xxxx-xxxx"
}
```

### Schemathesis (Automated API Testing)

**Best for:** Finding edge cases and schema violations

Test your API implementation against the OpenAPI spec:

**Install:**
```bash
pip install schemathesis
```

**Run tests:**
```bash
# Test all endpoints
schemathesis run \
  https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  --base-url https://your-nc.com/apps/intravox \
  --auth user:app-password \
  --hypothesis-max-examples=50

# Test specific endpoint
schemathesis run \
  https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json \
  --base-url https://your-nc.com/apps/intravox \
  --auth user:app-password \
  --endpoint /api/templates

# Test with custom checks
schemathesis run openapi.json \
  --base-url https://localhost/apps/intravox \
  --auth user:password \
  --checks all \
  --hypothesis-max-examples=100
```

**What it tests:**
- ✅ Response matches schema (correct types, required fields)
- ✅ HTTP status codes are valid
- ✅ Required parameters enforced
- ✅ Edge cases (empty strings, max integers, special characters)
- ✅ Content-Type headers correct
- ✅ No 500 errors on valid input

**Example output:**
```
GET /api/templates .                                            [PASSED]
GET /api/templates/{id} .                                       [PASSED]
POST /api/pages/from-template .                                 [PASSED]
POST /api/templates F                                           [FAILED]

FAILURES:
POST /api/templates
  - Schema violation: response missing 'templateId' field
```

### Dredd (API Blueprint Testing)

**Best for:** Contract testing (spec vs implementation)

```bash
# Install
npm install -g dredd

# Convert OpenAPI to API Blueprint (if needed)
# Or use dredd-openapi-generator

# Run tests
dredd openapi.json https://your-nc.com/apps/intravox \
  --user user:app-password
```

### HTTPie (cURL Alternative)

**Best for:** Manual testing with beautiful output

```bash
# Install
brew install httpie  # macOS
pip install httpie   # Other platforms

# List templates
http GET https://your-nc.com/apps/intravox/api/templates \
  -a username:app-password

# Create page from template
http POST https://your-nc.com/apps/intravox/api/pages/from-template \
  -a username:app-password \
  templateId=department \
  pageTitle="HR Department" \
  parentPath=/teams

# Pretty-printed JSON output
http --pretty=all GET https://your-nc.com/apps/intravox/api/templates \
  -a username:app-password
```

**Advantages over cURL:**
- ✅ Syntax highlighting
- ✅ Simpler syntax (no -H flags for JSON)
- ✅ Pretty-printed output by default
- ✅ Session support

## Next Steps

**Quick Start:**
- [TEMPLATE_API_QUICKSTART.md](TEMPLATE_API_QUICKSTART.md) - Get started with template API in 5 minutes

**API Reference:**
- [API_REFERENCE.md](API_REFERENCE.md) - Complete endpoint documentation

**Developer Guide:**
- [API_DEVELOPMENT_GUIDE.md](API_DEVELOPMENT_GUIDE.md) - Contributing to IntraVox API and maintaining OpenAPI spec

**Advanced Topics:**
- **CI/CD Integration:** Run Schemathesis tests in GitHub Actions
- **Mock Servers:** Use Prism to mock IntraVox API for frontend development
- **Contract Testing:** Ensure frontend and backend stay in sync with Pact
- **API Gateway:** Proxy IntraVox API through Kong or Nginx

**Resources:**
- OpenAPI Specification: https://spec.openapis.org/oas/v3.1.0
- OpenAPI Generator: https://openapi-generator.tech
- Swagger Tools: https://swagger.io/tools/
- Postman Learning: https://learning.postman.com
