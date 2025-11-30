# IntraVox Architectural Decisions

This document records significant architectural decisions made during IntraVox development.

---

## Decision Log

### ADR-001: Language-First Folder Structure
**Date:** November 2025
**Status:** Implemented

**Context:**
We needed to choose between two folder structure options for organizing content:
- **Option A:** Department-First (`/departments/marketing/nl/...`)
- **Option B:** Language-First (`/nl/departments/marketing/...`)

**Decision:**
We chose **Language-First (Option B)** as the standard structure.

**Rationale:**
- Simpler ACL configuration (permissions set per language)
- Better alignment with Nextcloud's groupfolder ACL behavior
- Easier language-specific content management
- More intuitive for multi-language installations

**Consequences:**
- All content is organized under language folders first (e.g., `/nl/`, `/en/`)
- ACL rules are applied at the language folder level
- Import commands must respect language folder separation

**References:**
- See `ARCHITECTURE.md` for implementation details
- Archived comparison: `docs/archived/STRUCTURE_COMPARISON.md`

---

### ADR-002: JSON-Based Page Storage
**Date:** November 2025
**Status:** Implemented

**Context:**
We needed to decide how to store page content and metadata.

**Decision:**
Store pages as `page.json` files in Nextcloud's filesystem (via Groupfolders).

**Rationale:**
- Leverages Nextcloud's existing file storage and versioning
- No separate database required
- Easy backup and migration (files are portable)
- Content is human-readable and editable
- Works with Nextcloud's ACL and sharing system

**Consequences:**
- Page lookup requires filesystem operations
- Need to implement caching for performance
- Must sanitize all input to prevent JSON injection

---

### ADR-003: UniqueId-Based Page Identification
**Date:** November 2025
**Status:** Implemented

**Context:**
Pages need stable identifiers that survive moves and renames.

**Decision:**
Each page has a `uniqueId` (UUID) that never changes, separate from the `id` (folder name).

**Rationale:**
- Page URLs remain stable even if pages are moved
- Navigation references don't break on rename
- Supports future page moving/reorganization features

**Consequences:**
- Two lookup methods needed (by uniqueId and by id)
- UniqueId stored in page.json metadata
- URL routing uses uniqueId for stable links

---

### ADR-004: Widget Sanitization Strategy
**Date:** November 2025
**Status:** Implemented

**Context:**
User-generated content needs sanitization for security, but we must preserve legitimate styling.

**Decision:**
Implement allowlist-based sanitization that:
- Preserves widget IDs and structural properties
- Allows specific CSS variables for theming
- Supports hash links for internal navigation
- Validates image properties (objectFit, objectPosition)

**Rationale:**
- Security: Prevent XSS and injection attacks
- Functionality: Allow legitimate theming and styling
- User experience: Don't strip necessary content

**Consequences:**
- `PageService::sanitizeWidget()` must be updated for new widget properties
- New CSS variables require explicit allowlisting
- Balance between security and flexibility

---

### ADR-005: Breadcrumb Generation
**Date:** November 2025
**Status:** Implemented

**Context:**
Users need to understand their location in the page hierarchy.

**Decision:**
Generate breadcrumbs dynamically from folder path, with caching in page response.

**Rationale:**
- Folder structure is source of truth for hierarchy
- Caching reduces filesystem lookups
- Breadcrumb included in page API response for single request

**Consequences:**
- Breadcrumb rebuilds when pages are moved
- Need fallback for missing parent pages
- Performance optimized through response bundling

---

## Future Decisions (Pending)

### Nested Pages Implementation
**Status:** Proposed (not implemented)

Options under consideration:
- Maximum nesting depth (2-3 levels recommended)
- Parent tracking via folder path vs. JSON metadata
- Page moving functionality

See `NESTED_PAGES.md` for detailed proposal.

---

## Decision Template

```markdown
### ADR-XXX: [Title]
**Date:** [YYYY-MM]
**Status:** [Proposed | Implemented | Deprecated | Superseded]

**Context:**
[What is the issue we're seeing that motivates this decision?]

**Decision:**
[What is the change we're making?]

**Rationale:**
[Why is this the best option?]

**Consequences:**
[What becomes easier or harder?]
```

---

**Last Updated:** 2025-11-30
