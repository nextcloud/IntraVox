<template>
  <div class="inline-text-editor" :class="{ 'is-focused': isFocused }">
    <!-- Floating Toolbar -->
    <div v-if="editor && isFocused && showToolbar" class="text-menubar" :class="{ 'text-menubar--compact': compact }">
      <!-- Text Formatting - Always visible -->
      <button
        type="button"
        @mousedown.prevent="editor.chain().focus().toggleBold().run()"
        :class="{ 'is-active': editor.isActive('bold') }"
        :title="t('Bold (Ctrl+B)')"
        :aria-label="t('Bold (Ctrl+B)')"
        class="menubar-button"
      >
        <FormatBold :size="18" />
      </button>
      <button
        type="button"
        @mousedown.prevent="editor.chain().focus().toggleItalic().run()"
        :class="{ 'is-active': editor.isActive('italic') }"
        :title="t('Italic (Ctrl+I)')"
        :aria-label="t('Italic (Ctrl+I)')"
        class="menubar-button"
      >
        <FormatItalic :size="18" />
      </button>
      <button
        type="button"
        @mousedown.prevent="editor.chain().focus().toggleUnderline().run()"
        :class="{ 'is-active': editor.isActive('underline') }"
        :title="t('Underline (Ctrl+U)')"
        :aria-label="t('Underline (Ctrl+U)')"
        class="menubar-button"
      >
        <FormatUnderline :size="18" />
      </button>

      <!-- COMPACT MODE: "More" dropdown with all other options -->
      <div v-if="compact" class="more-dropdown">
        <button
          ref="moreButton"
          type="button"
          @mousedown.prevent="toggleMoreMenu"
          :title="t('More options')"
          :aria-label="t('More options')"
          class="menubar-button"
        >
          <DotsHorizontal :size="18" />
        </button>
        <div v-if="showMoreMenu" class="dropdown-menu more-menu">
          <!-- Strikethrough -->
          <button
            type="button"
            @mousedown.prevent="editor.chain().focus().toggleStrike().run(); showMoreMenu = false"
            :class="{ 'is-active': editor.isActive('strike') }"
            class="dropdown-menu-item"
          >
            <FormatStrikethrough :size="16" />
            {{ t('Strikethrough') }}
          </button>
          <div class="dropdown-divider"></div>
          <!-- Headings -->
          <button
            v-for="level in [0, 1, 2, 3, 4]"
            :key="level"
            type="button"
            @mousedown.prevent="setHeadingLevel(level); showMoreMenu = false"
            :class="{ 'is-active': isHeadingLevel(level) }"
            class="dropdown-menu-item"
          >
            {{ getHeadingLabel(level) }}
          </button>
          <div class="dropdown-divider"></div>
          <!-- Lists & Blockquote -->
          <button
            type="button"
            @mousedown.prevent="editor.chain().focus().toggleBulletList().run(); showMoreMenu = false"
            :class="{ 'is-active': editor.isActive('bulletList') }"
            class="dropdown-menu-item"
          >
            <FormatListBulleted :size="16" />
            {{ t('Bullet list') }}
          </button>
          <button
            type="button"
            @mousedown.prevent="editor.chain().focus().toggleOrderedList().run(); showMoreMenu = false"
            :class="{ 'is-active': editor.isActive('orderedList') }"
            class="dropdown-menu-item"
          >
            <FormatListNumbered :size="16" />
            {{ t('Numbered list') }}
          </button>
          <button
            type="button"
            @mousedown.prevent="editor.chain().focus().toggleBlockquote().run(); showMoreMenu = false"
            :class="{ 'is-active': editor.isActive('blockquote') }"
            class="dropdown-menu-item"
          >
            <FormatQuoteClose :size="16" />
            {{ t('Blockquote') }}
          </button>
          <div class="dropdown-divider"></div>
          <!-- Text Alignment -->
          <button
            type="button"
            @mousedown.prevent="setAlignment('left'); showMoreMenu = false"
            :class="{ 'is-active': isAlignmentActive('left') }"
            class="dropdown-menu-item"
          >
            <FormatAlignLeft :size="16" />
            {{ t('Align left') }}
          </button>
          <button
            type="button"
            @mousedown.prevent="setAlignment('center'); showMoreMenu = false"
            :class="{ 'is-active': isAlignmentActive('center') }"
            class="dropdown-menu-item"
          >
            <FormatAlignCenter :size="16" />
            {{ t('Align center') }}
          </button>
          <button
            type="button"
            @mousedown.prevent="setAlignment('right'); showMoreMenu = false"
            :class="{ 'is-active': isAlignmentActive('right') }"
            class="dropdown-menu-item"
          >
            <FormatAlignRight :size="16" />
            {{ t('Align right') }}
          </button>
          <div class="dropdown-divider"></div>
          <!-- Link -->
          <button
            type="button"
            @mousedown.prevent="showLinkModalHandler(); showMoreMenu = false"
            :class="{ 'is-active': editor.isActive('link') }"
            class="dropdown-menu-item"
          >
            <LinkVariant :size="16" />
            {{ t('Link') }}
          </button>
          <!-- Table -->
          <button
            type="button"
            @mousedown.prevent="insertTable(); showMoreMenu = false"
            class="dropdown-menu-item"
          >
            <TableIcon :size="16" />
            {{ t('Insert table') }}
          </button>
          <!-- Table editing options when in table -->
          <template v-if="editor.isActive('table')">
            <div class="dropdown-divider"></div>
            <button
              type="button"
              @mousedown.prevent="addRowAfter(); showMoreMenu = false"
              class="dropdown-menu-item"
            >
              <TableRowPlusAfter :size="16" />
              {{ t('Add row') }}
            </button>
            <button
              type="button"
              @mousedown.prevent="addColumnAfter(); showMoreMenu = false"
              class="dropdown-menu-item"
            >
              <TableColumnPlusAfter :size="16" />
              {{ t('Add column') }}
            </button>
            <button
              type="button"
              @mousedown.prevent="deleteTable(); showMoreMenu = false"
              class="dropdown-menu-item dropdown-menu-item--danger"
            >
              <TableRemove :size="16" />
              {{ t('Delete table') }}
            </button>
          </template>
        </div>
      </div>

      <!-- FULL MODE: All buttons visible -->
      <!-- Group 1: Inline formatting | Group 2: Block structure | Group 3: Alignment | Group 4: Insert -->
      <template v-else>
        <button
          type="button"
          @mousedown.prevent="editor.chain().focus().toggleStrike().run()"
          :class="{ 'is-active': editor.isActive('strike') }"
          :title="t('Strikethrough')"
          :aria-label="t('Strikethrough')"
          class="menubar-button"
        >
          <FormatStrikethrough :size="18" />
        </button>

        <span class="menubar-divider"></span>

        <!-- Group 2: Block structure -->
        <!-- Heading Dropdown -->
        <div class="heading-dropdown">
          <button
            type="button"
            @mousedown.prevent="toggleHeadingMenu"
            class="menubar-button heading-button"
            :title="t('Heading')"
            :aria-label="t('Heading')"
          >
            {{ getCurrentHeadingLabel() }}
            <ChevronDown :size="14" />
          </button>
          <div v-if="showHeadingMenu" class="dropdown-menu">
            <button
              v-for="level in [0, 1, 2, 3, 4]"
              :key="level"
              type="button"
              @mousedown.prevent="setHeadingLevel(level)"
              :class="{ 'is-active': isHeadingLevel(level) }"
              class="dropdown-menu-item"
            >
              {{ getHeadingLabel(level) }}
            </button>
          </div>
        </div>

        <!-- Lists -->
        <button
          type="button"
          @mousedown.prevent="editor.chain().focus().toggleBulletList().run()"
          :class="{ 'is-active': editor.isActive('bulletList') }"
          :title="t('Bullet list')"
          :aria-label="t('Bullet list')"
          class="menubar-button"
        >
          <FormatListBulleted :size="18" />
        </button>
        <button
          type="button"
          @mousedown.prevent="editor.chain().focus().toggleOrderedList().run()"
          :class="{ 'is-active': editor.isActive('orderedList') }"
          :title="t('Numbered list')"
          :aria-label="t('Numbered list')"
          class="menubar-button"
        >
          <FormatListNumbered :size="18" />
        </button>

        <!-- Blockquote -->
        <button
          type="button"
          @mousedown.prevent="editor.chain().focus().toggleBlockquote().run()"
          :class="{ 'is-active': editor.isActive('blockquote') }"
          :title="t('Blockquote')"
          :aria-label="t('Blockquote')"
          class="menubar-button"
        >
          <FormatQuoteClose :size="18" />
        </button>

        <span class="menubar-divider"></span>

        <!-- Group 3: Alignment Dropdown -->
        <div class="alignment-dropdown">
          <button
            type="button"
            @mousedown.prevent="toggleAlignmentMenu"
            class="menubar-button alignment-button"
            :title="t('Text alignment')"
            :aria-label="t('Text alignment')"
          >
            <component :is="currentAlignmentIcon" :size="18" />
            <ChevronDown :size="14" />
          </button>
          <div v-if="showAlignmentMenu" class="dropdown-menu">
            <button
              v-for="align in ['left', 'center', 'right']"
              :key="align"
              type="button"
              @mousedown.prevent="setAlignment(align)"
              :class="{ 'is-active': isAlignmentActive(align) }"
              class="dropdown-menu-item"
            >
              <component :is="alignmentIcons[align]" :size="16" />
              {{ getAlignmentLabel(align) }}
            </button>
          </div>
        </div>

        <span class="menubar-divider"></span>

        <!-- Group 4: Insert actions -->
        <!-- Link -->
        <button
          type="button"
          @mousedown.prevent="showLinkModalHandler"
          :class="{ 'is-active': editor.isActive('link') }"
          :title="t('Insert link')"
          :aria-label="t('Insert link')"
          class="menubar-button"
        >
          <LinkVariant :size="18" />
        </button>

        <!-- Table Dropdown -->
        <div class="table-dropdown">
          <button
            type="button"
            @mousedown.prevent="toggleTableMenu"
            :class="{ 'is-active': editor.isActive('table') }"
            :title="t('Table')"
            :aria-label="t('Table')"
            class="menubar-button"
          >
            <TableIcon :size="18" />
            <ChevronDown :size="14" />
          </button>
          <div v-if="showTableMenu" class="dropdown-menu table-menu">
            <button
              type="button"
              @mousedown.prevent="insertTable"
              class="dropdown-menu-item"
            >
              <TableIcon :size="16" />
              {{ t('Insert table') }}
            </button>
            <template v-if="editor.isActive('table')">
              <div class="dropdown-divider"></div>
              <button
                type="button"
                @mousedown.prevent="addRowBefore"
                class="dropdown-menu-item"
              >
                <TableRowPlusBefore :size="16" />
                {{ t('Add row above') }}
              </button>
              <button
                type="button"
                @mousedown.prevent="addRowAfter"
                class="dropdown-menu-item"
              >
                <TableRowPlusAfter :size="16" />
                {{ t('Add row below') }}
              </button>
              <button
                type="button"
                @mousedown.prevent="addColumnBefore"
                class="dropdown-menu-item"
              >
                <TableColumnPlusBefore :size="16" />
                {{ t('Add column left') }}
              </button>
              <button
                type="button"
                @mousedown.prevent="addColumnAfter"
                class="dropdown-menu-item"
              >
                <TableColumnPlusAfter :size="16" />
                {{ t('Add column right') }}
              </button>
              <div class="dropdown-divider"></div>
              <div class="dropdown-menu-section-label">{{ t('Width') }}</div>
              <div class="dropdown-menu-row">
                <button
                  v-for="w in tableWidthPresets"
                  :key="w"
                  type="button"
                  @mousedown.prevent="setTableWidth(w)"
                  :class="{ 'is-active': isTableWidth(w) }"
                  class="dropdown-menu-pill"
                >
                  {{ w === null ? t('Auto') : w }}
                </button>
              </div>
              <div class="dropdown-menu-section-label">{{ t('Alignment') }}</div>
              <div class="dropdown-menu-row">
                <button
                  type="button"
                  @mousedown.prevent="setTableAlign('left')"
                  :class="{ 'is-active': isTableAlign('left') }"
                  :title="t('Align left')"
                  class="dropdown-menu-pill"
                >
                  <FormatAlignLeft :size="16" />
                </button>
                <button
                  type="button"
                  @mousedown.prevent="setTableAlign('center')"
                  :class="{ 'is-active': isTableAlign('center') }"
                  :title="t('Align center')"
                  class="dropdown-menu-pill"
                >
                  <FormatAlignCenter :size="16" />
                </button>
                <button
                  type="button"
                  @mousedown.prevent="setTableAlign('right')"
                  :class="{ 'is-active': isTableAlign('right') }"
                  :title="t('Align right')"
                  class="dropdown-menu-pill"
                >
                  <FormatAlignRight :size="16" />
                </button>
              </div>
              <div class="dropdown-divider"></div>
              <button
                type="button"
                @mousedown.prevent="deleteRow"
                class="dropdown-menu-item dropdown-menu-item--danger"
              >
                <TableRowRemove :size="16" />
                {{ t('Delete row') }}
              </button>
              <button
                type="button"
                @mousedown.prevent="deleteColumn"
                class="dropdown-menu-item dropdown-menu-item--danger"
              >
                <TableColumnRemove :size="16" />
                {{ t('Delete column') }}
              </button>
              <button
                type="button"
                @mousedown.prevent="deleteTable"
                class="dropdown-menu-item dropdown-menu-item--danger"
              >
                <TableRemove :size="16" />
                {{ t('Delete table') }}
              </button>
            </template>
          </div>
        </div>
      </template>
    </div>

    <!-- Editor Content -->
    <component :is="editorContentComponent" v-if="editorContentComponent && editor" :editor="editor" class="editor-content" />

    <!-- Link Modal -->
    <NcModal v-if="showLinkModal" @close="closeLinkModal" :name="t('Insert link')" size="normal">
      <div class="link-modal-content">
        <div class="form-group">
          <label for="link-text">{{ t('Text:') }}</label>
          <input
            id="link-text"
            v-model="linkText"
            type="text"
            :placeholder="t('Link text')"
            class="link-input"
            @keyup.enter="applyLink"
          />
        </div>
        <div class="form-group">
          <label for="link-url">{{ t('URL:') }}</label>
          <input
            id="link-url"
            v-model="linkUrl"
            type="url"
            :placeholder="t('https://example.com')"
            class="link-input"
            @keyup.enter="applyLink"
          />
        </div>
        <div class="modal-buttons">
          <NcButton @click="closeLinkModal" type="secondary">
            {{ t('Cancel') }}
          </NcButton>
          <NcButton @click="removeLink" v-if="editor && editor.isActive('link')" type="error">
            {{ t('Remove link') }}
          </NcButton>
          <NcButton @click="applyLink" type="primary">
            {{ t('Apply') }}
          </NcButton>
        </div>
      </div>
    </NcModal>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { NcModal, NcButton } from '@nextcloud/vue';
import { markdownToHtml, htmlToMarkdown, cleanMarkdown } from '../utils/markdownSerializer.js';

// TipTap and extensions are loaded lazily on first editor mount to reduce initial bundle size (~500KB)
let _tiptapModules = null;
async function loadTipTap() {
    if (_tiptapModules) return _tiptapModules;
    const [vue3, starterKit, underline, link, placeholder, table, tableRow, tableHeader, tableCell, textAlign, dummyText, tableResize] = await Promise.all([
        import('@tiptap/vue-3'),
        import('@tiptap/starter-kit'),
        import('@tiptap/extension-underline'),
        import('@tiptap/extension-link'),
        import('@tiptap/extension-placeholder'),
        import('@tiptap/extension-table'),
        import('@tiptap/extension-table-row'),
        import('@tiptap/extension-table-header'),
        import('@tiptap/extension-table-cell'),
        import('../utils/textAlignExtension.js'),
        import('../utils/dummyTextGenerator.js'),
        import('../utils/tableResizeHandle.js'),
    ]);
    _tiptapModules = {
        Editor: vue3.Editor,
        EditorContent: vue3.EditorContent,
        StarterKit: starterKit.default,
        Underline: underline.default,
        Link: link.default,
        Placeholder: placeholder.default,
        Table: table.Table,
        TableRow: tableRow.TableRow,
        TableHeader: tableHeader.TableHeader,
        TableCell: tableCell.TableCell,
        TextAlign: textAlign.TextAlign,
        DummyTextExtension: dummyText.DummyTextExtension,
        TableResizeHandle: tableResize.TableResizeHandle,
    };
    return _tiptapModules;
}

// Material Design Icons
import FormatBold from 'vue-material-design-icons/FormatBold.vue';
import FormatItalic from 'vue-material-design-icons/FormatItalic.vue';
import FormatUnderline from 'vue-material-design-icons/FormatUnderline.vue';
import FormatStrikethrough from 'vue-material-design-icons/FormatStrikethrough.vue';
import FormatListBulleted from 'vue-material-design-icons/FormatListBulleted.vue';
import FormatListNumbered from 'vue-material-design-icons/FormatListNumbered.vue';
import LinkVariant from 'vue-material-design-icons/LinkVariant.vue';
import TableIcon from 'vue-material-design-icons/Table.vue';
import TableRowPlusAfter from 'vue-material-design-icons/TableRowPlusAfter.vue';
import TableRowPlusBefore from 'vue-material-design-icons/TableRowPlusBefore.vue';
import TableColumnPlusAfter from 'vue-material-design-icons/TableColumnPlusAfter.vue';
import TableColumnPlusBefore from 'vue-material-design-icons/TableColumnPlusBefore.vue';
import TableRowRemove from 'vue-material-design-icons/TableRowRemove.vue';
import TableColumnRemove from 'vue-material-design-icons/TableColumnRemove.vue';
import TableRemove from 'vue-material-design-icons/TableRemove.vue';
import FormatAlignLeft from 'vue-material-design-icons/FormatAlignLeft.vue';
import FormatAlignCenter from 'vue-material-design-icons/FormatAlignCenter.vue';
import FormatAlignRight from 'vue-material-design-icons/FormatAlignRight.vue';
import FormatQuoteClose from 'vue-material-design-icons/FormatQuoteClose.vue';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue';

export default {
  name: 'InlineTextEditor',
  components: {
    NcModal,
    NcButton,
    FormatBold,
    FormatItalic,
    FormatUnderline,
    FormatStrikethrough,
    FormatListBulleted,
    FormatListNumbered,
    LinkVariant,
    TableIcon,
    TableRowPlusAfter,
    TableRowPlusBefore,
    TableColumnPlusAfter,
    TableColumnPlusBefore,
    TableRowRemove,
    TableColumnRemove,
    TableRemove,
    FormatAlignLeft,
    FormatAlignCenter,
    FormatAlignRight,
    FormatQuoteClose,
    ChevronDown,
    DotsHorizontal
  },
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    editable: {
      type: Boolean,
      default: true
    },
    placeholder: {
      type: String,
      default: ''
    },
    compact: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue', 'focus', 'blur'],
  data() {
    return {
      editor: null,
      editorContentComponent: null,
      isFocused: false,
      showToolbar: true,
      showLinkModal: false,
      linkUrl: '',
      linkText: '',
      showHeadingMenu: false,
      showTableMenu: false,
      // Width presets shown in the table dropdown — null means "auto"
      // (default: fill the widget container).
      tableWidthPresets: [null, '25%', '50%', '75%', '100%'],
      showAlignmentMenu: false,
      showMoreMenu: false,
      alignmentIcons: {
        left: FormatAlignLeft,
        center: FormatAlignCenter,
        right: FormatAlignRight,
      }
    };
  },
  async mounted() {
    // Lazy-load TipTap (~500KB) only when editor is actually mounted
    const modules = await loadTipTap();
    this.editorContentComponent = modules.EditorContent;

    // Convert Markdown to HTML for TipTap editor
    const htmlContent = markdownToHtml(this.modelValue);

    this.editor = new modules.Editor({
      content: htmlContent,
      editable: this.editable,
      extensions: [
        modules.StarterKit.configure({
          // Disable built-in extensions we configure separately
          link: false,
          underline: false,
        }),
        modules.Underline,
        modules.Link.configure({
          openOnClick: false,
          HTMLAttributes: {
            target: '_blank',
            rel: 'noopener noreferrer'
          }
        }),
        modules.Placeholder.configure({
          placeholder: this.placeholder || this.t('Enter text…')
        }),
        // Custom Table node — adds `width` and `align` attributes so editors
        // can make a table narrower than the widget container and align it
        // (left, center, right). The base Table.renderHTML composes the colgroup
        // and a default `style` for column-resize; if we hand it a `style`
        // here it short-circuits and uses ours, which is what we want for
        // explicit width/align. We deliberately keep the parent renderHTML so
        // the colgroup keeps working — only the attribute-level renderHTMLs
        // contribute to the merged style.
        modules.Table.extend({
          addAttributes() {
            const parent = this.parent?.() || {};
            return {
              ...parent,
              width: {
                default: null,
                parseHTML: (el) => {
                  const w = (el.style && el.style.width) || el.getAttribute('width') || null;
                  return w && w !== '100%' ? w : null;
                },
                renderHTML: (attrs) => (attrs.width ? { style: `width: ${attrs.width}` } : {}),
              },
              align: {
                default: null,
                parseHTML: (el) => {
                  if (!el.style) return null;
                  const ml = el.style.marginLeft;
                  const mr = el.style.marginRight;
                  if (ml === 'auto' && mr === 'auto') return 'center';
                  if (ml === 'auto' && mr !== 'auto') return 'right';
                  if (mr === 'auto' && ml !== 'auto') return 'left';
                  return null;
                },
                renderHTML: (attrs) => {
                  if (attrs.align === 'center') return { style: 'margin-left: auto; margin-right: auto' };
                  if (attrs.align === 'right') return { style: 'margin-left: auto; margin-right: 0' };
                  if (attrs.align === 'left') return { style: 'margin-left: 0; margin-right: auto' };
                  return {};
                },
              },
            };
          },
        }).configure({
          resizable: true,
          handleWidth: 4,
          // 80px is wide enough for ~3-4 short words plus padding; below
          // that the cell becomes effectively unusable for prose content.
          cellMinWidth: 80,
          lastColumnResizable: true,
        }),
        modules.TableRow,
        modules.TableHeader,
        modules.TableCell,
        modules.TableResizeHandle,
        modules.TextAlign.configure({
          types: ['heading', 'paragraph'],
          alignments: ['left', 'center', 'right'],
          defaultAlignment: 'left',
        }),
        modules.DummyTextExtension,
      ],
      onUpdate: () => {
        // Convert HTML back to Markdown for storage
        const html = this.editor.getHTML();
        const markdown = cleanMarkdown(htmlToMarkdown(html));
        this.$emit('update:modelValue', markdown);
      },
      onFocus: () => {
        this.isFocused = true;
        this.$emit('focus');

        // Prevent automatic select-all: place cursor at end instead
        // Use setTimeout to let the browser finish its default behavior first
        setTimeout(() => {
          if (!this.editor) return;
          const { state } = this.editor;
          const docSize = state.doc.content.size;
          // If entire content is selected (selectAll behavior), deselect and place cursor at end
          if (state.selection.from <= 1 && state.selection.to >= docSize - 1 && docSize > 2) {
            this.editor.commands.setTextSelection(docSize - 1);
          }
        }, 0);
      },
      onBlur: () => {
        this.isFocused = false;
        this.$emit('blur');
      }
    });

    // Close heading menu when clicking outside
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    if (this.editor) {
      this.editor.destroy();
    }
    document.removeEventListener('click', this.handleClickOutside);
  },
  watch: {
    modelValue(newValue) {
      // Skip update if editor is focused (user is typing)
      if (this.isFocused) {
        return;
      }

      // Convert Markdown to HTML for comparison
      const htmlContent = markdownToHtml(newValue);
      const currentHtml = this.editor.getHTML();

      // Only update if content actually changed
      if (currentHtml !== htmlContent) {
        // Save cursor position
        const { from, to } = this.editor.state.selection;

        // Update content (converted to HTML)
        this.editor.commands.setContent(htmlContent, false);

        // Restore cursor position if possible
        try {
          this.editor.commands.setTextSelection({ from, to });
        } catch (e) {
          // If restoration fails (content changed too much), just continue
        }
      }
    },
    editable(newValue) {
      this.editor.setEditable(newValue);
    }
  },
  computed: {
    currentAlignmentIcon() {
      if (this.editor?.isActive({ textAlign: 'center' })) return FormatAlignCenter;
      if (this.editor?.isActive({ textAlign: 'right' })) return FormatAlignRight;
      return FormatAlignLeft;
    },
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    getCurrentHeadingLevel() {
      if (!this.editor) return 0;
      for (let level = 1; level <= 6; level++) {
        if (this.editor.isActive('heading', { level })) {
          return level;
        }
      }
      return 0;
    },
    getCurrentHeadingLabel() {
      const level = this.getCurrentHeadingLevel();
      return this.getHeadingLabel(level);
    },
    getHeadingLabel(level) {
      if (level === 0) return this.t('Paragraph');
      return this.t(`H${level}`);
    },
    isHeadingLevel(level) {
      return this.getCurrentHeadingLevel() === level;
    },
    toggleHeadingMenu() {
      this.showHeadingMenu = !this.showHeadingMenu;
      this.showAlignmentMenu = false;
      this.showTableMenu = false;
    },
    setHeadingLevel(level) {
      if (level === 0) {
        this.editor.chain().focus().setParagraph().run();
      } else {
        this.editor.chain().focus().setHeading({ level }).run();
      }
      this.showHeadingMenu = false;
    },
    showLinkModalHandler() {
      const { from, to } = this.editor.state.selection;
      const selectedText = this.editor.state.doc.textBetween(from, to, ' ');

      const previousUrl = this.editor.getAttributes('link').href;
      this.linkUrl = previousUrl || '';
      this.linkText = selectedText || '';
      this.showLinkModal = true;
    },
    closeLinkModal() {
      this.showLinkModal = false;
      this.linkUrl = '';
      this.linkText = '';
      this.editor.chain().focus().run();
    },
    applyLink() {
      if (this.linkUrl === '') {
        this.removeLink();
        return;
      }

      // Use linkText if provided, otherwise use URL
      const displayText = this.linkText || this.linkUrl;

      // Get current selection
      const { from, to } = this.editor.state.selection;

      // If no text is selected or we want to replace it, insert new link
      if (from === to) {
        // Insert new link at cursor position
        this.editor
          .chain()
          .focus()
          .insertContent({
            type: 'text',
            marks: [
              {
                type: 'link',
                attrs: {
                  href: this.linkUrl,
                  target: '_blank',
                  rel: 'noopener noreferrer'
                }
              }
            ],
            text: displayText
          })
          .run();
      } else {
        // Replace selected text with link
        this.editor
          .chain()
          .focus()
          .deleteSelection()
          .insertContent({
            type: 'text',
            marks: [
              {
                type: 'link',
                attrs: {
                  href: this.linkUrl,
                  target: '_blank',
                  rel: 'noopener noreferrer'
                }
              }
            ],
            text: displayText
          })
          .run();
      }

      this.closeLinkModal();
    },
    removeLink() {
      this.editor
        .chain()
        .focus()
        .extendMarkRange('link')
        .unsetLink()
        .run();

      this.closeLinkModal();
    },
    handleClickOutside(event) {
      // Close heading menu if clicking outside the dropdown
      if (!event.target.closest('.heading-dropdown')) {
        this.showHeadingMenu = false;
      }
      // Close table menu if clicking outside the dropdown
      if (!event.target.closest('.table-dropdown')) {
        this.showTableMenu = false;
      }
      // Close alignment menu if clicking outside the dropdown
      if (!event.target.closest('.alignment-dropdown')) {
        this.showAlignmentMenu = false;
      }
      // Close more menu if clicking outside the dropdown
      if (!event.target.closest('.more-dropdown')) {
        this.showMoreMenu = false;
      }
    },
    toggleMoreMenu() {
      this.showMoreMenu = !this.showMoreMenu;
      if (this.showMoreMenu) {
        this.$nextTick(() => {
          const btn = this.$refs.moreButton;
          const menu = this.$el.querySelector('.more-menu');
          if (btn && menu) {
            const rect = btn.getBoundingClientRect();
            menu.style.top = (rect.bottom + 4) + 'px';
            menu.style.left = rect.left + 'px';
            // Constrain height to available viewport space
            const availableHeight = window.innerHeight - rect.bottom - 16;
            if (availableHeight < menu.scrollHeight) {
              menu.style.maxHeight = availableHeight + 'px';
            }
          }
        });
      }
    },
    toggleTableMenu() {
      this.showTableMenu = !this.showTableMenu;
      this.showHeadingMenu = false;
      this.showAlignmentMenu = false;
    },
    toggleAlignmentMenu() {
      this.showAlignmentMenu = !this.showAlignmentMenu;
      this.showHeadingMenu = false;
      this.showTableMenu = false;
    },
    setAlignment(align) {
      if (align === 'left') {
        this.editor.chain().focus().unsetTextAlign().run();
      } else {
        this.editor.chain().focus().setTextAlign(align).run();
      }
      this.showAlignmentMenu = false;
    },
    isAlignmentActive(alignment) {
      if (!this.editor) return false;
      if (alignment === 'left') {
        return !this.editor.isActive({ textAlign: 'center' })
            && !this.editor.isActive({ textAlign: 'right' });
      }
      return this.editor.isActive({ textAlign: alignment });
    },
    getAlignmentLabel(align) {
      const labels = {
        left: this.t('Align left'),
        center: this.t('Align center'),
        right: this.t('Align right'),
      };
      return labels[align];
    },
    insertTable() {
      this.editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
      this.showTableMenu = false;
    },
    addRowBefore() {
      this.editor.chain().focus().addRowBefore().run();
      this.showTableMenu = false;
    },
    addRowAfter() {
      this.editor.chain().focus().addRowAfter().run();
      this.showTableMenu = false;
    },
    addColumnBefore() {
      this.editor.chain().focus().addColumnBefore().run();
      this.showTableMenu = false;
    },
    addColumnAfter() {
      this.editor.chain().focus().addColumnAfter().run();
      this.showTableMenu = false;
    },
    deleteRow() {
      this.editor.chain().focus().deleteRow().run();
      this.showTableMenu = false;
    },
    deleteColumn() {
      this.editor.chain().focus().deleteColumn().run();
      this.showTableMenu = false;
    },
    deleteTable() {
      this.editor.chain().focus().deleteTable().run();
      this.showTableMenu = false;
    },

    /**
     * Set the table width. `value` is a CSS length (e.g. '50%', '600px') or
     * null to clear the explicit width and fall back to the default
     * (full-container width with TipTap's column-resize behaviour).
     */
    setTableWidth(value) {
      this.editor.chain().focus().updateAttributes('table', { width: value }).run();
    },
    isTableWidth(value) {
      if (!this.editor || !this.editor.isActive('table')) return false;
      return this.editor.getAttributes('table').width === value;
    },

    /**
     * Set the table alignment via CSS margin shorthand. Pass null to clear.
     */
    setTableAlign(value) {
      this.editor.chain().focus().updateAttributes('table', { align: value }).run();
    },
    isTableAlign(value) {
      if (!this.editor || !this.editor.isActive('table')) return false;
      return this.editor.getAttributes('table').align === value;
    },
  }
};
</script>

<style scoped>
.inline-text-editor {
  position: relative;
  width: 100%;
  display: block;
  background: transparent;
}

/* Floating Toolbar */
.text-menubar {
  position: sticky;
  top: 0;
  z-index: 1000;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
  padding: 8px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  margin-bottom: 8px;
  max-width: 100%;
  box-sizing: border-box;
}

.menubar-button {
  padding: 6px 10px;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--border-radius);
  cursor: pointer;
  font-size: 14px;
  color: var(--color-main-text);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  height: 32px;
}

.menubar-button:hover {
  background: var(--color-background-hover);
  border-color: var(--color-border);
}

.menubar-button.is-active {
  background: var(--color-primary-element);
  border-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
}

/* Dropdown Containers */
.heading-dropdown,
.table-dropdown,
.alignment-dropdown {
  position: relative;
}

.heading-button,
.alignment-button {
  min-width: 90px !important;
  gap: 4px;
}

.alignment-button {
  min-width: 60px !important;
}

/* Dropdown Menu */
.dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 1001;
  min-width: 140px;
  max-width: min(280px, calc(100vw - 32px));
  padding: 4px 0;
}

.table-menu {
  min-width: 180px;
}

/* Compact Toolbar Mode */
.text-menubar--compact {
  padding: 4px;
  gap: 2px;
}

.text-menubar--compact .menubar-button {
  padding: 4px 6px;
  min-width: 28px;
  height: 28px;
}

/* More dropdown for compact mode */
.more-dropdown {
  position: relative;
}

.more-menu {
  position: fixed;
  min-width: 180px;
  max-width: min(280px, calc(100vw - 32px));
  max-height: calc(100vh - 100px);
  overflow-y: auto;
  z-index: 1002;
}

.dropdown-menu-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 12px;
  background: transparent;
  border: none;
  text-align: left;
  cursor: pointer;
  font-size: 14px;
  color: var(--color-main-text);
  transition: background-color 0.15s ease;
}

.dropdown-menu-item:hover {
  background: var(--color-background-hover);
}

.dropdown-menu-item.is-active {
  background: var(--color-primary-element);
  color: var(--color-primary-element-text);
  font-weight: 600;
}

.dropdown-menu-item--danger {
  color: #c9302c;
  font-weight: 500;
}

.dropdown-menu-item--danger:hover {
  background: #c9302c;
  color: white;
}

.dropdown-divider {
  height: 1px;
  background: var(--color-border);
  margin: 4px 0;
}

/* Section label inside a dropdown — small, muted heading above a row of pills */
.dropdown-menu-section-label {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--color-text-maxcontrast, #6b7280);
  padding: 6px 12px 2px;
}

/* Horizontal row of compact buttons inside a dropdown (width presets, align) */
.dropdown-menu-row {
  display: flex;
  gap: 4px;
  padding: 2px 8px 6px;
  flex-wrap: wrap;
}

.dropdown-menu-pill {
  flex: 1 1 auto;
  min-width: 36px;
  padding: 4px 6px;
  border: 1px solid var(--color-border, #ccc);
  background: var(--color-main-background, #fff);
  color: inherit;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.dropdown-menu-pill:hover {
  background: var(--color-background-hover, #f3f4f6);
}

.dropdown-menu-pill.is-active {
  background: var(--color-primary-element, #2563eb);
  color: var(--color-primary-element-text, #fff);
  border-color: var(--color-primary-element, #2563eb);
}

.menubar-divider {
  width: 1px;
  height: 24px;
  background: var(--color-border);
  margin: 0 4px;
}

/* Link Modal */
.link-modal-content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.link-modal-content label {
  font-weight: 600;
  color: var(--color-main-text);
}

.link-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  font-size: 14px;
  color: var(--color-main-text);
  background: var(--color-main-background);
}

.link-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.modal-buttons {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

/* Editor Content */
.editor-content {
  width: 100%;
  display: block;
  background: transparent;
  overflow-x: auto;
}

.editor-content :deep(.ProseMirror) {
  outline: none;
  min-height: 50px;
  padding: 12px;
  border-radius: var(--border-radius);
  transition: background-color 0.2s ease;
  width: 100%;
  box-sizing: border-box;
  color: inherit;
  background: transparent;
}

.is-focused .editor-content :deep(.ProseMirror) {
  background: transparent;
}

/* Text Selection - uses CSS variables from parent Widget for contrast */
.editor-content :deep(.ProseMirror ::selection) {
  background: var(--widget-selection-bg, var(--color-primary-element-light));
  color: var(--widget-selection-text, var(--color-main-text));
}

.editor-content :deep(.ProseMirror ::-moz-selection) {
  background: var(--widget-selection-bg, var(--color-primary-element-light));
  color: var(--widget-selection-text, var(--color-main-text));
}

/* Placeholder */
.editor-content :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: var(--widget-placeholder-color, var(--color-text-maxcontrast));
  pointer-events: none;
  height: 0;
}

/* Typography */
.editor-content :deep(.ProseMirror p) {
  margin: 0 0 0.5em 0;
  width: 100%;
  color: inherit !important;
}

.editor-content :deep(.ProseMirror p:last-child) {
  margin-bottom: 0;
}

.editor-content :deep(.ProseMirror h1),
.editor-content :deep(.ProseMirror h2),
.editor-content :deep(.ProseMirror h3),
.editor-content :deep(.ProseMirror h4),
.editor-content :deep(.ProseMirror h5),
.editor-content :deep(.ProseMirror h6) {
  margin: 0.5em 0;
  font-weight: 600;
  color: inherit !important;
}

.editor-content :deep(.ProseMirror h1) { font-size: 32px; }
.editor-content :deep(.ProseMirror h2) { font-size: 28px; }
.editor-content :deep(.ProseMirror h3) { font-size: 24px; }
.editor-content :deep(.ProseMirror h4) { font-size: 20px; }
.editor-content :deep(.ProseMirror h5) { font-size: 18px; }
.editor-content :deep(.ProseMirror h6) { font-size: 16px; }

/* Text alignment */
.editor-content :deep(.ProseMirror .text-align-center) { text-align: center; }
.editor-content :deep(.ProseMirror .text-align-right) { text-align: right; }

.editor-content :deep(.ProseMirror ul),
.editor-content :deep(.ProseMirror ol) {
  padding-left: 1.5em;
  margin: 0.5em 0;
  width: 100%;
  list-style-position: outside;
  color: inherit !important;
}

.editor-content :deep(.ProseMirror ul) {
  list-style-type: disc;
}

.editor-content :deep(.ProseMirror ol) {
  list-style-type: decimal;
}

.editor-content :deep(.ProseMirror li) {
  margin: 0.25em 0;
  display: list-item;
  color: inherit !important;
}

/* Nested lists - indentation en list-style hierarchy */
.editor-content :deep(.ProseMirror li > ul),
.editor-content :deep(.ProseMirror li > ol) {
  margin: 0.25em 0;
  padding-left: 1.5em;
}

/* Ordered list nesting: 1. → a. → i. → 1. */
.editor-content :deep(.ProseMirror ol ol) {
  list-style-type: lower-alpha;
}
.editor-content :deep(.ProseMirror ol ol ol) {
  list-style-type: lower-roman;
}
.editor-content :deep(.ProseMirror ol ol ol ol) {
  list-style-type: decimal;
}

/* Unordered list nesting: • → ○ → ▪ → • */
.editor-content :deep(.ProseMirror ul ul) {
  list-style-type: circle;
}
.editor-content :deep(.ProseMirror ul ul ul) {
  list-style-type: square;
}
.editor-content :deep(.ProseMirror ul ul ul ul) {
  list-style-type: disc;
}

.editor-content :deep(.ProseMirror strong) {
  font-weight: bold;
  color: inherit !important;
}

.editor-content :deep(.ProseMirror em) {
  font-style: italic;
  color: inherit !important;
}

.editor-content :deep(.ProseMirror u) {
  text-decoration: underline;
  color: inherit !important;
}

.editor-content :deep(.ProseMirror s) {
  text-decoration: line-through;
  color: inherit !important;
}

/* Links - uses CSS variables from parent Widget for contrast */
.editor-content :deep(.ProseMirror a) {
  color: var(--widget-link-color, var(--color-primary-element));
  text-decoration: underline;
  cursor: pointer;
}

.editor-content :deep(.ProseMirror a:hover) {
  color: var(--widget-link-hover-color, var(--color-primary-element-hover));
  text-decoration: none;
}

/* Blockquote */
.editor-content :deep(.ProseMirror blockquote) {
  border-left: 4px solid var(--color-primary-element);
  padding-left: 1em;
  margin: 1em 0;
  color: inherit !important;
  font-style: italic;
}

/* Code */
.editor-content :deep(.ProseMirror code) {
  background: var(--color-background-dark);
  padding: 2px 6px;
  border-radius: var(--border-radius);
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.9em;
  color: inherit !important;
}

/* Code Block */
.editor-content :deep(.ProseMirror pre) {
  background: var(--color-background-dark);
  padding: 12px;
  border-radius: var(--border-radius-large);
  overflow-x: auto;
  margin: 1em 0;
}

.editor-content :deep(.ProseMirror pre code) {
  background: transparent;
  padding: 0;
  font-family: 'Courier New', Courier, monospace;
  color: inherit !important;
}

/* Tables wider than the page column scroll horizontally inside this wrapper
   instead of pushing the page sideways (TipTap injects the wrapper). */
.editor-content :deep(.ProseMirror .tableWrapper) {
  overflow-x: auto;
  max-width: 100%;
  margin: 1em 0;
}

.editor-content :deep(.ProseMirror table) {
  border-collapse: collapse;
  table-layout: fixed;
  min-width: 100%;
  margin: 1em 0;
}

.editor-content :deep(.ProseMirror .tableWrapper > table) {
  margin: 0;
}

/* When the user sets an explicit width via the Table extension, drop the
   min-width: 100% so the table can shrink to that width. */
.editor-content :deep(.ProseMirror table[style*="width:"]) {
  min-width: 0;
}

/* Right-edge resize handle for the active table. The TableResizeHandle plugin
   adds the data attribute via a node decoration; this CSS gives the handle a
   visible affordance so editors notice they can drag-resize the table. */
.editor-content :deep(.ProseMirror table[data-table-resize-active]) {
  position: relative;
}

.editor-content :deep(.ProseMirror table[data-table-resize-active])::after {
  content: '';
  position: absolute;
  top: 0;
  right: -4px;
  bottom: 0;
  width: 8px;
  background: linear-gradient(to right, transparent 0, var(--color-primary-element, #2563eb) 50%, transparent 100%);
  opacity: 0.35;
  pointer-events: none;
}

.editor-content :deep(.ProseMirror table[data-table-resize-active]):hover::after {
  opacity: 0.7;
}

/* Mirrors the read-mode wrap policy in Widget.vue: cells shrink to their
   column share, content wraps anywhere if needed, white-space: normal
   overrides a Nextcloud core nowrap rule. */
.editor-content :deep(.ProseMirror table td),
.editor-content :deep(.ProseMirror table th) {
  border: 1px solid var(--color-border-dark, #bbb);
  padding: 8px 12px;
  vertical-align: top;
  box-sizing: border-box;
  position: relative;
  color: inherit !important;
  overflow-wrap: anywhere;
  min-width: 0;
  hyphens: auto;
  white-space: normal;
}

.editor-content :deep(.ProseMirror table td > *),
.editor-content :deep(.ProseMirror table th > *) {
  overflow-wrap: anywhere;
  min-width: 0;
  max-width: 100%;
  white-space: normal;
}

.editor-content :deep(.ProseMirror table .selectedCell) {
  background: var(--color-primary-element-light);
}

/* Column resize handle — subtle line on cell border */
.editor-content :deep(.ProseMirror table .column-resize-handle) {
  position: absolute;
  right: -2px;
  top: 0;
  bottom: 0;
  width: 4px;
  background-color: var(--color-primary-element);
  pointer-events: none;
  z-index: 10;
}

/* Task List */
.editor-content :deep(.ProseMirror ul[data-type="taskList"]) {
  list-style: none;
  padding-left: 0;
}

.editor-content :deep(.ProseMirror ul[data-type="taskList"] li) {
  display: flex;
  align-items: flex-start;
  color: inherit !important;
}

.editor-content :deep(.ProseMirror ul[data-type="taskList"] li > label) {
  flex: 0 0 auto;
  margin-right: 0.5em;
  user-select: none;
}

.editor-content :deep(.ProseMirror ul[data-type="taskList"] li > div) {
  flex: 1 1 auto;
}

.editor-content :deep(.ProseMirror ul[data-type="taskList"] input[type="checkbox"]) {
  cursor: pointer;
  width: 1.2em;
  height: 1.2em;
}

/* Horizontal Rule */
.editor-content :deep(.ProseMirror hr) {
  border: none;
  border-top: 2px solid var(--color-border);
  margin: 2em 0;
}

/* Mobile styles */
@media (max-width: 480px) {
  .text-menubar {
    padding: 6px;
    gap: 2px;
  }

  .menubar-button {
    padding: 4px 6px;
    min-width: 28px;
    height: 28px;
  }

  .menubar-divider {
    display: none; /* Hide dividers on mobile to save space */
  }

  .heading-button {
    min-width: 70px !important;
    font-size: 12px;
  }

  .dropdown-menu {
    min-width: 120px;
    max-width: calc(100vw - 32px);
  }

  .dropdown-menu-item {
    padding: 6px 10px;
    font-size: 13px;
  }
}

@media (max-width: 360px) {
  .text-menubar {
    padding: 4px;
  }

  .menubar-button {
    padding: 3px 5px;
    min-width: 26px;
    height: 26px;
  }

  .heading-button {
    min-width: 60px !important;
    font-size: 11px;
  }
}
</style>
