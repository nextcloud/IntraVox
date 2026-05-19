/**
 * Enhanced cache service with localStorage persistence for IntraVox
 * Caches pages, navigation, and breadcrumbs to reduce API calls
 */
class CacheService {
  constructor() {
    this.cache = new Map();
    this.ttl = 5 * 60 * 1000; // 5 minutes default TTL
    this.persistentKeys = ['pages-list', 'navigation', 'footer'];
    this.storagePrefix = 'intravox_cache_';

    // Restore from localStorage on init
    this.restoreFromStorage();
  }

  /**
   * Restore cached items from localStorage
   */
  restoreFromStorage() {
    try {
      this.persistentKeys.forEach(key => {
        const stored = localStorage.getItem(this.storagePrefix + key);
        if (stored) {
          const { value, expiry } = JSON.parse(stored);
          if (Date.now() < expiry) {
            this.cache.set(key, { value, expiry });
          } else {
            localStorage.removeItem(this.storagePrefix + key);
          }
        }
      });
    } catch (e) {
      // localStorage not available or corrupt - silent fail
    }
  }

  /**
   * Get item from cache
   * @param {string} key - Cache key
   * @returns {any|null} Cached value or null if expired/not found
   */
  get(key) {
    const item = this.cache.get(key);

    if (!item) {
      return null;
    }

    // Check if expired
    if (Date.now() > item.expiry) {
      this.cache.delete(key);
      return null;
    }

    return item.value;
  }

  /**
   * Get age of cached item in milliseconds
   * @param {string} key - Cache key
   * @returns {number|null} Age in ms, or null if not cached
   */
  getAge(key) {
    const item = this.cache.get(key);
    if (!item) return null;

    // Calculate age: current time minus when it was set
    // expiry = setTime + ttl, so setTime = expiry - ttl
    const setTime = item.expiry - this.ttl;
    return Date.now() - setTime;
  }

  /**
   * Set item in cache
   * @param {string} key - Cache key
   * @param {any} value - Value to cache
   * @param {number} ttl - Time to live in milliseconds (optional)
   */
  set(key, value, ttl = this.ttl) {
    const item = {
      value,
      expiry: Date.now() + ttl
    };
    this.cache.set(key, item);

    // Persist important keys to localStorage with LRU eviction on quota
    // pressure: when the browser refuses our write we drop the oldest
    // persistent entry and retry once, rather than silently losing the
    // current set call.
    if (this.persistentKeys.includes(key)) {
      try {
        localStorage.setItem(this.storagePrefix + key, JSON.stringify(item));
      } catch (e) {
        if (e && e.name === 'QuotaExceededError') {
          this.evictOldestPersistent();
          try {
            localStorage.setItem(this.storagePrefix + key, JSON.stringify(item));
          } catch (e2) {
            // Quota still full — give up silently, in-memory cache still holds it
          }
        }
        // Other errors (localStorage disabled, etc) — silent fail
      }
    }
  }

  /**
   * Remove the persistent entry with the soonest expiry. Used by set()
   * when the browser rejects a write due to storage quota.
   */
  evictOldestPersistent() {
    let oldestKey = null
    let oldestExpiry = Infinity
    this.persistentKeys.forEach((k) => {
      try {
        const stored = localStorage.getItem(this.storagePrefix + k);
        if (!stored) return;
        const parsed = JSON.parse(stored);
        if (parsed && parsed.expiry < oldestExpiry) {
          oldestExpiry = parsed.expiry;
          oldestKey = k;
        }
      } catch (e) {
        // Skip corrupt entries
      }
    });
    if (oldestKey) {
      try {
        localStorage.removeItem(this.storagePrefix + oldestKey);
      } catch (e) {
        // localStorage gone — nothing left to do
      }
    }
  }

  /**
   * Check if key exists and is not expired
   * @param {string} key - Cache key
   * @returns {boolean}
   */
  has(key) {
    return this.get(key) !== null;
  }

  /**
   * Remove item from cache
   * @param {string} key - Cache key
   */
  delete(key) {
    this.cache.delete(key);
    if (this.persistentKeys.includes(key)) {
      try {
        localStorage.removeItem(this.storagePrefix + key);
      } catch (e) {
        // localStorage not available
      }
    }
  }

  /**
   * Clear entire cache
   */
  clear() {
    this.cache.clear();
    try {
      this.persistentKeys.forEach(key => {
        localStorage.removeItem(this.storagePrefix + key);
      });
    } catch (e) {
      // localStorage not available
    }
  }

  /**
   * Invalidate cache items by prefix
   * @param {string} prefix - Key prefix to invalidate
   */
  invalidateByPrefix(prefix) {
    const keys = Array.from(this.cache.keys());
    keys.forEach(key => {
      if (key.startsWith(prefix)) {
        this.delete(key);
      }
    });
  }

  /**
   * Get cache statistics
   * @returns {object} Cache stats
   */
  getStats() {
    return {
      size: this.cache.size,
      keys: Array.from(this.cache.keys())
    };
  }
}

// Export singleton instance
export default new CacheService();
