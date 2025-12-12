<template>
	<div class="comment-section">
		<div class="comment-section__header">
			<h3 class="comment-section__title">
				<CommentTextOutline :size="20" />
				{{ t('intravox', 'Comments') }}
				<span v-if="totalComments > 0" class="comment-section__count">({{ totalComments }})</span>
			</h3>
			<NcSelect
				v-model="sortOrder"
				:options="sortOptions"
				:clearable="false"
				:searchable="false"
				class="comment-section__sort" />
		</div>

		<!-- New comment input (at top) -->
		<div class="comment-section__input">
			<NcAvatar :user="currentUserId" :size="32" />
			<div class="comment-section__input-wrapper">
				<textarea
					ref="commentInput"
					v-model="newComment"
					class="comment-section__textarea"
					:placeholder="t('intravox', 'Write a comment...')"
					rows="2"
					@keydown.enter.ctrl="submitComment"
					@focus="inputFocused = true"
					@blur="inputFocused = false" />
				<div v-if="inputFocused || newComment.trim()" class="comment-section__submit">
					<span class="comment-section__hint">{{ t('intravox', 'Ctrl+Enter to send') }}</span>
					<NcButton
						type="primary"
						:disabled="!newComment.trim() || submitting"
						@click="submitComment">
						<template #icon>
							<Send :size="20" />
						</template>
						{{ t('intravox', 'Send') }}
					</NcButton>
				</div>
			</div>
		</div>

		<!-- Comment list -->
		<div v-if="loading" class="comment-section__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<div v-else-if="comments.length === 0" class="comment-section__empty">
			<p>{{ t('intravox', 'No comments yet. Be the first to comment!') }}</p>
		</div>

		<div v-else class="comment-section__list">
			<div v-for="comment in sortedComments" :key="comment.id" class="comment-wrapper">
				<CommentItem
					:comment="comment"
					:current-user-id="currentUserId"
					:allow-reactions="allowCommentReactions"
					@reply="startReply(comment)"
					@update="handleCommentUpdate"
					@delete="handleCommentDelete" />

				<!-- Inline reply input - shows below the comment being replied to -->
				<div v-if="replyingTo && replyingTo.id === comment.id" class="comment-section__inline-reply">
					<NcAvatar :user="currentUserId" :size="28" />
					<div class="comment-section__inline-reply-wrapper">
						<div class="comment-section__replying-indicator">
							<span>{{ t('intravox', 'Replying to {name}', { name: replyingTo.displayName }) }}</span>
							<NcButton type="tertiary" @click="cancelReply">
								<template #icon>
									<Close :size="16" />
								</template>
							</NcButton>
						</div>
						<textarea
							ref="replyInput"
							v-model="replyText"
							class="comment-section__reply-textarea"
							:placeholder="t('intravox', 'Write a reply...')"
							rows="2"
							@keydown.enter.ctrl="submitReply"
							@keydown.escape="cancelReply" />
						<div class="comment-section__reply-actions">
							<NcButton type="tertiary" @click="cancelReply">
								{{ t('intravox', 'Cancel') }}
							</NcButton>
							<NcButton
								type="primary"
								:disabled="!replyText.trim() || submitting"
								@click="submitReply">
								{{ t('intravox', 'Reply') }}
							</NcButton>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { NcAvatar, NcButton, NcSelect, NcLoadingIcon } from '@nextcloud/vue'
import CommentTextOutline from 'vue-material-design-icons/CommentTextOutline.vue'
import Send from 'vue-material-design-icons/Send.vue'
import Close from 'vue-material-design-icons/Close.vue'
import CommentItem from './CommentItem.vue'
import { getComments, createComment } from '../../services/CommentService.js'
import { getCurrentUser } from '@nextcloud/auth'

export default {
	name: 'CommentSection',
	components: {
		NcAvatar,
		NcButton,
		NcSelect,
		NcLoadingIcon,
		CommentTextOutline,
		Send,
		Close,
		CommentItem,
	},
	props: {
		pageId: {
			type: String,
			required: true,
		},
		allowCommentReactions: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			comments: [],
			totalComments: 0,
			loading: true,
			submitting: false,
			newComment: '',
			replyingTo: null,
			replyText: '',
			inputFocused: false,
			sortOrder: { value: 'newest', label: this.t('intravox', 'Newest first') },
			sortOptions: [
				{ value: 'newest', label: this.t('intravox', 'Newest first') },
				{ value: 'oldest', label: this.t('intravox', 'Oldest first') },
			],
		}
	},
	computed: {
		currentUserId() {
			return getCurrentUser()?.uid || ''
		},
		sortedComments() {
			const sorted = [...this.comments]
			if (this.sortOrder.value === 'newest') {
				sorted.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt))
			} else {
				sorted.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt))
			}
			return sorted
		},
	},
	watch: {
		pageId: {
			immediate: true,
			handler() {
				this.loadComments()
			},
		},
	},
	methods: {
		async loadComments() {
			this.loading = true
			try {
				const result = await getComments(this.pageId)
				this.comments = result.comments || []
				this.totalComments = result.total || 0
			} catch (error) {
				console.error('Failed to load comments:', error)
				this.comments = []
				this.totalComments = 0
			} finally {
				this.loading = false
			}
		},
		async submitComment() {
			if (!this.newComment.trim() || this.submitting) return

			this.submitting = true
			try {
				const parentId = this.replyingTo?.id || null
				const comment = await createComment(this.pageId, this.newComment, parentId)

				if (parentId) {
					// Add reply to parent comment
					const parent = this.comments.find(c => c.id === parentId)
					if (parent) {
						if (!parent.replies) parent.replies = []
						parent.replies.push(comment)
					}
				} else {
					// Add new top-level comment
					this.comments.unshift(comment)
				}

				this.totalComments++
				this.newComment = ''
				this.replyingTo = null
			} catch (error) {
				console.error('Failed to create comment:', error)
			} finally {
				this.submitting = false
			}
		},
		startReply(comment) {
			this.replyingTo = comment
			this.replyText = ''
			this.$nextTick(() => {
				this.$refs.replyInput?.focus()
			})
		},
		cancelReply() {
			this.replyingTo = null
			this.replyText = ''
		},
		async submitReply() {
			if (!this.replyText.trim() || this.submitting || !this.replyingTo) return

			this.submitting = true
			try {
				const parentId = this.replyingTo.id
				const reply = await createComment(this.pageId, this.replyText, parentId)

				// Add reply to parent comment
				const parent = this.comments.find(c => c.id === parentId)
				if (parent) {
					if (!parent.replies) parent.replies = []
					parent.replies.push(reply)
				}

				this.totalComments++
				this.replyText = ''
				this.replyingTo = null
			} catch (error) {
				console.error('Failed to create reply:', error)
			} finally {
				this.submitting = false
			}
		},
		handleCommentUpdate(updatedComment) {
			// Update in comments list
			const index = this.comments.findIndex(c => c.id === updatedComment.id)
			if (index !== -1) {
				this.comments[index] = { ...this.comments[index], ...updatedComment }
			} else {
				// Check in replies
				for (const comment of this.comments) {
					if (comment.replies) {
						const replyIndex = comment.replies.findIndex(r => r.id === updatedComment.id)
						if (replyIndex !== -1) {
							comment.replies[replyIndex] = { ...comment.replies[replyIndex], ...updatedComment }
							break
						}
					}
				}
			}
		},
		handleCommentDelete(commentId) {
			// Remove from comments list
			const index = this.comments.findIndex(c => c.id === commentId)
			if (index !== -1) {
				this.comments.splice(index, 1)
				this.totalComments--
			} else {
				// Check in replies
				for (const comment of this.comments) {
					if (comment.replies) {
						const replyIndex = comment.replies.findIndex(r => r.id === commentId)
						if (replyIndex !== -1) {
							comment.replies.splice(replyIndex, 1)
							this.totalComments--
							break
						}
					}
				}
			}
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
.comment-section {
	margin-top: 24px;
	padding-top: 24px;
	border-top: 1px solid var(--color-border);
}

.comment-section__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 16px;
}

.comment-section__title {
	display: flex;
	align-items: center;
	gap: 8px;
	margin: 0;
	font-size: 18px;
	font-weight: 600;
}

.comment-section__count {
	font-weight: 400;
	color: var(--color-text-maxcontrast);
}

.comment-section__sort {
	width: 150px;
}

.comment-section__loading {
	display: flex;
	justify-content: center;
	padding: 32px;
}

.comment-section__empty {
	text-align: center;
	padding: 32px;
	color: var(--color-text-maxcontrast);
}

.comment-section__list {
	margin-bottom: 24px;
}

.comment-section__replying {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 8px 12px;
	margin-bottom: 8px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius);
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

.comment-section__input {
	display: flex;
	gap: 12px;
	align-items: flex-start;
}

.comment-section__input-wrapper {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.comment-section__textarea {
	width: 100%;
	padding: 12px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	resize: vertical;
	font-family: inherit;
	font-size: inherit;
	min-height: 60px;
}

.comment-section__textarea:focus {
	border-color: var(--color-primary-element);
	outline: none;
}

.comment-section__textarea::placeholder {
	color: var(--color-text-maxcontrast);
}

.comment-section__submit {
	display: flex;
	align-items: center;
	justify-content: space-between;
}

.comment-section__hint {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

/* Inline reply styles */
.comment-wrapper {
	/* Wrapper for comment + inline reply */
}

.comment-section__inline-reply {
	display: flex;
	gap: 10px;
	align-items: flex-start;
	margin-left: 40px;
	margin-top: 8px;
	padding: 12px;
	background: var(--color-background-hover);
	border-radius: var(--border-radius-large);
	border-left: 3px solid var(--color-primary-element);
}

.comment-section__inline-reply-wrapper {
	flex: 1;
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.comment-section__replying-indicator {
	display: flex;
	align-items: center;
	justify-content: space-between;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

.comment-section__reply-textarea {
	width: 100%;
	padding: 10px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	resize: vertical;
	font-family: inherit;
	font-size: 14px;
	min-height: 50px;
	background: var(--color-main-background);
}

.comment-section__reply-textarea:focus {
	border-color: var(--color-primary-element);
	outline: none;
}

.comment-section__reply-textarea::placeholder {
	color: var(--color-text-maxcontrast);
}

.comment-section__reply-actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
</style>
