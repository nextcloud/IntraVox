<template>
  <div class="widget" :class="`widget-${widget.type}`">
    <!-- Text Widget with Inline Editing -->
    <div v-if="widget.type === 'text'" class="widget-text">
      <InlineTextEditor
        v-if="editable"
        v-model="localContent"
        :editable="true"
        :placeholder="t('Enter text...')"
        @focus="$emit('focus')"
        @blur="onBlur"
      />
      <div v-else v-html="sanitizeHtml(widget.content || '')"></div>
    </div>

    <!-- Heading Widget -->
    <component
      v-else-if="widget.type === 'heading'"
      :is="`h${widget.level}`"
      class="widget-heading"
      v-html="sanitizedContent"
    ></component>

    <!-- Image Widget -->
    <div v-else-if="widget.type === 'image'" class="widget-image">
      <img
        v-if="widget.src"
        :src="getImageUrl(widget.src)"
        :alt="widget.alt"
        :style="getImageStyle()"
        loading="lazy"
      />
      <div v-else class="placeholder">
        <span>{{ t('No image selected') }}</span>
      </div>
    </div>

    <!-- Links Widget (Grid of Links) -->
    <LinksWidget
      v-else-if="widget.type === 'links'"
      :widget="widget"
      @navigate="$emit('navigate', $event)"
    />

    <!-- File Widget -->
    <div v-else-if="widget.type === 'file'" class="widget-file">
      <a :href="getFileUrl(widget.path)" class="file-link">
        <span class="file-icon">📄</span>
        <span class="file-name">{{ widget.name }}</span>
      </a>
    </div>

    <!-- Divider Widget -->
    <div
      v-else-if="widget.type === 'divider'"
      class="widget-divider"
      :style="getDividerStyle()"
    ></div>

    <!-- Unknown Widget Type -->
    <div v-else class="widget-unknown">
      {{ t('Unknown widget type: {type}', { type: widget.type }) }}
    </div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import InlineTextEditor from './InlineTextEditor.vue';
import LinksWidget from './LinksWidget.vue';
import { markdownToHtml } from '../utils/markdownSerializer.js';

export default {
  name: 'Widget',
  components: {
    InlineTextEditor,
    LinksWidget
  },
  props: {
    widget: {
      type: Object,
      required: true
    },
    pageId: {
      type: String,
      required: true
    },
    editable: {
      type: Boolean,
      default: false
    },
    rowBackgroundColor: {
      type: String,
      default: ''
    }
  },
  emits: ['update', 'focus', 'blur', 'navigate'],
  data() {
    return {
      localContent: this.widget.content || ''
    };
  },
  computed: {
    sanitizedContent() {
      return this.sanitizeHtml(this.widget.content || '');
    }
  },
  watch: {
    'widget.content'(newValue) {
      this.localContent = newValue || '';
    },
    localContent(newValue) {
      // Emit update immediately when content changes (not just on blur)
      if (newValue !== this.widget.content) {
        this.$emit('update', {
          ...this.widget,
          content: newValue
        });
      }
    }
  },
  methods: {
    t(key, vars = {}) {
      return t('intravox', key, vars);
    },
    sanitizeHtml(content) {
      if (!content) return '';
      // Use the markdown serializer to convert markdown to HTML
      return markdownToHtml(content);
    },
    onBlur() {
      // No need to save here anymore - watcher handles it
      this.$emit('blur');
    },
    getImageUrl(filename) {
      if (!filename) return '';
      // Remove 'images/' or '📷 images/' prefix if present (for backwards compatibility)
      const cleanFilename = filename.replace(/^(📷 images\/|images\/)/, '');
      // Images are served via the IntraVox API with pageId
      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/images/${cleanFilename}`);
    },
    getFileUrl(filename) {
      if (!filename) return '';
      // Files are served via the IntraVox API with pageId
      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/files/${filename}`);
    },
    getImageStyle() {
      const style = {};

      if (this.widget.width) {
        // Custom width - height auto maintains aspect ratio
        style.width = `${this.widget.width}px`;
        style.height = 'auto';
        style.maxWidth = '100%'; // Don't overflow container
      } else {
        // Full width - natural aspect ratio with cropping
        style.width = '100%';
        style.height = 'auto';
        style.maxHeight = '500px';
        style.objectFit = 'cover';

        // Set vertical position for cropping
        const position = this.widget.objectPosition || 'center';
        style.objectPosition = `center ${position}`;
      }

      return style;
    },
    getDividerStyle() {
      const style = {};

      // If row has a dark primary background, use light divider
      // Otherwise use primary color divider
      if (this.rowBackgroundColor === 'var(--color-primary-element)') {
        style.background = 'var(--color-primary-element-light)';
      } else {
        style.background = 'var(--color-primary-element)';
      }

      return style;
    }
  }
};
</script>

<style scoped>
.widget {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

/* Text Widget */
.widget-text {
  width: 100%;
  color: inherit;
  line-height: 1.5;
  margin-top: 0;
  margin-bottom: 12px;
}

.widget-text :deep(p) {
  margin: 0 0 0.5em 0;
  color: inherit !important;
}

.widget-text :deep(p:last-child) {
  margin-bottom: 0;
}

.widget-text :deep(ul),
.widget-text :deep(ol) {
  padding-left: 1.5em;
  margin: 0.5em 0;
  list-style-position: outside;
  color: inherit !important;
}

.widget-text :deep(ul) {
  list-style-type: disc;
}

.widget-text :deep(ol) {
  list-style-type: decimal;
}

.widget-text :deep(li) {
  margin: 0.25em 0;
  color: inherit !important;
  display: list-item;
}

.widget-text :deep(strong) {
  font-weight: bold;
  color: inherit !important;
}

.widget-text :deep(em) {
  font-style: italic;
  color: inherit !important;
}

.widget-text :deep(u) {
  text-decoration: underline;
  color: inherit !important;
}

.widget-text :deep(s) {
  text-decoration: line-through;
  color: inherit !important;
}

.widget-text :deep(a) {
  color: var(--color-primary);
  text-decoration: underline;
}

.widget-text :deep(h1),
.widget-text :deep(h2),
.widget-text :deep(h3),
.widget-text :deep(h4),
.widget-text :deep(h5),
.widget-text :deep(h6) {
  margin: 0.5em 0 !important;
  font-weight: 600 !important;
  color: inherit !important;
}

.widget-text :deep(h1) { font-size: 32px !important; }
.widget-text :deep(h2) { font-size: 28px !important; }
.widget-text :deep(h3) { font-size: 24px !important; }
.widget-text :deep(h4) { font-size: 20px !important; }
.widget-text :deep(h5) { font-size: 18px !important; }
.widget-text :deep(h6) { font-size: 16px !important; }

/* Heading Widget */
.widget-heading {
  margin: 16px 0 8px 0;
  color: inherit;
  font-weight: 600;
}

.widget-heading h1 { font-size: 32px; }
.widget-heading h2 { font-size: 28px; }
.widget-heading h3 { font-size: 24px; }
.widget-heading h4 { font-size: 20px; }
.widget-heading h5 { font-size: 18px; }
.widget-heading h6 { font-size: 16px; }

/* Image Widget */
.widget-image {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  margin: 12px 0;
}

.widget-image img {
  max-width: 100%;
  width: 100%;
  height: auto;
  border-radius: var(--border-radius-container-large);
  display: block;
  object-fit: contain;
}

.widget-image .placeholder {
  padding: 40px;
  background: var(--color-background-dark);
  border: 2px dashed var(--color-border);
  border-radius: var(--border-radius-large);
  text-align: center;
  color: var(--color-text-maxcontrast);
}

/* File Widget */
.widget-file {
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: var(--border-radius-large);
  margin: 12px 0;
}

.file-link {
  display: flex;
  align-items: center;
  gap: 10px;
  color: inherit;
  text-decoration: none;
}

.file-link:hover {
  color: var(--color-primary);
}

.file-icon {
  font-size: 24px;
}

.file-name {
  font-weight: 500;
}

/* Divider Widget */
.widget-divider {
  width: 100%;
  height: 2px;
  margin: 16px 0;
  border: none;
}

/* Unknown Widget */
.widget-unknown {
  padding: 20px;
  background: var(--color-error);
  color: white;
  border-radius: var(--border-radius-large);
  text-align: center;
}

/* Mobile styles */
@media (max-width: 768px) {
  .widget-text :deep(h1) { font-size: 24px !important; }
  .widget-text :deep(h2) { font-size: 22px !important; }
  .widget-text :deep(h3) { font-size: 20px !important; }
  .widget-text :deep(h4) { font-size: 18px !important; }
  .widget-text :deep(h5) { font-size: 16px !important; }
  .widget-text :deep(h6) { font-size: 14px !important; }

  .widget-heading h1 { font-size: 24px; }
  .widget-heading h2 { font-size: 22px; }
  .widget-heading h3 { font-size: 20px; }
  .widget-heading h4 { font-size: 18px; }
  .widget-heading h5 { font-size: 16px; }
  .widget-heading h6 { font-size: 14px; }

  .widget-text {
    font-size: 14px;
  }

  .widget-image img {
    max-width: 100%;
    width: 100%;
    height: auto;
    object-fit: contain;
  }
}
</style>
