import axios from '@nextcloud/axios';
import { generateUrl } from '@nextcloud/router';

/**
 * Collects the feed requests a page makes and sends them as one.
 *
 * Every feed widget used to fetch on its own. Measured on the rss page: three
 * widgets produced nine requests once re-renders were counted, and a page with
 * five widgets spent five round trips before it settled. Each of those counts
 * against a rate limit that is applied per IP address, so an intranet behind
 * one outgoing address runs out after a dozen page views — the limit is 60 a
 * minute and a page costs five.
 *
 * Widgets keep asking for their own feed; this holds each request for a short
 * window and sends whatever accumulated. Nothing about a widget's code has to
 * know it is being batched, which is why the batching lives here and not in
 * the component.
 *
 * A failure in one feed never fails the others: the server answers per slot,
 * and a slot that errored resolves with its own error rather than rejecting
 * the batch.
 */

/** Matches FeedRequestTrait::MAX_BATCH_FEEDS. */
const MAX_PER_BATCH = 20;

/** One queue per share context: a share's feeds go to a different endpoint. */
const queues = new Map();

function queueFor(shareToken) {
  const key = shareToken || '';
  if (!queues.has(key)) {
    queues.set(key, { pending: [], timer: null });
  }
  return queues.get(key);
}

/**
 * The selectors a feed is addressed by.
 *
 * Only non-empty values are sent: the server treats an empty selector as
 * "unconstrained", and sending empty strings would make the payload noisier
 * without changing the result.
 */
function specFor(widget) {
  const spec = { sourceType: widget.sourceType || 'rss' };

  const velden = {
    feedUrl: 'url',
    connectionId: 'connectionId',
    courseId: 'courseId',
    contentType: 'contentType',
    jiraProject: 'jiraProject',
    moodleForumId: 'moodleForumId',
    listId: 'listId',
    sortBy: 'sortBy',
    sortOrder: 'sortOrder',
    filterKeyword: 'filterKeyword',
  };
  for (const [van, naar] of Object.entries(velden)) {
    if (widget[van]) {
      spec[naar] = widget[van];
    }
  }
  spec.limit = widget.limit || 5;

  return spec;
}

async function flush(shareToken) {
  const q = queueFor(shareToken);
  const batch = q.pending.splice(0, MAX_PER_BATCH);
  q.timer = null;

  // More than one batch worth: send the rest on the next tick rather than
  // raising the server's ceiling.
  if (q.pending.length > 0) {
    schedule(shareToken);
  }
  if (batch.length === 0) {
    return;
  }

  const url = shareToken
    ? generateUrl(`/apps/intravox/api/share/${shareToken}/feed/batch`)
    : generateUrl('/apps/intravox/api/feed/batch');

  const feeds = {};
  batch.forEach((entry, i) => {
    feeds[String(i)] = specFor(entry.widget);
  });

  try {
    const response = await axios.post(url, { feeds });
    const uit = response.data?.feeds || {};
    batch.forEach((entry, i) => {
      // A slot the server did not answer is not an error the widget can act
      // on; treat it as an empty feed so the widget shows its own message.
      entry.resolve(uit[String(i)] || { items: [], error: 'No response for this feed' });
    });
  } catch (err) {
    // One transport failure fails every slot in this batch — they shared a
    // request, so there is nothing per-feed to say.
    batch.forEach((entry) => entry.reject(err));
  }
}

function schedule(shareToken) {
  const q = queueFor(shareToken);
  if (q.timer !== null) {
    return;
  }
  // A short window rather than the next tick. Measured on dev: with 0 ms a
  // page of three widgets still produced three batches, because Vue mounts
  // them across separate frames and each landed in its own timeout. 50 ms
  // collects the whole page and stays below what a reader notices — the
  // fetch behind it takes an order of magnitude longer anyway.
  q.timer = setTimeout(() => flush(shareToken), 50);
}

/**
 * Ask for one widget's feed. Resolves with the same shape a single fetch gives.
 *
 * @param {object} widget the widget config
 * @param {string} shareToken share token, or '' when logged in
 * @return {Promise<object>} { items, source, cached, stale, fetchedAt, error? }
 */
export function fetchFeedBatched(widget, shareToken = '') {
  return new Promise((resolve, reject) => {
    queueFor(shareToken).pending.push({ widget, resolve, reject });
    schedule(shareToken);
  });
}
