# IntraVox - Transform Your Nextcloud into a Powerful Intranet

> **⚠️ PROTOTYPE - NO CODE AVAILABLE YET**: IntraVox is currently a prototype in active development. **No installable code or release is available yet.** This repository currently serves as a showcase of the planned functionality. The application is being built and tested privately. Check back later for updates on availability.

**Create beautiful, collaborative pages in Nextcloud with the simplicity of modern content management.**

IntraVox brings SharePoint-style pages to Nextcloud, empowering your team to build engaging intranets, knowledge bases, and collaborative workspaces—all within your secure Nextcloud environment.

![IntraVox Homepage](img/intravox%20home.png)

*Beautiful, professional pages with drag-and-drop editing and smart navigation*

![Edit Pages with Drag-and-Drop](img/Edit%20page.gif)

*Powerful visual editor with flexible layouts and intuitive drag-and-drop widget management*

![Fully Responsive Design](img/Responsive.gif)

*Seamless experience across desktop, tablet, and mobile devices*

---

## Why IntraVox?

Traditional intranets are complex, expensive, and often separate from your existing tools. IntraVox changes that by seamlessly integrating rich content pages directly into Nextcloud, where your team already works.

### Build Pages That Inspire

- **Visual Drag-and-Drop Editor** - No coding required. Simply drag widgets where you want them
- **Flexible Grid System** - Create professional layouts with 1-5 columns and unlimited rows
- **Rich Content Types** - Text, headings, images, links, files, and spacers
- **Real-Time Collaboration** - Everyone sees updates instantly
- **Beautiful & Responsive** - Looks great on desktop, tablet, and mobile

### Perfect For

- **Company Intranets** - Create your digital workplace with news, resources, and team information
- **Knowledge Bases** - Build comprehensive documentation that's easy to navigate and update
- **Team Wikis** - Collaborate on shared knowledge with your colleagues
- **Project Hubs** - Centralize project information, resources, and updates
- **Department Portals** - Give each team their own space to share information

---

## Get Started in Minutes

### For Users

1. **Click IntraVox** in your Nextcloud navigation bar
2. **Create Your First Page** - Click "+ New Page" and give it a title
3. **Add Content** - Click "Edit" and start adding widgets:
   - 📝 **Text** - Rich text with Markdown support (bold, italic, lists, and more)
   - 📌 **Headings** - Structure your content with H1-H6 headers
   - 🖼️ **Images** - Upload and display beautiful visuals (300px, 500px, 800px, or full width)
   - 🔗 **Links** - Add quick links to important resources
   - 📄 **Files** - Embed documents for easy access
   - ➖ **Dividers** - Separate content sections visually

4. **Organize Your Layout** - Drag widgets between columns and rows to create the perfect structure
5. **Save & Share** - Your page is instantly available to everyone with access!

### What Makes IntraVox Special?

**Truly Collaborative** - Unlike personal pages, IntraVox uses shared folders so everyone on your team sees the same content, in real-time.

**Multi-Language Support** - Automatically shows content in each user's preferred language (Dutch, English, German, French supported out of the box).

**Beautifully Integrated** - No need to learn a new system. IntraVox lives right in your Nextcloud interface.

**Secure by Design** - Built with enterprise-grade security. All content is validated, sanitized, and stored safely.

**Markdown Support** - Write naturally with Markdown formatting in text widgets:
- **Bold** and *italic* text
- Bullet and numbered lists
- Links and images
- Code blocks
- And more!

---

## Installation

### For Administrators

**New to IntraVox?** Check out the comprehensive [Administrator Guide](ADMINISTRATOR_GUIDE.md) for:
- Complete installation instructions
- Service account setup
- Multi-language configuration
- Architecture overview
- Security features
- Troubleshooting guide
- Advanced configuration options

### Requirements

- Nextcloud 32 or higher
- PHP 8.0 or higher
- GroupFolders app installed and enabled

### Quick Installation

1. **Install from App Store** (recommended)
   - Navigate to Apps in Nextcloud
   - Search for "IntraVox"
   - Click Install

2. **Manual Installation**
   ```bash
   cd /path/to/nextcloud/apps
   git clone https://github.com/shalution/IntraVox.git intravox
   cd intravox
   npm install
   npm run build
   ```

3. **Enable the App**
   ```bash
   php occ app:enable intravox
   ```

4. **Run Setup**
   ```bash
   php occ intravox:setup
   ```

This will automatically:
- Create the IntraVox groupfolder
- Set up the required groups (IntraVox Admins and IntraVox Users)
- Create default demo content in all supported languages
- Configure permissions properly

### How It Works

IntraVox uses Nextcloud's GroupFolders feature to store all content:
- **Automatic Setup** - The setup command creates a shared groupfolder named "IntraVox"
- **ID-Independent** - The app automatically finds the IntraVox folder, regardless of its internal ID
- **Resilient** - If multiple IntraVox folders exist, it uses the most recent one
- **No Manual Configuration** - Just run the setup command and you're ready to go

The app stores pages as JSON files in a language-specific folder structure:
```
IntraVox/
├── nl/
│   ├── home.json
│   └── navigation.json
├── en/
│   ├── home.json
│   └── navigation.json
├── de/
└── fr/
```

### Technical Details

**Groupfolder Discovery**

IntraVox automatically finds the correct IntraVox groupfolder using an intelligent discovery mechanism:

1. **Searches all groupfolders** - Uses the Nextcloud GroupFolders API to list all available groupfolders
2. **Filters by name** - Looks for folders with mount point "IntraVox"
3. **Selects the highest ID** - If multiple IntraVox folders exist, uses the most recent one (highest ID)
4. **Works everywhere** - This logic is used both during setup and at runtime for API calls

This approach ensures the app works reliably:
- No hardcoded folder IDs
- Survives reinstallations
- Works across different Nextcloud instances
- Handles edge cases gracefully

**Object Type Compatibility**

The app handles different versions of the GroupFolders API:
- Supports `FolderDefinitionWithMappings` objects (Nextcloud 32+)
- Falls back to array access for older versions
- Uses property access (`mountPoint`) with method fallback (`getMountPoint()`)

---

## Need Help?

### For End Users

Visit the IntraVox page in your Nextcloud and start creating! The interface is intuitive and self-explanatory.

### For Technical Support

- **Installation & Setup**: See the [Administrator Guide](ADMINISTRATOR_GUIDE.md)
- **Issues & Bugs**: [GitHub Issues](https://github.com/shalution/IntraVox/issues)
- **Contact**: rik@shalution.nl

---

## Contributing

We welcome contributions! If you have ideas, found a bug, or want to contribute code, please:

1. Open an issue on GitHub
2. Submit a pull request
3. Share your feedback

---

## Support & Contact

**Developed by:** Rik Dekker - [Shalution](https://shalution.nl)
**Email:** rik@shalution.nl
**License:** AGPL-3.0

---

## Acknowledgments

IntraVox is built with love using:
- **Nextcloud** - The world's most popular open-source content collaboration platform
- **Vue.js** - The progressive JavaScript framework
- **SortableJS** - Beautiful drag-and-drop functionality

---

**Ready to transform your Nextcloud into a powerful collaborative workspace? [Get Started with IntraVox](#get-started-in-minutes)**
