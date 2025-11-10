<template>
  <NcModal @close="$emit('close')"
           :name="t('Edit widget')"
           size="large"
           class="widget-editor-modal">
    <div class="widget-editor-content">
        <!-- Text Widget -->
        <div v-if="localWidget.type === 'text'" class="form-group full-width-editor">
          <label>{{ t('Text:') }}</label>

          <!-- Tiptap Rich Text Editor -->
          <div v-if="editor" class="rich-text-toolbar">
            <button
              type="button"
              @click="editor.chain().focus().toggleBold().run()"
              :class="{ 'is-active': editor.isActive('bold') }"
              :title="t('Bold')"
              class="toolbar-btn"
            >
              <strong>B</strong>
            </button>
            <button
              type="button"
              @click="editor.chain().focus().toggleItalic().run()"
              :class="{ 'is-active': editor.isActive('italic') }"
              :title="t('Italic')"
              class="toolbar-btn"
            >
              <em>I</em>
            </button>
            <button
              type="button"
              @click="editor.chain().focus().toggleUnderline().run()"
              :class="{ 'is-active': editor.isActive('underline') }"
              :title="t('Underline')"
              class="toolbar-btn"
            >
              <u>U</u>
            </button>
            <button
              type="button"
              @click="editor.chain().focus().toggleStrike().run()"
              :class="{ 'is-active': editor.isActive('strike') }"
              :title="t('Strikethrough')"
              class="toolbar-btn"
            >
              <s>S</s>
            </button>
            <span class="toolbar-divider"></span>
            <button
              type="button"
              @click="editor.chain().focus().toggleBulletList().run()"
              :class="{ 'is-active': editor.isActive('bulletList') }"
              :title="t('Bullet list')"
              class="toolbar-btn"
            >
              • List
            </button>
            <button
              type="button"
              @click="editor.chain().focus().toggleOrderedList().run()"
              :class="{ 'is-active': editor.isActive('orderedList') }"
              :title="t('Numbered list')"
              class="toolbar-btn"
            >
              1. List
            </button>
          </div>

          <editor-content :editor="editor" class="rich-text-editor" />
        </div>

        <!-- Heading Widget -->
        <div v-else-if="localWidget.type === 'heading'">
          <div class="form-group">
            <label>{{ t('Heading:') }}</label>
            <input
              v-model="localWidget.content"
              type="text"
              :placeholder="t('Enter your heading...')"
              class="heading-text-input"
            />
          </div>
          <div class="form-group">
            <label>{{ t('Level:') }}</label>
            <select v-model.number="localWidget.level" class="heading-level-select">
              <option :value="1">{{ t('H1 - Largest') }}</option>
              <option :value="2">{{ t('H2') }}</option>
              <option :value="3">{{ t('H3') }}</option>
              <option :value="4">{{ t('H4') }}</option>
              <option :value="5">{{ t('H5') }}</option>
              <option :value="6">{{ t('H6 - Smallest') }}</option>
            </select>
          </div>
        </div>

        <!-- Image Widget -->
        <div v-else-if="localWidget.type === 'image'">
          <div class="form-group">
            <label>{{ t('Upload image:') }}</label>
            <input
              type="file"
              accept="image/jpeg,image/png,image/gif,image/webp"
              @change="handleImageUpload"
            />
          </div>
          <div v-if="localWidget.src" class="image-preview">
            <img :src="getImageUrl(localWidget.src)" :alt="localWidget.alt" />
          </div>
          <div class="form-group">
            <label>{{ t('Alt text:') }}</label>
            <input
              v-model="localWidget.alt"
              type="text"
              :placeholder="t('Description of the image...')"
              class="widget-input"
            />
          </div>
          <div class="form-group">
            <label>{{ t('Size:') }}</label>
            <div class="size-presets">
              <button
                type="button"
                :class="{ active: localWidget.width === 300 }"
                @click="setImageSize(300)"
                class="size-preset-btn"
              >
                {{ t('Small') }} (300px)
              </button>
              <button
                type="button"
                :class="{ active: localWidget.width === 500 }"
                @click="setImageSize(500)"
                class="size-preset-btn"
              >
                {{ t('Medium') }} (500px)
              </button>
              <button
                type="button"
                :class="{ active: localWidget.width === 800 }"
                @click="setImageSize(800)"
                class="size-preset-btn"
              >
                {{ t('Large') }} (800px)
              </button>
              <button
                type="button"
                :class="{ active: !localWidget.width || localWidget.width === null }"
                @click="setImageSize(null)"
                class="size-preset-btn"
              >
                {{ t('Full width') }}
              </button>
            </div>
          </div>
          <div v-if="localWidget.width" class="form-group">
            <label>{{ t('Custom width (pixels):') }}</label>
            <input
              v-model.number="localWidget.width"
              type="number"
              min="50"
              max="2000"
              class="widget-input"
              :placeholder="t('Width in pixels')"
            />
            <p class="size-hint">{{ t('Height will automatically adjust to maintain aspect ratio') }}</p>
          </div>
          <div v-if="!localWidget.width || localWidget.width === null" class="form-group">
            <label>{{ t('Vertical position:') }}</label>
            <div class="position-presets">
              <button
                type="button"
                :class="{ active: !localWidget.objectPosition || localWidget.objectPosition === 'center' }"
                @click="setObjectPosition('center')"
                class="size-preset-btn"
              >
                {{ t('Center') }}
              </button>
              <button
                type="button"
                :class="{ active: localWidget.objectPosition === 'top' }"
                @click="setObjectPosition('top')"
                class="size-preset-btn"
              >
                {{ t('Top') }}
              </button>
              <button
                type="button"
                :class="{ active: localWidget.objectPosition === 'bottom' }"
                @click="setObjectPosition('bottom')"
                class="size-preset-btn"
              >
                {{ t('Bottom') }}
              </button>
            </div>
            <p class="size-hint">{{ t('Choose which part of the image is visible when cropped') }}</p>
          </div>
        </div>

        <!-- Link Widget -->
        <div v-else-if="localWidget.type === 'link'">
          <div class="form-group">
            <label>{{ t('Link text:') }}</label>
            <input
              v-model="localWidget.text"
              type="text"
              :placeholder="t('Click here')"
              class="widget-input"
            />
          </div>
          <div class="form-group">
            <label>{{ t('URL:') }}</label>
            <input
              v-model="localWidget.url"
              type="url"
              placeholder="https://example.com"
              class="widget-input"
            />
          </div>
        </div>

        <!-- File Widget -->
        <div v-else-if="localWidget.type === 'file'">
          <div class="form-group">
            <label>{{ t('File name:') }}</label>
            <input
              v-model="localWidget.name"
              type="text"
              placeholder="Document.pdf"
              class="widget-input"
            />
          </div>
          <div class="form-group">
            <label>{{ t('File path (in IntraVox folder):') }}</label>
            <input
              v-model="localWidget.path"
              type="text"
              placeholder="documents/file.pdf"
              class="widget-input"
            />
          </div>
        </div>

        <!-- Spacer Widget -->
        <div v-else-if="localWidget.type === 'spacer'">
          <div class="form-group">
            <label>{{ t('Height (pixels):') }}</label>
            <input
              v-model.number="localWidget.height"
              type="number"
              min="10"
              max="200"
              placeholder="20"
              class="widget-input"
            />
          </div>
        </div>

      <div class="modal-footer">
        <NcButton @click="$emit('close')" type="secondary">
          {{ t('Cancel') }}
        </NcButton>
        <NcButton @click="save" type="primary">
          {{ t('Save') }}
        </NcButton>
      </div>
    </div>
  </NcModal>
</template>

<script>
import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';
import { translate as t } from '@nextcloud/l10n';
import { NcButton, NcModal } from '@nextcloud/vue';
import { showError } from '@nextcloud/dialogs';

// Tiptap imports
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';

export default {
  name: 'WidgetEditor',
  components: {
    NcButton,
    NcModal,
    EditorContent
  },
  props: {
    widget: {
      type: Object,
      required: true
    },
    pageId: {
      type: String,
      required: true
    }
  },
  emits: ['close', 'save'],
  data() {
    return {
      localWidget: JSON.parse(JSON.stringify(this.widget)),
      editor: null
    };
  },
  mounted() {
    // Initialize Tiptap editor for text widgets
    if (this.localWidget.type === 'text') {
      // Decode HTML entities if content contains escaped HTML
      let content = this.localWidget.content || '';
      if (content.includes('&lt;') || content.includes('&gt;')) {
        content = this.decodeHtml(content);
      }

      this.editor = new Editor({
        extensions: [
          StarterKit.configure({
            // Configure paragraph to have no spacing
            paragraph: {
              HTMLAttributes: {
                class: 'tight-paragraph',
              },
            },
          }),
          Underline,
          Placeholder.configure({
            placeholder: 'Typ hier je tekst...'
          })
        ],
        editorProps: {
          attributes: {
            class: 'intravox-editor-full-width'
          }
        },
        content: content,
        onUpdate: ({ editor }) => {
          // Update widget content as HTML
          this.localWidget.content = editor.getHTML();
        }
      });
    }
  },
  beforeUnmount() {
    // Clean up editor instance
    if (this.editor) {
      this.editor.destroy();
    }
  },
  methods: {
    t(key, vars = {}) {
      return this.$t(key, vars);
    },
    decodeHtml(html) {
      // Decode HTML entities
      const txt = document.createElement('textarea');
      txt.innerHTML = html;
      return txt.value;
    },
    save() {
      this.$emit('save', this.localWidget);
      this.$emit('close');
    },
    async handleImageUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('image', file);

      try {
        const response = await axios.post(
          generateUrl(`/apps/intravox/api/pages/${this.pageId}/images`),
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          }
        );

        // Vue 3: Direct assignment is reactive
        this.localWidget.src = response.data.filename;
        // Initialize width if not set (default to null for full width)
        if (this.localWidget.width === undefined) {
          this.localWidget.width = null;
        }
      } catch (err) {
        showError('Kon afbeelding niet uploaden: ' + err.message);
      }
    },
    getImageUrl(src) {
      if (!src) return '';
      return generateUrl(`/apps/files/ajax/download.php?dir=/IntraVox&files=${src}`);
    },
    setImageSize(width) {
      // Vue 3: Direct assignment is reactive
      this.localWidget.width = width;
      // Remove height - let aspect ratio determine it
      if (this.localWidget.height !== undefined) {
        delete this.localWidget.height;
      }
    },
    setObjectPosition(position) {
      this.localWidget.objectPosition = position;
    }
  }
};
</script>

<style scoped>
/* Make modal wider for better editing experience */
.widget-editor-modal :deep(.modal-container) {
  max-width: 1200px !important;
}

/* Remove all padding from modal content wrapper */
:deep(.modal-wrapper--large .modal-container) {
  padding: 0;
}

:deep(.modal-wrapper .modal-container .modal-container__content) {
  padding: 0;
  margin: 0;
}

:deep(.modal-container > *) {
  padding: 0;
}

.widget-editor-content {
  padding: 0;
  width: 100%;
  margin: 0;
}

.form-group {
  margin-bottom: 20px;
  padding: 0 20px;
}

.full-width-editor {
  padding: 0;
  margin: 0;
}

.full-width-editor label {
  padding: 20px 20px 0 20px;
  display: block;
  margin: 0;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--color-main-text);
}

.heading-text-input,
.heading-level-select,
.widget-input {
  width: 100%;
  padding: 8px 12px;
  border: 2px solid var(--color-border-dark);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

.heading-text-input,
.widget-input {
  cursor: text;
}

.heading-level-select {
  cursor: pointer;
}

.heading-text-input:focus,
.heading-level-select:focus,
.widget-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.image-preview {
  margin: 15px 0;
  max-width: 100%;
}

.image-preview img {
  max-width: 100%;
  height: auto;
  border-radius: var(--border-radius-large);
  border: 1px solid var(--color-border);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid var(--color-border);
  margin-top: 0;
}

/* Rich Text Editor Toolbar */
.rich-text-toolbar {
  display: flex;
  gap: 4px;
  padding: 12px 20px;
  background: var(--color-background-dark);
  border: 1px solid var(--color-border);
  border-left: none;
  border-right: none;
  border-radius: 0;
  align-items: center;
  flex-wrap: wrap;
}

.toolbar-btn {
  padding: 6px 12px;
  background: var(--color-main-background);
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius-large);
  color: var(--color-main-text);
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 4px;
}

.toolbar-btn:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
}

.toolbar-btn.is-active {
  background: var(--color-primary-element-light);
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.toolbar-divider {
  width: 1px;
  height: 24px;
  background: var(--color-border);
  margin: 0 4px;
}

/* Tiptap Editor Styles */
.rich-text-editor {
  width: 100%;
  border: 1px solid var(--color-border);
  border-top: none;
  border-left: none;
  border-right: none;
  border-radius: 0;
  background: var(--color-main-background);
}

.rich-text-editor :deep(.tiptap) {
  width: 100%;
  max-width: 100%;
}

/* Force full width using custom class to override Tiptap inline styles */
.rich-text-editor :deep(.intravox-editor-full-width) {
  width: 100% !important;
  min-height: 400px;
  max-height: 600px;
  padding: 20px;
  color: var(--color-main-text);
  font-family: var(--font-face);
  font-size: 14px;
  line-height: 1.5;
  overflow-y: auto;
  outline: none;
}

.rich-text-editor :deep(.ProseMirror) {
  width: 100%;
  min-height: 400px;
  max-height: 600px;
  padding: 20px;
  color: var(--color-main-text);
  font-family: var(--font-face);
  font-size: 14px;
  line-height: 1.5;
  overflow-y: auto;
  outline: none;
}

.rich-text-editor :deep(.ProseMirror:focus) {
  outline: none;
}

/* Rich text content styling */
.rich-text-editor :deep(.ProseMirror p) {
  margin: 0;
}

.rich-text-editor :deep(.ProseMirror p + p) {
  margin-top: 0;
}

.rich-text-editor :deep(.ProseMirror ul),
.rich-text-editor :deep(.ProseMirror ol) {
  padding-left: 1.5em;
  margin: 0.5em 0;
  list-style-position: outside;
}

.rich-text-editor :deep(.ProseMirror ul) {
  list-style-type: disc;
}

.rich-text-editor :deep(.ProseMirror ol) {
  list-style-type: decimal;
}

.rich-text-editor :deep(.ProseMirror li) {
  margin: 0.25em 0;
  display: list-item;
}

.rich-text-editor :deep(.ProseMirror li p) {
  margin: 0;
}

.rich-text-editor :deep(.ProseMirror strong) {
  font-weight: 700;
}

.rich-text-editor :deep(.ProseMirror em) {
  font-style: italic;
}

.rich-text-editor :deep(.ProseMirror u) {
  text-decoration: underline;
}

.rich-text-editor :deep(.ProseMirror s) {
  text-decoration: line-through;
}

/* Placeholder */
.rich-text-editor :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: var(--color-text-maxcontrast);
  pointer-events: none;
  height: 0;
}

.size-presets {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.size-preset-btn {
  padding: 8px 16px;
  border: 2px solid var(--color-border);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  color: var(--color-main-text);
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.size-preset-btn:hover {
  background: var(--color-background-hover);
  border-color: var(--color-primary);
}

.size-preset-btn.active {
  background: var(--color-primary);
  color: white;
  border-color: var(--color-primary);
}

.custom-size-inputs {
  display: flex;
  gap: 15px;
}

.size-input-group {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 8px;
}

.size-label {
  font-size: 14px;
  color: var(--color-text-maxcontrast);
  min-width: 60px;
}

.size-input {
  flex: 1;
  padding: 8px 12px;
  border: 2px solid var(--color-border-dark);
  border-radius: var(--border-radius-large);
  background: var(--color-main-background);
  color: var(--color-main-text);
  font-size: 14px;
}

.size-input:focus {
  outline: none;
  border-color: var(--color-primary-element);
}

.size-hint {
  margin-top: 8px;
  font-size: 12px;
  color: var(--color-text-maxcontrast);
  font-style: italic;
}
</style>
