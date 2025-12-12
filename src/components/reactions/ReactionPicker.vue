<template>
	<NcPopover :shown="visible" @update:shown="$emit('update:visible', $event)">
		<template #trigger>
			<slot name="trigger">
				<NcButton type="tertiary" :aria-label="t('intravox', 'Add reaction')">
					<template #icon>
						<Plus :size="20" />
					</template>
				</NcButton>
			</slot>
		</template>
		<div class="reaction-picker">
			<div class="reaction-picker__grid">
				<button
					v-for="emoji in commonEmojis"
					:key="emoji"
					class="reaction-picker__emoji"
					:class="{ 'reaction-picker__emoji--selected': selectedEmojis.includes(emoji) }"
					:title="emoji"
					@click="selectEmoji(emoji)">
					{{ emoji }}
				</button>
			</div>
		</div>
	</NcPopover>
</template>

<script>
import { NcButton, NcPopover } from '@nextcloud/vue'
import Plus from 'vue-material-design-icons/Plus.vue'

export default {
	name: 'ReactionPicker',
	components: {
		NcButton,
		NcPopover,
		Plus,
	},
	props: {
		visible: {
			type: Boolean,
			default: false,
		},
		selectedEmojis: {
			type: Array,
			default: () => [],
		},
	},
	emits: ['update:visible', 'select'],
	data() {
		return {
			// Common emoji reactions for quick access
			commonEmojis: [
				'👍', '👎', '❤️', '🎉', '😊', '😢',
				'😮', '😂', '🤔', '👏', '🙏', '💪',
				'✅', '⭐', '🔥', '💯', '👀', '🚀',
			],
		}
	},
	methods: {
		selectEmoji(emoji) {
			this.$emit('select', emoji)
			this.$emit('update:visible', false)
		},
		t(app, str) {
			return window.t ? window.t(app, str) : str
		},
	},
}
</script>

<style scoped>
.reaction-picker {
	padding: 8px;
}

.reaction-picker__grid {
	display: grid;
	grid-template-columns: repeat(6, 1fr);
	gap: 4px;
}

.reaction-picker__emoji {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 36px;
	height: 36px;
	font-size: 20px;
	border: none;
	background: transparent;
	border-radius: var(--border-radius);
	cursor: pointer;
	transition: background-color 0.1s ease;
}

.reaction-picker__emoji:hover {
	background-color: var(--color-background-hover);
}

.reaction-picker__emoji--selected {
	background-color: var(--color-primary-element-light);
}
</style>
