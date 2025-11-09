<template>
  <NcModal @close="$emit('close')"
           :name="modalTitle"
           size="normal">
    <div class="widget-picker-content">
      <div class="widget-types">
        <div
          v-for="widgetType in widgetTypes"
          :key="widgetType.type"
          class="widget-type-card"
          @click="$emit('select', widgetType.type)"
        >
          <div class="widget-icon">{{ widgetType.icon }}</div>
          <div class="widget-info">
            <h3>{{ widgetType.name }}</h3>
            <p>{{ widgetType.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { NcModal } from '@nextcloud/vue';

export default {
  name: 'WidgetPicker',
  components: {
    NcModal
  },
  emits: ['close', 'select'],
  computed: {
    modalTitle() {
      return this.$t('Add widget');
    },
    widgetTypes() {
      return [
        {
          type: 'text',
          name: this.$t('Text'),
          icon: '📝',
          description: this.$t('Add text to your page')
        },
        {
          type: 'heading',
          name: this.$t('Heading'),
          icon: '📌',
          description: this.$t('Add a heading')
        },
        {
          type: 'image',
          name: this.$t('Image'),
          icon: '🖼️',
          description: this.$t('Add an image')
        },
        {
          type: 'link',
          name: this.$t('Link'),
          icon: '🔗',
          description: this.$t('Add a link')
        },
        {
          type: 'file',
          name: this.$t('File'),
          icon: '📄',
          description: this.$t('Add a file')
        },
        {
          type: 'divider',
          name: this.$t('Divider'),
          icon: '➖',
          description: this.$t('Add a horizontal line')
        }
      ];
    }
  }
};
</script>

<style scoped>
.widget-picker-content {
  padding: 20px;
}

.widget-types {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 15px;
}

.widget-type-card {
  padding: 20px;
  border: 2px solid var(--color-border);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  gap: 15px;
  align-items: flex-start;
}

.widget-type-card:hover {
  border-color: var(--color-primary);
  background: var(--color-background-hover);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.widget-icon {
  font-size: 32px;
  flex-shrink: 0;
}

.widget-info h3 {
  margin: 0 0 5px 0;
  font-size: 16px;
  color: var(--color-main-text);
}

.widget-info p {
  margin: 0;
  font-size: 14px;
  color: var(--color-text-maxcontrast);
}
</style>
