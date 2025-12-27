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

    // Parse markdown (GFM configured globally above)
    const html = marked.parse(normalizedMarkdown);

    const result = DOMPurify.sanitize(html, {
      ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre', 'hr',
                     'table', 'thead', 'tbody', 'tr', 'th', 'td'],
      ALLOWED_ATTR: ['href', 'target', 'rel', 'class']
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
  const content = nodeToMarkdown(element, listDepth);

  switch (tag) {
    case 'p':
      return content + '\n\n';

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

    default:
      return content;
  }
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
    const indent = '  '.repeat(listDepth);
    const bullet = ordered ? `${index + 1}.` : '-';
    const content = nodeToMarkdown(li, listDepth + 1).trim();

    markdown += `${indent}${bullet} ${content}\n`;
  });

  if (listDepth === 0) {
    markdown += '\n';
  }

  return markdown;
}

/**
 * Clean up markdown formatting
 * Preserves table structure while removing excessive newlines elsewhere
 */
export function cleanMarkdown(markdown) {
  if (!markdown) return '';

  // Split into lines and process
  const lines = markdown.split('\n');
  const result = [];
  let consecutiveEmpty = 0;
  let inTable = false;

  for (const line of lines) {
    const isTableRow = line.trim().startsWith('|') && line.trim().endsWith('|');
    const isEmpty = line.trim() === '';

    if (isTableRow) {
      inTable = true;
      consecutiveEmpty = 0;
      result.push(line);
    } else if (isEmpty) {
      if (inTable) {
        // End of table, allow one blank line after
        inTable = false;
        result.push(line);
        consecutiveEmpty = 1;
      } else {
        consecutiveEmpty++;
        // Allow max 1 empty line (which creates 2 newlines = paragraph break)
        if (consecutiveEmpty <= 1) {
          result.push(line);
        }
      }
    } else {
      inTable = false;
      consecutiveEmpty = 0;
      result.push(line);
    }
  }

  return result.join('\n').trim();
}
