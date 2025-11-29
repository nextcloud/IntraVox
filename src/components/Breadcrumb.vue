<template>
	<nav v-if="breadcrumb && breadcrumb.length > 1" class="breadcrumb" aria-label="Breadcrumb">
		<ol class="breadcrumb-list">
			<li v-for="(item, index) in breadcrumbItems"
			    :key="item.id"
			    class="breadcrumb-item">

				<a :href="item.url"
				   @click.prevent="navigateToPage(item.id || item.uniqueId)"
				   :class="{'breadcrumb-link': true, 'breadcrumb-current-page': item.current}">
					{{ item.title }}
				</a>

				<ChevronRight v-if="index < breadcrumbItems.length - 1"
				              :size="16"
				              class="breadcrumb-separator" />
			</li>
		</ol>
	</nav>
</template>

<script>
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'

export default {
	name: 'Breadcrumb',
	components: {
		ChevronRight
	},
	props: {
		breadcrumb: {
			type: Array,
			required: true,
			default: () => []
		}
	},
	computed: {
		// Return all breadcrumb items including the current page
		breadcrumbItems() {
			if (!this.breadcrumb || this.breadcrumb.length === 0) {
				return []
			}
			return this.breadcrumb
		}
	},
	methods: {
		navigateToPage(pageId) {
			this.$emit('navigate', pageId)
		}
	}
}
</script>

<style scoped>
.breadcrumb {
	padding: 8px 0;
	margin-bottom: 8px;
	border-bottom: 1px solid var(--color-border);
}

.breadcrumb-list {
	display: flex;
	align-items: center;
	list-style: none;
	padding: 0;
	margin: 0;
	flex-wrap: wrap;
	gap: 4px;
}

.breadcrumb-item {
	display: flex;
	align-items: center;
	font-size: 14px;
}

.breadcrumb-link {
	color: var(--color-primary-element);
	text-decoration: none;
	padding: 4px 8px;
	border-radius: var(--border-radius);
	transition: background-color 0.1s ease;
}

.breadcrumb-link:hover {
	background-color: var(--color-background-hover);
}

.breadcrumb-current-page {
	font-weight: 600;
	color: var(--color-main-text);
}

.breadcrumb-separator {
	margin: 0 4px;
	color: var(--color-text-lighter);
	flex-shrink: 0;
}

/* Mobile responsive */
@media (max-width: 768px) {
	.breadcrumb-list {
		font-size: 13px;
	}

	.breadcrumb-link,
	.breadcrumb-current {
		padding: 2px 6px;
	}
}
</style>
