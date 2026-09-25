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

/**
 * Feeds per request, and why it is not a constant.
 *
 * The two costs pull in opposite directions, and which one dominates depends
 * entirely on whether the feeds are cached:
 *
 *   COLD  the server fetches a batch's feeds one after another (PHP cannot run
 *         them concurrently through Nextcloud's HTTP client), so one big batch
 *         is one long serial queue. Measured on dev with 15 cold feeds: 5061 ms
 *         in one request against 2380 ms split over three.
 *
 *   WARM  a cached feed costs ~0.1 ms. Fifteen of them are 1.6 ms, and the
 *         request around them is the whole cost. Splitting then makes the page
 *         SLOWER — 65 ms as one batch against 98 ms as three — and asks three
 *         PHP workers to do one worker's job.
 *
 * Warm is the normal case by a wide margin. The RSS cache key carries no user,
 * so a page of feeds is warmed by whoever opens it first and every reader after
 * them is served from it for CACHE_TTL. On a busy intranet the cold path is
 * one reader in hundreds; optimising for it at the expense of the warm path is
 * how a change that helps one person costs the other 299 three times the
 * server.
 *
 * So the size adapts. A page starts at MAX_PER_REQUEST — one request, the warm
 * shape — and drops to COLD_CHUNK only after the server says a response was
 * actually cold. The signal is already in the payload: each slot carries
 * `cached`, set by FeedReaderService on every path.
 */
const MAX_PER_REQUEST = 20;

/**
 * Feeds per request once a cold response has been seen.
 *
 * Not the fastest number: three-per-request was quicker still on dev, but at
 * that point upstream hosts start refusing connections (9 of 15 feeds came back
 * empty) and every extra request costs one of the 30 per minute the rate limit
 * allows. Five keeps a 15-widget page at 3 requests.
 */
const COLD_CHUNK = 5;

/**
 * How long a page keeps splitting after it saw a cold response.
 *
 * Long enough to cover the rest of this page load and a reader clicking
 * straight through to a sibling page, short enough that a page which was cold
 * once does not keep paying three requests for the rest of the session.
 */
const COLD_MEMORY_MS = 30_000;

/** One queue per share context: a share's feeds go to a different endpoint. */
const queues = new Map();

function queueFor(shareToken) {
  const key = shareToken || '';
  if (!queues.has(key)) {
    // coldUntil: while in the future, requests are split into COLD_CHUNK.
    queues.set(key, { pending: [], timer: null, coldUntil: 0 });
  }
  return queues.get(key);
}

/** Feeds to put in the next request: small only while the feeds are cold. */
function chunkSize(q) {
  return Date.now() < q.coldUntil ? COLD_CHUNK : MAX_PER_REQUEST;
}

/**
 * Remember that the server had to fetch, so the next requests are split.
 *
 * One uncached slot is enough: the feeds on a page tend to expire together
 * (they were warmed together), so the first cold answer predicts the rest.
 */
function noteColdness(slots) {
  return slots.some((s) => s && s.cached === false);
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
  const batch = q.pending.splice(0, chunkSize(q));
  q.timer = null;

  // More than one request worth: send the rest ALONGSIDE this one, not after it.
  // Each request is fetched serially on the server, so chaining them behind
  // another 50ms timer made a page of 45 widgets wait for three requests in a
  // row — measured at 13806 ms against 8787 ms for the same three in parallel.
  // Recursing here keeps draining until the queue is empty, and every chunk is
  // in flight at once.
  //
  // Note what this does NOT do: the FIRST request of a cold page is already on
  // its way before any answer can say the page is cold, so that one reader
  // still waits for one serial batch. Every reader after them is warm and pays
  // a single cheap request. Splitting pre-emptively would invert that trade —
  // three workers for everyone, to help the one.
  if (q.pending.length > 0) {
    flush(shareToken);
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
    // A cold answer means the server did real fetching, so the requests after
    // this one are split. A warm page never takes this branch and keeps paying
    // for a single request.
    if (noteColdness(Object.values(uit))) {
      q.coldUntil = Date.now() + COLD_MEMORY_MS;
    }
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
