/**
 * IntraVox MetaVox Integration Plugin
 * Sets up communication between IntraVox and MetaVox sidebar tab
 *
 * Supports two MetaVox registration paths:
 * - NC33+: MetaVox registers via window._nc_files_scope.v4_0.filesSidebarTabs (custom element)
 * - NC31-32: MetaVox registers via OCA.Files.Sidebar.registerTab (legacy mount/update API)
 *
 * Note: MetaVox scripts are loaded by the PHP backend via Util::addScript()
 * This file handles the sidebar mock and event communication for MetaVox integration.
 *
 * Versions are handled directly by IntraVox using the internal versions API,
 * which leverages Nextcloud's GroupFolders versioning backend.
 */

// Get Nextcloud webroot
function getWebRoot() {
    if (typeof OC !== 'undefined' && OC.webroot !== undefined) {
        return OC.webroot;
    }
    return '';
}

// Check if MetaVox is available (for API checks)
async function checkMetaVoxAvailability() {
    try {
        const url = getWebRoot() + '/apps/intravox/api/metavox/status';
        const response = await fetch(url);
        const data = await response.json();
        return data.installed === true;
    } catch (error) {
        return false;
    }
}

// Create a mock Files Sidebar API for MetaVox to register with (NC31-32 legacy path)
// Only used on IntraVox pages where the real Files sidebar doesn't exist
function createFilesSidebarMock() {
    if (!window.OCA) {
        window.OCA = {};
    }
    if (!window.OCA.Files) {
        window.OCA.Files = {};
    }

    // If real sidebar exists (Files app), find MetaVox tab from there
    if (window.OCA.Files.Sidebar && window.OCA.Files.Sidebar._tabs) {
        const metavoxTab = window.OCA.Files.Sidebar._tabs.find(
            tab => tab.id === 'metavox-metadata' || tab.id === 'metavox'
        );
        if (metavoxTab) {
            window._metaVoxTab = metavoxTab;
        }
        return; // Don't create mock, use existing sidebar
    }

    // Create mock sidebar only if it doesn't exist (IntraVox pages)
    if (!window.OCA.Files.Sidebar) {
        // Mock Tab class that MetaVox uses to create tabs
        class MockTab {
            constructor(options) {
                this.id = options.id;
                this.name = options.name;
                this.iconSvg = options.iconSvg;
                this.mount = options.mount;
                this.update = options.update;
                this.destroy = options.destroy;
                this.enabled = options.enabled;
            }
        }

        window.OCA.Files.Sidebar = {
            _tabs: [],
            Tab: MockTab,
            registerTab(tab) {
                this._tabs.push(tab);
                // Store MetaVox tab for IntraVox to use
                if (tab.id === 'metavox-metadata' || tab.id === 'metavox') {
                    window._metaVoxTab = tab;
                    window._metaVoxTabType = 'legacy';
                }
            },
            open(path) {},
            close() {},
            setActiveTab(tabId) {}
        };
    }
}

/**
 * Check if MetaVox registered via NC33+ scoped globals API.
 * On NC33, MetaVox writes to window._nc_files_scope.v4_0.filesSidebarTabs
 * instead of using OCA.Files.Sidebar.registerTab. We need to detect this
 * and set up _metaVoxTab accordingly.
 */
function checkNC33ScopedGlobals() {
    const scope = window._nc_files_scope?.v4_0;
    if (!scope?.filesSidebarTabs) {
        return false;
    }

    const metavoxEntry = scope.filesSidebarTabs.get('metavox');
    if (!metavoxEntry) {
        return false;
    }

    // Store a reference so we know to use the NC33 custom element path
    window._metaVoxTab = metavoxEntry;
    window._metaVoxTabType = 'nc33';
    return true;
}

// Get groupfolder name from groupfolder ID
async function getGroupfolderName(groupfolderId) {
    try {
        const url = getWebRoot() + '/apps/metavox/api/groupfolders';
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            }
        });

        if (response.ok) {
            const groupfolders = await response.json();
            const gf = groupfolders.find(g => g.id === parseInt(groupfolderId));
            if (gf) {
                return gf.mount_point;
            }
        }
    } catch (error) {
        // Silently fallback
    }
    return 'IntraVox';
}

/**
 * Build a fileInfo object from IntraVox page metadata.
 * This object mimics what the Nextcloud Files app provides to sidebar tabs.
 */
async function buildFileInfo(pageId, pageName, metadata) {
    const fullPath = metadata?.path || `/IntraVox/${pageName}`;
    const groupfolderMatch = fullPath.match(/\/__groupfolders\/(\d+)/);
    const groupfolderId = groupfolderMatch ? groupfolderMatch[1] : null;

    const groupfolderName = groupfolderId ? await getGroupfolderName(groupfolderId) : 'IntraVox';

    // MetaVox expects path in format /GroupfolderName/... not /__groupfolders/X/...
    let path = fullPath;
    if (fullPath.startsWith('/__groupfolders/')) {
        const pathAfterGroupfolder = fullPath.replace(/\/__groupfolders\/\d+\//, '');
        path = `/${groupfolderName}/${pathAfterGroupfolder}`;
    }

    const rawPermissions = metadata?.permissions?.raw ?? 1;

    return {
        id: metadata?.fileId || pageId,
        name: pageName,
        path: path,
        mimetype: 'application/json',
        size: metadata?.size || 0,
        mtime: metadata?.modified || Date.now(),
        permissions: rawPermissions,
        shareTypes: [],
        mountType: 'group',
        type: 'file',
        isMountRoot: false,
        mountPoint: groupfolderName
    };
}

/**
 * Mount MetaVox using the legacy API (NC31-32).
 * Calls the tab's mount() function directly with a fileInfo object.
 */
async function mountLegacy(container, fileInfo) {
    if (typeof window._metaVoxTab.mount === 'function') {
        container.innerHTML = '';
        await window._metaVoxTab.mount(container, fileInfo, {
            rootPath: '/IntraVox',
            isActive: true
        });
    }
}

/**
 * Mount MetaVox using the NC33+ custom element API.
 * Creates a <metavox-sidebar-tab> element and sets its properties,
 * mimicking how NC33's sidebar renders registered tabs.
 */
async function mountNC33(container, fileInfo) {
    const tabDef = window._metaVoxTab;

    // Ensure the custom element is initialized (calls onInit which defines the element)
    if (tabDef.onInit && !customElements.get(tabDef.tagName || 'metavox-sidebar-tab')) {
        await tabDef.onInit();
    }

    const tagName = tabDef.tagName || 'metavox-sidebar-tab';

    container.innerHTML = '';
    const el = document.createElement(tagName);

    // NC33 MetaVox expects a Node object with specific properties
    // Create a mock Node that matches what @nextcloud/files provides
    const mockNode = {
        fileid: fileInfo.id,
        basename: fileInfo.name,
        path: fileInfo.path,
        mime: fileInfo.mimetype,
        size: fileInfo.size,
        mtime: fileInfo.mtime,
        permissions: fileInfo.permissions,
        type: fileInfo.type,
        attributes: {
            mountType: fileInfo.mountType,
            mountPoint: fileInfo.mountPoint,
            'is-mount-root': false,
        }
    };

    // Set properties on the custom element (NC33 uses property setters, not attributes)
    el.node = mockNode;
    el.active = true;

    container.appendChild(el);
}

// Setup event listeners for IntraVox-MetaVox communication
function setupMetaVoxEventListeners() {
    window.addEventListener('intravox:metavox:update', async (event) => {
        if (!event.detail.container) {
            return;
        }

        // If _metaVoxTab isn't set yet, try to find it from NC33 scoped globals
        if (!window._metaVoxTab) {
            checkNC33ScopedGlobals();
        }

        if (!window._metaVoxTab) {
            return;
        }

        const { pageId, pageName, container, metadata } = event.detail;

        try {
            const fileInfo = await buildFileInfo(pageId, pageName, metadata);

            if (window._metaVoxTabType === 'nc33') {
                await mountNC33(container, fileInfo);
            } else {
                await mountLegacy(container, fileInfo);
            }
        } catch (error) {
            console.error('[IntraVox] Error mounting MetaVox:', error);
        }
    });
}

// Initialize MetaVox integration
// MetaVox scripts are loaded by PHP backend, we just need to setup the mock and listeners
function initMetaVoxIntegration() {
    // Create the sidebar mock so MetaVox can register its tab (NC31-32 legacy path)
    createFilesSidebarMock();

    // Also check if MetaVox already registered via NC33 scoped globals
    checkNC33ScopedGlobals();

    // If MetaVox hasn't registered yet (script loading order), poll for it
    if (!window._metaVoxTab) {
        let attempts = 0;
        const maxAttempts = 30; // 3 seconds max
        const pollInterval = setInterval(() => {
            attempts++;
            // Check both registration paths
            if (window._metaVoxTab || checkNC33ScopedGlobals() || attempts >= maxAttempts) {
                clearInterval(pollInterval);
            }
        }, 100);
    }

    // Setup event listeners for MetaVox communication
    setupMetaVoxEventListeners();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMetaVoxIntegration);
} else {
    initMetaVoxIntegration();
}

export { checkMetaVoxAvailability, initMetaVoxIntegration };
