#!/usr/bin/env python3
"""
Generate IntraVox demo pages with correct structure
Includes all 5 widget types: text, heading, image, links, divider
"""

import json
import os
import uuid
import time
from pathlib import Path

# Base timestamp for realistic dates
BASE_TIME = int(time.time()) - (30 * 24 * 60 * 60)  # 30 days ago

def generate_uuid():
    """Generate a UUID v4 compatible with PHP's format"""
    return str(uuid.uuid4())

def create_page_structure(page_id, title, rows, language="en"):
    """Create a complete page structure"""
    return {
        "id": page_id,
        "uniqueId": f"page-{generate_uuid()}",
        "title": title,
        "language": language,
        "created": BASE_TIME,
        "modified": int(time.time()),
        "layout": {
            "columns": 1,
            "rows": rows
        }
    }

def create_row(widgets, bg_color="", columns=1):
    """Create a row with widgets"""
    return {
        "columns": columns,
        "backgroundColor": bg_color,
        "widgets": widgets
    }

def widget_heading(content, level=1, order=1, column=1):
    return {
        "type": "heading",
        "column": column,
        "order": order,
        "content": content,
        "level": level
    }

def widget_text(content, order=1, column=1):
    return {
        "type": "text",
        "column": column,
        "order": order,
        "content": content
    }

def widget_image(src, alt, width=None, object_fit="cover", order=1, column=1):
    widget = {
        "type": "image",
        "column": column,
        "order": order,
        "src": src,
        "alt": alt
    }
    # Only add width if specified (null = full width)
    if width is not None:
        widget["width"] = width
    if object_fit:
        widget["objectFit"] = object_fit
    return widget

def widget_links(items, columns=3, order=1, column=1):
    return {
        "type": "links",
        "column": column,
        "order": order,
        "columns": columns,
        "items": items
    }

def widget_divider(style="solid", color="var(--color-border)", height="2px", order=1, column=1):
    return {
        "type": "divider",
        "column": column,
        "order": order,
        "style": style,
        "color": color,
        "height": height
    }

# Demo pages configuration
# Structure: Each page (except home) goes in folder/page.json
# Only home.json, navigation.json, footer.json are in root
DEMO_PAGES = {
    # Root level - only home
    "home.json": {
        "id": "home",
        "title": "Welcome to IntraVox",
        "rows": [
            create_row([
                widget_image("images/team-hero.jpg", "The IntraVox team", order=1),
                widget_heading("Welcome to IntraVox", 1, order=2),
                widget_text("Your modern intranet platform built on Nextcloud", order=3)
            ], "var(--color-primary-element)"),
            create_row([
                widget_heading("What is IntraVox?", 2, order=1),
                widget_text("IntraVox is a powerful **intranet application** for Nextcloud that brings SharePoint-style content management to the open-source world.\n\n✓ Create beautiful pages with a visual editor\n✓ Organize content with multi-level navigation\n✓ Support for multiple languages\n✓ Secure team collaboration", order=2)
            ]),
            create_row([
                widget_image("images/features-showcase.jpg", "Feature showcase", order=1),
                widget_heading("Built for Modern Teams", 2, order=2),
                widget_text("IntraVox combines the power of **open-source** with enterprise-grade features. Create stunning pages, organize content effortlessly, and collaborate with your team in a secure environment.", order=3)
            ]),
            create_row([widget_divider(order=1)]),
            create_row([
                widget_heading("Why Choose IntraVox?", 2, order=1),
                widget_text("Discover what makes IntraVox the perfect choice for your organization's intranet needs.", order=2)
            ], "var(--color-background-hover)"),
            # 2-column feature section
            create_row([
                widget_image("images/modern-workspace.jpg", "Modern digital workspace", order=1, column=1),
                widget_heading("Modern & Intuitive", 2, order=2, column=1),
                widget_text("A beautiful, user-friendly interface that your team will love. Built with modern web technologies for the best user experience.", order=3, column=1),
                widget_image("images/secure-platform.jpg", "Secure collaboration platform", order=1, column=2),
                widget_heading("Secure by Design", 2, order=2, column=2),
                widget_text("Your data stays on your servers. Built on Nextcloud's proven security architecture with enterprise-grade protection.", order=3, column=2)
            ], columns=2),
            create_row([widget_divider(order=1)]),
            create_row([
                widget_heading("Quick Links", 2, order=1),
                widget_links([
                    {"title": "About Us", "url": "#about", "icon": "information", "target": "_self"},
                    {"title": "Our Team", "url": "#team", "icon": "account-multiple", "target": "_self"},
                    {"title": "Documentation", "url": "#documentation", "icon": "book-open", "target": "_self"},
                    {"title": "Contact", "url": "#contact", "icon": "email", "target": "_self"}
                ], 4, order=2)
            ], "var(--color-background-hover)"),
            create_row([
                widget_image("images/collaboration.jpg", "Team collaboration", order=1),
                widget_heading("Collaborate Seamlessly", 2, order=2),
                widget_text("Work together with your team in real-time. Share knowledge, build beautiful intranets, and keep everyone connected with IntraVox.", order=3)
            ]),
            create_row([
                widget_image("images/open-source.jpg", "Open source community", order=1),
                widget_heading("Open Source Freedom", 2, order=2),
                widget_text("No vendor lock-in, no hidden costs. IntraVox is **100% open source** under the AGPL license, giving you complete control and freedom.", order=3)
            ]),
            create_row([widget_divider(order=1)]),
            create_row([
                widget_heading("Latest Updates", 2, order=1),
                widget_text("Stay up to date with the latest news, events, and announcements from our organization.", order=2),
                widget_links([
                    {"title": "News", "url": "#news", "icon": "newspaper", "target": "_self"},
                    {"title": "Events", "url": "#events", "icon": "calendar", "target": "_self"},
                    {"title": "Press Releases", "url": "#press", "icon": "bullhorn", "target": "_self"}
                ], 3, order=3)
            ])
        ]
    },

    "about/about.json": {
        "id": "about",
        "title": "About IntraVox",
        "rows": [
            create_row([
                widget_image("images/about-mission.jpg", "Our mission", order=1),
                widget_heading("About IntraVox", 1, order=2),
                widget_text("Building the future of collaborative intranets on Nextcloud", order=3)
            ], "var(--color-primary-element)"),
            # 2-column layout
            create_row([
                widget_heading("Our Mission", 2, order=1, column=1),
                widget_text("IntraVox was created to bring **SharePoint-style content management** to the open-source Nextcloud platform. We believe that every organization should have access to powerful intranet tools without vendor lock-in or excessive costs.", order=2, column=1),
                widget_heading("Our Vision", 2, order=1, column=2),
                widget_text("We envision a world where every organization can build **powerful, secure intranets** without compromising on privacy or paying excessive licensing fees. IntraVox makes this possible.", order=2, column=2)
            ], columns=2),
            create_row([widget_divider(order=1)]),
            # 3-column layout for features
            create_row([
                widget_heading("Simple Yet Powerful", 3, order=1, column=1),
                widget_text("Easy enough for non-technical users, powerful enough for complex intranets", order=2, column=1),
                widget_heading("Open Source", 3, order=1, column=2),
                widget_text("Built on the AGPL license, free to use and modify", order=2, column=2),
                widget_heading("Privacy First", 3, order=1, column=3),
                widget_text("Your data stays on your servers, under your control", order=2, column=3)
            ], "var(--color-background-hover)", columns=3),
            create_row([
                widget_heading("Why Choose IntraVox?", 2, order=1),
                widget_links([
                    {"title": "Easy to Use", "url": "#", "icon": "hand-okay", "target": "_self"},
                    {"title": "Secure", "url": "#", "icon": "shield-check", "target": "_self"},
                    {"title": "Open Source", "url": "#", "icon": "open-source-initiative", "target": "_self"},
                    {"title": "Multi-Language", "url": "#", "icon": "translate", "target": "_self"},
                    {"title": "Responsive", "url": "#", "icon": "responsive", "target": "_self"},
                    {"title": "Collaborative", "url": "#", "icon": "account-multiple", "target": "_self"}
                ], 3, order=2)
            ])
        ]
    },

    "team/team.json": {
        "id": "team",
        "title": "Our Team",
        "rows": [
            create_row([
                widget_image("images/team-collaboration.jpg", "Team collaboration", order=1),
                widget_heading("Our Team", 1, order=2),
                widget_text("Meet the people behind IntraVox", order=3)
            ], "var(--color-primary-element)"),
            create_row([
                widget_heading("Who We Are", 2, order=1),
                widget_text("We're a passionate team of developers, designers, and open-source enthusiasts dedicated to building better collaboration tools for everyone.", order=2),
                widget_links([
                    {"title": "Management", "url": "#team/management", "icon": "account-tie", "target": "_self"},
                    {"title": "Departments", "url": "#team/departments", "icon": "office-building", "target": "_self"}
                ], 2, order=3)
            ])
        ]
    },

    "team/management/management.json": {
        "id": "management",
        "title": "Management Team",
        "rows": [
            create_row([
                widget_heading("Management Team", 1, order=1),
                widget_text("Our leadership team driving IntraVox forward", order=2)
            ], "var(--color-primary-element)"),
            create_row([
                widget_heading("Leadership", 2, order=1),
                widget_text("Our management team brings decades of experience in open-source software, enterprise collaboration, and product development.", order=2)
            ])
        ]
    },

    "team/departments/departments.json": {
        "id": "departments",
        "title": "Our Departments",
        "rows": [
            create_row([
                widget_heading("Our Departments", 1, order=1),
                widget_text("Discover the teams that make IntraVox possible", order=2)
            ], "var(--color-primary-element)"),
            create_row([
                widget_links([
                    {"title": "Development", "url": "#", "icon": "code-tags", "target": "_self"},
                    {"title": "Design", "url": "#", "icon": "palette", "target": "_self"},
                    {"title": "Support", "url": "#", "icon": "lifebuoy", "target": "_self"},
                    {"title": "Sales", "url": "#", "icon": "currency-usd", "target": "_self"}
                ], 4, order=1)
            ])
        ]
    },

    "careers/careers.json": {
        "id": "careers",
        "title": "Careers",
        "rows": [
            create_row([
                widget_image("images/careers.jpg", "Join our team", order=1),
                widget_heading("Join Our Team", 1, order=2),
                widget_text("Build the future of open-source collaboration with us", order=3)
            ], "var(--color-primary-element)"),
            create_row([
                widget_heading("Why Work With Us?", 2, order=1),
                widget_text("We're looking for talented individuals who share our passion for open source and believe in the power of collaborative software.", order=2)
            ]),
            # 3-column benefits
            create_row([
                widget_heading("Remote-Friendly", 3, order=1, column=1),
                widget_text("Work from anywhere in the world. We embrace remote work and async collaboration.", order=2, column=1),
                widget_heading("Open Source", 3, order=1, column=2),
                widget_text("Contribute to meaningful open-source projects that make a real difference.", order=2, column=2),
                widget_heading("Growth", 3, order=1, column=3),
                widget_text("Professional development opportunities and competitive compensation.", order=2, column=3)
            ], "var(--color-background-hover)", columns=3),
            create_row([
                widget_heading("Open Positions", 2, order=1),
                widget_text("Check back soon for available positions or send us your resume at jobs@intravox.example", order=2)
            ])
        ]
    },

    "contact/contact.json": {
        "id": "contact",
        "title": "Contact Us",
        "rows": [
            create_row([
                widget_image("images/contact.jpg", "Contact us", order=1),
                widget_heading("Get in Touch", 1, order=2),
                widget_text("We'd love to hear from you", order=3)
            ], "var(--color-primary-element)"),
            # 2-column contact info
            create_row([
                widget_heading("Office", 2, order=1, column=1),
                widget_text("**Address:**\nIntraVox HQ\nAmsterdam Science Park\n1098 XH Amsterdam\nNetherlands", order=2, column=1),
                widget_heading("Get in Touch", 2, order=1, column=2),
                widget_text("**Email:** info@intravox.example\n**Phone:** +31 (0) 20 123 4567\n**Support:** support@intravox.example", order=2, column=2)
            ], columns=2),
            create_row([
                widget_heading("Business Hours", 2, order=1),
                widget_text("Monday - Friday: 9:00 AM - 5:00 PM (CET)\n\nFor urgent support inquiries, please use our 24/7 support portal.", order=2)
            ], "var(--color-background-hover)")
        ]
    },

    "news/news.json": {
        "id": "news",
        "title": "News",
        "rows": [
            create_row([
                widget_image("images/news-press.jpg", "Latest news", order=1),
                widget_heading("Latest News", 1, order=2),
                widget_text("Stay informed about the latest developments", order=3)
            ], "var(--color-primary-element)"),
            # 2-column news layout
            create_row([
                widget_heading("IntraVox 1.0 Released", 3, order=1, column=1),
                widget_text("**November 2025**\n\nWe're excited to announce the stable release of IntraVox 1.0! This major milestone brings enterprise-grade content management to Nextcloud with a beautiful, user-friendly interface.", order=2, column=1),
                widget_heading("New Features in 0.4.5", 3, order=1, column=2),
                widget_text("**November 2025**\n\nVisual folder distinction with 📷 emoji prefix for images folders. Multi-column layouts support. Enhanced mobile experience for editing pages.", order=2, column=2)
            ], columns=2),
            create_row([
                widget_heading("Community Highlights", 3, order=1, column=1),
                widget_text("**October 2025**\n\nThank you to our growing community of contributors! We've reached 1000+ downloads on the Nextcloud App Store.", order=2, column=1),
                widget_heading("Beta Testing Success", 3, order=1, column=2),
                widget_text("**September 2025**\n\nBeta testing phase completed with excellent feedback from early adopters. Ready for stable release!", order=2, column=2)
            ], "var(--color-background-hover)", columns=2)
        ]
    },

    "events/events.json": {
        "id": "events",
        "title": "Events",
        "rows": [
            create_row([
                widget_image("images/events.jpg", "Events", order=1),
                widget_heading("Upcoming Events", 1, order=2),
                widget_text("Join us at these upcoming events and webinars", order=3)
            ], "var(--color-primary-element)"),
            # 3-column events layout
            create_row([
                widget_heading("Nextcloud Conference", 3, order=1, column=1),
                widget_text("**December 2025**\n\nBerlin, Germany\n\nJoin us at the annual Nextcloud Conference where we'll showcase IntraVox's latest features and roadmap.", order=2, column=1),
                widget_heading("Open Source Summit", 3, order=1, column=2),
                widget_text("**January 2026**\n\nAmsterdam, NL\n\nMeet the IntraVox team at the Open Source Summit and learn about building intranets.", order=2, column=2),
                widget_heading("IntraVox Webinar", 3, order=1, column=3),
                widget_text("**Monthly**\n\nOnline\n\nJoin our monthly webinars to learn tips and tricks for getting the most out of IntraVox.", order=2, column=3)
            ], columns=3),
            create_row([
                widget_heading("Past Events", 2, order=1),
                widget_text("**IntraVox Launch Event** - November 2025\n\nThank you to everyone who joined our virtual launch event! Watch the recording on our YouTube channel.", order=2)
            ], "var(--color-background-hover)")
        ]
    },

    "press/press.json": {
        "id": "press",
        "title": "Press Releases",
        "rows": [
            create_row([
                widget_image("images/news-media.jpg", "Press releases", order=1),
                widget_heading("Press Releases", 1, order=2),
                widget_text("Official press releases and media resources", order=3)
            ], "var(--color-primary-element)"),
            create_row([
                widget_heading("Latest Press Releases", 2, order=1),
                widget_text("**IntraVox Announces Version 1.0** - November 2025\n\nIntraVox, the open-source intranet platform for Nextcloud, today announced the release of version 1.0, marking a major milestone in bringing enterprise-grade content management to the Nextcloud ecosystem.", order=2)
            ]),
            create_row([
                widget_heading("Media Kit", 2, order=1),
                widget_text("Download our press kit including logos, screenshots, and brand guidelines.", order=2),
                widget_links([
                    {"title": "Download Press Kit", "url": "#", "icon": "download", "target": "_blank"},
                    {"title": "Brand Guidelines", "url": "#", "icon": "palette", "target": "_blank"}
                ], 2, order=3)
            ], "var(--color-background-hover)")
        ]
    },

    "documentation/documentation.json": {
        "id": "documentation",
        "title": "Documentation",
        "rows": [
            create_row([
                widget_image("images/documentation.jpg", "Documentation", order=1),
                widget_heading("Documentation", 1, order=2),
                widget_text("Everything you need to know about using IntraVox", order=3)
            ], "var(--color-primary-element)"),
            create_row([
                widget_heading("Get Started", 2, order=1),
                widget_links([
                    {"title": "Getting Started", "url": "#documentation/getting-started", "icon": "rocket-launch", "target": "_self"},
                    {"title": "User Guide", "url": "#documentation/user-guide", "icon": "book-open", "target": "_self"},
                    {"title": "FAQ", "url": "#documentation/faq", "icon": "help-circle", "target": "_self"}
                ], 3, order=2)
            ])
        ]
    },

    "documentation/getting-started/getting-started.json": {
        "id": "getting-started",
        "title": "Getting Started",
        "rows": [
            create_row([
                widget_heading("Getting Started with IntraVox", 1, order=1),
                widget_text("Learn the basics in just a few minutes", order=2)
            ], "var(--color-primary-element)"),
            create_row([
                widget_heading("Quick Start", 2, order=1),
                widget_text("### Installation\n\n1. Install IntraVox from the Nextcloud App Store\n2. Enable the app in your Nextcloud instance\n3. Run the setup command: `occ intravox:setup`\n\n### Creating Your First Page\n\n1. Click the IntraVox icon in the top navigation\n2. Click 'New Page'\n3. Add widgets to build your page\n4. Save and publish!", order=2)
            ])
        ]
    },

    "documentation/user-guide/user-guide.json": {
        "id": "user-guide",
        "title": "User Guide",
        "rows": [
            create_row([
                widget_heading("User Guide", 1, order=1),
                widget_text("Comprehensive guide to all IntraVox features", order=2)
            ], "var(--color-primary-element)"),
            create_row([
                widget_heading("Working with Pages", 2, order=1),
                widget_text("### Page Editor\n\nThe IntraVox page editor lets you create professional-looking pages without any technical knowledge.\n\n### Available Widgets\n\n- **Text**: Rich text with Markdown support\n- **Heading**: H1-H6 headings\n- **Image**: Upload and display images\n- **Links**: Grid of clickable links with icons\n- **Divider**: Visual separators between sections", order=2)
            ])
        ]
    },

    "documentation/faq/faq.json": {
        "id": "faq",
        "title": "Frequently Asked Questions",
        "rows": [
            create_row([
                widget_heading("FAQ", 1, order=1),
                widget_text("Find answers to common questions", order=2)
            ], "var(--color-primary-element)"),
            create_row([
                widget_heading("Common Questions", 2, order=1),
                widget_text("**Q: Is IntraVox free?**\nA: Yes! IntraVox is open-source software licensed under AGPL.\n\n**Q: Does it work with Nextcloud Hub?**\nA: Yes, IntraVox is fully compatible with Nextcloud Hub.\n\n**Q: Can I use custom themes?**\nA: IntraVox automatically adapts to your Nextcloud theme.", order=2)
            ])
        ]
    },

    "downloads/downloads.json": {
        "id": "downloads",
        "title": "Downloads",
        "rows": [
            create_row([
                widget_image("images/downloads.jpg", "Downloads", order=1),
                widget_heading("Downloads", 1, order=2),
                widget_text("Get the latest version of IntraVox", order=3)
            ], "var(--color-primary-element)"),
            # 2-column download options
            create_row([
                widget_heading("Nextcloud App Store", 2, order=1, column=1),
                widget_text("**Recommended**\n\nInstall IntraVox directly from your Nextcloud instance via the App Store. One-click installation with automatic updates.", order=2, column=1),
                widget_links([
                    {"title": "Install from App Store", "url": "https://apps.nextcloud.com", "icon": "download", "target": "_blank"}
                ], 1, order=3, column=1),
                widget_heading("GitHub", 2, order=1, column=2),
                widget_text("**For Developers**\n\nGet the source code, report issues, or contribute to the project on GitHub.", order=2, column=2),
                widget_links([
                    {"title": "View on GitHub", "url": "https://github.com", "icon": "github", "target": "_blank"}
                ], 1, order=3, column=2)
            ], columns=2),
            create_row([
                widget_heading("Latest Release", 2, order=1),
                widget_text("**IntraVox 1.0.0** - November 2025\n\nStable release with full feature set. Compatible with Nextcloud 28+", order=2)
            ], "var(--color-background-hover)")
        ]
    },

    "support/support.json": {
        "id": "support",
        "title": "Support",
        "rows": [
            create_row([
                widget_image("images/support-help.jpg", "Support", order=1),
                widget_heading("Support", 1, order=2),
                widget_text("We're here to help", order=3)
            ], "var(--color-primary-element)"),
            # 2-column support channels
            create_row([
                widget_heading("Community Support", 2, order=1, column=1),
                widget_text("**Free community support** through our forums and GitHub discussions. Get help from other IntraVox users and contributors.\n\n• Response time: 24-48 hours\n• Available 24/7\n• Community-driven", order=2, column=1),
                widget_heading("Professional Support", 2, order=1, column=2),
                widget_text("**Enterprise support** for organizations requiring guaranteed response times and dedicated assistance.\n\n• Response time: <4 hours\n• Direct email support\n• Custom development", order=2, column=2)
            ], columns=2),
            create_row([
                widget_heading("Get Help", 2, order=1),
                widget_text("Choose your preferred support channel:", order=2),
                widget_links([
                    {"title": "Documentation", "url": "#documentation", "icon": "book-open", "target": "_self"},
                    {"title": "Community Forum", "url": "#", "icon": "forum", "target": "_blank"},
                    {"title": "GitHub Issues", "url": "#", "icon": "bug", "target": "_blank"},
                    {"title": "Contact Us", "url": "#contact", "icon": "email", "target": "_self"}
                ], 4, order=3)
            ], "var(--color-background-hover)")
        ]
    }
}

def main():
    """Generate all demo pages"""
    base_dir = Path(__file__).parent / "en"

    print("🚀 Generating IntraVox demo pages...")
    print(f"📁 Output directory: {base_dir}")
    print()

    for filepath, config in DEMO_PAGES.items():
        # Create directory structure
        full_path = base_dir / filepath
        full_path.parent.mkdir(parents=True, exist_ok=True)

        # Create page structure
        page = create_page_structure(
            config["id"],
            config["title"],
            config["rows"]
        )

        # Write JSON file
        with open(full_path, 'w', encoding='utf-8') as f:
            json.dump(page, f, indent=4, ensure_ascii=False)

        print(f"✓ Created {filepath}")

        # Create 📷 images folder for each page (except home.json, navigation.json, footer.json)
        if not filepath.endswith(('home.json', 'navigation.json', 'footer.json')):
            images_folder = full_path.parent / "📷 images"
            images_folder.mkdir(exist_ok=True)
            # Create .nomedia marker file
            nomedia_file = images_folder / ".nomedia"
            nomedia_file.touch(exist_ok=True)
            print(f"  → Created {images_folder.relative_to(base_dir)}")

    print()
    print(f"✅ Generated {len(DEMO_PAGES)} demo pages successfully!")
    print()
    print("📋 Created pages:")
    for filepath in sorted(DEMO_PAGES.keys()):
        print(f"  - {filepath}")

if __name__ == "__main__":
    main()
