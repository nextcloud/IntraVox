# API Development Guide

Guide for IntraVox contributors to add new API endpoints and maintain OpenAPI documentation.

## Table of Contents
- [Architecture Overview](#architecture-overview)
- [Adding a New Endpoint](#adding-a-new-endpoint)
- [Updating OpenAPI Spec](#updating-openapi-spec)
- [Schema Best Practices](#schema-best-practices)
- [Validation Checklist](#validation-checklist)
- [Case Study: Template Endpoints](#case-study-template-endpoints)
- [Resources](#resources)

## Architecture Overview

IntraVox API follows a layered architecture pattern:

```
HTTP Request
    ↓
Router (appinfo/routes.php)
    ↓
Controller (lib/Controller/ApiController.php)
    ↓
Service Layer (lib/Service/PageService.php, etc.)
    ↓
Data Access (Filesystem via Nextcloud API)
    ↓
HTTP Response (JSONResponse)
```

### Components

**Router (`appinfo/routes.php`):**
- Maps URL patterns to controller methods
- Defines HTTP verbs (GET, POST, PUT, DELETE)
- Groups related endpoints

**Controller (`lib/Controller/ApiController.php`):**
- Handles HTTP concerns (request parsing, auth checks)
- Validates input parameters
- Delegates business logic to services
- Formats responses as JSON
- Catches exceptions and returns appropriate HTTP status codes

**Service Layer (`lib/Service/`):**
- Contains business logic
- Interacts with Nextcloud filesystem API
- Reusable across controllers, CLI commands, background jobs
- Throws exceptions on errors (controller catches them)

**OpenAPI Spec (`openapi.json`):**
- Source of truth for API contracts
- Documents all endpoints, parameters, responses
- Used by Swagger UI, Postman, code generators
- Must stay in sync with actual implementation

### Design Principles

1. **Thin controllers** - Delegate to services, no business logic
2. **Reusable services** - Used by API, CLI, cron jobs
3. **OpenAPI as contract** - Spec defines what endpoints should do
4. **Consistent error handling** - All errors return JSON with `error` and `errorId` fields
5. **Semantic versioning** - Breaking changes require new API version

## Adding a New Endpoint

Follow these steps to add a new API endpoint to IntraVox.

### Step 1: Define Route

**File:** `appinfo/routes.php`

Add route to the `'routes'` array:

```php
[
    'name' => 'api#myNewEndpoint',
    'url' => '/api/my-resource',
    'verb' => 'POST'
]
```

**Naming conventions:**
- Route name: `api#{camelCase}` (e.g., `api#createPageFromTemplate`)
- URL path: `/api/{kebab-case}` (e.g., `/api/pages/from-template`)
- HTTP verb: GET (read), POST (create), PUT (update), DELETE (delete)

**Examples:**
```php
// List resources
['name' => 'api#listResources', 'url' => '/api/resources', 'verb' => 'GET']

// Get single resource
['name' => 'api#getResource', 'url' => '/api/resources/{id}', 'verb' => 'GET']

// Create resource
['name' => 'api#createResource', 'url' => '/api/resources', 'verb' => 'POST']

// Update resource
['name' => 'api#updateResource', 'url' => '/api/resources/{id}', 'verb' => 'PUT']

// Delete resource
['name' => 'api#deleteResource', 'url' => '/api/resources/{id}', 'verb' => 'DELETE']
```

### Step 2: Implement Controller Method

**File:** `lib/Controller/ApiController.php`

Add public method matching route name:

```php
/**
 * Create a new resource
 *
 * @NoAdminRequired
 * @param string $param Required parameter
 * @param string|null $optionalParam Optional parameter
 * @return JSONResponse
 */
public function myNewEndpoint(string $param, ?string $optionalParam = null): JSONResponse {
    try {
        // 1. Validate input
        if (empty($param)) {
            return new JSONResponse([
                'error' => 'Parameter "param" is required'
            ], 400);
        }

        // 2. Check permissions (if needed)
        if (!$this->permissionService->canWrite($this->userId, '/some/path')) {
            return new JSONResponse([
                'error' => 'Permission denied'
            ], 403);
        }

        // 3. Delegate to service
        $result = $this->pageService->doSomething($param, $optionalParam);

        // 4. Return success response
        return new JSONResponse([
            'success' => true,
            'data' => $result
        ], 200);

    } catch (\InvalidArgumentException $e) {
        // 5. Handle validation errors
        return new JSONResponse([
            'error' => $e->getMessage()
        ], 400);

    } catch (\Exception $e) {
        // 6. Handle unexpected errors
        $errorId = bin2hex(random_bytes(4));
        $this->logger->error('myNewEndpoint failed', [
            'errorId' => $errorId,
            'exception' => $e->getMessage(),
            'param' => $param,
            'userId' => $this->userId
        ]);

        return new JSONResponse([
            'error' => 'Internal server error',
            'errorId' => $errorId
        ], 500);
    }
}
```

**Annotations:**
- `@NoAdminRequired` - Allow non-admin users (default: admin-only)
- `@NoCSRFRequired` - Skip CSRF check (only for read-only GET endpoints)
- `@PublicPage` - Allow anonymous access (use with caution)

**Response conventions:**
- Success: `{'success': true, 'data': ...}` with 200 (or 201 for creation)
- Client error: `{'error': 'message'}` with 400/403/404
- Server error: `{'error': 'message', 'errorId': 'abc123'}` with 500

### Step 3: Implement Service Logic

**File:** `lib/Service/PageService.php` (or create new service)

Add business logic method:

```php
/**
 * Do something with a resource
 *
 * @param string $param Parameter description
 * @param string|null $optionalParam Optional parameter
 * @return array Result data
 * @throws \InvalidArgumentException if validation fails
 * @throws \Exception if operation fails
 */
public function doSomething(string $param, ?string $optionalParam = null): array {
    // 1. Validate business rules
    if (strlen($param) < 3) {
        throw new \InvalidArgumentException('Parameter must be at least 3 characters');
    }

    // 2. Access data (filesystem, database)
    $folder = $this->rootFolder->getUserFolder($this->userId);
    $file = $folder->get('/IntraVox/' . $param);

    // 3. Perform business logic
    $content = $file->getContent();
    $processed = $this->processContent($content);

    // 4. Return result
    return [
        'id' => $param,
        'content' => $processed,
        'optional' => $optionalParam
    ];
}

/**
 * Helper method for processing
 *
 * @param string $content
 * @return string
 */
private function processContent(string $content): string {
    // Processing logic here
    return strtoupper($content);
}
```

**Service responsibilities:**
- Business logic and validation
- Data access (files, database)
- Data transformation
- Throw exceptions on errors (controller catches)
- No HTTP concerns (no JSONResponse, no status codes)

### Step 4: Update OpenAPI Spec

**File:** `openapi.json`

Add endpoint documentation. See [Updating OpenAPI Spec](#updating-openapi-spec) section.

### Step 5: Test

See [Validation Checklist](#validation-checklist) section.

## Updating OpenAPI Spec

### Add Path Entry

**Location:** `openapi.json` → `paths` object

```json
"/api/my-resource": {
  "post": {
    "operationId": "my-new-endpoint",
    "summary": "Create a new resource",
    "description": "Creates a new resource with the given parameters. Requires write permission to the target folder.",
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
        "description": "Resource created successfully",
        "content": {
          "application/json": {
            "schema": {
              "$ref": "#/components/schemas/ResourceResponse"
            }
          }
        }
      },
      "400": {
        "description": "Invalid parameters",
        "content": {
          "application/json": {
            "schema": {
              "type": "object",
              "properties": {
                "error": { "type": "string" }
              }
            },
            "example": {
              "error": "Parameter \"param\" is required"
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

### Add Schema Definitions

**Location:** `openapi.json` → `components` → `schemas`

```json
"ResourceCreateRequest": {
  "type": "object",
  "required": ["param"],
  "properties": {
    "param": {
      "type": "string",
      "description": "Required parameter (minimum 3 characters)",
      "minLength": 3
    },
    "optionalParam": {
      "type": "string",
      "nullable": true,
      "description": "Optional parameter for additional configuration"
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
          "description": "Resource identifier"
        },
        "content": {
          "type": "string",
          "description": "Processed content"
        },
        "optional": {
          "type": "string",
          "nullable": true,
          "description": "Optional parameter value"
        }
      }
    }
  }
}
```

### Add Tag (if new category)

**Location:** `openapi.json` → `tags` array

```json
{
  "name": "Resources",
  "description": "Resource management endpoints for creating, reading, updating, and deleting resources"
}
```

## Schema Best Practices

### Naming Conventions

| Element | Convention | Example | Why |
|---------|------------|---------|-----|
| operationId | kebab-case | `create-page-from-template` | URL-friendly, readable |
| Schema name | PascalCase | `TemplateCreateFromRequest` | Matches class naming |
| Tag name | PascalCase, singular | `Templates` (not `Template`) | Consistency |
| Property name | camelCase | `pageTitle`, `parentPath` | JavaScript convention |

**Schema naming patterns:**
- Request schemas: `{Resource}{Action}Request`
  - `TemplateCreateFromRequest` - Create page from template
  - `TemplateSaveRequest` - Save page as template
  - `PageUpdateRequest` - Update page
- Response schemas: `{Resource}` or `{Resource}Response`
  - `Page` - Full page object
  - `TemplatePreview` - Template list item (lightweight)
  - `ErrorResponse` - Error details
- Suffixes:
  - `Request` - Input data for POST/PUT
  - `Response` - Output data (optional if same as resource)
  - `Preview` - Lightweight version for lists

### Required Fields

Always specify `required` array for object schemas:

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

**Rules:**
- List only truly required fields (validation will fail without them)
- Optional fields: omit from `required` array
- Nullable fields: add `"nullable": true`
- Never mark all fields as required if some are optional

### Descriptions

Write clear, actionable descriptions:

**Bad:**
```json
{
  "templateId": {
    "type": "string",
    "description": "The template ID"
  }
}
```

**Good:**
```json
{
  "templateId": {
    "type": "string",
    "description": "Template folder name (e.g., 'department', 'event'). Use GET /api/templates to list available IDs."
  }
}
```

**Guidelines:**
- Explain format/validation (e.g., "ISO 8601 date string", "UUID v4")
- Provide examples in description
- Link to related endpoints if helpful
- Clarify constraints (min/max length, regex patterns, enum values)
- Explain business meaning, not just technical type

### Validation Constraints

Use OpenAPI validation keywords:

```json
{
  "pageTitle": {
    "type": "string",
    "description": "Page title",
    "minLength": 1,
    "maxLength": 255
  },
  "columnCount": {
    "type": "integer",
    "description": "Number of columns",
    "minimum": 1,
    "maximum": 5
  },
  "complexity": {
    "type": "string",
    "description": "Template complexity level",
    "enum": ["simple", "medium", "advanced"]
  },
  "email": {
    "type": "string",
    "description": "User email address",
    "format": "email"
  },
  "url": {
    "type": "string",
    "description": "External URL",
    "format": "uri"
  }
}
```

**Available constraints:**
- `minLength`, `maxLength` - String length
- `minimum`, `maximum` - Number range
- `enum` - Limited set of values
- `pattern` - Regex validation
- `format` - Standard formats (email, uri, date-time, uuid)

### Examples

Provide realistic, copy-paste-ready examples:

**Single example:**
```json
"example": {
  "templateId": "department",
  "pageTitle": "HR Department",
  "parentPath": "/teams"
}
```

**Multiple examples (better):**
```json
"examples": {
  "department": {
    "summary": "Create HR department page",
    "value": {
      "templateId": "department",
      "pageTitle": "HR Department",
      "parentPath": "/teams"
    }
  },
  "event": {
    "summary": "Create company event page (no parent)",
    "value": {
      "templateId": "event",
      "pageTitle": "Annual Conference 2026"
    }
  }
}
```

**Benefits:**
- Swagger UI shows dropdown with examples
- Code generators use examples for tests
- Developers can copy-paste working values
- Documents different use cases

### Error Responses

Use reusable error response schemas:

**Reusable errors (already defined in IntraVox):**
```json
"responses": {
  "400": { "$ref": "#/components/responses/BadRequest" },
  "401": { "$ref": "#/components/responses/Unauthorized" },
  "403": { "$ref": "#/components/responses/Forbidden" },
  "404": { "$ref": "#/components/responses/NotFound" }
}
```

**Custom error with example:**
```json
"400": {
  "description": "Invalid template ID or missing required parameters",
  "content": {
    "application/json": {
      "schema": {
        "type": "object",
        "properties": {
          "error": {
            "type": "string",
            "description": "Human-readable error message"
          }
        }
      },
      "examples": {
        "missing-param": {
          "summary": "Missing required parameter",
          "value": {
            "error": "Missing required parameter: templateId"
          }
        },
        "invalid-template": {
          "summary": "Invalid template ID",
          "value": {
            "error": "Template not found: invalid-id"
          }
        }
      }
    }
  }
}
```

## Validation Checklist

Before merging your API changes, complete this checklist:

### 1. OpenAPI Spec Validation

**Install validator:**
```bash
npm install -g @apidevtools/swagger-cli
```

**Validate spec:**
```bash
cd /path/to/IntraVox
swagger-cli validate openapi.json
```

**Expected output:**
```
openapi.json is valid
```

**Common errors and fixes:**

| Error | Cause | Fix |
|-------|-------|-----|
| Missing required field | Schema missing `required` array | Add required fields list |
| Invalid $ref | Path doesn't exist | Check schema name spelling |
| Duplicate operationId | Two endpoints same ID | Use unique kebab-case IDs |
| Missing response schema | Response has no schema | Add schema or use existing |

### 2. Manual Testing (cURL)

**Test success case:**
```bash
curl -X POST \
  -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{"param":"valid-value"}' \
  https://localhost/apps/intravox/api/my-resource
```

**Test error cases:**
```bash
# Missing parameter
curl -X POST \
  -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{}' \
  https://localhost/apps/intravox/api/my-resource

# Invalid parameter
curl -X POST \
  -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{"param":"ab"}' \
  https://localhost/apps/intravox/api/my-resource

# Unauthorized (wrong password)
curl -X POST \
  -u "user:wrong-password" \
  -H "Content-Type: application/json" \
  -d '{"param":"valid"}' \
  https://localhost/apps/intravox/api/my-resource
```

**Verify:**
- ✅ Response matches OpenAPI schema
- ✅ HTTP status codes correct (200, 201, 400, 401, 403, 404, 500)
- ✅ Error messages are helpful (not just "Bad Request")
- ✅ Authentication works (401 if credentials wrong)
- ✅ Authorization works (403 if no permission)

### 3. Postman Import Test

1. Open Postman
2. Import → Link → `https://raw.githubusercontent.com/nextcloud/IntraVox/main/openapi.json`
3. Find your new endpoint in collection
4. Send request with valid data
5. Verify response matches schema
6. Try invalid data (should return 400)

### 4. Code Generation Test

**Generate TypeScript client:**
```bash
npx @openapitools/openapi-generator-cli generate \
  -i openapi.json \
  -g typescript-fetch \
  -o /tmp/intravox-test-client
```

**Check for:**
- ✅ No generation errors
- ✅ Method names make sense (check `/tmp/intravox-test-client/apis/`)
- ✅ Type definitions accurate
- ✅ Examples included in docs

**Inspect generated files:**
```bash
# List generated API methods
ls /tmp/intravox-test-client/apis/

# Check your new endpoint method
grep -A 10 "myNewEndpoint" /tmp/intravox-test-client/apis/*.ts
```

### 5. Documentation Review

**Self-review checklist:**
- [ ] `operationId` follows kebab-case convention
- [ ] Summary is concise (<70 characters)
- [ ] Description explains what, why, and when to use
- [ ] All parameters have descriptions
- [ ] Examples are realistic and copy-paste ready
- [ ] Error responses documented with examples
- [ ] Schema names follow PascalCase
- [ ] Required fields array is accurate
- [ ] Tag exists and is appropriate
- [ ] No typos in descriptions

**Peer review:**
- Ask colleague to read OpenAPI docs without context
- Can they understand what endpoint does?
- Are examples clear enough to use immediately?

## Case Study: Template Endpoints

The template endpoints added in v0.9.17 demonstrate best practices. Let's analyze them.

### Routes Definition

**File:** `appinfo/routes.php:127-132`

```php
// List templates
['name' => 'api#listTemplates', 'url' => '/api/templates', 'verb' => 'GET'],

// Get template details
['name' => 'api#getTemplate', 'url' => '/api/templates/{id}', 'verb' => 'GET'],

// Create page from template
['name' => 'api#createPageFromTemplate', 'url' => '/api/pages/from-template', 'verb' => 'POST'],

// Save page as template
['name' => 'api#saveAsTemplate', 'url' => '/api/templates', 'verb' => 'POST'],

// Delete template
['name' => 'api#deleteTemplate', 'url' => '/api/templates/{id}', 'verb' => 'DELETE'],
```

**Design decisions:**
- ✅ RESTful URL structure (`/api/templates` for collection)
- ✅ Consistent naming (`listTemplates`, `getTemplate`, etc.)
- ✅ Special case: `/api/pages/from-template` (creates pages, not templates)
- ✅ Standard HTTP verbs (GET list/details, POST create, DELETE remove)

### Controller Implementation

**File:** `lib/Controller/ApiController.php:3156-3183`

Example: `createPageFromTemplate()`

```php
/**
 * Create page from template
 *
 * @NoAdminRequired
 * @param string $templateId Template ID
 * @param string $pageTitle Page title
 * @param string|null $parentPath Parent folder path
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
        ], 201); // 201 Created for new resource

    } catch (\Exception $e) {
        return new JSONResponse([
            'error' => $e->getMessage()
        ], 400);
    }
}
```

**Key patterns:**
- ✅ Thin controller (delegates to service immediately)
- ✅ Type hints for all parameters
- ✅ Nullable parameter with default value (`?string $parentPath = null`)
- ✅ 201 status for resource creation (not just 200)
- ✅ Generic exception handling (service throws specific errors)
- ✅ Current user ID injected (`$this->userId`)

### OpenAPI Schemas

**TemplatePreview** (for list endpoint):
```json
{
  "type": "object",
  "properties": {
    "id": {
      "type": "string",
      "description": "Template identifier (folder name)"
    },
    "name": {
      "type": "string",
      "description": "Template display name"
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

**TemplateCreateFromRequest** (for create endpoint):
```json
{
  "type": "object",
  "required": ["templateId", "pageTitle"],
  "properties": {
    "templateId": {
      "type": "string",
      "description": "Template folder name (e.g., 'department', 'event')"
    },
    "pageTitle": {
      "type": "string",
      "description": "Title for the new page"
    },
    "parentPath": {
      "type": "string",
      "nullable": true,
      "description": "Optional parent folder path (e.g., '/teams')"
    }
  }
}
```

**Design decisions:**
- ✅ Separate schemas for different use cases (list vs create)
- ✅ Enums for validation (`complexity`)
- ✅ Min/max constraints (`columnCount` 1-5)
- ✅ Explicit nullability (`parentPath`)
- ✅ Descriptive descriptions with examples
- ✅ Clear required fields (`templateId`, `pageTitle`)

### Examples

Multiple examples for different use cases:

```json
"examples": {
  "department": {
    "summary": "Create HR department page",
    "value": {
      "templateId": "department",
      "pageTitle": "HR Department",
      "parentPath": "/teams"
    }
  },
  "event": {
    "summary": "Create company event page",
    "value": {
      "templateId": "event",
      "pageTitle": "Annual Conference 2026"
    }
  }
}
```

**Why multiple examples:**
- ✅ Shows different templates
- ✅ Demonstrates optional parameter (with/without `parentPath`)
- ✅ Helps developers choose right template for use case

### Commits

See CHANGELOG v0.9.17:
- Template endpoints implementation (controller + service)
- OpenAPI spec update (schemas + endpoints)
- Version bump to 0.9.17

## Resources

**OpenAPI Specification:**
- Official spec: https://spec.openapis.org/oas/v3.1.0
- Examples: https://github.com/OAI/OpenAPI-Specification/tree/main/examples

**Tools:**
- Swagger Editor: https://editor.swagger.io (online validator)
- OpenAPI Generator: https://openapi-generator.tech (client generation)
- swagger-cli: https://github.com/APIDevTools/swagger-cli (validation)

**Nextcloud Development:**
- App development: https://docs.nextcloud.com/server/latest/developer_manual/
- Coding standards: https://docs.nextcloud.com/server/latest/developer_manual/basics/coding_standard.html
- API guidelines: https://docs.nextcloud.com/server/latest/developer_manual/basics/controllers.html

**IntraVox Docs:**
- Quick Start: [TEMPLATE_API_QUICKSTART.md](TEMPLATE_API_QUICKSTART.md)
- OpenAPI Tooling: [OPENAPI_TOOLING.md](OPENAPI_TOOLING.md)
- API Reference: [API_REFERENCE.md](API_REFERENCE.md)

**Learning Resources:**
- OpenAPI tutorial: https://oai.github.io/Documentation/
- RESTful API design: https://restfulapi.net/
- JSON Schema: https://json-schema.org/learn/
