# IntraVox Import/Export Functionality - Implementation Plan

## Executive Summary

Extension of the existing IntraVox import system with **focus on migration from Confluence and SharePoint** to IntraVox. The goal is to support organizations in switching to IntraVox by providing robust import functionality. Additionally, IntraVox-to-IntraVox migration needs to be improved for demo packs and multi-environment deployments.

## Objectives (Import Focus)

1. **Import into IntraVox from** (Priority):
   - ⭐⭐⭐ **Confluence** (XML backup + REST API) - Highest priority for enterprise adoption
   - ⭐⭐⭐ **SharePoint** - Extend/integrate existing framework
   - ⭐⭐ **IntraVox-to-IntraVox** - Demo packs and migration between environments

2. **Export from IntraVox** (Future - Phase 2):
   - HTML (universal)
   - WordPress WXR, Drupal JSON (later)

3. **Demo Pack System**:
   - Reusable content packages for demos
   - Easy 1-click import
   - Multi-language support
   - Best practices examples

## Priorities

### Phase 1: Confluence Import ⭐⭐⭐ (Week 2-4)

**Why Confluence first?**
- Enterprise target market - highest chance of adoption
- Many organizations want to migrate from Confluence
- High business value for sales/marketing
- XWiki has already proven this is possible

**Confluence Version Support:**
- ✅ **Confluence Cloud** (Atlassian hosted)
- ✅ **Confluence Server** (On-premise v7.x - 8.x)
- ✅ **Confluence Data Center** (Enterprise on-premise)

**Import Options:**
1. **XML Backup Import** - Complete space export, requires admin access (All versions)
2. **REST API Import** - Selective import, no admin access needed
   - Cloud: OAuth 2.0 / API Token
   - Server/DC: Personal Access Token / Basic Auth

**Macro Support (Top priority):**
1. Info/Note/Warning/Tip panels
2. Code blocks with syntax highlighting
3. Images and attachments
4. Table of Contents
5. Expand/collapse sections

### Phase 2: SharePoint Import ⭐⭐⭐ (Week 4-5)

**Strategy:**
- Reuse existing Python framework (`/IntranetMigration/`)
- PHP wrapper to call Python CLI
- UI integration in IntraVox admin panel

### Phase 3: Demo Pack System ⭐⭐ (Week 5-6)

**Use Cases:**
- Pre-defined content packages for demos
- Multi-environment deployments (dev → staging → prod)
- Template distribution for best practices

**Demo Packs to create:**
1. corporate-basic - Basic corporate intranet
2. news-portal - News-focused
3. knowledge-base - Documentation style
4. team-workspace - Team collaboration
5. multilingual-showcase - Multi-language best practices

## Time Estimation

**Total: 6-7 weeks**

- **Week 1**: Preparation + Foundation (Abstract Importer, HTML converter)
- **Week 2-3**: Confluence Import - Core (Parser, Macro handlers, XML backup)
- **Week 3-4**: Confluence Import - REST API + UI
- **Week 4-5**: SharePoint Integration (PHP wrapper, UI)
- **Week 5-6**: Demo Pack System (Multi-language export, demo packs)
- **Week 6-7**: Testing, Documentation, User Acceptance Testing

**Quick Wins (Week 1-3):**
- Confluence XML backup import (basic functionality)
- Top 5 macros supported
- Demo pack from Confluence test space

## Success Metrics

**Business Impact:**
- 🎯 5+ Confluence → IntraVox migrations within 3 months after release
- 🎯 3+ demo pack downloads per week
- 🎯 Average migration time < 2 hours (for 50 pages)

**Technical KPIs:**
- ✅ 90%+ Confluence macros correctly converted (top 10 macros)
- ✅ Support for Confluence Cloud, Server (v7.x-8.x), and Data Center
- ✅ Auto-detection of Confluence version (Cloud vs Server/DC)
- ✅ Import performance: >10 pages per minute
- ✅ Zero data loss for text content
- ✅ 80%+ attachment success rate

**User Satisfaction:**
- ⭐ 4+ stars average rating
- 📝 <3 support tickets per migration
- 💬 Positive feedback from beta testers

## Architecture

### Import Pipeline

```
Source Format (Confluence/SharePoint)
    ↓
Format Parser (XML/REST API)
    ↓
Intermediate Representation
    ↓
Widget Mapper (HTML → IntraVox widgets)
    ↓
IntraVox JSON + Media
    ↓
ImportService (existing)
```

### Confluence Macro → Widget Mapping

- Info/Warning panels → Text widget with styling
- Code blocks → Text widget with `<pre><code>`
- Images → Image widget
- Attachments → File widget
- TOC → Links widget or skip
- Expand → Text widget with `<details>`

## Critical Files

### To Create (High Priority)
- `lib/Service/Import/ConfluenceImporter.php`
- `lib/Service/Import/ConfluenceApiImporter.php`
- `lib/Service/Import/ConfluenceXmlBackupImporter.php`
- `lib/Service/Import/HtmlToWidgetConverter.php`
- `lib/Service/Import/Confluence/Macros/*.php`
- `src/admin/components/ConfluenceImport.vue`

### To Modify (Existing)
- `lib/Controller/ImportController.php` - Add Confluence routes
- `lib/Service/ExportService.php` - Add multi-language export
- `src/admin/AdminApp.vue` - Update UI

## Next Steps

1. **Plan Approval** - Review and approval
2. **Confluence Test Environment** - Set up test instance
3. **Technical Spike** - Test REST API authentication
4. **MVP Definition** - Minimum viable Confluence import
5. **Implementation Start** - Begin week 1

## Risks & Mitigations

1. **Macro Complexity** → Plugin-based handler system, start with top 5
2. **Performance** → Streaming parsers, background processing
3. **Media Downloads** → Retry logic, track failures
4. **Content Fidelity** → Fallback to text widgets, clear documentation

---

*For complete technical details, see the comprehensive plan in `.claude/plans/`*
