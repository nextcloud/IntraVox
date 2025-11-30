#!/usr/bin/env node
/**
 * Sync version between package.json and appinfo/info.xml
 *
 * Usage:
 *   node scripts/sync-version.js           # Show current versions
 *   node scripts/sync-version.js 0.6.0     # Set version to 0.6.0 in both files
 *   node scripts/sync-version.js --check   # Check if versions are in sync (for CI/prebuild)
 *   npm run version:sync -- 0.6.0          # Same via npm
 */

const fs = require('fs');
const path = require('path');

const ROOT_DIR = path.resolve(__dirname, '..');
const PACKAGE_JSON = path.join(ROOT_DIR, 'package.json');
const INFO_XML = path.join(ROOT_DIR, 'appinfo', 'info.xml');

// Read current versions
function getPackageVersion() {
    const pkg = JSON.parse(fs.readFileSync(PACKAGE_JSON, 'utf8'));
    return pkg.version;
}

function getInfoXmlVersion() {
    const xml = fs.readFileSync(INFO_XML, 'utf8');
    const match = xml.match(/<version>([^<]+)<\/version>/);
    return match ? match[1] : null;
}

// Update versions
function setPackageVersion(version) {
    const pkg = JSON.parse(fs.readFileSync(PACKAGE_JSON, 'utf8'));
    pkg.version = version;
    fs.writeFileSync(PACKAGE_JSON, JSON.stringify(pkg, null, 2) + '\n');
}

function setInfoXmlVersion(version) {
    let xml = fs.readFileSync(INFO_XML, 'utf8');
    xml = xml.replace(/<version>[^<]+<\/version>/, `<version>${version}</version>`);
    fs.writeFileSync(INFO_XML, xml);
}

// Main
const arg = process.argv[2];
const pkgVersion = getPackageVersion();
const xmlVersion = getInfoXmlVersion();

// Check mode: verify versions are in sync (for prebuild/CI)
if (arg === '--check') {
    if (pkgVersion !== xmlVersion) {
        console.error('❌ Version mismatch detected!');
        console.error(`   package.json:     ${pkgVersion}`);
        console.error(`   appinfo/info.xml: ${xmlVersion}`);
        console.error('\nRun "npm run version:sync -- <version>" to fix this.');
        process.exit(1);
    }
    console.log(`✅ Versions in sync: ${pkgVersion}`);
    process.exit(0);
}

console.log('IntraVox Version Sync');
console.log('=====================\n');
console.log('Current versions:');
console.log(`  package.json:     ${pkgVersion}`);
console.log(`  appinfo/info.xml: ${xmlVersion}`);

if (pkgVersion !== xmlVersion) {
    console.log('\n⚠️  Versions are out of sync!');
}

if (arg && arg !== '--check') {
    const newVersion = arg;

    // Validate semver format
    if (!/^\d+\.\d+\.\d+$/.test(newVersion)) {
        console.error('\n❌ Invalid version format. Use semantic versioning (e.g., 0.6.0)');
        process.exit(1);
    }

    console.log(`\nUpdating both files to version ${newVersion}...`);
    setPackageVersion(newVersion);
    setInfoXmlVersion(newVersion);
    console.log('✅ Done!\n');

    console.log('Updated versions:');
    console.log(`  package.json:     ${getPackageVersion()}`);
    console.log(`  appinfo/info.xml: ${getInfoXmlVersion()}`);
} else if (!arg) {
    console.log('\nUsage:');
    console.log('  npm run version:sync -- <version>   Set version in both files');
    console.log('  npm run version:sync -- --check     Check if versions match');
    console.log('\nExample: npm run version:sync -- 0.6.0');
}
