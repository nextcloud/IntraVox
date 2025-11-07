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

    <!-- Link Widget -->
    <div v-else-if="widget.type === 'link'" class="widget-link">
      <a :href="widget.url" target="_blank" rel="noopener noreferrer">
        {{ widget.text }}
      </a>
    </div>

    <!-- File Widget -->
    <div v-else-if="widget.type === 'file'" class="widget-file">
      <a :href="getFileUrl(widget.path)" class="file-link">
        <span class="file-icon">📄</span>
        <span class="file-name">{{ widget.name }}</span>
      </a>
    </div>

    <!-- Divider Widget -->
    <hr
      v-else-if="widget.type === 'divider' || widget.type === 'spacer'"
      class="widget-divider"
    />

    <!-- Unknown Widget Type -->
    <div v-else class="widget-unknown">
      {{ t('Unknown widget type: {type}', { type: widget.type }) }}
    </div>
  </div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n';
import { generateUrl } from '@nextcloud/router';
import DOMPurify from 'dompurify';
import InlineTextEditor from './InlineTextEditor.vue';

export default {
  name: 'Widget',
  components: {
    InlineTextEditor
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
    }
  },
  emits: ['update', 'focus', 'blur'],
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
    sanitizeHtml(html) {
      // Configure DOMPurify to allow common formatting tags
      return DOMPurify.sanitize(html, {
        ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        ALLOWED_ATTR: ['href', 'target', 'rel']
      });
    },
    onBlur() {
      // No need to save here anymore - watcher handles it
      this.$emit('blur');
    },
    getImageUrl(filename) {
      if (!filename) return '';
      // Images are served via the IntraVox API with pageId
      return generateUrl(`/apps/intravox/api/pages/${this.pageId}/images/${filename}`);
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
        // Full width - use existing CSS
        style.width = '100%';
        style.height = '300px';
        style.objectFit = 'cover';
      }

      return style;
    }
  }
};
</script>

<style scoped>
.widget {
  width: 100%;
}

/* Text Widget */
.widget-text {
  width: 100%;
  color: var(--color-main-text);
  line-height: 1.5;
}

.widget-text :deep(p) {
  margin: 0 0 0.5em 0;
}

.widget-text :deep(p:last-child) {
  margin-bottom: 0;
}

.widget-text :deep(ul),
.widget-text :deep(ol) {
  padding-left: 1.5em;
  margin: 0.5em 0;
  list-style-position: outside;
}

.widget-text :deep(ul) {
  list-style-type: disc;
}

.widget-text :deep(ol) {
  list-style-type: decimal;
}

.widget-text :deep(li) {
  margin: 0.25em 0;
  display: list-item;
}

.widget-text :deep(strong) {
  font-weight: bold;
}

.widget-text :deep(em) {
  font-style: italic;
}

.widget-text :deep(u) {
  text-decoration: underline;
}

.widget-text :deep(s) {
  text-decoration: line-through;
}

.widget-text :deep(a) {
  color: var(--color-primary);
  text-decoration: underline;
}

/* Heading Widget */
.widget-heading {
  margin: 0 0 15px 0;
  color: var(--color-main-text);
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
}

.widget-image img {
  max-width: 100%;
  height: auto;
  border-radius: 3px;
  display: block;
}

.widget-image .placeholder {
  padding: 40px;
  background: var(--color-background-dark);
  border: 2px dashed var(--color-border);
  border-radius: 3px;
  text-align: center;
  color: var(--color-text-maxcontrast);
}

/* Link Widget */
.widget-link {
  padding: 12px;
  background: var(--color-background-hover);
  border-left: 3px solid var(--color-primary);
  border-radius: 3px;
}

.widget-link a {
  color: var(--color-primary);
  text-decoration: none;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
}

.widget-link a:hover {
  text-decoration: underline;
}

.widget-link a::after {
  content: ' →';
  margin-left: 5px;
}

/* File Widget */
.widget-file {
  padding: 12px;
  background: var(--color-background-hover);
  border-radius: 3px;
}

.file-link {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--color-main-text);
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
  margin: 20px 0;
  border: none;
  border-top: 1px solid var(--color-border);
}

/* Unknown Widget */
.widget-unknown {
  padding: 20px;
  background: var(--color-error);
  color: white;
  border-radius: 3px;
  text-align: center;
}
</style>
