/**
 * TipTap / ProseMirror plugin: free-form table width drag handle.
 *
 * Adds an 8px-wide handle on the right edge of the active table. Clicking
 * and dragging it sets `width` (in pixels) on the table node, complementing
 * the discrete preset buttons in the toolbar dropdown.
 *
 * Pixel values are stored on the table; on read-mode the table renders at
 * exactly that width inside the widget container (capped to 100% by CSS so
 * a 800px-wide drag still fits in a 600px-wide column).
 *
 * Built as a standalone Extension so InlineTextEditor.vue stays compact and
 * the resize logic is unit-testable in isolation.
 */
import { Extension } from '@tiptap/core';
import { Plugin, PluginKey } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';

const KEY = new PluginKey('tableResizeHandle');
const HANDLE_WIDTH = 8;

function findActiveTable(state) {
  const { $from } = state.selection;
  for (let depth = $from.depth; depth > 0; depth--) {
    const node = $from.node(depth);
    if (node.type.name === 'table') {
      return { node, pos: $from.before(depth) };
    }
  }
  return null;
}

function buildDecorations(state) {
  const found = findActiveTable(state);
  if (!found) return DecorationSet.empty;
  const deco = Decoration.node(found.pos, found.pos + found.node.nodeSize, {
    'data-table-resize-active': 'true',
  });
  return DecorationSet.create(state.doc, [deco]);
}

export const TableResizeHandle = Extension.create({
  name: 'tableResizeHandle',

  addProseMirrorPlugins() {
    const editor = this.editor;
    return [
      new Plugin({
        key: KEY,
        state: {
          init: (_, state) => buildDecorations(state),
          apply: (tr, old, _oldState, newState) =>
            tr.docChanged || tr.selectionSet ? buildDecorations(newState) : old,
        },
        props: {
          decorations(state) {
            return this.getState(state);
          },
          handleDOMEvents: {
            mousedown(view, event) {
              const tableEl = event.target instanceof Element
                ? event.target.closest('table[data-table-resize-active="true"]')
                : null;
              if (!tableEl) return false;

              const rect = tableEl.getBoundingClientRect();
              // Only react when the click lands within HANDLE_WIDTH px of the right edge
              if (event.clientX < rect.right - HANDLE_WIDTH || event.clientX > rect.right + HANDLE_WIDTH) {
                return false;
              }

              event.preventDefault();
              const startX = event.clientX;
              const startWidth = rect.width;
              // Container width caps the drag — table never wider than its widget column.
              const container = tableEl.closest('.editor-content') || tableEl.parentElement;
              const maxWidth = container ? container.getBoundingClientRect().width : 2000;
              const minWidth = 80;

              const onMove = (e) => {
                const next = Math.max(minWidth, Math.min(maxWidth, startWidth + (e.clientX - startX)));
                tableEl.style.width = `${Math.round(next)}px`;
                tableEl.style.minWidth = '0';
              };
              const onUp = (e) => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                const finalPx = Math.max(minWidth, Math.min(maxWidth, startWidth + (e.clientX - startX)));
                editor.chain().focus().updateAttributes('table', { width: `${Math.round(finalPx)}px` }).run();
              };
              document.addEventListener('mousemove', onMove);
              document.addEventListener('mouseup', onUp);
              return true;
            },
            mousemove(view, event) {
              // Cursor feedback: show ew-resize when hovering near the right edge of the active table.
              const tableEl = event.target instanceof Element
                ? event.target.closest('table[data-table-resize-active="true"]')
                : null;
              if (!tableEl) return false;
              const rect = tableEl.getBoundingClientRect();
              const onEdge = event.clientX >= rect.right - HANDLE_WIDTH && event.clientX <= rect.right + HANDLE_WIDTH;
              tableEl.style.cursor = onEdge ? 'ew-resize' : '';
              return false;
            },
          },
        },
      }),
    ];
  },
});
