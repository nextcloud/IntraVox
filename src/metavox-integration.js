/**
 * IntraVox MetaVox Integration Plugin
 * Sets up communication between IntraVox and MetaVox for pages
 *
 * Note: MetaVox scripts are loaded by the PHP backend via Util::addScript()
 * This file only handles the sidebar mock and event communication.
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

// Create a mock Files Sidebar API for MetaVox to register with
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
                }
            },
            open(path) {},
            close() {},
            setActiveTab(tabId) {}
        };
    }
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

// Setup event listeners for IntraVox-MetaVox communication
function setupMetaVoxEventListeners() {
    window.addEventListener('intravox:metavox:update', async (event) => {
        if (!event.detail.container || !window._metaVoxTab) {
            return;
        }

        const { pageId, pageName, container, metadata } = event.detail;

        // Extract groupfolder info from path (format: /__groupfolders/X/files/...)
        const fullPath = metadata?.path || `/IntraVox/${pageName}`;
        const groupfolderMatch = fullPath.match(/\/__groupfolders\/(\d+)/);
        const groupfolderId = groupfolderMatch ? groupfolderMatch[1] : null;

        // Get the actual groupfolder name from MetaVox API
        const groupfolderName = groupfolderId ? await getGroupfolderName(groupfolderId) : 'IntraVox';

        // MetaVox expects path in format /GroupfolderName/... not /__groupfolders/X/...
        let path = fullPath;
        if (fullPath.startsWith('/__groupfolders/')) {
            // Extract the part after /__groupfolders/X/ and prepend the groupfolder name
            const pathAfterGroupfolder = fullPath.replace(/\/__groupfolders\/\d+\//, '');
            path = `/${groupfolderName}/${pathAfterGroupfolder}`;
        }

        // Get permissions from metadata (uses Nextcloud's native permissions)
        // Default to read-only (1) if not available
        const rawPermissions = metadata?.permissions?.raw ?? 1;

        // Create a file info object that looks like what Files app provides
        const fileInfo = {
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

        try {
            if (window._metaVoxTab && typeof window._metaVoxTab.mount === 'function') {
                container.innerHTML = '';
                await window._metaVoxTab.mount(container, fileInfo, {
                    rootPath: '/IntraVox',
                    isActive: true
                });
            }
        } catch (error) {
            console.error('[IntraVox] Error mounting MetaVox:', error);
        }
    });
}

// Initialize MetaVox integration
// Scripts are loaded by PHP backend, we just need to setup the mock and listeners
function initMetaVoxIntegration() {
    // Create the sidebar mock so MetaVox can register its tab
    createFilesSidebarMock();

    // Setup event listeners for communication
    setupMetaVoxEventListeners();
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMetaVoxIntegration);
} else {
    initMetaVoxIntegration();
}

export { checkMetaVoxAvailability, initMetaVoxIntegration };
