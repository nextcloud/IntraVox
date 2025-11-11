/**
 * Markdown Serializer for TipTap
 * Converts between TipTap JSON/HTML and Markdown
 */

import { marked } from 'marked';
import DOMPurify from 'dompurify';

/**
 * Convert Markdown to HTML for TipTap
 */
export function markdownToHtml(markdown) {
  if (!markdown) return '';

  try {
    const html = marked(markdown, {
      breaks: true,
      gfm: true
    });

    return DOMPurify.sanitize(html, {
      ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'code', 'pre', 'hr'],
      ALLOWED_ATTR: ['href', 'target', 'rel', 'class']
    });
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
  let markdown = '\n';
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
 */
export function cleanMarkdown(markdown) {
  if (!markdown) return '';

  return markdown
    // Remove excessive newlines (more than 2)
    .replace(/\n{3,}/g, '\n\n')
    // Trim whitespace
    .trim();
}
