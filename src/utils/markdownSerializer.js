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
        // Use a zero-width space (invisible but creates a paragraph)
        result.push('\u200B');
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
    const html = marked.parse(preservedMarkdown);

    const result = DOMPurify.sanitize(html, {
      ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre', 'hr',
                     'table', 'thead', 'tbody', 'tr', 'th', 'td',
                     'details', 'summary', 'details-content'],
      ALLOWED_ATTR: ['href', 'target', 'rel', 'class', 'open']
    });

    // Store in cache with LRU eviction
    if (markdownCache.size >= MAX_CACHE_SIZE) {
      const firstKey = markdownCache.keys().next().value;
      markdownCache.delete(firstKey);
    }
    markdownCache.set(markdown, result);

    return result;
  } catch (err) {
    console.error('Markdown parsing error:', err);
    return markdown;
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
    case 'p':
      // Empty paragraph (with or without <br>) should produce a blank line
      // content will be '\n' if it contains only <br>, or '' if truly empty
      // Zero-width space (\u200B) is used as a placeholder for preserved empty lines
      // All of these should result in an empty line in the markdown
      const trimmedContent = content.replace(/\u200B/g, '').trim();
      // If content is only zero-width spaces or empty, treat as empty paragraph
      if (trimmedContent === '' || trimmedContent === '\n') {
        return '\n\n';
      }
      return content.replace(/\u200B/g, '') + '\n\n';

    case 'br':
      return '\n';

    case 'strong':
    case 'b':
      return `**${content}**`;

    case 'em':
    case 'i':
      return `*${content}*`;

    case 'u':
      // Markdown doesn't have native underline, use HTML
      return `<u>${content}</u>`;

    case 's':
    case 'del':
    case 'strike':
      return `~~${content}~~`;

    case 'h1':
      return `# ${content}\n\n`;

    case 'h2':
      return `## ${content}\n\n`;

    case 'h3':
      return `### ${content}\n\n`;

    case 'h4':
      return `#### ${content}\n\n`;

    case 'h5':
      return `##### ${content}\n\n`;

    case 'h6':
      return `###### ${content}\n\n`;

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
      return `\`\`\`\n${content}\n\`\`\`\n\n`;

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
  // Tables need a blank line before them in Markdown for proper parsing
  let markdown = '\n\n';
  const rows = tableElement.querySelectorAll('tr');

  rows.forEach((row, rowIndex) => {
    const cells = row.querySelectorAll('th, td');
    const cellContents = Array.from(cells).map(cell => nodeToMarkdown(cell).trim());

    // Table row
    markdown += '| ' + cellContents.join(' | ') + ' |\n';

    // Header separator after first row
    if (rowIndex === 0) {
      markdown += '| ' + cellContents.map(() => '---').join(' | ') + ' |\n';
    }
  });

  return markdown + '\n';
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
 * Preserves table structure and user-intended blank lines
 */
export function cleanMarkdown(markdown) {
  if (!markdown) return '';

  // Split into lines and process
  const lines = markdown.split('\n');
  const result = [];
  let inTable = false;

  for (const line of lines) {
    const isTableRow = line.trim().startsWith('|') && line.trim().endsWith('|');

    if (isTableRow) {
      inTable = true;
      result.push(line);
    } else {
      if (inTable && line.trim() === '') {
        // End of table
        inTable = false;
      }
      // Preserve all lines including empty ones (user-intended spacing)
      result.push(line);
    }
  }

  // Join lines and only trim leading/trailing whitespace, but preserve internal blank lines
  return result.join('\n').trim();
}
