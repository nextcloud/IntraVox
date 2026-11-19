/**
 * Format a feed item's date for display, honouring the widget's dateFormat.
 *
 * 'datetime' adds the time of day, which suits activity such as Talk
 * messages, where "when today" is the point. It only does so for items whose
 * source gave a time (item.dateHasTime): the server normalises a date-only
 * value to midnight UTC and a missing one to "now", and printing either as a
 * clock time would be invented precision. Those items fall back to the date
 * alone, so one feed can show both.
 *
 * Every source sets the flag, so an item without it was cached before the
 * flag existed. It is treated as having a time, as most sources timestamp
 * to the second; the cache expires it soon enough.
 *
 * @param {object} item feed item, with an ISO 8601 `date`
 * @param {string} dateFormat 'date' (default) or 'datetime'
 * @param {object} [style] Intl options for the date part
 * @return {string}
 */
export function formatFeedDate(item, dateFormat, style = { year: 'numeric', month: 'short', day: 'numeric' }) {
	if (!item?.date) return ''
	const date = new Date(item.date)
	if (isNaN(date.getTime())) return String(item.date)
	const locale = document.documentElement.lang || undefined
	const options = { ...style }
	if (dateFormat === 'datetime' && item.dateHasTime !== false) {
		options.hour = '2-digit'
		options.minute = '2-digit'
	}
	return date.toLocaleString(locale, options)
}
