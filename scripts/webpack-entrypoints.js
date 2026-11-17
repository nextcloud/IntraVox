// Nextcloud loads scripts via PHP rather than webpack's HTML plugin.
// Emit the exact ordered entry assets so chunk splitting cannot break the shell.
class NextcloudEntrypointsPlugin {
    apply(compiler) {
        compiler.hooks.beforeRun.tap('NextcloudEntrypoints', () => {
            const fs = require('fs');
            const path = require('path');
            const css = path.resolve(compiler.context, 'css');
            for (const file of fs.readdirSync(css)) {
                if (file !== 'intravox-main.css' && /^intravox-[\w.-]+\.css(?:\.map)?$/.test(file)) {
                    fs.unlinkSync(path.join(css, file));
                }
            }
        });
        compiler.hooks.thisCompilation.tap('NextcloudEntrypoints', (compilation) => {
            compilation.hooks.processAssets.tap({
                name: 'NextcloudEntrypoints',
                stage: compiler.webpack.Compilation.PROCESS_ASSETS_STAGE_SUMMARIZE,
            }, () => {
                const entries = {};
                for (const [name, entry] of compilation.entrypoints) {
                    entries[name] = entry.getFiles()
                        .filter(file => /\.(js|css)$/.test(file))
                        .map(file => file.startsWith('../css/') ? file.slice(3) : `js/${file}`);
                }
                compilation.emitAsset('entrypoints.json',
                    new compiler.webpack.sources.RawSource(JSON.stringify(entries, null, 2) + '\n'));
            });
        });
        compiler.hooks.afterEmit.tap('RefreshManagedPreview', (compilation) => {
            if (compilation.errors.length > 0) return;
            const fs = require('fs');
            const path = require('path');
            const app = path.resolve(compiler.context, '.runtime/nextcloud/custom_apps/intravox');
            const target = path.join(app, 'js');
            if (!fs.existsSync(path.join(app, '.replit-managed'))) return;
            if (!fs.existsSync(target) || fs.lstatSync(target).isSymbolicLink()) {
                throw new Error('Managed Preview JavaScript directory is missing or is a symlink.');
            }
            const output = compiler.options.output.path;
            if (fs.realpathSync(output) === fs.realpathSync(target)) return;
            const files = Object.keys(compilation.assets).filter(file =>
                path.basename(file) === file && file !== 'entrypoints.json');
            for (const file of [...files, 'entrypoints.json']) {
                // Publish the manifest last, after all of its dependencies exist.
                const temp = path.join(target, `${file}.next`);
                fs.copyFileSync(path.join(output, file), temp);
                fs.renameSync(temp, path.join(target, file));
            }
            console.log('Refreshed JavaScript and entrypoint manifest for the managed Preview.');
        });
    }
}
module.exports = NextcloudEntrypointsPlugin;
