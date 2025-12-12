<template>
	<div class="reaction-bar">
		<!-- Existing reactions -->
		<div class="reaction-bar__reactions">
			<button
				v-for="(count, emoji) in reactions"
				:key="emoji"
				class="reaction-bar__reaction"
				:class="{ 'reaction-bar__reaction--active': userReactions.includes(emoji) }"
				:title="getReactionTitle(emoji, count)"
				@click="toggleReaction(emoji)">
				<span class="reaction-bar__emoji">{{ emoji }}</span>
				<span class="reaction-bar__count">{{ count }}</span>
			</button>
		</div>

		<!-- Add reaction button -->
		<ReactionPicker
			v-model:visible="showPicker"
			:selected-emojis="userReactions"
			@select="addReaction">
			<template #trigger>
				<NcButton
					type="tertiary"
					class="reaction-bar__add"
					:aria-label="t('intravox', 'Add reaction')">
					<template #icon>
						<EmoticonHappyOutline :size="20" />
					</template>
					<template v-if="Object.keys(reactions).length === 0">
						{{ t('intravox', 'React') }}
					</template>
				</NcButton>
			</template>
		</ReactionPicker>
	</div>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import EmoticonHappyOutline from 'vue-material-design-icons/EmoticonHappyOutline.vue'
import ReactionPicker from './ReactionPicker.vue'
import { togglePageReaction } from '../../services/CommentService.js'

export default {
	name: 'ReactionBar',
	components: {
		NcButton,
		EmoticonHappyOutline,
		ReactionPicker,
	},
	props: {
		pageId: {
			type: String,
			required: true,
		},
		reactions: {
			type: Object,
			default: () => ({}),
		},
		userReactions: {
			type: Array,
			default: () => [],
		},
	},
	emits: ['update'],
	data() {
		return {
			showPicker: false,
			loading: false,
		}
	},
	methods: {
		async toggleReaction(emoji) {
			if (this.loading) return
			this.loading = true

			try {
				const result = await togglePageReaction(this.pageId, emoji, this.userReactions)
				this.$emit('update', result)
			} catch (error) {
				console.error('Failed to toggle reaction:', error)
			} finally {
				this.loading = false
			}
		},
		async addReaction(emoji) {
			await this.toggleReaction(emoji)
		},
		getReactionTitle(emoji, count) {
			const isActive = this.userReactions.includes(emoji)
			if (isActive) {
				return this.t('intravox', 'You and {count} others reacted with {emoji}', { count: count - 1, emoji })
			}
			return this.t('intravox', '{count} people reacted with {emoji}', { count, emoji })
		},
		t(app, str, params = {}) {
			if (window.t) {
				return window.t(app, str, params)
			}
			// Fallback: simple parameter replacement
			let result = str
			for (const [key, value] of Object.entries(params)) {
				result = result.replace(`{${key}}`, value)
			}
			return result
		},
	},
}
</script>

<style scoped>
.reaction-bar {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 8px;
	padding: 12px 0;
}

.reaction-bar__reactions {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
}

.reaction-bar__reaction {
	display: flex;
	align-items: center;
	gap: 4px;
	padding: 4px 10px;
	border: 1px solid var(--color-border);
	border-radius: 16px;
	background: var(--color-main-background);
	cursor: pointer;
	transition: all 0.1s ease;
}

.reaction-bar__reaction:hover {
	background: var(--color-background-hover);
	border-color: var(--color-border-dark);
}

.reaction-bar__reaction--active {
	background: var(--color-primary-element-light);
	border-color: var(--color-primary-element);
}

.reaction-bar__reaction--active:hover {
	background: var(--color-primary-element-light);
}

.reaction-bar__emoji {
	font-size: 16px;
}

.reaction-bar__count {
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}
</style>
