/**
 * Prefetches IntraVox pages in the background when users hover over
 * navigation links or when those links scroll into view on mobile.
 *
 * Designed to be safe at enterprise scale:
 *   - Respects the Save-Data hint (no prefetch on metered connections)
 *   - Caps in-flight requests so a navigation-heavy page doesn't fan
 *     out a hundred parallel fetches
 *   - Hover delay (default 100ms) avoids prefetching pages that the
 *     mouse only crossed on its way somewhere else
 *
 * Cache writes go through the shared CacheService so subsequent
 * full-page navigations see the prefetch result.
 */
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import CacheService from './CacheService.js'

class PrefetchService {
  constructor() {
    this.queue = []
    this.active = 0
    this.maxConcurrent = 3
    this.hoverDelay = 100
    this.inFlight = new Set()
    this.observer = null
  }

  /**
   * Returns true if prefetching is disabled. Respects the user's
   * Save-Data preference (sent by browsers on metered/slow connections)
   * so we never spend their data on speculative loads.
   * @returns {boolean}
   */
  isDisabled() {
    return !!(navigator.connection && navigator.connection.saveData)
  }

  /**
   * Attach hover-triggered prefetching to a link element. Cleans up
   * automatically when the element is removed (no listeners leak).
   * @param {HTMLElement} linkEl
   * @param {string} pageId
   */
  attachHover(linkEl, pageId) {
    if (this.isDisabled() || !linkEl || !pageId) return

    let timer = null
    const onEnter = () => {
      timer = setTimeout(() => this.prefetch(pageId), this.hoverDelay)
    }
    const onLeave = () => {
      if (timer) {
        clearTimeout(timer)
        timer = null
      }
    }
    linkEl.addEventListener('mouseenter', onEnter)
    linkEl.addEventListener('mouseleave', onLeave)
  }

  /**
   * Attach intersection-observer-triggered prefetching for touch/mobile
   * navigations where there is no hover signal. Uses a wide rootMargin
   * so prefetch starts before the link is fully on screen.
   * @param {HTMLElement} linkEl
   * @param {string} pageId
   */
  attachIntersection(linkEl, pageId) {
    if (this.isDisabled() || !linkEl || !pageId) return

    if (!this.observer) {
      this.observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              const id = entry.target.dataset.intravoxPrefetchId
              if (id) this.prefetch(id)
            }
          })
        },
        { rootMargin: '200px' }
      )
    }
    linkEl.dataset.intravoxPrefetchId = pageId
    this.observer.observe(linkEl)
  }

  /**
   * Queue a page-id for prefetch. No-op when the page is already in
   * the cache or already being fetched.
   * @param {string} pageId
   */
  prefetch(pageId) {
    if (this.isDisabled()) return
    if (!pageId) return
    if (CacheService.has(`page-${pageId}`)) return
    if (this.inFlight.has(pageId)) return
    if (this.queue.includes(pageId)) return

    this.queue.push(pageId)
    this.drain()
  }

  /**
   * Pull from the queue while we're under the concurrency cap.
   * Failures are silent — prefetch is opportunistic and must never
   * surface errors to the user.
   */
  drain() {
    while (this.active < this.maxConcurrent && this.queue.length > 0) {
      const pageId = this.queue.shift()
      this.fetchOne(pageId)
    }
  }

  async fetchOne(pageId) {
    this.active++
    this.inFlight.add(pageId)
    try {
      const response = await axios.get(
        generateUrl(`/apps/intravox/api/pages/${pageId}`)
      )
      if (response && response.data) {
        CacheService.set(`page-${pageId}`, response.data)
      }
    } catch (e) {
      // Silent: a failing prefetch is invisible to the user by design.
      // Real navigation will retry through the regular request path.
    } finally {
      this.active--
      this.inFlight.delete(pageId)
      this.drain()
    }
  }
}

export default new PrefetchService()
