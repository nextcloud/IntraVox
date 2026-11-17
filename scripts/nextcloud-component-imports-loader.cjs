/**
 * Compile named component imports to Nextcloud's documented per-component
 * entrypoints. Its root barrel has stylesheet side effects for every exported
 * component, so ordinary tree-shaking still bundles unused editors/pickers.
 * Keep the source convention consistent while limiting the production graph.
 */
module.exports = function nextcloudComponentImports(source) {
    return source.replace(
        /import\s*\{([^}]+)\}\s*from\s*(['"])@nextcloud\/vue\2\s*;?/g,
        (statement, names) => {
            const imports = names.split(',').map(name => name.trim()).filter(Boolean);
            if (!imports.every(name => /^Nc[A-Z]\w*(?:\s+as\s+\w+)?$/.test(name))) {
                throw new Error('Unsupported @nextcloud/vue import; use a documented component entrypoint.');
            }
            return imports.map(name => {
                const [exported, local = exported] = name.split(/\s+as\s+/);
                return `import ${local} from '@nextcloud/vue/components/${exported}';`;
            }).join('\n');
        },
    );
};
