const { parse } = require('acorn');

/**
 * Nextcloud dialogs embeds every translation in one unsplittable JS literal.
 * Expose groups as real webpack modules so they can be shared between reader
 * and admin chunks. Preserve every locale and synchronous registration order.
 */
module.exports = function dialogLocaleChunks(source) {
    const program = parse(source, { ecmaVersion: 'latest', sourceType: 'module' });
    const loop = program.body.find(node => node.type === 'ForOfStatement'
        && node.right.type === 'ArrayExpression' && node.right.end - node.right.start > 200000);
    if (!loop) throw new Error('Nextcloud dialogs locale layout changed; review the locale chunk loader.');
    const groups = [];
    let group = [], bytes = 0;
    for (const element of loop.right.elements) {
        if (element?.type !== 'ObjectExpression'
            || !element.properties.some(property => property.key?.name === 'language' || property.key?.value === 'language')) {
            throw new Error('Unexpected Nextcloud dialogs locale entry.');
        }
        const text = source.slice(element.start, element.end);
        if (bytes + Buffer.byteLength(text) > 80000 && group.length) {
            groups.push(group);
            group = []; bytes = 0;
        }
        group.push(text);
        bytes += Buffer.byteLength(text);
    }
    if (group.length) groups.push(group);
    if (this.resourceQuery) {
        const match = this.resourceQuery.match(/^\?aglDialogLocales=(\d+)$/);
        if (!match || !groups[Number(match[1])]) throw new Error('Invalid dialog locale chunk.');
        return `export default [${groups[Number(match[1])].join(',')}];`;
    }
    const imports = groups.map((_, index) =>
        `import aglLocales${index} from ${JSON.stringify(`${this.resourcePath}?aglDialogLocales=${index}`)};`).join('\n');
    const array = `[${groups.map((_, index) => `...aglLocales${index}`).join(',')}]`;
    return `${imports}\n${source.slice(0, loop.right.start)}${array}${source.slice(loop.right.end)}`;
};
