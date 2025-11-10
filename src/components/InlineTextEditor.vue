<template>
  <div class="inline-text-editor" :class="{ 'is-focused': isFocused }">
    <!-- Floating Toolbar -->
    <div v-if="editor && isFocused && showToolbar" class="text-menubar">
      <button
        type="button"
        @mousedown.prevent="editor.chain().focus().toggleBold().run()"
        :class="{ 'is-active': editor.isActive('bold') }"
        :title="t('Bold (Ctrl+B)')"
        class="menubar-button"
      >
        <strong>B</strong>
      </button>
      <button
        type="button"
        @mousedown.prevent="editor.chain().focus().toggleItalic().run()"
        :class="{ 'is-active': editor.isActive('italic') }"
        :title="t('Italic (Ctrl+I)')"
        class="menubar-button"
      >
        <em>I</em>
      </button>
      <button
        type="button"
        @mousedown.prevent="editor.chain().focus().toggleUnderline().run()"
        :class="{ 'is-active': editor.isActive('underline') }"
        :title="t('Underline (Ctrl+U)')"
        class="menubar-button"
      >
        <u>U</u>
      </button>
      <button
        type="button"
        @mousedown.prevent="editor.chain().focus().toggleStrike().run()"
        :class="{ 'is-active': editor.isActive('strike') }"
        :title="t('Strikethrough')"
        class="menubar-button"
      >
        <s>S</s>
      </button>
      <span class="menubar-divider"></span>
      <!-- Heading Dropdown Button -->
      <div class="heading-dropdown">
        <button
          type="button"
          @mousedown.prevent="toggleHeadingMenu"
          class="menubar-button heading-button"
          :title="t('Heading')"
        >
          {{ getCurrentHeadingLabel() }}
        </button>
        <div v-if="showHeadingMenu" class="heading-menu">
          <button
            v-for="level in [0, 1, 2, 3, 4, 5, 6]"
            :key="level"
            type="button"
            @mousedown.prevent="setHeadingLevel(level)"
            :class="{ 'is-active': isHeadingLevel(level) }"
            class="heading-menu-item"
          >
            {{ getHeadingLabel(level) }}
          </button>
        </div>
      </div>
      <span class="menubar-divider"></span>
      <button
        type="button"
        @mousedown.prevent="editor.chain().focus().toggleBulletList().run()"
        :class="{ 'is-active': editor.isActive('bulletList') }"
        :title="t('Unordered list')"
        class="menubar-button"
      >
        ●
      </button>
      <button
        type="button"
        @mousedown.prevent="editor.chain().focus().toggleOrderedList().run()"
        :class="{ 'is-active': editor.isActive('orderedList') }"
        :title="t('Ordered list')"
        class="menubar-button"
      >
        1.
      </button>
      <span class="menubar-divider"></span>
      <button
        type="button"
        @mousedown.prevent="showLinkModalHandler"
        :class="{ 'is-active': editor.isActive('link') }"
        :title="t('Insert link')"
        class="menubar-button"
      >
        🔗
      </button>
    </div>

    <!-- Editor Content -->
    <editor-content :editor="editor" class="editor-content" />

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
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { NcModal, NcButton } from '@nextcloud/vue';

export default {
  name: 'InlineTextEditor',
  components: {
    EditorContent,
    NcModal,
    NcButton
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
    }
  },
  emits: ['update:modelValue', 'focus', 'blur'],
  data() {
    return {
      editor: null,
      isFocused: false,
      showToolbar: true,
      showLinkModal: false,
      linkUrl: '',
      linkText: '',
      showHeadingMenu: false
    };
  },
  mounted() {
    this.editor = new Editor({
      content: this.modelValue,
      editable: this.editable,
      extensions: [
        StarterKit,
        Underline,
        Link.configure({
          openOnClick: false,
          HTMLAttributes: {
            target: '_blank',
            rel: 'noopener noreferrer'
          }
        }),
        Placeholder.configure({
          placeholder: this.placeholder || this.t('Enter text...')
        })
      ],
      onUpdate: () => {
        this.$emit('update:modelValue', this.editor.getHTML());
      },
      onFocus: () => {
        this.isFocused = true;
        this.$emit('focus');
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
      const isSame = this.editor.getHTML() === newValue;
      if (!isSame) {
        this.editor.commands.setContent(newValue, false);
      }
    },
    editable(newValue) {
      this.editor.setEditable(newValue);
    }
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
    }
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
  top: 50px;
  z-index: 100;
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 8px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 8px;
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
  background: var(--color-primary-element-light);
  border-color: var(--color-primary-element);
  color: var(--color-primary-element-text);
}

/* Heading Dropdown */
.heading-dropdown {
  position: relative;
}

.heading-button {
  min-width: 80px !important;
}

.heading-menu {
  position: absolute;
  top: 100%;
  left: 0;
  margin-top: 4px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  min-width: 120px;
}

.heading-menu-item {
  display: block;
  width: 100%;
  padding: 8px 12px;
  background: transparent;
  border: none;
  text-align: left;
  cursor: pointer;
  font-size: 14px;
  color: var(--color-main-text);
  transition: background-color 0.2s ease;
}

.heading-menu-item:hover {
  background: var(--color-background-hover);
}

.heading-menu-item.is-active {
  background: var(--color-primary-element-light);
  color: var(--color-primary-element-text);
  font-weight: 600;
}

.heading-menu-item:first-child {
  border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.heading-menu-item:last-child {
  border-radius: 0 0 var(--border-radius) var(--border-radius);
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

/* Placeholder */
.editor-content :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: var(--color-text-maxcontrast);
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

.editor-content :deep(.ProseMirror a) {
  color: var(--color-primary);
  text-decoration: underline;
  cursor: pointer;
}

.editor-content :deep(.ProseMirror a:hover) {
  text-decoration: none;
}
</style>
