/**
 * Comment Service - API client for IntraVox comments and reactions
 *
 * Uses Nextcloud's native Comments API via REST wrapper
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const BASE_URL = '/apps/intravox/api'

/**
 * Get comments for a page
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {number} limit - Maximum number of comments (default 50)
 * @param {number} offset - Pagination offset (default 0)
 * @returns {Promise<{comments: Array, total: number}>}
 */
export async function getComments(pageId, limit = 50, offset = 0) {
	const url = generateUrl(`${BASE_URL}/pages/{pageId}/comments`, { pageId })
	const response = await axios.get(url, { params: { limit, offset } })
	return response.data
}

/**
 * Create a new comment
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {string} message - The comment message
 * @param {string|null} parentId - Parent comment ID for replies (optional)
 * @returns {Promise<Object>} The created comment
 */
export async function createComment(pageId, message, parentId = null) {
	const url = generateUrl(`${BASE_URL}/pages/{pageId}/comments`, { pageId })
	const response = await axios.post(url, { message, parentId })
	return response.data
}

/**
 * Update an existing comment
 *
 * @param {string} commentId - The comment ID
 * @param {string} message - The new message
 * @returns {Promise<Object>} The updated comment
 */
export async function updateComment(commentId, message) {
	const url = generateUrl(`${BASE_URL}/comments/{commentId}`, { commentId })
	const response = await axios.put(url, { message })
	return response.data
}

/**
 * Delete a comment
 *
 * @param {string} commentId - The comment ID
 * @returns {Promise<void>}
 */
export async function deleteComment(commentId) {
	const url = generateUrl(`${BASE_URL}/comments/{commentId}`, { commentId })
	await axios.delete(url)
}

/**
 * Get reactions for a page
 *
 * @param {string} pageId - The uniqueId of the page
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
export async function getPageReactions(pageId) {
	const url = generateUrl(`${BASE_URL}/pages/{pageId}/reactions`, { pageId })
	const response = await axios.get(url)
	return response.data
}

/**
 * Add a reaction to a page
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {string} emoji - The emoji reaction
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
export async function addPageReaction(pageId, emoji) {
	const url = generateUrl(`${BASE_URL}/pages/{pageId}/reactions/{emoji}`, { pageId, emoji })
	const response = await axios.post(url)
	return response.data
}

/**
 * Remove a reaction from a page
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {string} emoji - The emoji reaction
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
export async function removePageReaction(pageId, emoji) {
	const url = generateUrl(`${BASE_URL}/pages/{pageId}/reactions/{emoji}`, { pageId, emoji })
	const response = await axios.delete(url)
	return response.data
}

/**
 * Toggle a reaction on a page (add if not exists, remove if exists)
 *
 * @param {string} pageId - The uniqueId of the page
 * @param {string} emoji - The emoji reaction
 * @param {Array} userReactions - Current user reactions
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
export async function togglePageReaction(pageId, emoji, userReactions = []) {
	if (userReactions.includes(emoji)) {
		return removePageReaction(pageId, emoji)
	}
	return addPageReaction(pageId, emoji)
}

/**
 * Get reactions for a comment
 *
 * @param {string} commentId - The comment ID
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
export async function getCommentReactions(commentId) {
	const url = generateUrl(`${BASE_URL}/comments/{commentId}/reactions`, { commentId })
	const response = await axios.get(url)
	return response.data
}

/**
 * Add a reaction to a comment
 *
 * @param {string} commentId - The comment ID
 * @param {string} emoji - The emoji reaction
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
export async function addCommentReaction(commentId, emoji) {
	const url = generateUrl(`${BASE_URL}/comments/{commentId}/reactions/{emoji}`, { commentId, emoji })
	const response = await axios.post(url)
	return response.data
}

/**
 * Remove a reaction from a comment
 *
 * @param {string} commentId - The comment ID
 * @param {string} emoji - The emoji reaction
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
export async function removeCommentReaction(commentId, emoji) {
	const url = generateUrl(`${BASE_URL}/comments/{commentId}/reactions/{emoji}`, { commentId, emoji })
	const response = await axios.delete(url)
	return response.data
}

/**
 * Toggle a reaction on a comment
 *
 * @param {string} commentId - The comment ID
 * @param {string} emoji - The emoji reaction
 * @param {Array} userReactions - Current user reactions
 * @returns {Promise<{reactions: Object, userReactions: Array}>}
 */
export async function toggleCommentReaction(commentId, emoji, userReactions = []) {
	if (userReactions.includes(emoji)) {
		return removeCommentReaction(commentId, emoji)
	}
	return addCommentReaction(commentId, emoji)
}

/**
 * Get engagement settings (reactions & comments configuration)
 *
 * @returns {Promise<{allowPageReactions: boolean, allowComments: boolean, allowCommentReactions: boolean, singleReactionPerUser: boolean}>}
 */
export async function getEngagementSettings() {
	const url = generateUrl(`${BASE_URL}/settings/engagement`)
	const response = await axios.get(url)
	return response.data
}

export default {
	getComments,
	createComment,
	updateComment,
	deleteComment,
	getPageReactions,
	addPageReaction,
	removePageReaction,
	togglePageReaction,
	getCommentReactions,
	addCommentReaction,
	removeCommentReaction,
	toggleCommentReaction,
	getEngagementSettings,
}
