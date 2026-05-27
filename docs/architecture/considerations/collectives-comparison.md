# IntraVox vs Nextcloud Collectives: Comparison & Integration Analysis

> **Document type**: Architectural consideration
> **Purpose**: Help stakeholders understand the differences, overlap, and potential integration between IntraVox and Nextcloud Collectives

---

## Executive Summary

IntraVox and Nextcloud Collectives are **complementary applications** with distinct purposes:

| Aspect | IntraVox | Collectives |
|--------|----------|-------------|
| **Primary model** | Top-down broadcast (1 → many) | Horizontal collaboration (many ↔ many) |
| **Use case** | Communications team publishes → organization reads | Teams work together on shared knowledge |
| **Content type** | Rich layouts, widgets, visual pages | Markdown wiki-style pages |
| **Permissions** | Hierarchical (admin → editor → user) | Flat (all team members equal) |

**Recommendation**: Keep both apps separate but create integration points for seamless navigation and unified user experience.

---

## Application Overview

### IntraVox

**Purpose**: SharePoint-like intranet pages within Nextcloud

**Core characteristics**:
- Drag-and-drop page editor with rich widgets
- Top-down communication model
- GroupFolder-based storage with ACL permissions
- Engagement features (reactions, comments) for readers
- Multi-language support

**Typical content**:
- Company news and announcements
- HR policies and updates
- Department portals
- Official documentation

**Tech stack**: Vue 3, PHP, JSON files in GroupFolder

### Nextcloud Collectives

**Purpose**: Wiki-like collaboration environment for teams

**Core characteristics**:
- Real-time collaborative editing (Etherpad-style)
- Non-hierarchical, collective ownership
- Team/Circles-based access control
- Markdown-based content
- Full-text search

**Typical content**:
- Project wikis
- Team knowledge bases
- Working group documentation
- Ideas and brainstorms

**Tech stack**: Vue, PHP, custom storage with mountpoints

---

## Key Differences

### 1. Communication Model

| IntraVox | Collectives |
|----------|-------------|
| **Broadcast** | **Collaborative** |
| One team publishes, many consume | Everyone contributes equally |
| Readers react passively | Members edit actively |
| Clear author/audience separation | Collective authorship |

### 2. Ownership Model

| IntraVox | Collectives |
|----------|-------------|
| **Centralized** | **Collective** |
| Content owned by GroupFolder | Content owned by the collective |
| Managed by editors/admins | Managed by all team members |
| Individual accountability | Shared responsibility |

### 3. Permission Philosophy

| IntraVox | Collectives |
|----------|-------------|
| **Hierarchical** | **Flat** |
| Admin → Manager → Editor → User | All members have equal rights |
| Fine-grained ACL rules | Team membership = full access |
| Role-based capabilities | Democratic participation |

### 4. Content Structure

| IntraVox | Collectives |
|----------|-------------|
| **Visual/Rich** | **Document-centric** |
| Drag-drop grid layouts | Linear page structure |
| Widgets: images, videos, links, files | Markdown with embeds |
| Design-focused | Content-focused |

---

## Overlap and Complementarity

### Where They Overlap

- Both are Nextcloud apps for internal content
- Both support page hierarchies/navigation
- Both integrate with Nextcloud authentication
- Both can serve as knowledge repositories

### Where They're Complementary

```
┌─────────────────────────────────────────────────────┐
│                    Nextcloud                         │
├─────────────────────┬───────────────────────────────┤
│     IntraVox        │        Collectives            │
│   (Broadcast)       │      (Collaboration)          │
├─────────────────────┼───────────────────────────────┤
│ • Company news      │ • Project wikis               │
│ • Announcements     │ • Team knowledge bases        │
│ • Policy documents  │ • Working group docs          │
│ • HR updates        │ • Ideas & brainstorms         │
│ • Department pages  │ • Meeting notes               │
├─────────────────────┴───────────────────────────────┤
│          Shared: Nextcloud Groups/Teams             │
│        (Common authorization foundation)            │
└─────────────────────────────────────────────────────┘
```

---

## Authorization Challenges

### IntraVox Authorization Model

**Mechanism**: GroupFolder + ACL (Access Control Lists)

**Two-layer system**:
1. **Base permissions**: Via Nextcloud group membership
2. **Fine-grained ACL**: Override rules per folder/path

**Role hierarchy**:
| Role | Permissions |
|------|-------------|
| Users | Read only |
| Editors | Read, Write, Create |
| Managers | Read, Write, Create, Delete |
| Admins | Full access + settings |

**Database**: Uses `group_folders_acl` table

### Collectives Authorization Model

**Mechanism**: Team/Circles integration

**Characteristics**:
- Each collective is bound to one team (auto-created)
- All team members have equal rights within the collective
- No internal hierarchy
- Collective owns the content, not individuals

**Storage**: Custom mountpoint system to user home directories

### Integration Challenge

The core challenge is that **both systems don't know about each other**:

| Challenge | Description |
|-----------|-------------|
| Conflicting ownership | GroupFolder vs Collective storage |
| Different philosophies | Hierarchical vs flat permissions |
| No shared auth layer | Each app manages access independently |
| User context differs | "Reader" mindset vs "Contributor" mindset |

---

## Integration Options Analysis

### Option A: Add IntraVox to Collectives

**Concept**: Collectives gets an "announcements" widget or page type

**Pros**:
- Collectives already has team infrastructure
- Users are already in collaboration context
- Single app for everything

**Cons**:
- IntraVox rich layouts don't fit Markdown model
- Collectives permissions are too flat for broadcast scenario
- Would require fundamental architecture changes to Collectives

**Verdict**: ❌ Not recommended - requires too many fundamental changes

---

### Option B: Add Collectives to IntraVox

**Concept**: IntraVox gets collaborative wiki-spaces

**Pros**:
- IntraVox already has hierarchical permission system
- Could treat wiki-spaces as sub-folders with own ACL

**Cons**:
- Loses the "collective ownership" concept
- TipTap editor isn't built for real-time collaborative editing
- Fundamentally different user experience

**Verdict**: ❌ Not recommended - loses Collectives core value

---

### Option C: Side-by-Side Integration (Recommended)

**Concept**: Both apps remain independent with connection points

**Pros**:
- Preserves strengths of both apps
- No fundamental architecture changes needed
- Authorization remains clear and manageable
- Users choose based on context

**Cons**:
- Two apps to maintain
- Requires explicit integration work

**Verdict**: ✅ Recommended approach

---

## Recommended Architecture

### Integration Layers

**1. Navigation Integration**
- IntraVox navigation can contain links to Collectives
- Unified sidebar showing both apps
- Breadcrumb trails that cross app boundaries

**2. Cross-App Linking**
- IntraVox pages can link to Collectives
- Collectives can embed IntraVox announcements (read-only)
- Deep-linking support between both apps

**3. Shared Authorization Context**
```
Nextcloud Groups/Teams
├── Communications Team (IntraVox Editors)
├── Marketing Team (Collectives + IntraVox read)
├── Development Team (Collectives + IntraVox read)
└── All Staff (IntraVox read only)
```

**4. Optional Widget Integration**
- "Recent Collectives" widget in IntraVox
- "Company Announcements" widget in Collectives (read-only)

### User Perspective

```
Employee opens Nextcloud:
├── IntraVox (via navigation or app icon)
│   ├── Reads company news, HR updates
│   ├── Reacts with emojis/comments
│   └── Clicks through to team's Collective
│
└── Collectives (via link or app icon)
    ├── Collaborates on team wiki
    └── Sees links to relevant IntraVox articles
```

### Administrator Perspective

```
Admin configures in Nextcloud:
├── Manage Groups (central location)
│   ├── "All employees" → IntraVox read + Collectives read
│   ├── "Communications" → IntraVox edit + own Collective
│   └── "Development" → IntraVox read + own Collective
│
├── IntraVox GroupFolder
│   └── ACL rules linked to above groups
│
└── Collectives
    ├── Teams auto-created per Collective
    └── Can add groups to Teams
```

---

## Benefits by Stakeholder

| Stakeholder | Benefit |
|-------------|---------|
| **End users** | Seamless navigation, context-relevant content, single sign-on |
| **Administrators** | Central group management, clear separation of concerns |
| **Communications team** | Professional publishing tools, engagement metrics |
| **Project teams** | Collaborative editing, collective ownership |
| **IT/Security** | Clear authorization model, audit trails |

---

## Conclusion

IntraVox and Collectives serve **different but complementary needs**:

- **IntraVox** = Communication platform (top-down)
- **Collectives** = Collaboration platform (horizontal)

The recommended approach is **side-by-side integration** where:
- Both apps remain architecturally independent
- Nextcloud Groups/Teams serve as the common authorization foundation
- Cross-app links and optional widgets provide seamless user experience
- Each app excels at its core purpose

This approach provides the best balance of:
- User experience (seamless navigation)
- Administrator control (central group management)
- Technical maintainability (no fundamental rewrites)
- Future flexibility (independent evolution of both apps)

---

## References

- [Nextcloud Collectives GitHub](https://github.com/nextcloud/collectives)
- [Collectives Documentation](https://nextcloud.github.io/collectives/)
- [Collectives Development Guide](https://github.com/nextcloud/collectives/blob/main/DEVELOPING.md)
- [IntraVox Authorization Documentation](../AUTHORIZATION.md)
- [IntraVox Architecture Documentation](../ARCHITECTURE.md)
