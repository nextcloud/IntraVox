# IntraVox Scenarios

Practical recipes for common use cases, combining IntraVox with other Nextcloud apps. These scenarios require no changes to IntraVox itself — they use existing Nextcloud features and IntraVox's native permission model.

**Related documentation:**
- [Administrator Guide](guide.md) - Installation and configuration
- [Authorization Guide](authorization.md) - Permissions and access control
- [Editor Guide](../user/editor.md) - Content editing and publishing

---

## Scenario 1: Content Approval Workflow

A three-tier content workflow where creators draft pages, reviewers provide feedback, and publishers make content visible to the organization.

> **References issue:** [#33 — Capabilities for moderation of content and workflow function](https://github.com/nextcloud/IntraVox/issues/33)

### Goal

| Role | Can do | Group |
|------|--------|-------|
| Creator | Create and edit draft pages | IntraVox Editors |
| Reviewer | Edit drafts, request publication | Section-specific editor group |
| Publisher | Publish pages (toggle draft → published) | IntraVox Admins |

### Prerequisites

| App | Required? | Purpose |
|-----|-----------|---------|
| [GroupFolders](https://apps.nextcloud.com/apps/groupfolders) | Yes | File-based permissions and ACL |
| [Approval](https://apps.nextcloud.com/apps/approval) | Recommended | Formal approve/reject workflow on files |
| [MetaVox](https://apps.nextcloud.com/apps/metavox) | Optional | Custom metadata fields for status tracking |

### Step 1: Use the Built-in Permission Groups

IntraVox automatically creates three groups during setup:

| Group | Permissions | Role in this workflow |
|-------|-------------|----------------------|
| IntraVox Admins | Full access | **Publishers** — can toggle draft/published status |
| IntraVox Editors | Read + Write + Create | **Creators/Reviewers** — can create and edit pages |
| IntraVox Users | Read only | **Readers** — see published pages only |

This three-tier structure already covers the basic workflow:

1. **Editors** create a new page — it starts as **Draft** automatically
2. Editors write and refine the content while it remains invisible to readers
3. An **Admin** reviews the page and toggles it to **Published**

> **Tip:** Draft pages are completely invisible to users with read-only access. They do not appear in navigation, search, RSS feeds, or public share links.

### Step 2: Add Section-Specific Groups (Optional)

For larger organizations, create custom groups per department or section:

1. Go to **Nextcloud Admin → Users** and create groups like:
   - `Marketing Editors`
   - `HR Editors`
2. Go to **Admin → GroupFolders** → IntraVox folder
3. Enable **Advanced Permissions** (ACL)
4. In Nextcloud Files, navigate to the section folder (e.g. `IntraVox/en/marketing/`)
5. Click the share icon → set ACL rules:
   - `Marketing Editors`: Read + Write + Create
   - Others: Read only

This way, Marketing editors can only edit marketing pages, not HR pages.

### Step 3: Set Up the Approval App

The [Nextcloud Approval app](https://apps.nextcloud.com/apps/approval) adds a formal review workflow to files. Since IntraVox pages are stored as JSON files in GroupFolders, the Approval app works with them directly.

#### 3a. Install and Enable

```bash
occ app:enable approval
```

#### 3b. Create System Tags

Go to **Admin → Basic settings → Collaborative tags** and create:

| Tag | Visibility | Purpose |
|-----|-----------|---------|
| `Pending Review` | Hidden | Assigned when editor requests review |
| `Approved` | Hidden | Assigned when publisher approves |
| `Rejected` | Hidden | Assigned when publisher requests changes |

> Hidden tags are only visible to administrators, keeping the workflow clean for regular users.

#### 3c. Configure the Approval Workflow

Go to **Admin → Approval workflows** and create a workflow:

| Setting | Value |
|---------|-------|
| **Description** | Content publication review |
| **Pending tag** | `Pending Review` |
| **Approved tag** | `Approved` |
| **Rejected tag** | `Rejected` |
| **Approvers** | The `IntraVox Admins` group (publishers) |

#### 3d. The Workflow in Practice

1. **Editor** creates a page in IntraVox (starts as Draft)
2. Editor opens the page's JSON file in Nextcloud Files sidebar → clicks **Request approval**
3. Publishers (IntraVox Admins) receive a notification
4. Publisher reviews the page in IntraVox, then:
   - **Approves** → opens Files sidebar and approves the file → toggles page to Published in IntraVox
   - **Rejects** → rejects in Files sidebar, editor gets a notification to revise

#### 3e. Chain Workflows (Multi-Level Approval)

For multi-level approval (e.g. editor → section lead → publisher), create chained workflows where the "approved" tag of workflow A is the "pending" tag of workflow B:

| Workflow | Pending Tag | Approved Tag | Approvers |
|----------|-------------|-------------|-----------|
| **A: Section Review** | `Pending Review` | `Section Approved` | Section editors |
| **B: Publication** | `Section Approved` | `Published` | IntraVox Admins |

### Step 4: Track Review Status with MetaVox (Optional)

If [MetaVox](https://apps.nextcloud.com/apps/metavox) is installed, you can add custom metadata fields to pages for visual status tracking.

#### 4a. Create Metadata Fields

In MetaVox, create these fields:

| Field Name | Type | Options |
|------------|------|---------|
| Review Status | Select | `Draft`, `In Review`, `Approved`, `Published` |
| Reviewer | Text | *(free text)* |
| Review Date | Date | *(date picker)* |

#### 4b. Use in News Widget

The IntraVox News widget supports MetaVox field filtering. This means you can create a "Pending Reviews" page for publishers:

1. Create a page with a News widget
2. Set the source folder to the section you want to monitor
3. Add a MetaVox filter: `Review Status` equals `In Review`

This gives publishers a dashboard of all pages awaiting their review.

### Page mockup — Publisher dashboard

A "Pending Reviews" page that publishers open as their daily landing spot. Two News widgets side by side, both filtered on the MetaVox `Review Status` field.

```
┌──────────────────────────────────────────────────────────────────────┐
│  IntraVox    Home  News  Departments  Pending Reviews        🔍 EN   │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   # Editorial dashboard                                              │
│   Pages waiting for review, plus what's recently been published.     │
│                                                                      │
├───────────────────────────────────┬──────────────────────────────────┤
│  ⏳ Awaiting your review          │  ✅ Recently published           │
│  ─────────────────────────────    │  ────────────────────────────    │
│  News widget · List               │  News widget · List              │
│  source: /en/                     │  source: /en/                    │
│  filter: Review Status = "In      │  filter: Review Status =         │
│          Review"                  │          "Published"             │
│  sort:   Date modified desc       │  sort:   Date modified desc      │
│                                   │                                  │
│  • Q3 financial update            │  • New office in Utrecht         │
│    Marketing · Anna · 2h ago      │    HR · Sam · today              │
│  • Updated travel policy          │  • Code of conduct refresh       │
│    HR · Mira · yesterday          │    HR · Anna · 2 days ago        │
│  • Pilot results — Project X      │  • Q3 product launch             │
│    R&D · Jan · 3 days ago         │    Marketing · Mira · last week  │
│                                   │                                  │
├───────────────────────────────────┴──────────────────────────────────┤
│  ❌ Recently rejected — needs revision                               │
│  News widget · Grid (3 cols)                                         │
│  filter: Review Status = "Rejected"                                  │
│                                                                      │
│  [ Onboarding ]   [ Travel policy ]   [ Q3 forecast ]                │
│   Sales · 1d ago   HR · 3d ago         Finance · 5d ago              │
└──────────────────────────────────────────────────────────────────────┘
```

Each card on this dashboard links straight to the page; the publisher opens the page, clicks **Approve** in the Files sidebar (Approval app), and toggles **Published** in IntraVox. Same dashboard for editors works just as well — replace the filter values with `Draft` / `Rejected` to surface their own pending work.

### Tips

- Communicate the workflow process to your team — the technology supports it, but people need to know the steps
- Use **Nextcloud Talk** for review discussions: share a link to the draft page in a Talk conversation
- The Approval app sends notifications — publishers don't need to check manually
- Consider using **Nextcloud Flow** (automated tagging) to automatically tag files in specific folders, reducing manual steps

---

## Scenario 2: Department-Based Intranet

A multi-department intranet where each department manages its own section, with organization-wide pages visible to everyone.

### Goal

```
IntraVox/
├── en/
│   ├── home.json              ← Everyone can read
│   ├── news/                  ← Editors: central comms team
│   ├── departments/
│   │   ├── hr/                ← HR team: full control
│   │   ├── sales/             ← Sales team: full control
│   │   ├── marketing/         ← Marketing team: full control
│   │   └── it/                ← IT team: full control
│   └── general/               ← Everyone can read
```

Each department has full control over its own section. All employees can read everything. A central communications team manages organization-wide news.

### Prerequisites

| App | Required? | Purpose |
|-----|-----------|---------|
| [GroupFolders](https://apps.nextcloud.com/apps/groupfolders) | Yes | File-based permissions and ACL |

### Step 1: Create Department Groups

Go to **Nextcloud Admin → Users** and create groups for each department:

- `Department HR`
- `Department Sales`
- `Department Marketing`
- `Department IT`
- `Communications Team`

Add the appropriate users to each group.

### Step 2: Configure Base Permissions

In **Admin → GroupFolders**, ensure the IntraVox folder has these groups:

| Group | Base Permissions |
|-------|-----------------|
| IntraVox Admins | All (automatic) |
| IntraVox Editors | Read + Write + Create (automatic) |
| IntraVox Users | Read (automatic) |
| All department groups | Read |
| Communications Team | Read |

> Add all department groups and the Communications Team to the GroupFolder with **Read** permission as their base level. The ACL rules in the next step will grant elevated permissions on specific folders.

### Step 3: Enable ACL and Set Folder Permissions

1. In **Admin → GroupFolders**, enable **Advanced Permissions** on the IntraVox folder
2. In Nextcloud Files, navigate to each department folder and configure ACL:

**For `IntraVox/en/departments/hr/`:**

| Group/User | Read | Write | Create | Delete |
|------------|------|-------|--------|--------|
| Department HR | ✅ | ✅ | ✅ | ✅ |

**For `IntraVox/en/departments/sales/`:**

| Group/User | Read | Write | Create | Delete |
|------------|------|-------|--------|--------|
| Department Sales | ✅ | ✅ | ✅ | ✅ |

*(Repeat for each department)*

**For `IntraVox/en/news/`:**

| Group/User | Read | Write | Create | Delete |
|------------|------|-------|--------|--------|
| Communications Team | ✅ | ✅ | ✅ | ✅ |

### Step 4: Create the Folder Structure

In IntraVox, create the page hierarchy:

1. Create a **Departments** parent page
2. Under it, create subpages for each department: HR, Sales, Marketing, IT
3. Create a **News** section for organization-wide news
4. Set up navigation to reflect this structure

### Step 5: Set Up News Widgets per Department

Each department homepage can include a News widget showing its own latest pages:

1. Edit the department homepage (e.g. HR homepage)
2. Add a News widget
3. Set the source folder to the department's folder (e.g. `departments/hr`)
4. Configure layout (Grid, List, or Carousel)

The main homepage can include a News widget showing organization-wide news from the `news/` folder.

### Result

| User | Can read | Can edit |
|------|----------|----------|
| Any employee (IntraVox Users) | All pages | Nothing |
| HR team member | All pages | Only `departments/hr/` |
| Sales team member | All pages | Only `departments/sales/` |
| Communications Team | All pages | Only `news/` |
| IntraVox Admins | All pages | Everything |

### Page mockup — Department homepage (HR)

The HR department's own homepage: HR-only news on the left, shared organisation-wide news on the right, plus an "about the team" sidebar. Only HR team members see the *Edit Page* button.

```
┌──────────────────────────────────────────────────────────────────────┐
│  Acme Intranet   Home  News  Departments ▾  General         🔍 NL EN │
│                                                                      │
│  Home › Departments › HR                              [ Edit Page ]  │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   # HR — People & Culture                                            │
│   Policies, onboarding, leave, training, and everything between.     │
│                                                                      │
├───────────────────────────────────────────────────┬──────────────────┤
│  📣 Latest from HR                                │  About this team │
│  ──────────────────────────                       │  ──────────────  │
│  News widget · Grid (2 cols)                      │  • 12 people     │
│  source: /en/departments/hr/                      │  • Director:     │
│                                                   │    Sam de Vries  │
│  ┌──────────────┐ ┌──────────────┐                │ • hr@acme.example│
│  │ New office   │ │ Travel policy│                │                  │
│  │ in Utrecht   │ │ refresh      │                │  Quick links     │
│  │ Sam · today  │ │ Mira · 2d    │                │  ──────────────  │
│  └──────────────┘ └──────────────┘                │  → Leave request │
│  ┌──────────────┐ ┌──────────────┐                │  → Expense form  │
│  │ Onboarding   │ │ Code of      │                │  → Org chart     │
│  │ checklist v3 │ │ conduct      │                │  → Pay slips     │
│  │ Anna · 3d    │ │ Anna · 1w    │                │                  │
│  └──────────────┘ └──────────────┘                │  People (HR)     │
│                                                   │  ──────────────  │
├───────────────────────────────────────────────────┤  People widget   │
│  🏢 Across the company                            │  filter: group = │
│  ──────────────────────────                       │  "Department HR" │
│  News widget · List                               │                  │
│  source: /en/news/                                │  👤 Sam de Vries │
│  • All-hands recap — last Friday                  │  👤 Mira Patel   │
│  • Q3 results announced                           │  👤 Anna Kuipers │
│  • Office reorganisation update                   │  👤 Jan van Os   │
│                                                   │                  │
└───────────────────────────────────────────────────┴──────────────────┘
```

Each department duplicates this exact page layout under its own folder, changing only the News widget's source folder. The right sidebar's People widget filter follows the corresponding `Department <name>` group automatically.

---

## Scenario 3: Knowledge Base with Documents + Photos

A page-per-topic knowledge base where every topic page automatically shows the documents and photos that belong to that topic — without copying files, without folder-per-topic explosion, and without manual link curation.

### Goal

Build a knowledge base where each topic (a procedure, a technique, a product, …) lives on its own page. The page renders:

- An introduction + collapsible work instructions (text)
- A grouped list of related documents (PDF, Word, drawings, …)
- A photo gallery of practical examples

All documents live in one shared folder; all photos stay in their original project folders. A single MetaVox tag (`topic`) is the only link between them.

Worked example: a heritage-restoration office with topic pages for *Stained-glass window restoration*, *Brick repointing*, *Slate roofing*, etc.

### Prerequisites

- MetaVox app installed and configured
- One **Select** or **Text** field in MetaVox named `topic`, with values matching your topic pages — *stained-glass*, *repointing*, *slate-roofing*, …
- Optionally a second field `document_type` (specification / inspection-report / drawing / permit) for the documents-section grouping
- A central documents folder, e.g. `/KnowledgeBase/Documents/`
- Photo folders anywhere — they will be picked up cross-folder

### Step 1: Tag documents with the topic field

In the central documents folder, set MetaVox `topic` on each file (and optionally `document_type`). Tagging existing files in bulk is possible via the MetaVox bulk editor.

### Step 2: Tag photos with the same field

Set MetaVox `topic` on the relevant photos in their existing project folders. No need to move or copy them.

### Step 3: Create one topic page

Duplicate an existing topic page (or start fresh) for the new topic. The recipe per topic page:

| Row | Layout | Widget(s) | Notes |
|-----|--------|-----------|-------|
| 1 | 1 col | Heading + Text | Topic title + short intro |
| 2 | 1 col | Collapsible Section | Work instructions, FAQ-style |
| 3 | 2 col | File Story (left) + Text (right) | Documents on the left, "how to read these" on the right |
| 4 | 1 col | Photo Story | Examples gallery |
| Right sidebar | — | People + Links | Specialists + external references |

### Step 4: Configure File Story (documents)

- **Source folder**: the central documents folder (e.g. `/KnowledgeBase/Documents/`)
- **Mode**: Grouped
- **Group by**: `document_type` (or *Category (file type)* if you skipped the field)
- **Sort by**: Date modified (desc)
- **Filters**: `topic equals <topic-value>` — the only line that differs between topic pages

### Step 5: Configure Photo Story (gallery)

- **Source folder**: *leave empty* — this activates cross-folder MetaVox search
- **Filters**: `topic equals <topic-value>` (required when source is empty)
- **Mode**: Grid (3 or 4 columns); Timeline if chronology matters
- **Show captions**: on

Photos stay in their original project folders; cross-folder mode pulls every photo with the matching tag.

### Step 6: Duplicate for each new topic

Per topic: copy the page, rename it, change the `topic` filter value in both widgets. No new folders, no copied files.

### Page mockup — Topic page (*Stained-glass window restoration*)

A single topic page from the heritage knowledge base. Everything below the intro is filled by widgets that filter on `topic = "stained-glass"` — change one value and you have a brand-new topic page.

```
┌──────────────────────────────────────────────────────────────────────┐
│  Heritage Restoration   Home  Knowledge Base ▾  Projects     🔍 EN   │
│                                                                      │
│  Home › Knowledge Base › Stained glass                [ Edit Page ]  │
├──────────────────────────────────────────────────────────────────────┤
│                                                                      │
│   # Stained-glass window restoration                                 │
│   When to apply, what to watch out for, which permits you need,      │
│   and which specialists in our region do this work.                  │
│                                                                      │
├──────────────────────────────────────────────────────────────────────┤
│  ▸ Method & key considerations         (collapsible, closed)         │
│  ▸ Materials & lead profiles           (collapsible, closed)         │
│  ▸ Permits & listed-building status    (collapsible, closed)         │
├───────────────────────────────────────────────────┬──────────────────┤
│  📄 Project documents                             │  Specialists     │
│  ──────────────────────────                       │  ──────────────  │
│  File Story · Grouped by `document_type`          │  People widget   │
│  source: /KnowledgeBase/Documents/                │  filter: group = │
│  filter: topic = "stained-glass"                  │  "glass-         │
│                                                   │   restorers"     │
│  ▸ Specification               3 files            │                  │
│    Spec-2024-073-St-Martin.pdf       · 8 May      │  👤 Jan van Os   │
│    Spec-2023-051-Our-Lady.pdf        · 12 Apr     │     Glazier      │
│    Spec-2022-018-Town-Hall.pdf       · 3 Mar      │  👤 Mira Patel   │
│                                                   │     Lead expert  │
│  ▸ Inspection report           5 files            │  👤 Anna Kuipers │
│  ▸ Drawing                     2 files (.dwg)     │     Permits      │
│  ▸ Permit                      2 files            │                  │
│                                                   │  ──────────────  │
│  *Click a group to open or download*              │  External refs   │
│                                                   │  ──────────────  │
├───────────────────────────────────────────────────┤  → ICOMOS        │
│  🖼  Practical examples — before / during / after │  → Heritage      │
│  ──────────────────────────                       │    Watch         │
│  Photo Story · Grid (3 cols)                      │  → Glass         │
│  source: (empty — cross-folder MetaVox search)    │    Restorers     │
│  filter: topic = "stained-glass"                  │    Network       │
│                                                   │                  │
│  ┌───────┐ ┌───────┐ ┌───────┐                   │  ──────────────   │
│  │ church│ │ church│ │ church│  St Martin's       │  Related topics  │
│  │ before│ │ during│ │ after │  · 8 May 2024      │  ──────────────  │
│  └───────┘ └───────┘ └───────┘                   │  → Repointing     │
│  ┌───────┐ ┌───────┐ ┌───────┐                   │  → Leadwork       │
│  │ chapel│ │ chapel│ │ town- │  Various projects  │  → Slate roofing │
│  │ detail│ │ work  │ │ hall  │                   │  → Wrought iron   │
│  └───────┘ └───────┘ └───────┘                   │                   │
└───────────────────────────────────────────────────┴──────────────────┘
```

Photos stay in their project folders (`/Projects/2024-073-St-Martin/photos/`, `/Projects/2023-051-Our-Lady/...`, …). Adding the `topic = "stained-glass"` tag in MetaVox is enough — the gallery picks them up automatically the next time the page is opened.

### Tips

- **One MetaVox tag is all the maintenance** — drop a new document in the shared folder, tag it, and it appears on the right topic page.
- **No folder-per-topic explosion** — the central documents folder stays a flat, browsable archive.
- **Photos are reused for free** — a single project photo can appear on multiple topic pages (e.g. a window-restoration photo on both *stained-glass* and *leadwork*) just by adding a second tag value.
- **`document_type` grouping** turns the file list into a self-explaining index — readers immediately see *"3 specifications, 5 inspection reports"* without filtering.
- **Hub page**: add a News Widget on a parent page filtered by `topic is not empty` to list all knowledge items, giving readers a single browsable entry point.
- **Editorial flow**: combine with [Scenario 1](#scenario-1-content-approval-workflow) if topic pages need a review/approval gate before publication.

---

## Adding More Scenarios

Have a use case that others could benefit from? Scenarios we're considering:

- **Multi-language intranet** — managing content across languages with separate editorial teams
- **External news aggregation** — using RSS feeds to share IntraVox content with external tools
- **Template-based page creation** — standardizing page layouts across departments

Contributions welcome via [GitHub issues](https://github.com/nextcloud/IntraVox/issues).
