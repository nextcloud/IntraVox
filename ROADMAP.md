# IntraVox Roadmap

## Overview
IntraVox is a Nextcloud app for creating rich intranet pages with a visual page builder. This document outlines the phased rollout plan for features across multiple releases.

---

## Version 1.0 - Foundation Release (MVP)
**Target**: First production-ready release with core functionality

### Core Features
- ✅ Visual page builder with drag-and-drop interface
- ✅ Multi-column layouts (1-4 columns per row)
- ✅ Basic widgets:
  - Text (with Markdown support)
  - Heading (H1-H6)
  - Image (with upload and sizing)
  - Link
  - File reference
  - Divider
  - Spacer
- ✅ Page management (create, edit, delete)
- ✅ Hierarchical page structure (nested pages/folders)
- ✅ Navigation system:
  - Dropdown (cascading) navigation
  - Mega menu navigation
- ✅ Search integration:
  - Nextcloud unified search (Ctrl+K)
  - Search in titles, headings, and content
  - Direct links to IntraVox pages
- ✅ Settings page:
  - Custom app name
  - Custom app icon (library or upload)
- ✅ Internationalization (i18n) support
- ✅ Responsive design for mobile/tablet

### Technical Foundation
- ✅ Clean architecture with service layer
- ✅ JSON-based page storage in user files
- ✅ RESTful API endpoints
- ✅ Vue 3 frontend with Nextcloud Vue components
- ✅ Proper error handling and validation

### Documentation
- ✅ README with installation instructions
- ✅ CHANGELOG for tracking releases
- User guide (in progress)

---

## Version 2.0 - Collaboration & Permissions
**Focus**: Multi-user collaboration and advanced content management

### Collaboration Features
- [ ] **User permissions system**:
  - Page-level permissions (view, edit, admin)
  - Folder-level permissions with inheritance
  - Group-based permissions
  - Share pages with specific users/groups
- [ ] **Version history**:
  - Automatic versioning on save
  - View previous versions
  - Restore previous versions
  - Compare versions (diff view)
  - Version metadata (who, when, why)
- [ ] **Multi-user editing**:
  - Lock pages while editing
  - Show who is currently editing
  - Conflict resolution
- [ ] **Comments system**:
  - Add comments to specific widgets
  - Comment threads
  - Resolve/unresolve comments
  - Notifications for new comments

### Advanced Content Features
- [ ] **Content templates**:
  - Pre-built page templates
  - Save custom templates
  - Template library
- [ ] **Widget improvements**:
  - Video widget (embed or upload)
  - Gallery widget (image carousel)
  - Card widget (info boxes)
  - Button widget
  - Accordion/collapsible sections
  - Tabs widget
- [ ] **Advanced text editor**:
  - Rich text toolbar (WYSIWYG)
  - Tables in text widgets
  - Code syntax highlighting
  - Emoji picker

### Search Enhancements
- [ ] **Advanced search**:
  - Full-text indexing for better performance
  - Search filters (by date, author, tags)
  - Search term highlighting in results
  - Search within specific folders
- [ ] **Search term highlighting**:
  - Highlight matching terms on page
  - Auto-scroll to first match
  - Navigate between matches

### Organization Features
- [ ] **Tags and categories**:
  - Add tags to pages
  - Filter by tags
  - Tag-based navigation
- [ ] **Favorites**:
  - Mark pages as favorites
  - Quick access to favorite pages
- [ ] **Recent pages**:
  - Track recently viewed pages
  - Recent pages sidebar

---

## Version 3.0 - Enterprise & Integration
**Focus**: Advanced features for larger organizations and external integrations

### Enterprise Features
- [ ] **Workflows and automation**:
  - Automated page publishing schedules
  - Review/approval workflows
  - Automated archiving of old content
  - Custom workflow rules
- [ ] **Analytics and insights**:
  - Page view statistics
  - User engagement metrics
  - Popular content reports
  - Search analytics
- [ ] **Content moderation**:
  - Draft/published status
  - Scheduled publishing
  - Content review queues
  - Editorial calendar
- [ ] **Multi-language support**:
  - Create pages in multiple languages
  - Language switcher
  - Translation management
  - Auto-detect user language

### Advanced Integrations
- [ ] **External content widgets**:
  - Embed Nextcloud Talk conversations
  - Embed Nextcloud Deck boards
  - Embed external websites (iframe)
  - RSS feed widget
  - Weather widget
  - Calendar widget
- [ ] **File integration enhancements**:
  - Browse and embed files directly
  - File previews in pages
  - Automatic file linking
  - Document viewer widget
- [ ] **API and webhooks**:
  - Public API for external integrations
  - Webhooks for page events
  - Import/export API
  - Headless CMS capabilities

### Performance & Scalability
- [ ] **Caching layer**:
  - Server-side page caching
  - CDN support for assets
  - Lazy loading for images
  - Progressive loading for large pages
- [ ] **Database backend** (optional):
  - PostgreSQL/MySQL support as alternative to JSON files
  - Better performance for large installations
  - Advanced querying capabilities
- [ ] **Media library**:
  - Centralized image management
  - Image optimization and resizing
  - Media search and filtering
  - Usage tracking for images

### Advanced Administration
- [ ] **Admin dashboard**:
  - System overview and statistics
  - User activity monitoring
  - Storage usage reports
  - Health checks
- [ ] **Backup and restore**:
  - Export entire IntraVox installation
  - Import from backup
  - Scheduled backups
  - Disaster recovery tools
- [ ] **Theme customization**:
  - Custom CSS injection
  - Brand colors and fonts
  - Logo customization
  - Custom page templates

---

## Future Considerations (Post v3.0)

### Potential Features
- [ ] Mobile app (native iOS/Android)
- [ ] Offline mode with sync
- [ ] AI-powered content suggestions
- [ ] Automated content translation
- [ ] Advanced SEO tools
- [ ] Newsletter generation from pages
- [ ] PDF export of pages
- [ ] Print-optimized layouts
- [ ] Accessibility improvements (WCAG compliance)
- [ ] Custom widget development SDK
- [ ] Marketplace for third-party widgets/templates

### Community Requests
This section will be updated based on user feedback and feature requests from the community.

---

## Release Philosophy

### Version 1.0 (MVP)
- **Goal**: Provide core functionality that makes IntraVox immediately useful
- **Approach**: Focus on stability, usability, and essential features
- **Timeline**: Ready for production use and initial sales

### Version 2.0 (Collaboration)
- **Goal**: Enable team collaboration and advanced content management
- **Approach**: Build on solid v1.0 foundation with user-requested features
- **Timeline**: Based on v1.0 adoption and feedback

### Version 3.0 (Enterprise)
- **Goal**: Scale to large organizations with advanced needs
- **Approach**: Enterprise-grade features and integrations
- **Timeline**: Driven by enterprise customer requirements

---

## Licensing Model

### Version 1.0
- Initial sales with basic feature set
- One-time purchase or annual subscription
- Updates and bug fixes included

### Version 2.0+
- Major version upgrades may require upgrade fee
- Or: All features included in subscription
- Enterprise features may have separate pricing tier

---

## Feedback and Contributions

We welcome feedback and feature requests from the community. Please:
- Report bugs on GitHub Issues
- Request features through GitHub Discussions
- Contribute code via Pull Requests
- Join our community forum for discussions

---

**Last Updated**: 2025-01-18
**Current Version**: 1.0.0 (in development)
