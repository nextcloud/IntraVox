# IntraVox Roadmap

## Overview

IntraVox is a Nextcloud app for creating rich intranet pages with a visual page builder. This document outlines the phased rollout plan.

**Current Version:** 0.5.5 (November 2025)

---

## Version 0.x - Foundation (Current)

### Implemented Features (v0.5.x)

- ✅ Visual page builder with drag-and-drop interface
- ✅ Multi-column layouts (1-4 columns per row)
- ✅ Widget types:
  - Text (with Markdown support)
  - Heading (H1-H6)
  - Image (with upload, sizing, object-fit)
  - Links (grid and list layouts)
  - File reference
  - Divider
- ✅ Page management (create, edit, delete)
- ✅ UniqueId-based page identification
- ✅ Navigation system (dropdown with 3 levels)
- ✅ Breadcrumb navigation
- ✅ Page tree modal for structure viewing
- ✅ Side columns for page layouts
- ✅ Version history with labels
- ✅ Internationalization (i18n) - Dutch, English
- ✅ Search integration with Nextcloud
- ✅ Demo data import system
- ✅ Responsive design

### Technical Foundation

- ✅ Clean architecture with service layer
- ✅ JSON-based page storage in Groupfolders
- ✅ RESTful API endpoints
- ✅ Vue 3 frontend with Nextcloud Vue components
- ✅ Input sanitization and security

---

## Version 1.0 - Production Ready

**Target:** Stable release for production use

### Remaining Features

- [ ] User guide documentation
- [ ] Settings page (custom app name, icon)
- [ ] Mega menu navigation option
- [ ] Performance optimizations
- [ ] Comprehensive test coverage
- [ ] Bug fixes and polish

---

## Version 2.0 - Collaboration & Permissions

**Focus:** Multi-user collaboration and advanced content management

### Planned Features

- [ ] **Nested pages** - Hierarchical page organization (see `NESTED_PAGES.md`)
- [ ] **Department folders** - Isolated content spaces
- [ ] **User permissions** - Page-level and folder-level access control
- [ ] **Version comparison** - Diff view between versions
- [ ] **Content templates** - Pre-built page templates
- [ ] **Advanced widgets:**
  - Video widget
  - Gallery/carousel widget
  - Card widget
  - Accordion/tabs widget
- [ ] **Tags and categories**
- [ ] **Favorites and recent pages**

---

## Version 3.0 - Enterprise Features

**Focus:** Advanced features for larger organizations

### Planned Features

- [ ] **Workflows** - Review/approval workflows
- [ ] **Analytics** - Page view statistics
- [ ] **Scheduled publishing** - Draft/publish workflow
- [ ] **Multi-language pages** - Linked translations
- [ ] **External integrations** - Nextcloud Talk, Deck embeds
- [ ] **API and webhooks** - External integrations
- [ ] **Media library** - Centralized image management
- [ ] **Admin dashboard** - System overview

---

## Future Considerations (Post v3.0)

- Mobile app (native iOS/Android)
- Offline mode with sync
- AI-powered content suggestions
- PDF export
- Custom widget development SDK
- Marketplace for templates/widgets

---

## Release Philosophy

### Pre-1.0 (Current)
- Active development with frequent releases
- Features may change based on feedback
- Not recommended for critical production use

### Version 1.0
- Stable, production-ready release
- Core features complete and tested
- Documentation complete

### Version 2.0+
- Major features require planning
- Backward compatibility maintained
- Enterprise features in dedicated tier

---

## Feedback

We welcome feedback and feature requests:
- Report bugs on GitHub Issues
- Request features through GitHub Discussions
- Contribute code via Pull Requests

---

**Last Updated:** 2025-11-30
