<template>
  <NcModal @close="$emit('close')"
           :name="modalTitle"
           size="small">
    <div class="widget-picker-content">
      <div class="widget-types">
        <div
          v-for="widgetType in widgetTypes"
          :key="widgetType.type"
          class="widget-type-card"
          @click="$emit('select', widgetType.type)"
        >
          <component :is="getIconComponent(widgetType.icon)" :size="32" class="widget-icon" />
          <div class="widget-info">
            <h3>{{ widgetType.name }}</h3>
          </div>
        </div>
      </div>
    </div>
  </NcModal>
</template>

<script>
import { NcModal } from '@nextcloud/vue';
import Text from 'vue-material-design-icons/Text.vue';
import FormatTitle from 'vue-material-design-icons/FormatTitle.vue';
import Image from 'vue-material-design-icons/Image.vue';
import Minus from 'vue-material-design-icons/Minus.vue';
import ViewGrid from 'vue-material-design-icons/ViewGrid.vue';

export default {
  name: 'WidgetPicker',
  components: {
    NcModal,
    Text,
    FormatTitle,
    Image,
    Minus,
    ViewGrid
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
          icon: 'text',
          description: this.$t('Add text to your page')
        },
        {
          type: 'heading',
          name: this.$t('Heading'),
          icon: 'format-title',
          description: this.$t('Add a heading')
        },
        {
          type: 'image',
          name: this.$t('Image'),
          icon: 'image',
          description: this.$t('Add an image')
        },
        {
          type: 'divider',
          name: this.$t('Divider'),
          icon: 'minus',
          description: this.$t('Add a horizontal line')
        },
        {
          type: 'links',
          name: this.$t('Links'),
          icon: 'view-grid',
          description: this.$t('Add a grid of links with icons')
        }
      ];
    }
  },
  methods: {
    getIconComponent(iconName) {
      const iconMap = {
        'text': 'Text',
        'format-title': 'FormatTitle',
        'image': 'Image',
        'minus': 'Minus',
        'view-grid': 'ViewGrid'
      };
      return iconMap[iconName] || 'Text';
    }
  }
};
</script>

<style scoped>
.widget-picker-content {
  padding: 12px;
}

.widget-types {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  max-width: 400px;
}

.widget-type-card {
  padding: 20px 16px;
  border: 1px solid var(--color-border);
  border-radius: var(--border-radius);
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 8px;
  background: var(--color-main-background);
  min-height: 80px;
  justify-content: center;
}

.widget-type-card:hover {
  border-color: var(--color-primary);
  background: var(--color-background-hover);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.widget-icon {
  color: var(--color-text-maxcontrast);
}

.widget-info {
  width: 100%;
}

.widget-info h3 {
  margin: 0;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-main-text);
}
</style>
