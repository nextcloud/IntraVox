# IntraVox Authorization Model

IntraVox uses Nextcloud's native GroupFolder permissions for authorization. This means that access control is managed entirely through Nextcloud's existing permission system - no separate permission configuration is needed in IntraVox.

## Overview

```
+------------------------------------------------------------------+
|                       Nextcloud Server                            |
|  +--------------------------------------------------------------+ |
|  |                     GroupFolders App                         | |
|  |  +----------------------------------------------------------+ |
|  |  |                 IntraVox GroupFolder                     | |
|  |  |  +--------------------+  +--------------------+          | |
|  |  |  | Group Permissions  |  |    ACL Rules       |          | |
|  |  |  | (Base Access)      |  |  (Fine-grained)    |          | |
|  |  |  +--------------------+  +--------------------+          | |
|  |  +----------------------------------------------------------+ |
|  +--------------------------------------------------------------+ |
|                              |                                    |
|                              v                                    |
|  +--------------------------------------------------------------+ |
|  |                      IntraVox App                            | |
|  |        PermissionService reads permissions                   | |
|  |        and enforces them on all operations                   | |
|  +--------------------------------------------------------------+ |
+------------------------------------------------------------------+
```

## Permission Types

IntraVox respects the standard Nextcloud permission bits:

| Permission | Bit | Description |
|------------|-----|-------------|
| Read | 1 | View pages and content |
| Update | 2 | Edit existing pages |
| Create | 4 | Create new pages |
| Delete | 8 | Delete pages |
| Share | 16 | Required for RSS feed access (public endpoints require Read + Share) |

## How Permissions Work

### 1. Base Permissions (GroupFolder Groups)

When a group is added to the IntraVox GroupFolder, all members of that group receive the configured base permissions. This is the first layer of access control.

Example:
- Group "Employees" has Read permission on IntraVox folder
- Group "Editors" has Read + Write + Create permission
- Group "Admins" has All permissions

### 2. ACL Rules (Fine-grained Control)

If the GroupFolders ACL feature is enabled, administrators can set more specific permissions on subfolders. ACL rules override base permissions for specific paths.

Example:
- `/en/departments/hr` - HR group has full access, others read-only
- `/en/departments/sales` - Sales group has full access, others read-only

### 3. Permission Inheritance

Permissions are inherited from parent to child folders:
- A child folder cannot have more permissions than its parent
- ACL rules on parent folders affect all children
- More specific rules (deeper paths) override less specific rules

## Setting Up Permissions

### Step 1: Create GroupFolder

1. Go to Nextcloud Admin → GroupFolders
2. Create a folder named "IntraVox"
3. Add groups that should have access

### Step 2: Configure Base Permissions

For each group, set the appropriate permission level:

| Role | Recommended Permissions |
|------|------------------------|
| All Employees | Read, Share |
| Content Editors | Read, Write, Create, Share |
| Department Managers | Read, Write, Create, Delete, Share |
| Administrators | All |

> **Why Share?** The RSS feed is a public endpoint (no user session). GroupFolders requires both Read and Share permissions for folders to be visible in public requests. Without Share, user feeds will be empty.

### Step 3: Enable ACL (Optional)

For fine-grained control:

1. Enable "Advanced Permissions" on the GroupFolder
2. Navigate to subfolders in Nextcloud Files
3. Click the share icon and configure ACL rules

### Example: Department-based Access

```
IntraVox/
├── en/
│   ├── departments/
│   │   ├── hr/          → HR group: full access, Others: read
│   │   ├── sales/       → Sales group: full access, Others: read
│   │   ├── marketing/   → Marketing group: full access, Others: read
│   │   └── it/          → IT group: full access, Others: read
│   └── news/            → Editors group: full access, Others: read
└── nl/
    └── (same structure)
```

## Permission Checks in IntraVox

IntraVox checks permissions at multiple levels:

### API Level
Every API call validates permissions before executing:
- `GET /api/page` - Requires Read permission
- `PUT /api/page` - Requires Write permission
- `POST /api/page` - Requires Create permission
- `DELETE /api/page` - Requires Delete permission

### UI Level
The frontend adapts based on permissions:
- Edit buttons only shown if user has Write permission
- Create page options only shown if user has Create permission
- Delete options only shown if user has Delete permission

### Navigation
Navigation items are filtered based on page permissions - users only see pages they can access.

## Troubleshooting

### User cannot see a page
1. Check if user is member of a group with access to the IntraVox GroupFolder
2. Check ACL rules on the specific folder path
3. Verify the page file exists in the expected location

### User cannot edit a page
1. Verify the user's group has Write permission on the GroupFolder
2. Check if ACL rules restrict Write access on that path
3. Check parent folder permissions (child cannot exceed parent)

### User's RSS feed is empty
1. Check that the user's group has **Share** permission on the GroupFolder (base level)
2. If using ACL: verify Share permission on the language folder (`en/`, `nl/`, etc.) and all parent folders
3. Verify that "Allow users to share via link and emails" is enabled in Nextcloud Admin → Sharing
4. See [RSS_FEED.md](RSS_FEED.md#administrator-setup) for the full setup guide

### Navigation shows pages user cannot access
This should not happen if permissions are configured correctly. Check:
1. Navigation file permissions vs page file permissions
2. Cache issues - try clearing Nextcloud cache

## Technical Implementation

The `PermissionService` class handles all permission logic:

```php
// Check if user can read a path
$canRead = $permissionService->canRead('en/departments/hr');

// Check if user can write to a path
$canWrite = $permissionService->canWrite('en/departments/hr');

// Get full permissions object for API response
$permissions = $permissionService->getPermissionsObject('en/departments/hr');
// Returns: { canRead: true, canWrite: false, canCreate: false, ... }
```

The service:
1. Gets base permissions from GroupFolder group membership
2. Queries ACL rules from the database
3. Applies rules from least specific to most specific path
4. Returns the effective permission bitmask

## Best Practices

1. **Use Groups** - Always assign permissions to groups, not individual users
2. **Principle of Least Privilege** - Start with read-only and add permissions as needed
3. **Document Your Structure** - Keep a record of which groups have access to what
4. **Test Thoroughly** - After setting up permissions, test with users from each group
5. **Regular Audits** - Periodically review group memberships and ACL rules
6. **RSS Feed requires Share** - The RSS feed is a public endpoint. GroupFolders requires both Read and Share permissions for folders to be visible in public (unauthenticated) requests. If users report empty feeds, check that their group has Share permission on the relevant folders.
