#!/usr/bin/env node
/**
 * Copy NL demo data to EN with new unique IDs
 * This script:
 * 1. First reads all NL pages to collect their uniqueIds
 * 2. Generates new unique IDs for EN version
 * 3. Copies all NL folders/pages to EN with translated folder names
 * 4. Updates uniqueIds and internal references to use new IDs
 * 5. Changes language from 'nl' to 'en'
 * 6. Updates navigation.json for EN
 */

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const nlDir = path.join(__dirname, 'nl');
const enDir = path.join(__dirname, 'en');

// Map old NL unique IDs to new EN unique IDs
const idMap = new Map();

// Folder name translations (Dutch -> English)
const folderTranslations = {
    'afdeling': 'departments',
    'afdelingen': 'departments',
    'documentatie': 'documentation',
    'functies': 'features',
    'handleidingen': 'guides',
    'installatie': 'installation',
    'klanten': 'customers',
    'nieuws': 'news',
    'evenementen': 'events',
    'updates': 'updates',
    'over-intravox': 'about',
    'prijzen': 'pricing',
    'contact': 'contact',
    'nextcloud': 'nextcloud',
    'images': 'images',
    'marketing': 'marketing',
    'sales': 'sales',
    'hr': 'hr',
    'it': 'it',
    'campagnes': 'campaigns',
    'branding': 'branding',
    'social-media': 'social-media',
    'crm': 'crm',
    'pipeline': 'pipeline',
    'targets': 'targets',
    'vacatures': 'jobs',
    'onboarding': 'onboarding',
    'policies': 'policies',
    'helpdesk': 'helpdesk',
    'systemen': 'systems',
    'security': 'security',
    'api': 'api',
    'faq': 'faq',
    'tips': 'tips',
    'widgets': 'widgets',
    'layouts': 'layouts',
    'navigatie': 'navigation'
};

// Translate a folder name
function translateFolderName(name) {
    return folderTranslations[name] || name;
}

// Translate a full path
function translatePath(relativePath) {
    const parts = relativePath.split(path.sep);
    const translatedParts = parts.map(part => {
        // Don't translate file names, only folder names
        if (part.endsWith('.json') || part.endsWith('.jpg') || part.endsWith('.png')) {
            return part;
        }
        return translateFolderName(part);
    });
    return translatedParts.join(path.sep);
}

// Generate a new UUID-style unique ID
function generateUniqueId() {
    const uuid = crypto.randomUUID();
    return `page-${uuid}`;
}

// Copy directory recursively with translated folder names
function copyDirTranslated(src, dest) {
    if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
    }

    const entries = fs.readdirSync(src, { withFileTypes: true });

    for (const entry of entries) {
        const srcPath = path.join(src, entry.name);
        const translatedName = entry.isDirectory() ? translateFolderName(entry.name) : entry.name;
        const destPath = path.join(dest, translatedName);

        if (entry.isDirectory()) {
            copyDirTranslated(srcPath, destPath);
        } else {
            fs.copyFileSync(srcPath, destPath);
        }
    }
}

// Find all JSON page files recursively
function findPageFiles(dir, files = []) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });

    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);

        if (entry.isDirectory() && entry.name !== 'images') {
            findPageFiles(fullPath, files);
        } else if (entry.name.endsWith('.json') &&
                   entry.name !== 'navigation.json' &&
                   entry.name !== 'footer.json' &&
                   entry.name !== 'manifest.json') {
            files.push(fullPath);
        }
    }

    return files;
}

// Recursively update values in an object
function updateObject(obj) {
    if (typeof obj !== 'object' || obj === null) {
        return obj;
    }

    if (Array.isArray(obj)) {
        return obj.map(item => updateObject(item));
    }

    const result = {};
    for (const [key, value] of Object.entries(obj)) {
        if (key === 'uniqueId' && typeof value === 'string' && idMap.has(value)) {
            // Replace uniqueId with new mapped ID
            result[key] = idMap.get(value);
        } else if (key === 'url' && typeof value === 'string' && value.startsWith('#page-')) {
            // This is an internal page link - update to new ID
            const oldId = value.substring(1); // Remove # prefix
            const newId = idMap.get(oldId);
            if (newId) {
                result[key] = `#${newId}`;
            } else {
                result[key] = value;
            }
        } else if (key === 'language' && value === 'nl') {
            result[key] = 'en';
        } else {
            result[key] = updateObject(value);
        }
    }
    return result;
}

// Main execution
console.log('🔄 Copying NL demo data to EN with new unique IDs...\n');

// Step 1: Read all NL page files to collect uniqueIds and build the mapping
console.log('🔑 Step 1: Reading NL pages and generating ID mapping...');
const nlPageFiles = findPageFiles(nlDir);

// First add home.json
const nlHomeContent = fs.readFileSync(path.join(nlDir, 'home.json'), 'utf8');
try {
    const homeData = JSON.parse(nlHomeContent);
    if (homeData.uniqueId) {
        const oldId = homeData.uniqueId;
        const newId = generateUniqueId();
        idMap.set(oldId, newId);
        console.log(`   home.json: ${oldId.substring(0, 35)}... → ${newId.substring(0, 35)}...`);
    }
} catch (err) {
    console.error('   Error reading home.json:', err.message);
}

// Process all other NL page files
for (const file of nlPageFiles) {
    const relativePath = path.relative(nlDir, file);
    try {
        const content = fs.readFileSync(file, 'utf8');
        const data = JSON.parse(content);
        if (data.uniqueId) {
            const oldId = data.uniqueId;
            const newId = generateUniqueId();
            idMap.set(oldId, newId);
            console.log(`   ${relativePath}: ${oldId.substring(0, 30)}... → ${newId.substring(0, 30)}...`);
        }
    } catch (err) {
        console.error(`   Error reading ${relativePath}:`, err.message);
    }
}

console.log(`\n   Total IDs mapped: ${idMap.size}`);

// Step 2: Clear existing EN folder content
console.log('\n📁 Step 2: Preparing EN folder...');
const existingEnDirs = fs.readdirSync(enDir, { withFileTypes: true });
for (const entry of existingEnDirs) {
    const fullPath = path.join(enDir, entry.name);
    if (entry.isDirectory()) {
        fs.rmSync(fullPath, { recursive: true, force: true });
    } else if (entry.name.endsWith('.json')) {
        fs.unlinkSync(fullPath);
    }
}

// Step 3: Copy NL structure to EN with translated folder names
console.log('\n📋 Step 3: Copying NL structure to EN with translated folder names...');
const nlEntries = fs.readdirSync(nlDir, { withFileTypes: true });
for (const entry of nlEntries) {
    const srcPath = path.join(nlDir, entry.name);
    const translatedName = entry.isDirectory() ? translateFolderName(entry.name) : entry.name;
    const destPath = path.join(enDir, translatedName);

    if (entry.isDirectory()) {
        copyDirTranslated(srcPath, destPath);
        if (entry.name !== translatedName) {
            console.log(`   ✓ Copied folder: ${entry.name}/ → ${translatedName}/`);
        } else {
            console.log(`   ✓ Copied folder: ${entry.name}/`);
        }
    } else if (entry.name.endsWith('.json')) {
        fs.copyFileSync(srcPath, destPath);
        console.log(`   ✓ Copied: ${entry.name}`);
    }
}

// Step 4: Update all EN page files with new IDs and language
console.log('\n📝 Step 4: Updating pages with new IDs, links, and language...');
const enPageFiles = findPageFiles(enDir);

// Update home.json
const homeJsonPath = path.join(enDir, 'home.json');
try {
    let homeData = JSON.parse(fs.readFileSync(homeJsonPath, 'utf8'));
    homeData = updateObject(homeData);
    fs.writeFileSync(homeJsonPath, JSON.stringify(homeData, null, 4));
    console.log('   ✓ Updated: home.json');
} catch (err) {
    console.error('   Error updating home.json:', err.message);
}

// Update all other page files
for (const file of enPageFiles) {
    const relativePath = path.relative(enDir, file);
    try {
        let data = JSON.parse(fs.readFileSync(file, 'utf8'));
        data = updateObject(data);
        fs.writeFileSync(file, JSON.stringify(data, null, 4));
        console.log(`   ✓ Updated: ${relativePath}`);
    } catch (err) {
        console.error(`   Error updating ${relativePath}:`, err.message);
    }
}

// Step 5: Update navigation.json with new IDs
console.log('\n🧭 Step 5: Updating navigation.json...');
const navPath = path.join(enDir, 'navigation.json');
try {
    let navData = JSON.parse(fs.readFileSync(navPath, 'utf8'));
    navData = updateObject(navData);
    fs.writeFileSync(navPath, JSON.stringify(navData, null, 4));
    console.log('   ✓ Navigation updated with new IDs');
} catch (err) {
    console.error('   Error updating navigation.json:', err.message);
}

// Step 6: Remove manifest.json (not used by app)
const manifestPath = path.join(enDir, 'manifest.json');
if (fs.existsSync(manifestPath)) {
    fs.unlinkSync(manifestPath);
}

console.log('\n✅ Done! EN demo data created with new unique IDs.');
console.log(`   Processed ${enPageFiles.length + 1} page files`);
console.log(`   Mapped ${idMap.size} unique IDs`);
console.log(`   Folder names translated to English`);
console.log(`   Navigation updated with correct EN page references`);
