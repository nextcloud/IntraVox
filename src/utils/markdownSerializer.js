/**
 * Markdown Serializer for TipTap
 * Converts between TipTap JSON/HTML and Markdown
 */

import { marked } from 'marked';
import DOMPurify from 'dompurify';

// Configure marked for GFM (GitHub Flavored Markdown) with tables
// Use synchronous parsing to avoid Promise issues
marked.use({
  gfm: true,
  breaks: true,
  async: false  // Force synchronous parsing
});

/**
 * Simple LRU cache for markdown parsing results
 * Avoids re-parsing the same content on every render
 */
const markdownCache = new Map();
const MAX_CACHE_SIZE = 100;

/**
 * Escape HTML special characters to prevent XSS and raw markdown display
 */
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, char => map[char]);
}

/**
 * Normalize markdown to ensure tables parse correctly
 * Tables need blank lines before them, but NOT between rows
 */
function normalizeMarkdownTables(markdown) {
  const lines = markdown.split('\n');
  const result = [];
  let inTable = false;

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const isTableRow = line.trim().startsWith('|') && line.trim().endsWith('|');
    const prevLine = result.length > 0 ? result[result.length - 1] : '';
    const prevIsTableRow = prevLine.trim().startsWith('|') && prevLine.trim().endsWith('|');
    const prevIsEmpty = prevLine.trim() === '';

    if (isTableRow && !inTable) {
      // Starting a new table - ensure blank line before it (unless at start or already blank)
      if (result.length > 0 && !prevIsEmpty) {
        result.push('');
      }
      inTable = true;
    } else if (!isTableRow && inTable) {
      // Leaving a table
      inTable = false;
    }

    result.push(line);
  }

  return result.join('\n');
}

/**
 * Preserve empty lines in markdown by converting them to zero-width space paragraphs
 * This prevents marked from collapsing multiple blank lines into one
 *
 * In Markdown, multiple blank lines are treated as a single paragraph break.
 * But users expect their empty lines to be preserved for visual spacing.
 *
 * Solution: Replace each "extra" empty line with a paragraph containing a zero-width space.
 * This creates actual <p> tags that render as empty visual lines.
 */
function preserveEmptyLines(markdown) {
  const lines = markdown.split('\n');
  const result = [];
  let consecutiveEmpty = 0;

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const isEmpty = line.trim() === '';

    if (isEmpty) {
      consecutiveEmpty++;
      // First empty line is the normal paragraph separator (keep as is)
      // Additional empty lines need to become actual content to be preserved
      if (consecutiveEmpty > 1) {
        // Check if the next non-empty line starts a table.
        // Tables need a real blank line before them (\n\n), not a ZWS paragraph,
        // otherwise marked won't parse them as tables.
        let nextNonEmpty = '';
        for (let j = i + 1; j < lines.length; j++) {
          if (lines[j].trim() !== '') {
            nextNonEmpty = lines[j].trim();
            break;
          }
        }
        const nextIsTable = nextNonEmpty.startsWith('|') && nextNonEmpty.endsWith('|');
        if (nextIsTable) {
          result.push('');
        } else {
          result.push('\u200B');
        }
      } else {
        result.push(line);
      }
    } else {
      consecutiveEmpty = 0;
      result.push(line);
    }
  }

  return result.join('\n');
}

/**
 * Convert Markdown to HTML for TipTap
 * OPTIMIZED: Results are cached to avoid repeated parsing
 */
/**
 * Read-mode table post-processor. Runs after DOMPurify on the rendered HTML.
 *
 * - Strips TipTap's auto-generated `style="width: …px"` from <table> (TipTap
 *   writes that on every save based on summed colwidths) and rebuilds a
 *   minimal style from the user-set `data-table-width` / `data-table-align`
 *   attributes set by the custom Table extension.
 * - Collects column widths from cell `data-colwidth`/`colwidth` attributes
 *   AND from any existing <col style="width: Xpx">, then converts them to
 *   percentages so the table always fits its container — pixel widths can
 *   sum higher than the container in narrow page-rows and would otherwise
 *   overflow even with `table-layout: fixed`.
 * - Wraps every table in a `<div class="tableWrapper">` (same class TipTap
 *   uses in edit-mode) so wide tables get horizontal scroll inside the widget
 *   instead of pushing the page layout sideways.
 *
 * Idempotent: a tableWrapper already in place is reused.
 */
function hydrateTables(html) {
  if (!html || html.indexOf('<table') === -1) return html;
  const doc = new DOMParser().parseFromString(`<div>${html}</div>`, 'text/html');
  const root = doc.body.firstElementChild;
  if (!root) return html;

  for (const table of Array.from(root.querySelectorAll('table'))) {
    // 1. Reset <table> inline style; rebuild only from user-set attributes.
    const userWidth = table.getAttribute('data-table-width');
    const userAlign = table.getAttribute('data-table-align');
    const styles = [];
    if (userWidth) styles.push(`width: ${userWidth}`);
    if (userAlign === 'center') styles.push('margin-left: auto', 'margin-right: auto');
    else if (userAlign === 'right') styles.push('margin-left: auto', 'margin-right: 0');
    else if (userAlign === 'left') styles.push('margin-left: 0', 'margin-right: auto');
    if (styles.length > 0) table.setAttribute('style', styles.join('; '));
    else table.removeAttribute('style');

    // 2. Normalize colgroup. Read widths from BOTH cell-level data-colwidth and
    //    existing <col style="width:Xpx">, convert to percentages so the table
    //    always fits its container regardless of stored pixel widths.
    const existingColgroup = table.querySelector('colgroup');
    const existingCols = existingColgroup ? Array.from(existingColgroup.children) : [];
    const firstRow = table.querySelector('tr');
    if (firstRow) {
      const cells = Array.from(firstRow.children);
      const widths = cells.map((cell, i) => {
        // Modern data-colwidth or legacy colwidth on the cell
        const cellRaw = cell.getAttribute('data-colwidth') || cell.getAttribute('colwidth');
        if (cellRaw) {
          const first = String(cellRaw).split(',')[0].trim();
          const px = Number.parseInt(first, 10);
          if (Number.isFinite(px) && px > 0) return px;
        }
        // Fallback to existing <col style="width: Xpx">
        const col = existingCols[i];
        if (col) {
          const m = (col.getAttribute('style') || '').match(/width\s*:\s*(\d+(?:\.\d+)?)\s*px/);
          if (m) {
            const px = Number.parseFloat(m[1]);
            if (Number.isFinite(px) && px > 0) return px;
          }
        }
        return null;
      });

      if (widths.some((w) => w !== null)) {
        // Convert to percentages relative to total share. Empty cols get a
        // proportional fallback share so a 3-col table with 1 empty col still
        // sums to 100%.
        const knownSum = widths.reduce((s, w) => s + (w ?? 0), 0);
        const knownCount = widths.filter((w) => w !== null).length;
        const emptyCount = widths.length - knownCount;
        const totalShare = knownSum + emptyCount * (knownSum / Math.max(1, knownCount));
        const colgroup = doc.createElement('colgroup');
        for (const w of widths) {
          const col = doc.createElement('col');
          if (w !== null) {
            col.setAttribute('style', `width: ${(w / totalShare * 100).toFixed(2)}%`);
          } else if (knownCount > 0) {
            col.setAttribute('style', `width: ${(knownSum / knownCount / totalShare * 100).toFixed(2)}%`);
          }
          colgroup.appendChild(col);
        }
        if (existingColgroup) existingColgroup.replaceWith(colgroup);
        else table.insertBefore(colgroup, table.firstChild);
      } else if (existingColgroup) {
        // Empty <col>s without widths — drop the colgroup so CSS distributes evenly.
        const allEmpty = existingCols.every((c) => !c.getAttribute('style') && !c.getAttribute('width'));
        if (allEmpty) existingColgroup.remove();
      }
    }

    // 3. Wrap in scroll container (idempotent).
    const parent = table.parentElement;
    if (!parent || !parent.classList.contains('tableWrapper')) {
      const wrap = doc.createElement('div');
      wrap.className = 'tableWrapper';
      table.parentNode.insertBefore(wrap, table);
      wrap.appendChild(table);
    }
  }

  return root.innerHTML;
}

export function markdownToHtml(markdown) {
  if (!markdown) return '';

  // Check cache first
  if (markdownCache.has(markdown)) {
    return markdownCache.get(markdown);
  }

  try {
    // Normalize tables: ensure blank line before table, but not between rows
    const normalizedMarkdown = normalizeMarkdownTables(markdown);

    // Preserve empty lines by converting them to special markers before parsing
    // Markdown collapses multiple blank lines into one, but we want to keep them
    // Strategy: Replace sequences of empty lines with placeholder paragraphs
    const preservedMarkdown = preserveEmptyLines(normalizedMarkdown);

    // Parse markdown (GFM configured globally above)
    let html = marked.parse(preservedMarkdown);

    // Validate that marked returned HTML, not raw markdown
    // If the result looks like raw markdown (contains unprocessed emphasis markers),
    // escape it to prevent asterisks from showing up.
    // Skip this check if content is already HTML (starts with <) — marked passes
    // HTML blocks through unchanged, which is correct behavior, not a parse failure.
    if (typeof html !== 'string' || (html === preservedMarkdown && !html.trimStart().startsWith('<'))) {
      // Parse failed silently - escape the content to prevent raw markdown display
      const escaped = escapeHtml(markdown);
      return `<p>${escaped}</p>`;
    }

    // Convert alignment comment markers to CSS classes on the following element.
    // Format: <!-- align:center -->\n<p>text</p> → <p class="text-align-center">text</p>
    // Also handle legacy HTML block format: <p class="text-align-center">text</p>
    html = html.replace(/<!--\s*align:(center|right)\s*-->\s*<(p|h[1-6])>/g,
      (_, align, tag) => `<${tag} class="text-align-${align}">`);

    const sanitized = DOMPurify.sanitize(html, {
      ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre', 'hr',
                     'table', 'thead', 'tbody', 'tr', 'th', 'td', 'colgroup', 'col',
                     'details', 'summary', 'details-content', 'div'],
      ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'open', 'style', 'colspan', 'rowspan',
                     'colwidth', 'data-colwidth', 'data-table-width', 'data-table-align']
    });

    // Post-process tables for read-mode (colgroup hydration + scroll wrapper).
    const result = hydrateTables(sanitized);

    // Store in cache with LRU eviction
    if (markdownCache.size >= MAX_CACHE_SIZE) {
      const firstKey = markdownCache.keys().next().value;
      markdownCache.delete(firstKey);
    }
    markdownCache.set(markdown, result);

    return result;
  } catch (err) {
    console.error('Markdown parsing error:', err);
    // On error, escape the content to prevent raw markdown/asterisks from showing
    const escaped = escapeHtml(markdown);
    return `<p>${escaped}</p>`;
  }
}

/**
 * Convert HTML to Markdown
 * Simplified conversion that handles the most common cases
 */
export function htmlToMarkdown(html) {
  if (!html) return '';

  // Create a temporary element to parse HTML
  const temp = document.createElement('div');
  temp.innerHTML = html;

  return nodeToMarkdown(temp);
}

function nodeToMarkdown(node, listDepth = 0) {
  let markdown = '';

  for (const child of node.childNodes) {
    if (child.nodeType === Node.TEXT_NODE) {
      markdown += child.textContent;
    } else if (child.nodeType === Node.ELEMENT_NODE) {
      markdown += elementToMarkdown(child, listDepth);
    }
  }

  return markdown;
}

/**
 * Check if element has a non-default text alignment class
 * Returns 'center' or 'right', or null for default (left)
 */
function getAlignmentFromClass(element) {
  if (element.classList?.contains('text-align-center')) return 'center';
  if (element.classList?.contains('text-align-right')) return 'right';
  return null;
}

function elementToMarkdown(element, listDepth = 0) {
  const tag = element.tagName.toLowerCase();

  // Check for TipTap NodeView data-type attributes first
  const dataType = element.getAttribute('data-type');
  if (dataType === 'details') {
    return convertTipTapDetails(element);
  }
  if (dataType === 'detailsContent') {
    // Content is handled by convertTipTapDetails
    return nodeToMarkdown(element, listDepth);
  }

  const content = nodeToMarkdown(element, listDepth);

  switch (tag) {
    case 'p': {
      // Empty paragraph (with or without <br>) should produce a blank line
      // content will be '\n' if it contains only <br>, or '' if truly empty
      // Zero-width space (\u200B) is used as a placeholder for preserved empty lines
      const trimmedContent = content.replace(/\u200B/g, '').trim();
      if (trimmedContent === '' || trimmedContent === '\n') {
        return '\n\n';
      }
      const pAlign = getAlignmentFromClass(element);
      if (pAlign) {
        // Store aligned content as HTML block with innerHTML (not markdown).
        // We use innerHTML because marked doesn't process markdown syntax
        // inside HTML blocks — so the content must already be HTML.
        return `<p class="text-align-${pAlign}">${element.innerHTML}</p>\n\n`;
      }
      return content.replace(/\u200B/g, '') + '\n\n';
    }

    case 'br':
      return '\n';

    case 'strong':
    case 'b':
      // Use HTML tags when content contains HTML (e.g., <u>) to avoid
      // mixing markdown emphasis markers with HTML tags, which breaks parsing
      if (content.includes('<')) {
        return `<strong>${content}</strong>`;
      }
      return `**${content}**`;

    case 'em':
    case 'i':
      if (content.includes('<')) {
        return `<em>${content}</em>`;
      }
      return `*${content}*`;

    case 'u':
      // Markdown doesn't have native underline, use HTML
      return `<u>${content}</u>`;

    case 's':
    case 'del':
    case 'strike':
      return `~~${content}~~`;

    case 'h1': {
      const h1Align = getAlignmentFromClass(element);
      if (h1Align) return `<h1 class="text-align-${h1Align}">${element.innerHTML}</h1>\n\n`;
      return `# ${content}\n\n`;
    }

    case 'h2': {
      const h2Align = getAlignmentFromClass(element);
      if (h2Align) return `<h2 class="text-align-${h2Align}">${element.innerHTML}</h2>\n\n`;
      return `## ${content}\n\n`;
    }

    case 'h3': {
      const h3Align = getAlignmentFromClass(element);
      if (h3Align) return `<h3 class="text-align-${h3Align}">${element.innerHTML}</h3>\n\n`;
      return `### ${content}\n\n`;
    }

    case 'h4': {
      const h4Align = getAlignmentFromClass(element);
      if (h4Align) return `<h4 class="text-align-${h4Align}">${element.innerHTML}</h4>\n\n`;
      return `#### ${content}\n\n`;
    }

    case 'h5': {
      const h5Align = getAlignmentFromClass(element);
      if (h5Align) return `<h5 class="text-align-${h5Align}">${element.innerHTML}</h5>\n\n`;
      return `##### ${content}\n\n`;
    }

    case 'h6': {
      const h6Align = getAlignmentFromClass(element);
      if (h6Align) return `<h6 class="text-align-${h6Align}">${element.innerHTML}</h6>\n\n`;
      return `###### ${content}\n\n`;
    }

    case 'ul':
      return convertList(element, listDepth, false);

    case 'ol':
      return convertList(element, listDepth, true);

    case 'li':
      // List items are handled by convertList
      return content;

    case 'a':
      const href = element.getAttribute('href') || '';
      return `[${content}](${href})`;

    case 'blockquote':
      return content.split('\n').map(line => `> ${line}`).join('\n') + '\n\n';

    case 'code':
      return `\`${content}\``;

    case 'pre':
      // TipTap wraps code blocks as <pre><code>content</code></pre>
      // Get raw text content to avoid double-processing the nested <code> tag
      const codeElement = element.querySelector('code');
      const codeContent = codeElement ? codeElement.textContent : element.textContent;
      return `\`\`\`\n${codeContent}\n\`\`\`\n\n`;

    case 'hr':
      return '---\n\n';

    case 'table':
      return convertTable(element);

    case 'input':
      // Task list checkbox
      if (element.getAttribute('type') === 'checkbox') {
        return element.checked ? '[x] ' : '[ ] ';
      }
      return '';

    case 'button':
      // Skip buttons (e.g., TipTap details toggle button)
      return '';

    case 'details':
      // Preserve details as raw HTML in markdown (no native markdown syntax for this)
      return convertDetails(element);

    case 'summary':
      // Summary is handled by convertDetails
      return content;

    case 'details-content':
      // TipTap uses custom element for details content, convert it
      return content;

    default:
      return content;
  }
}

/**
 * Convert TipTap NodeView details structure to HTML
 * TipTap renders: <div data-type="details"> with <summary> and <div data-type="detailsContent">
 *
 * Content inside details is stored as HTML (not markdown) because:
 * 1. Markdown parsers don't parse content inside HTML tags
 * 2. This ensures tables, lists, etc. render correctly inside details
 */
function convertTipTapDetails(detailsElement) {
  // TipTap structure: div[data-type="details"] > button + div > summary + div[data-type="detailsContent"]
  const summary = detailsElement.querySelector('summary');
  const content = detailsElement.querySelector('[data-type="detailsContent"]');

  const summaryText = summary ? summary.textContent.trim() : 'Details';

  // Get the inner HTML of content, preserving HTML structure for tables, lists, etc.
  // We need to get the actual HTML content, not convert to markdown
  let contentHtml = '';
  if (content) {
    // Clone the content to avoid modifying the original
    const clone = content.cloneNode(true);
    contentHtml = clone.innerHTML.trim();
  }

  // Convert to standard HTML <details> for markdown storage
  // Content is stored as HTML to preserve tables, formatting, etc.
  return `\n\n<details>\n<summary>${summaryText}</summary>\n\n${contentHtml}\n\n</details>\n\n`;
}

/**
 * Convert native HTML details element to markdown-embedded HTML
 * Since markdown doesn't have native collapsible syntax, we preserve the HTML
 * Content is stored as HTML to preserve tables, formatting, etc.
 */
function convertDetails(detailsElement) {
  const summary = detailsElement.querySelector('summary');
  const summaryText = summary ? summary.textContent.trim() : 'Details';

  // Get HTML content from children that are not summary
  let contentHtml = '';
  for (const child of detailsElement.children) {
    if (child.tagName.toLowerCase() !== 'summary') {
      contentHtml += child.outerHTML;
    }
  }
  contentHtml = contentHtml.trim();

  return `\n\n<details>\n<summary>${summaryText}</summary>\n\n${contentHtml}\n\n</details>\n\n`;
}

function convertTable(tableElement) {
  // Store tables as HTML to preserve column widths, line breaks,
  // and formatting that GFM markdown cannot represent
  return tableElement.outerHTML + '\n\n';
}

function convertList(listElement, listDepth, ordered) {
  let markdown = '';
  const items = listElement.querySelectorAll(':scope > li');

  items.forEach((li, index) => {
    // Use 3 spaces for indentation (works better with marked parser for nested lists)
    const indent = '   '.repeat(listDepth);
    const bullet = ordered ? `${index + 1}.` : '-';

    // Separate text content from nested lists
    let textContent = '';
    let nestedLists = '';

    for (const child of li.childNodes) {
      if (child.nodeType === Node.TEXT_NODE) {
        textContent += child.textContent;
      } else if (child.nodeType === Node.ELEMENT_NODE) {
        const tagName = child.tagName.toLowerCase();
        if (tagName === 'ul' || tagName === 'ol') {
          // Process nested list separately
          nestedLists += convertList(child, listDepth + 1, tagName === 'ol');
        } else {
          textContent += nodeToMarkdown(child, listDepth + 1);
        }
      }
    }

    markdown += `${indent}${bullet} ${textContent.trim()}\n`;
    if (nestedLists) {
      markdown += nestedLists;
    }
  });

  if (listDepth === 0) {
    markdown += '\n';
  }

  return markdown;
}

/**
 * Clean up markdown formatting
 * Normalizes spacing around tables to prevent doubling on each save
 */
export function cleanMarkdown(markdown) {
  if (!markdown) return '';

  // Normalize: collapse 4+ consecutive newlines into exactly 3.
  // \n\n = standard paragraph break (no visual blank line)
  // \n\n\n = one visual blank line (user intentionally added spacing)
  // \n\n\n\n+ = too many — TipTap adds phantom <p> between block nodes on each
  //   save cycle, which generates an extra \n\n. Cap at \n\n\n to stop growth.
  let cleaned = markdown.replace(/\n{4,}/g, '\n\n\n');

  return cleaned.trim();
}
