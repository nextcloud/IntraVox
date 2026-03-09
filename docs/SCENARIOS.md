# IntraVox Scenarios

Practical recipes for common use cases, combining IntraVox with other Nextcloud apps. These scenarios require no changes to IntraVox itself — they use existing Nextcloud features and IntraVox's native permission model.

**Related documentation:**
- [Administrator Guide](ADMIN_GUIDE.md) - Installation and configuration
- [Authorization Guide](AUTHORIZATION.md) - Permissions and access control
- [Editor Guide](EDITOR_GUIDE.md) - Content editing and publishing

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

---

## Adding More Scenarios

Have a use case that others could benefit from? Scenarios we're considering:

- **Multi-language intranet** — managing content across languages with separate editorial teams
- **External news aggregation** — using RSS feeds to share IntraVox content with external tools
- **Template-based page creation** — standardizing page layouts across departments

Contributions welcome via [GitHub issues](https://github.com/nextcloud/IntraVox/issues).
