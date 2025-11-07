const fs = require('fs');
const path = require('path');

const homePageContent = {
  "id": "home",
  "title": "Welcome to IntraVox",
  "layout": {
    "columns": 3,
    "rows": [
      {
        "columns": 1,
        "widgets": [
          {
            "id": "hero_image",
            "type": "image",
            "src": "team-collaboration.jpg",
            "alt": "Team collaborating on IntraVox",
            "width": null,
            "column": 1,
            "order": 1
          }
        ]
      },
      {
        "columns": 3,
        "widgets": [
          {
            "id": "welcome_heading",
            "type": "heading",
            "content": "Welcome to IntraVox",
            "level": 1,
            "column": 1,
            "order": 1
          },
          {
            "id": "welcome_text",
            "type": "text",
            "content": "**Create Beautiful Pages Without Code**\n\nTransform your Nextcloud into a powerful collaborative workspace. IntraVox brings *beautiful*, SharePoint-style pages to your organization.\n\n**No coding required** – Just drag, drop, and create!",
            "column": 1,
            "order": 2
          },
          {
            "id": "what_heading",
            "type": "heading",
            "content": "What is IntraVox?",
            "level": 2,
            "column": 2,
            "order": 1
          },
          {
            "id": "what_text",
            "type": "text",
            "content": "IntraVox is a **visual page builder** for Nextcloud that empowers your team to:\n\n- **Create rich content pages** with text, images, links, and files\n- **Design professional layouts** with 1-5 column grids\n- **Collaborate in real-time** – everyone sees updates instantly\n- **Support multiple languages** – automatic content localization\n- **Stay secure** – all data stays in your Nextcloud",
            "column": 2,
            "order": 2
          },
          {
            "id": "perfect_heading",
            "type": "heading",
            "content": "Perfect For",
            "level": 2,
            "column": 3,
            "order": 1
          },
          {
            "id": "perfect_list",
            "type": "text",
            "content": "- **Company Intranets** – Share news, company updates\n- **Knowledge Bases** – Document processes and procedures\n- **Department Portals** – Give each team their own space\n- **Project Hubs** – Centralize project resources\n- **Team Wikis** – Collaborate on shared knowledge",
            "column": 3,
            "order": 2
          }
        ]
      },
      {
        "columns": 1,
        "widgets": [
          {
            "id": "divider_1",
            "type": "divider",
            "column": 1,
            "order": 1
          }
        ]
      },
      {
        "columns": 2,
        "widgets": [
          {
            "id": "workspace_image",
            "type": "image",
            "src": "workspace.jpg",
            "alt": "Professional workspace",
            "width": 500,
            "column": 1,
            "order": 1
          },
          {
            "id": "started_heading",
            "type": "heading",
            "content": "Getting Started",
            "level": 2,
            "column": 2,
            "order": 1
          },
          {
            "id": "started_text",
            "type": "text",
            "content": "**Create Your First Page**\n\n1. Click **\"+ New Page\"** in the top navigation\n2. Enter a **meaningful title** for your page\n3. Click **\"Edit\"** to enter editing mode\n4. Start **adding widgets** using the toolbar buttons\n5. **Drag widgets** to arrange your layout\n6. Click **\"Save\"** when finished\n\n**Pro Tip:** Start with headings to structure your content, then fill in the details!",
            "column": 2,
            "order": 2
          }
        ]
      },
      {
        "columns": 1,
        "widgets": [
          {
            "id": "divider_2",
            "type": "divider",
            "column": 1,
            "order": 1
          }
        ]
      },
      {
        "columns": 3,
        "widgets": [
          {
            "id": "text_heading",
            "type": "heading",
            "content": "Text Widget",
            "level": 2,
            "column": 1,
            "order": 1
          },
          {
            "id": "text_desc",
            "type": "text",
            "content": "Add rich text with full **Markdown** support:\n\n- **Bold** with `**text**`\n- *Italic* with `*text*`\n- Bullet and numbered lists\n- Links and blockquotes\n- Code blocks\n\nPerfect for paragraphs, instructions, and general content.",
            "column": 1,
            "order": 2
          },
          {
            "id": "image_heading",
            "type": "heading",
            "content": "Image Widget",
            "level": 2,
            "column": 2,
            "order": 1
          },
          {
            "id": "image_desc",
            "type": "text",
            "content": "Add visual impact with images:\n\n**Size Options:**\n- Small (300px)\n- Medium (500px)\n- Large (800px)\n- Full width (100%)\n\nUpload to images folder or use external URLs. Always add alt text for accessibility!",
            "column": 2,
            "order": 2
          },
          {
            "id": "interactive_heading",
            "type": "heading",
            "content": "Interactive Widgets",
            "level": 2,
            "column": 3,
            "order": 1
          },
          {
            "id": "interactive_desc",
            "type": "text",
            "content": "**Link Widget** – Quick access to pages, websites, and files\n\n**File Widget** – Embed PDFs, documents, and media\n\n**Divider Widget** – Separate sections visually\n\nCreate beautiful, organized pages with ease!",
            "column": 3,
            "order": 2
          }
        ]
      },
      {
        "columns": 1,
        "widgets": [
          {
            "id": "divider_3",
            "type": "divider",
            "column": 1,
            "order": 1
          }
        ]
      },
      {
        "columns": 2,
        "widgets": [
          {
            "id": "layout_heading",
            "type": "heading",
            "content": "Master the Layout System",
            "level": 2,
            "column": 1,
            "order": 1
          },
          {
            "id": "layout_text",
            "type": "text",
            "content": "IntraVox uses a **flexible grid system** that makes professional layouts easy:\n\n**Choose Your Columns:**\n- **1 column** – Great for focused, article-style content\n- **2 columns** – Perfect for side-by-side comparisons\n- **3 columns** – Ideal for feature showcases\n- **4-5 columns** – Maximum flexibility for dashboards\n\n**Arrange Your Content:**\n- **Drag widgets** between columns\n- **Drop into different rows** to reorganize\n- **Mix widget types** for visual variety\n- **Mobile responsive** – automatically adapts\n\nYour layouts look great on **all devices** – desktop, tablet, and mobile!",
            "column": 1,
            "order": 2
          },
          {
            "id": "collaboration_image",
            "type": "image",
            "src": "team-meeting.jpg",
            "alt": "Team meeting and collaboration",
            "width": 500,
            "column": 2,
            "order": 1
          }
        ]
      }
    ]
  }
};

// Write the file
fs.writeFileSync(
  path.join(__dirname, 'demo-data', 'en', 'home.json'),
  JSON.stringify(homePageContent, null, 2)
);

console.log('✓ Homepage updated successfully');
