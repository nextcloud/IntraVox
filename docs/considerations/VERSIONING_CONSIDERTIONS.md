# Version Control Architecture for IntraVox

## Executive Summary

IntraVox, a Nextcloud app for building collaborative intranet pages, currently stores page data as JSON files within GroupFolders. This architecture leverages existing Nextcloud infrastructure for permissions (ACL), quota management, and team collaboration. However, **file versioning does not work reliably** in GroupFolders, which is a critical gap for enterprise wiki/intranet use cases.

This document compares three architectural options for addressing version control in IntraVox and provides a recommendation that benefits the broader Nextcloud ecosystem.

---

## Problem Statement

### Current Architecture

```
IntraVox/                          # GroupFolder
├── en/
│   ├── home.json                  # Page content (JSON)
│   ├── navigation.json
│   └── department/
│       ├── department.json
│       └── _media/
└── nl/
    └── ...
```

IntraVox relies on GroupFolders for:
- **Access Control**: ACL-based permissions per folder/page
- **Quota Management**: Centralized storage limits
- **Team Integration**: Assignment to groups/teams
- **File Sync**: Desktop/mobile client compatibility

### The Versioning Gap

GroupFolders has **known, long-standing issues** with file versioning:

| Issue | Status | Impact |
|-------|--------|--------|
| [#1901](https://github.com/nextcloud/groupfolders/issues/1901) - Versioning broken | Open since Feb 2022 | Timestamps incorrect, versions not created reliably |
| [#2077](https://github.com/nextcloud/groupfolders/issues/2077) - Rollback breaks history | Open | Restored versions remain in history incorrectly |
| [#2718](https://github.com/nextcloud/groupfolders/issues/2718) - No versioning at all | Open since Jan 2024 | Some installations report zero versions being created |
| [#3095](https://github.com/nextcloud/groupfolders/issues/3095) - Named versions lost | Open since Aug 2024 | Named versions disappear after time, potential data loss |

**Root cause**: GroupFolders implements custom storage and mount providers that bypass or conflict with the standard `files_versions` app behavior. The versioning backend (`TrashBackend`, `VersionsBackend`) requires careful synchronization with Nextcloud core that has proven fragile across releases.

### Why This Matters

For wiki/intranet applications like IntraVox, version history is not optional:
- **Audit compliance**: Organizations need to track who changed what and when
- **Accidental changes**: Users must be able to revert to previous versions
- **Collaborative editing**: Multiple editors need confidence that work won't be lost
- **Enterprise adoption**: Version control is table stakes for knowledge management

---

## Option Analysis

### Option 1: Build Custom Versioning in IntraVox

Implement version control at the application layer, independent of Nextcloud's `files_versions`.

#### Implementation Approach

**1a. Embedded in JSON files:**
```json
{
  "currentVersion": 5,
  "lastModified": "2025-01-15T10:30:00Z",
  "lastModifiedBy": "user123",
  "content": { /* current page layout */ },
  "history": [
    {
      "version": 4,
      "timestamp": "2025-01-14T15:20:00Z",
      "author": "user456",
      "snapshot": { /* full page content */ }
    }
  ]
}
```

**1b. Separate version files:**
```
department/
├── department.json           # Current version
└── _versions/
    ├── department.v4.json    # Previous versions
    ├── department.v3.json
    └── department.v2.json
```

#### Evaluation

| Criterion | Assessment |
|-----------|------------|
| **Development effort** | Medium (2-4 weeks) |
| **Maintenance burden** | High - must maintain indefinitely |
| **Reliability** | High - full control over implementation |
| **Storage efficiency** | Low - full snapshots per version |
| **Ecosystem alignment** | Poor - diverges from Nextcloud patterns |
| **Reusability** | None - benefits only IntraVox |

#### Risks
- Increases technical debt for IntraVox maintainers
- JSON files grow large with embedded history
- No integration with Nextcloud's version UI in Files app
- Must implement own retention/expiration policies
- Diverges from how other Nextcloud apps handle versioning

---

### Option 2: Wait for GroupFolders Fix

Continue with current architecture and wait for GroupFolders versioning to be fixed upstream.

#### Current State of GroupFolders Versioning

Nextcloud has acknowledged versioning issues and made incremental improvements:
- NC 27: "Advanced versioning with Groupfolders" mentioned in release notes
- Ongoing maintenance releases address specific bugs
- Active issue tracking on GitHub

However:
- Core architectural issues remain unresolved
- No published roadmap or timeline for comprehensive fix
- Issues span multiple years without resolution
- Each Nextcloud major version introduces regressions

#### Evaluation

| Criterion | Assessment |
|-----------|------------|
| **Development effort** | None (waiting) |
| **Maintenance burden** | None |
| **Reliability** | Unknown - depends on upstream |
| **Timeline** | Uncertain - could be months or years |
| **Ecosystem alignment** | Perfect - uses standard infrastructure |
| **Reusability** | High - benefits all GroupFolders users |

#### Risks
- IntraVox cannot ship reliable version control until fix lands
- No influence over timeline or prioritization
- May wait indefinitely for a fix that never comes
- Enterprise customers may reject IntraVox due to missing feature

---

### Option 3: Migrate to Collectives-Style Storage

Adopt the storage architecture used by Nextcloud Collectives: custom app storage with own mountpoints.

#### Collectives Architecture

```
data/appdata_<instance>/collectives/
├── <collective_id>/
│   ├── Readme.md
│   ├── Page.md
│   └── .attachments/
```

Key characteristics:
- Files stored in `appdata`, not user storage
- Custom mountpoint provider exposes files to users
- Own implementation of versions and trash
- Content owned by "collective", not individual users

#### Migration Path for IntraVox

1. Create `CollectiveStorage` class implementing `IStorage`
2. Implement `MountProvider` to expose pages to users
3. Port GroupFolders ACL logic or implement own permission system
4. Implement `VersionsBackend` for version control
5. Build migration tool for existing GroupFolders installations
6. Update all file operations to use new storage layer

#### Evaluation

| Criterion | Assessment |
|-----------|------------|
| **Development effort** | Very High (2-3 months) |
| **Maintenance burden** | Very High - own storage layer |
| **Reliability** | High - full control |
| **Breaking change** | Yes - requires migration |
| **Ecosystem alignment** | Moderate - follows Collectives pattern |
| **Reusability** | Low - IntraVox-specific |

#### Risks
- Massive development investment
- Breaking change for existing users
- Loss of GroupFolders ACL granularity (must reimplement)
- Loss of GroupFolders quota integration
- Two Nextcloud apps (Collectives, IntraVox) now maintain similar custom storage code
- Desktop/mobile sync behavior may differ

---

## Comparison Matrix

| Criterion | Option 1: Custom in IntraVox | Option 2: Wait for GF Fix | Option 3: Collectives-Style |
|-----------|------------------------------|---------------------------|----------------------------|
| Development effort | Medium | None | Very High |
| Time to deliver | 2-4 weeks | Unknown | 2-3 months |
| Maintenance burden | High | None | Very High |
| Reliability | High | Unknown | High |
| Breaking change | No | No | Yes |
| Benefits ecosystem | No | Yes | No |
| Preserves ACL | Yes | Yes | Must reimplement |
| Preserves quota | Yes | Yes | Must reimplement |
| Files app integration | No | Yes | Partial |
| Desktop sync | Yes | Yes | Custom behavior |

---

## Recommendation

### The Case for Fixing GroupFolders

We recommend **prioritizing a fix for GroupFolders versioning** (Option 2) as the strategic choice, with a **time-boxed fallback** to Option 1 if progress stalls.

#### Why GroupFolders is the Right Place to Fix This

1. **Ecosystem impact**: GroupFolders is used by thousands of Nextcloud installations. Fixing versioning benefits:
   - IntraVox
   - Collectives (could potentially migrate to GroupFolders)
   - All enterprise customers using GroupFolders for shared documents
   - ONLYOFFICE and Collabora users editing in GroupFolders

2. **Avoiding fragmentation**: If every app builds custom versioning, we end up with:
   - Multiple incompatible implementations
   - Inconsistent user experience
   - Duplicated maintenance burden across the ecosystem

3. **Architecture alignment**: GroupFolders already has the infrastructure:
   - `VersionsBackend` class exists
   - `TrashBackend` class exists
   - Integration points with `files_versions` are defined
   - The issue is bugs, not missing architecture

4. **Enterprise requirement**: Version control in shared folders is a baseline expectation for enterprise file management. This is not a niche feature request.

#### Proposed Path Forward

```
Phase 1: Immediate (0-2 months)
├── Document IntraVox version control requirements
├── Engage with GroupFolders maintainers
├── Contribute to issue triage and testing
└── Evaluate contributing development resources

Phase 2: Decision Point (2-3 months)
├── If GroupFolders fix is progressing → Continue waiting
└── If no progress → Implement Option 1 (JSON-based versioning)

Phase 3: Long-term
├── If Option 1 was implemented → Deprecate when GF is fixed
└── Advocate for standardized versioning API in Nextcloud
```

#### What We Need from Nextcloud/GroupFolders Team

1. **Acknowledgment** that GroupFolders versioning is broken for real-world use cases
2. **Prioritization** of versioning fixes in the GroupFolders roadmap
3. **Timeline** or milestone for when versioning will be reliable
4. **Collaboration** opportunity - IntraVox team willing to contribute testing, documentation, or development resources

---

## Appendix A: Technical Details

### GroupFolders Version Storage

Versions in GroupFolders are stored at:
```
data/__groupfolders/versions/<folder_id>/<relative_path>/<filename>.v<timestamp>
```

The `VersionsBackend` class (`lib/Versions/VersionsBackend.php`) handles:
- Creating versions on file modification
- Listing available versions
- Restoring previous versions
- Expiring old versions per retention policy


### Known Code Paths with Issues

1. **Version creation timing**: Versions may be created with current timestamp instead of previous modification time
2. **Mount point resolution**: Version queries may fail to resolve the correct storage for GroupFolder files
3. **Expiration logic**: Named versions may be incorrectly included in automatic expiration
4. **Cross-user scenarios**: Versions created by user A may not be visible to user B

### Collectives Implementation Reference

Collectives solves this with:
- `lib/Mount/CollectiveFolderManager.php` - Custom folder management
- `lib/Mount/MountProvider.php` - Exposes folders to users
- `lib/Versions/VersionsBackend.php` - Own versioning implementation

---

## Appendix B: Related Issues and Discussions

- [nextcloud/groupfolders#1901](https://github.com/nextcloud/groupfolders/issues/1901) - File versioning broken
- [nextcloud/groupfolders#2077](https://github.com/nextcloud/groupfolders/issues/2077) - Rollback breaks history
- [nextcloud/groupfolders#2718](https://github.com/nextcloud/groupfolders/issues/2718) - No versioning for files
- [nextcloud/groupfolders#3095](https://github.com/nextcloud/groupfolders/issues/3095) - Named versions lost
- [nextcloud/collectives#52](https://github.com/nextcloud/collectives/issues/52) - Collectives own mountpoint decision
- [ONLYOFFICE/onlyoffice-nextcloud#695](https://github.com/ONLYOFFICE/onlyoffice-nextcloud/issues/695) - Version history in GroupFolders

---

## Appendix C: Testing Results (January 2026)

### Test Environment

| Component | Version |
|-----------|---------|
| Nextcloud | 32.0.3 |
| GroupFolders | 20.1.7 |
| files_versions | 1.25.0 |
| Test Server | 3dev (145.38.188.218) |

### Issue Status Update

All major versioning issues have been **CLOSED** as of January 2026:

| Issue | Status | Resolution |
|-------|--------|------------|
| [#1901](https://github.com/nextcloud/groupfolders/issues/1901) | CLOSED | Version ordering fixed (PR #2047). Timestamp issue tracked separately in #2055. |
| [#2077](https://github.com/nextcloud/groupfolders/issues/2077) | CLOSED | Rollback behavior fixed (PR #2543) |
| [#2718](https://github.com/nextcloud/groupfolders/issues/2718) | CLOSED | Versioning restored via app updates |
| [#3095](https://github.com/nextcloud/groupfolders/issues/3095) | CLOSED | Labeled versions protected from expiration (PR #3213). Note: edge cases may remain for auto-expiration. |

### Test Results

**Versioning is now functional** in GroupFolders 20.1.7 with Nextcloud 32.

Verification performed on 2026-01-13:

1. **Version Creation**: Versions are automatically created when files are modified
2. **Database Storage**: Versions are stored in `oc_group_folders_versions` table (separate from regular `oc_files_versions`)
3. **File Storage**: Version files are stored in `/data/__groupfolders/<folder_id>/versions/<file_id>/<timestamp>`
4. **Author Tracking**: Metadata includes author information: `{"author":"admin"}`
5. **Multiple Versions**: Multiple versions accumulate correctly over time

### Recommendation Update

Based on these findings, **Option 2 (Wait for GroupFolders Fix)** is now viable:

- GroupFolders versioning works reliably in version 20.1.7
- IntraVox can leverage native Nextcloud versioning without custom implementation
- Recommend minimum GroupFolders version: **20.1.7** (for Nextcloud 32) or equivalent stable branch for other Nextcloud versions

### Next Steps

1. Update IntraVox minimum requirements to specify GroupFolders 20.x+
2. Test version UI integration (viewing versions in Files app sidebar)
3. Test rollback functionality via UI
4. Monitor for any regressions in future GroupFolders releases

---

## Document Information

- **Author**: IntraVox Development Team
- **Date**: December 2025 (Updated: January 2026)
- **Status**: Verified - GroupFolders versioning confirmed working
- **Target Audience**: Nextcloud architects, GroupFolders maintainers, IntraVox contributors

---

*This document is intended to start a conversation about the best path forward for version control in GroupFolders-based applications. We welcome feedback and collaboration from the Nextcloud community.*