# Template API Quickstart

Get started with IntraVox Template API in 5 minutes.

## Table of Contents
- [Authentication](#authentication)
- [Quick Reference](#quick-reference)
- [Use Case 1: List Templates](#use-case-1-list-templates)
- [Use Case 2: Create Page from Template](#use-case-2-create-page-from-template)
- [Use Case 3: Save Custom Template](#use-case-3-save-custom-template)
- [Error Handling](#error-handling)
- [Code Examples](#code-examples)
- [Next Steps](#next-steps)

## Authentication

IntraVox API uses HTTP Basic Authentication with Nextcloud app passwords.

**Create an app password:**
1. Nextcloud Settings → Security → Devices & sessions
2. Create new app password
3. Copy the generated token

**Use in API calls:**
```bash
curl -u "username:app-password-token" \
  https://your-nextcloud.com/apps/intravox/api/templates
```

> **Note:** Never use your actual Nextcloud password for API calls. Always use app-specific passwords for security.

## Quick Reference

| Endpoint | Method | Purpose | Auth Required |
|----------|--------|---------|---------------|
| `/api/templates` | GET | List all available templates | Yes |
| `/api/templates/{id}` | GET | Get template details | Yes |
| `/api/pages/from-template` | POST | **Create page from template** | Yes |
| `/api/templates` | POST | Save page as custom template | Yes |
| `/api/templates/{id}` | DELETE | Delete custom template | Yes |

**Built-in templates:** department, event, knowledge-base, landing-page, news-article, news-hub, project

Full API Reference: [API_REFERENCE.md](API_REFERENCE.md)

## Use Case 1: List Templates

**Goal:** Get all available templates with metadata

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
      "description": "Team information, services, and resources",
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
      "description": "Event planning, speakers, and registration",
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

**What you get:**
- `id` - Template identifier (use this to create pages)
- `name` - Display name
- `description` - Template purpose
- `complexity` - Simple, medium, or advanced
- `columnCount` - Number of columns in layout
- `widgetCount` - Total widgets in template
- `widgetTypes` - Types of widgets used
- `hasHeaderRow` - Whether template has header row
- `hasSidebars` - Whether template has side columns

## Use Case 2: Create Page from Template

**Goal:** Create a new "HR Department" page based on the "department" template

**Request:**
```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "templateId": "department",
    "pageTitle": "HR Department",
    "parentPath": "/teams"
  }' \
  https://your-nextcloud.com/apps/intravox/api/pages/from-template
```

**Request Parameters:**
- `templateId` (**required**) - Template ID from list (e.g., "department", "event")
- `pageTitle` (**required**) - Title for your new page
- `parentPath` (optional) - Parent folder path (e.g., "/teams", "/departments")

**Response (201 Created):**
```json
{
  "success": true,
  "page": {
    "id": "hr-department",
    "title": "HR Department",
    "path": "/teams/hr-department",
    "uniqueId": "a3f8e9c2-4d5b-6f7e-8a9b-0c1d2e3f4a5b",
    "layout": {
      "columns": 3,
      "hasHeaderRow": true,
      "hasSidebars": false
    },
    "widgets": [
      {
        "type": "heading",
        "content": "Welcome to HR Department"
      }
    ]
  }
}
```

**What happens:**
1. Template layout and widgets are cloned to new page
2. New page is created in specified parent folder (or root if omitted)
3. Media files are copied from template directory
4. Unique page ID is generated for permalinks
5. Page is immediately accessible in IntraVox UI

**More examples:**

Create event page without parent folder:
```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "templateId": "event",
    "pageTitle": "Annual Conference 2026"
  }' \
  https://your-nextcloud.com/apps/intravox/api/pages/from-template
```

Create knowledge base page in docs folder:
```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "templateId": "knowledge-base",
    "pageTitle": "API Documentation",
    "parentPath": "/docs"
  }' \
  https://your-nextcloud.com/apps/intravox/api/pages/from-template
```

## Use Case 3: Save Custom Template

**Goal:** Save your existing page as a reusable template

**Request:**
```bash
curl -X POST \
  -u "username:app-password" \
  -H "Content-Type: application/json" \
  -d '{
    "pageId": "my-custom-page",
    "name": "My Project Template",
    "description": "Custom layout for project pages"
  }' \
  https://your-nextcloud.com/apps/intravox/api/templates
```

**Request Parameters:**
- `pageId` (**required**) - Unique ID of existing page to save as template
- `name` (**required**) - Template name (displayed in template picker)
- `description` (optional) - Template description

**Response (200 OK):**
```json
{
  "success": true,
  "templateId": "my-project-template"
}
```

**Delete custom template:**
```bash
curl -X DELETE \
  -u "username:app-password" \
  https://your-nextcloud.com/apps/intravox/api/templates/my-project-template
```

> **Note:** Built-in templates (department, event, etc.) cannot be deleted. Only custom templates can be removed.

## Error Handling

**Common Errors:**

| HTTP Code | Error | Cause | Solution |
|-----------|-------|-------|----------|
| 401 | Unauthorized | Invalid credentials | Check username/app-password |
| 400 | Invalid template ID | Template doesn't exist | Use ID from `GET /api/templates` |
| 400 | Missing required parameter | `templateId` or `pageTitle` not provided | Include all required fields |
| 403 | Forbidden | No write permission to folder | Check GroupFolder ACL permissions |
| 404 | Not Found | Page or template not found | Verify page/template ID exists |

**Example Error Response:**
```json
{
  "error": "Template not found: invalid-id",
  "errorId": "6f8a4c2b"
}
```

Use `errorId` when reporting issues to support for easier debugging.

**Validation errors:**
```json
{
  "error": "Missing required parameter: templateId"
}
```

## Code Examples

### JavaScript (Fetch API)

```javascript
// List templates
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

// Create page from template
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
    throw new Error(error.error || 'Failed to create page');
  }

  const data = await response.json();
  console.log('Created page:', data.page.id);
  return data.page;
}

// Usage
try {
  const templates = await listTemplates();
  const page = await createPageFromTemplate('department', 'HR Department', '/teams');
  console.log('Success! Page URL:', `/apps/intravox#${page.path}`);
} catch (error) {
  console.error('Error:', error.message);
}
```

### Python (requests)

```python
import requests
from requests.auth import HTTPBasicAuth

# Configuration
BASE_URL = 'https://your-nc.com/apps/intravox/api'
auth = HTTPBasicAuth('username', 'app-password')

# List templates
def list_templates():
    response = requests.get(f'{BASE_URL}/templates', auth=auth)
    response.raise_for_status()
    templates = response.json()['templates']
    print(f'Found {len(templates)} templates')
    return templates

# Create page from template
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
    print(f'Created page: {page["id"]}')
    return page

# Save page as template
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
    print(f'Saved template: {template_id}')
    return template_id

# Usage
try:
    templates = list_templates()
    page = create_page_from_template('department', 'HR Department', '/teams')
    print(f'Success! Page path: {page["path"]}')
except requests.exceptions.HTTPError as e:
    print(f'HTTP Error: {e.response.status_code}')
    print(f'Error message: {e.response.json().get("error")}')
except Exception as e:
    print(f'Error: {e}')
```

### PHP (Guzzle)

```php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Configuration
$client = new Client([
    'base_uri' => 'https://your-nc.com/apps/intravox/api/',
    'auth' => ['username', 'app-password']
]);

// List templates
function listTemplates($client) {
    try {
        $response = $client->get('templates');
        $data = json_decode($response->getBody(), true);
        $templates = $data['templates'];
        echo "Found " . count($templates) . " templates\n";
        return $templates;
    } catch (RequestException $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return [];
    }
}

// Create page from template
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
        echo "Created page: {$page['id']}\n";
        return $page;
    } catch (RequestException $e) {
        if ($e->hasResponse()) {
            $error = json_decode($e->getResponse()->getBody(), true);
            echo "Error: " . ($error['error'] ?? 'Unknown error') . "\n";
        }
        return null;
    }
}

// Save page as template
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
        echo "Saved template: $templateId\n";
        return $templateId;
    } catch (RequestException $e) {
        echo "Error: " . $e->getMessage() . "\n";
        return null;
    }
}

// Usage
$templates = listTemplates($client);
$page = createPageFromTemplate($client, 'department', 'HR Department', '/teams');
if ($page) {
    echo "Success! Page path: {$page['path']}\n";
}
```

### cURL (Shell Script)

```bash
#!/bin/bash

# Configuration
BASE_URL="https://your-nextcloud.com/apps/intravox/api"
AUTH="username:app-password"

# List templates
list_templates() {
    echo "Fetching templates..."
    curl -s -u "$AUTH" "$BASE_URL/templates" | jq '.'
}

# Create page from template
create_page_from_template() {
    local template_id=$1
    local page_title=$2
    local parent_path=${3:-}

    local payload='{"templateId":"'$template_id'","pageTitle":"'$page_title'"'

    if [ -n "$parent_path" ]; then
        payload+=', "parentPath":"'$parent_path'"'
    fi

    payload+='}'

    echo "Creating page '$page_title' from template '$template_id'..."
    curl -s -X POST \
        -u "$AUTH" \
        -H "Content-Type: application/json" \
        -d "$payload" \
        "$BASE_URL/pages/from-template" | jq '.'
}

# Save page as template
save_as_template() {
    local page_id=$1
    local name=$2
    local description=${3:-}

    local payload='{"pageId":"'$page_id'","name":"'$name'"'

    if [ -n "$description" ]; then
        payload+=', "description":"'$description'"'
    fi

    payload+='}'

    echo "Saving page '$page_id' as template '$name'..."
    curl -s -X POST \
        -u "$AUTH" \
        -H "Content-Type: application/json" \
        -d "$payload" \
        "$BASE_URL/templates" | jq '.'
}

# Usage examples
list_templates
create_page_from_template "department" "HR Department" "/teams"
# save_as_template "my-page-id" "My Custom Template" "Custom project layout"
```

## Next Steps

**Explore More:**
- **Full API Reference:** [API_REFERENCE.md](API_REFERENCE.md) - Complete endpoint documentation with all parameters and responses
- **Template Features:** [TEMPLATES.md](TEMPLATES.md) - Template system overview and built-in template descriptions
- **OpenAPI Tooling:** [OPENAPI_TOOLING.md](OPENAPI_TOOLING.md) - Use Swagger UI, Postman, and code generators with IntraVox API
- **Authorization:** [AUTHORIZATION.md](AUTHORIZATION.md) - GroupFolder permissions and access control

**Advanced Topics:**
- **Bulk Operations:** Create multiple pages from templates programmatically
- **Custom Workflows:** Integrate template creation in your CI/CD pipelines
- **Webhooks:** Trigger page creation from external events
- **Media Management:** Upload and manage template media files via WebDAV

**Need Help?**
- GitHub Issues: https://github.com/nextcloud/IntraVox/issues
- Nextcloud Community: https://help.nextcloud.com
