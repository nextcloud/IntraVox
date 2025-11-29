/**
 * IntraVox MetaVox Integration Plugin
 * Loads and initializes MetaVox for IntraVox pages
 */

// Get Nextcloud webroot
function getWebRoot() {
    if (typeof OC !== 'undefined' && OC.webroot !== undefined) {
        return OC.webroot;
    }
    return '';
}

// Check if MetaVox is available
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

// Load MetaVox scripts if installed
async function loadMetaVoxScripts() {
    const isInstalled = await checkMetaVoxAvailability();

    if (!isInstalled) {
        return false;
    }

    // Create Files Sidebar mock API BEFORE loading MetaVox scripts
    createFilesSidebarMock();

    const webroot = getWebRoot();

    // Load MetaVox CSS
    const cssLink = document.createElement('link');
    cssLink.rel = 'stylesheet';
    cssLink.href = webroot + '/apps/metavox/css/files.css';
    document.head.appendChild(cssLink);

    // Load MetaVox scripts
    try {
        const scriptsToLoad = [
            webroot + '/apps/metavox/js/files-plugin1.js'
        ];

        for (const scriptSrc of scriptsToLoad) {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = scriptSrc;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        // Setup event listeners for MetaVox integration
        setupMetaVoxEventListeners();

        return true;
    } catch (error) {
        console.error('[IntraVox] Failed to load MetaVox scripts:', error);
        return false;
    }
}

// Create a mock Files Sidebar API for MetaVox to register with
function createFilesSidebarMock() {
    if (!window.OCA) {
        window.OCA = {};
    }
    if (!window.OCA.Files) {
        window.OCA.Files = {};
    }
    if (!window.OCA.Files.Sidebar) {
        window.OCA.Files.Sidebar = {
            _tabs: [],
            registerTab(tab) {
                this._tabs.push(tab);
                window._metaVoxTab = tab;
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

        // Create a file info object that looks like what Files app provides
        const fileInfo = {
            id: metadata?.fileId || pageId,
            name: pageName,
            path: path,
            mimetype: 'application/json',
            size: metadata?.size || 0,
            mtime: metadata?.modified || Date.now(),
            permissions: 31,
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

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadMetaVoxScripts);
} else {
    loadMetaVoxScripts();
}

export { checkMetaVoxAvailability, loadMetaVoxScripts };
