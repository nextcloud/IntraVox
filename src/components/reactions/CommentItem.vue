<template>
	<div class="comment-item" :class="{ 'comment-item--reply': isReply }">
		<div class="comment-item__header">
			<NcAvatar :user="comment.userId" :display-name="comment.displayName" :size="isReply ? 28 : 32" />
			<div class="comment-item__meta">
				<span class="comment-item__author">{{ comment.displayName }}</span>
				<span class="comment-item__time" :title="formattedDate">{{ relativeTime }}</span>
				<span v-if="comment.isEdited" class="comment-item__edited">({{ t('intravox', 'edited') }})</span>
			</div>
			<div v-if="canModify" class="comment-item__actions">
				<NcActions>
					<NcActionButton @click="startEdit">
						<template #icon>
							<Pencil :size="20" />
						</template>
						{{ t('intravox', 'Edit') }}
					</NcActionButton>
					<NcActionButton @click="confirmDelete">
						<template #icon>
							<Delete :size="20" />
						</template>
						{{ t('intravox', 'Delete') }}
					</NcActionButton>
				</NcActions>
			</div>
		</div>

		<div class="comment-item__body">
			<!-- Edit mode -->
			<div v-if="editing" class="comment-item__edit">
				<textarea
					ref="editInput"
					v-model="editMessage"
					class="comment-item__edit-input"
					rows="3"
					@keydown.enter.ctrl="saveEdit"
					@keydown.escape="cancelEdit" />
				<div class="comment-item__edit-actions">
					<NcButton type="tertiary" @click="cancelEdit">
						{{ t('intravox', 'Cancel') }}
					</NcButton>
					<NcButton type="primary" :disabled="!editMessage.trim()" @click="saveEdit">
						{{ t('intravox', 'Save') }}
					</NcButton>
				</div>
			</div>

			<!-- View mode -->
			<p v-else class="comment-item__message">{{ comment.message }}</p>
		</div>

		<!-- Reactions on comment -->
		<div class="comment-item__footer">
			<!-- Buttons on the left -->
			<div class="comment-item__buttons">
				<NcButton v-if="!isReply" type="tertiary" @click="$emit('reply')">
					<template #icon>
						<Reply :size="16" />
					</template>
					{{ t('intravox', 'Reply') }}
				</NcButton>
				<ReactionPicker
					v-if="allowReactions"
					v-model:visible="showReactionPicker"
					:selected-emojis="localUserReactions"
					@select="addReaction">
					<template #trigger>
						<NcButton type="tertiary" :aria-label="t('intravox', 'Add reaction')">
							<template #icon>
								<EmoticonHappyOutline :size="16" />
							</template>
						</NcButton>
					</template>
				</ReactionPicker>
			</div>
			<!-- Reactions after buttons (always show existing reactions, even if adding new is disabled) -->
			<div v-if="hasReactions" class="comment-item__reactions">
				<button
					v-for="(count, emoji) in comment.reactions"
					:key="emoji"
					class="comment-item__reaction"
					:class="{ 'comment-item__reaction--active': isUserReaction(emoji) }"
					:disabled="!allowReactions"
					@click="allowReactions && toggleReaction(emoji)">
					{{ emoji }} {{ count }}
				</button>
			</div>
		</div>

		<!-- Replies (only for top-level comments) -->
		<div v-if="!isReply && comment.replies && comment.replies.length > 0" class="comment-item__replies">
			<CommentItem
				v-for="reply in comment.replies"
				:key="reply.id"
				:comment="reply"
				:current-user-id="currentUserId"
				:is-reply="true"
				:allow-reactions="allowReactions"
				@update="$emit('update', $event)"
				@delete="$emit('delete', $event)" />
		</div>
	</div>
</template>

<script>
import { NcAvatar, NcButton, NcActions, NcActionButton } from '@nextcloud/vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Reply from 'vue-material-design-icons/Reply.vue'
import EmoticonHappyOutline from 'vue-material-design-icons/EmoticonHappyOutline.vue'
import ReactionPicker from './ReactionPicker.vue'
import { updateComment, deleteComment, toggleCommentReaction } from '../../services/CommentService.js'

export default {
	name: 'CommentItem',
	components: {
		NcAvatar,
		NcButton,
		NcActions,
		NcActionButton,
		Pencil,
		Delete,
		Reply,
		EmoticonHappyOutline,
		ReactionPicker,
	},
	props: {
		comment: {
			type: Object,
			required: true,
		},
		currentUserId: {
			type: String,
			default: '',
		},
		isReply: {
			type: Boolean,
			default: false,
		},
		allowReactions: {
			type: Boolean,
			default: true,
		},
	},
	emits: ['update', 'delete', 'reply'],
	data() {
		return {
			editing: false,
			editMessage: '',
			showReactionPicker: false,
			// Initialize from prop, will be updated locally on toggle
			localUserReactions: [],
		}
	},
	watch: {
		'comment.userReactions': {
			immediate: true,
			handler(newVal) {
				// Sync from backend when comment data changes
				this.localUserReactions = newVal || []
			},
		},
	},
	computed: {
		canModify() {
			return this.comment.userId === this.currentUserId
		},
		hasReactions() {
			return this.comment.reactions && Object.keys(this.comment.reactions).length > 0
		},
		formattedDate() {
			return new Date(this.comment.createdAt).toLocaleString()
		},
		relativeTime() {
			const date = new Date(this.comment.createdAt)
			const now = new Date()
			const diffMs = now - date
			const diffMins = Math.floor(diffMs / 60000)
			const diffHours = Math.floor(diffMs / 3600000)
			const diffDays = Math.floor(diffMs / 86400000)

			if (diffMins < 1) return this.t('intravox', 'just now')
			if (diffMins < 60) return this.t('intravox', '{minutes} min ago', { minutes: diffMins })
			if (diffHours < 24) return this.t('intravox', '{hours} hours ago', { hours: diffHours })
			if (diffDays < 7) return this.t('intravox', '{days} days ago', { days: diffDays })
			return date.toLocaleDateString()
		},
	},
	methods: {
		startEdit() {
			this.editMessage = this.comment.message
			this.editing = true
			this.$nextTick(() => {
				this.$refs.editInput?.focus()
			})
		},
		cancelEdit() {
			this.editing = false
			this.editMessage = ''
		},
		async saveEdit() {
			if (!this.editMessage.trim()) return

			try {
				const updated = await updateComment(this.comment.id, this.editMessage)
				this.$emit('update', updated)
				this.editing = false
			} catch (error) {
				console.error('Failed to update comment:', error)
			}
		},
		async confirmDelete() {
			if (confirm(this.t('intravox', 'Are you sure you want to delete this comment?'))) {
				try {
					await deleteComment(this.comment.id)
					this.$emit('delete', this.comment.id)
				} catch (error) {
					console.error('Failed to delete comment:', error)
				}
			}
		},
		isUserReaction(emoji) {
			return this.localUserReactions.includes(emoji)
		},
		async toggleReaction(emoji) {
			try {
				const result = await toggleCommentReaction(
					this.comment.id,
					emoji,
					this.localUserReactions,
				)
				this.localUserReactions = result.userReactions || []
				// Update comment reactions
				this.comment.reactions = result.reactions
			} catch (error) {
				console.error('Failed to toggle reaction:', error)
			}
		},
		async addReaction(emoji) {
			await this.toggleReaction(emoji)
		},
		t(app, str, params = {}) {
			if (window.t) {
				return window.t(app, str, params)
			}
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
.comment-item {
	padding: 12px 0;
	border-bottom: 1px solid var(--color-border);
}

.comment-item--reply {
	padding: 8px 0;
	border-bottom: none;
	margin-left: 40px;
}

.comment-item__header {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 8px;
}

.comment-item__meta {
	flex: 1;
	display: flex;
	align-items: baseline;
	gap: 8px;
}

.comment-item__author {
	font-weight: 600;
	color: var(--color-main-text);
}

.comment-item__time {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.comment-item__edited {
	font-size: 12px;
	font-style: italic;
	color: var(--color-text-maxcontrast);
}

.comment-item__body {
	margin-left: 40px;
}

.comment-item--reply .comment-item__body {
	margin-left: 36px;
}

.comment-item__message {
	margin: 0;
	white-space: pre-wrap;
	word-break: break-word;
}

.comment-item__edit {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.comment-item__edit-input {
	width: 100%;
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	resize: vertical;
	font-family: inherit;
	font-size: inherit;
}

.comment-item__edit-input:focus {
	border-color: var(--color-primary-element);
	outline: none;
}

.comment-item__edit-actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}

.comment-item__footer {
	display: flex;
	align-items: center;
	justify-content: flex-start;
	gap: 8px;
	margin-left: 40px;
	margin-top: 8px;
}

.comment-item--reply .comment-item__footer {
	margin-left: 36px;
}

.comment-item__reactions {
	display: flex;
	flex-wrap: wrap;
	gap: 4px;
}

.comment-item__reaction {
	padding: 2px 8px;
	font-size: 12px;
	border: 1px solid var(--color-border);
	border-radius: 12px;
	background: transparent;
	cursor: pointer;
	transition: all 0.1s ease;
}

.comment-item__reaction:hover {
	background: var(--color-background-hover);
}

.comment-item__reaction--active {
	background: var(--color-primary-element-light);
	border-color: var(--color-primary-element);
}

.comment-item__buttons {
	display: flex;
	gap: 4px;
}

.comment-item__replies {
	margin-top: 8px;
}
</style>
