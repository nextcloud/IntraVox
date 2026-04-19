# Feed Widget

The Feed Widget displays content from external sources on your intranet pages. It supports RSS/Atom feeds and learning management systems (Canvas, Moodle, Brightspace), with optional per-user personalization through OAuth2.

## Features

- **Multiple source types** — RSS/Atom feeds, Canvas LMS, Moodle, and Brightspace
- **Content types** — News/announcements, My Courses, and Upcoming Deadlines for all LMS platforms
- **Two layouts** — List or Grid view (2-4 columns)
- **Personalized content** — Users connect their own LMS account to see only their courses, deadlines, and announcements
- **OAuth2 integration** — One-click account linking with Canvas, Moodle, and Brightspace via popup flow
- **Manual token fallback** — Users can paste an API token for Moodle or Brightspace without requiring OAuth2 setup
- **OIDC auto-connect** — Zero-click personalization when Nextcloud and the LMS share the same identity provider
- **Configurable display** — Show/hide images, dates, excerpts, source name, and author
- **15-minute server-side cache** — Reduces API calls to external systems
- **Public share support** — Feed widgets work on publicly shared IntraVox pages

## Source Types

### RSS / Atom Feed

The simplest source type. Enter a feed URL and the widget fetches and displays the items.

**How to use:**

1. Add a Feed widget to your page
2. Select **RSS / Atom feed** as the source type
3. Enter the feed URL (e.g., `https://example.com/feed.xml`)
4. Click **Preview** to verify the feed works
5. Click **Save**

The widget automatically detects RSS 2.0 and Atom feed formats. Images are extracted from feed enclosures, `media:content`, `media:thumbnail`, or inline `<img>` tags.

### Canvas LMS

Displays personalized content from a Canvas LMS instance. Supports both shared (admin token) and personalized (per-user OAuth2) access.

**Content types:**

| Content type | What it shows | API used |
|---|---|---|
| **News / Announcements** (default) | Course announcements with title, message, author, and date. Optionally filtered by course. | `/api/v1/announcements` |
| **My Courses** | The user's enrolled courses with course code and link | `/api/v1/courses` |
| **Upcoming Deadlines** | Assignment deadlines for the next 30 days across all courses | `/api/v1/calendar_events` |

**Personalization:**
When the connection uses OAuth2 (`authMode: oauth2` or `both`), each user connects their own Canvas account. The widget then shows only content from courses the user is enrolled in.

**Setup requirements:**
- Administrator configures a Canvas connection in IntraVox Admin Settings (see [Admin Settings Guide](ADMIN_SETTINGS.md))
- For OAuth2: a Developer Key must be created in Canvas Admin

### Moodle

Displays personalized content from a Moodle instance.

**Content types:**

| Content type | What it shows | API used |
|---|---|---|
| **News / Announcements** (default) | Forum discussions (with course ID) or course listings (without). | `mod_forum_get_forum_discussions` / `core_course_get_courses` |
| **My Courses** | The user's enrolled courses | `core_enrol_get_users_courses` |
| **Upcoming Deadlines** | Upcoming calendar events across all courses | `core_calendar_get_calendar_upcoming_view` |

**Personalization options:**
1. **OAuth2** — Requires the [local_oauth2 plugin](https://moodle.org/plugins/local_oauth2) installed on Moodle
2. **Manual token** — Users paste their personal web service token (generated in Moodle under Preferences > Security keys)

**Setup requirements:**
- Administrator configures a Moodle connection in IntraVox Admin Settings
- Moodle web services must be enabled with the REST protocol activated
- For manual tokens: the web service must expose `core_course_get_courses`, `core_enrol_get_users_courses`, `core_calendar_get_calendar_upcoming_view`, `core_webservice_get_site_info`, `mod_forum_get_forums_by_courses`, and `mod_forum_get_forum_discussions` functions

### Brightspace (D2L)

Displays personalized content from a Brightspace (Desire2Learn) instance.

**Content types:**

| Content type | What it shows | API used |
|---|---|---|
| **News / Announcements** (default) | News items from a specific org unit, or organization-level news | `/d2l/api/le/1.67/{orgUnitId}/news/` |
| **My Courses** | The user's enrolled courses (active enrollments) | `/d2l/api/lp/1.43/enrollments/myenrollments/` |
| **Upcoming Deadlines** | Calendar events for the next 30 days | `/d2l/api/le/1.67/calendar/events/myEvents/` |

**Personalization options:**
1. **OAuth2** — Register an OAuth2 app in Brightspace Admin under Manage Extensibility
2. **Manual token** — Users paste a personal bearer token from Brightspace Account Settings

**Setup requirements:**
- Administrator configures a Brightspace connection in IntraVox Admin Settings
- For OAuth2: register an OAuth2 application in Brightspace with the redirect URI shown in IntraVox

### REST API (custom)

Connect to any system with a REST/JSON API — Jira, Confluence, AFAS, TOPdesk, OpenProject, ZGW APIs, or any other REST endpoint.

**How to use:**

1. Go to IntraVox **Admin Settings** → Feed Connections
2. Add a connection with type **REST API (custom)**
3. Configure:
   - **Base URL** — the API root (e.g., `https://jira.example.com`)
   - **Endpoint path** — the API path (e.g., `/rest/api/2/search?jql=project=KEY`)
   - **Auth method** — Bearer token, API key (custom header), Basic auth, or No authentication
   - **Response mapping** — map JSON fields to feed items (title, URL, excerpt, date, image, author)
4. Save the connection
5. Add a Feed widget and select your REST API connection

**Response mapping:**

The admin maps JSON fields from the API response to feed item properties using dot-notation paths:

| Feed field | JSON path example | Description |
|-----------|------------------|-------------|
| Items path | `results` or `issues` or `data.items` | Where the array of items lives in the JSON response. Leave empty if the response itself is an array |
| Title | `title` or `fields.summary` | Item title (required) |
| URL | `url` or `_links.webui` | Clickable link. Relative URLs are made absolute using the base URL |
| Excerpt | `body` or `fields.description` | Text excerpt (HTML is stripped, max 300 chars) |
| Date | `created_at` or `history.lastUpdated.when` | Publication/update date |
| Image | `thumbnail` or `avatar_url` | Image URL (optional) |
| Author | `author` or `history.lastUpdated.by.displayName` | Author name (optional) |

**Example configurations:**

| System | Endpoint | Items path | Title | URL |
|--------|----------|-----------|-------|-----|
| Jira | `/rest/api/2/search?jql=ORDER+BY+updated` | `issues` | `fields.summary` | `self` |
| Confluence | `/rest/api/content?spaceKey=WIKI&expand=history.lastUpdated` | `results` | `title` | `_links.webui` |
| AFAS | `/profitrestservices/connectors/Employees` | `rows` | `Naam` | — |
| TOPdesk | `/tas/api/incidents?status=open` | — | `briefDescription` | `_links.self.href` |
| OpenProject | `/api/v3/work_packages` | `_embedded.elements` | `subject` | `_links.self.href` |
| ZGW (Open Zaak) | `/zaken/api/v1/zaken` | `results` | `omschrijving` | `url` |

**Authentication & personalization:**

REST API connections support the same three authentication levels as LMS connections. This is how security-trimmed, personalized feeds work:

1. **Admin token (shared)** — One API token configured by the admin. All users see the same data, limited to what the service account has access to. Good for public APIs or shared dashboards.

2. **OAuth2 (per-user)** — Each user connects their own account via OAuth2. The API returns only data that user has access to. Fully security-trimmed. Requires the external system to support OAuth2 and the admin to configure Client ID + Secret.

3. **OIDC auto-connect (zero-click SSO)** — If Nextcloud and the external system share the same identity provider (e.g., SURFconext, Keycloak, Azure AD), IntraVox automatically uses the user's existing SSO token. No clicks needed — fully personalized, fully security-trimmed, fully transparent.

The widget resolves tokens in this priority order: OIDC auto-connect → per-user OAuth2 → admin fallback. If no token is available and authMode is `oauth2`, the widget shows a "Connect your account" prompt.

**When to use which auth level:**

| Scenario | Auth level | Example |
|----------|-----------|---------|
| Public API, no login needed | No authentication | SURF Confluence (public spaces) |
| Shared dashboard, same view for everyone | Admin token | Company-wide Jira board |
| Personal feed, each user sees their own data | OAuth2 (per-user) | My Jira tickets, my TOPdesk incidents |
| SSO environment, zero friction | OIDC auto-connect | SURFconext + Jira, Azure AD + M365 |

## Layouts

### List Layout

Displays items in a vertical list with optional image, title, date, excerpt, and source.

### Grid Layout

Shows items in a responsive grid. Configure between 2, 3, or 4 columns.

## Adding the Widget

1. Click **+ Add Widget** in edit mode
2. Select **Feed** from the widget picker
3. Choose a source type from the dropdown
4. Configure the source (URL for RSS, or select a connection for LMS)
5. Adjust layout and display options
6. Click **Save**

## Configuration

### Source Settings

| Setting | Description | Applies to |
|---------|-------------|------------|
| **Source type** | RSS/Atom feed, LMS type (Canvas, Moodle, Brightspace), or REST API (custom) | All |
| **Feed URL** | URL of the RSS or Atom feed | RSS only |
| **Connection** | Admin-configured connection to use | LMS + REST API |
| **Content type** | News/Announcements, My Courses, or Upcoming Deadlines | LMS only |
| **Course** | Limit results to a specific course (optional, only shown for News content type) | LMS only |

### Sort & Filter

| Setting | Description | Default |
|---------|-------------|---------|
| **Sort by** | Date or Title | Date |
| **Sort order** | Newest first or Oldest first | Newest first |
| **Filter by keyword** | Only show items containing this word in title, excerpt, or author | *(empty — no filter)* |

Sorting and filtering are applied server-side after caching. The cache stores all items; sort/filter selects from the cached set. This means changing sort/filter is instant (no re-fetch from external API).

### Layout Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Layout** | List or Grid | List |
| **Columns** | Number of grid columns (2-4) | 3 |
| **Number of items** | Maximum items to display (1-20) | 5 |

### Display Options

| Setting | Description | Default |
|---------|-------------|---------|
| **Show image** | Display item thumbnail or extracted image | Enabled |
| **Show date** | Display publication date | Enabled |
| **Show excerpt** | Display text excerpt | Enabled |
| **Show source** | Display the source/feed name | Disabled |
| **Open links in new tab** | Links open in a new browser tab | Enabled |

## Personalized LMS Content

By default, LMS connections use a shared admin token — all users see the same data. With per-user authentication, each user connects their own account and sees only content they have access to.

### How It Works

The widget resolves tokens in this order:

1. **OIDC auto-connect** — If enabled and Nextcloud + LMS share the same identity provider, the widget uses the user's existing SSO token automatically (zero clicks)
2. **Per-user OAuth2 token** — If the user previously connected via OAuth2
3. **Per-user manual token** — If the user entered an API token manually
4. **Admin fallback** — If `authMode` is `both`, falls back to the shared admin token
5. **No token** — If `authMode` is `oauth2` and the user hasn't connected, shows a "Connect your account" prompt

### Connecting Your Account

When you open a page with a Feed widget that requires authentication:

1. The widget shows a **"Not connected"** badge and a **"Connect your account"** button
2. Click the button — a popup opens with the LMS login page
3. Log in and authorize IntraVox
4. The popup closes automatically and the widget loads your personalized content

For Moodle or Brightspace without OAuth2, you can also click **"Enter token manually"** and paste your API token.

### Disconnecting

Click the **"Disconnect"** button next to the green "Connected" badge in the widget editor.

## Token Refresh

OAuth2 tokens for Canvas, Moodle, and Brightspace are automatically refreshed when expired — no user action required. If the refresh fails (e.g., access revoked in the LMS), the user is prompted to reconnect.

Manual tokens (Moodle and Brightspace) do not expire automatically.

## Tips

- **Test with Preview** — For RSS feeds, use the Preview button in the editor to verify the feed before saving
- **Content types** — Use "My Courses" to show enrolled courses, "Upcoming Deadlines" for assignment deadlines, or "News" for announcements
- **Course filter** — Only shown for the "News" content type. Leave empty for all courses, or select a specific course to filter
- **Multiple feeds** — Add multiple Feed widgets to a page for different sources or content types (e.g., one for Canvas deadlines, one for Brightspace announcements, one for RSS)
- **Public pages** — Feed widgets on publicly shared pages use the admin token (if available). Per-user tokens are not used for anonymous visitors

## Troubleshooting

| Problem | Solution |
|---------|----------|
| "No items found" | Check the feed URL or verify the LMS has content in the selected course |
| "Failed to load feed" | Verify the connection URL and credentials in Admin Settings |
| Canvas shows "Missing context_codes" | This is fixed in v1.2.0 — the widget now fetches courses automatically |
| OAuth2 popup blocked | Allow popups for your Nextcloud domain in browser settings |
| "Connect your account" not showing | The connection must have `authMode` set to `oauth2` or `both` in Admin Settings |
| Stale data | The widget caches results for 15 minutes. Wait or clear the Nextcloud cache |
