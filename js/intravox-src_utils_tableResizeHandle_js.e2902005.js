"use strict";
(self["webpackChunknextcloud_intravox"] = self["webpackChunknextcloud_intravox"] || []).push([["src_utils_tableResizeHandle_js"],{

/***/ "./src/utils/tableResizeHandle.js"
/*!****************************************!*\
  !*** ./src/utils/tableResizeHandle.js ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   TableResizeHandle: () => (/* binding */ TableResizeHandle)
/* harmony export */ });
/* harmony import */ var _tiptap_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @tiptap/core */ "./node_modules/@tiptap/core/dist/index.js");
/* harmony import */ var _tiptap_pm_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @tiptap/pm/state */ "./node_modules/@tiptap/pm/dist/state/index.js");
/* harmony import */ var _tiptap_pm_view__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @tiptap/pm/view */ "./node_modules/@tiptap/pm/dist/view/index.js");
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




const KEY = new _tiptap_pm_state__WEBPACK_IMPORTED_MODULE_1__.PluginKey('tableResizeHandle');
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
  if (!found) return _tiptap_pm_view__WEBPACK_IMPORTED_MODULE_2__.DecorationSet.empty;
  const deco = _tiptap_pm_view__WEBPACK_IMPORTED_MODULE_2__.Decoration.node(found.pos, found.pos + found.node.nodeSize, {
    'data-table-resize-active': 'true',
  });
  return _tiptap_pm_view__WEBPACK_IMPORTED_MODULE_2__.DecorationSet.create(state.doc, [deco]);
}

const TableResizeHandle = _tiptap_core__WEBPACK_IMPORTED_MODULE_0__.Extension.create({
  name: 'tableResizeHandle',

  addProseMirrorPlugins() {
    const editor = this.editor;
    return [
      new _tiptap_pm_state__WEBPACK_IMPORTED_MODULE_1__.Plugin({
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


/***/ }

}]);
//# sourceMappingURL=intravox-src_utils_tableResizeHandle_js.e2902005.js.map