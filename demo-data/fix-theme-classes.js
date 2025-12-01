#!/usr/bin/env node
/**
 * Fix theme classes in demo data
 * Replaces shorthand theme class names with CSS variables used in UI
 *
 * The UI (PageEditor.vue) supports these backgroundColor values:
 * - "" (empty/transparent)
 * - "var(--color-primary-element)" (Primary - dark)
 * - "var(--color-primary-element-light)" (Primary light)
 * - "var(--color-background-hover)" (Light background)
 */

const fs = require('fs');
const path = require('path');

// Mapping of theme classes to CSS variables (matching UI options)
const classToVar = {
    '"backgroundColor": "primary"': '"backgroundColor": "var(--color-primary-element)"',
    '"backgroundColor": "primary-light"': '"backgroundColor": "var(--color-primary-element-light)"',
    '"backgroundColor": "background"': '"backgroundColor": "var(--color-background-hover)"',
    // These are not in the UI dropdown but we'll map them to the closest available
    '"backgroundColor": "success"': '"backgroundColor": "var(--color-primary-element)"',
    '"backgroundColor": "warning"': '"backgroundColor": "var(--color-primary-element-light)"',
    '"backgroundColor": "error"': '"backgroundColor": "var(--color-primary-element)"',
    '"backgroundColor": "background-dark"': '"backgroundColor": "var(--color-background-hover)"',
};

// Dutch to English translations for EN demo data
const translations = {
    // ========== WIDGETS PAGE ==========
    '"title": "Widget Overzicht"': '"title": "Widget Overview"',
    '"content": "Widget Bibliotheek"': '"content": "Widget Library"',
    'Discover alle beschikbare widgets om uw pagina\'s tot leven te brengen. Van tekst tot afbeeldingen, van links tot interactieve elementen.': 'Discover all available widgets to bring your pages to life. From text to images, from links to interactive elements.',
    '"alt": "Tekst widget"': '"alt": "Text widget"',
    'Rijke tekst met **Markdown** ondersteuning, inclusief lijsten, tabellen en links.': 'Rich text with **Markdown** support, including lists, tables and links.',
    '"alt": "Afbeelding widget"': '"alt": "Image widget"',
    'Upload foto\'s direct vanuit Nextcloud met automatische optimalisatie.': 'Upload photos directly from Nextcloud with automatic optimization.',
    'Koppen van H1 tot H6 voor duidelijke hiërarchie in uw content.': 'Headings from H1 to H6 for clear hierarchy in your content.',
    '"content": "Populaire Widgets"': '"content": "Popular Widgets"',
    '"title": "Tekst Editor"': '"title": "Text Editor"',
    '"title": "Galerij"': '"title": "Gallery"',
    '"text": "Foto collecties"': '"text": "Photo collections"',
    '"title": "Code Blokken"': '"title": "Code Blocks"',
    '"content": "Layout Elementen"': '"content": "Layout Elements"',
    '"text": "Scheidingslijnen"': '"text": "Divider lines"',
    '"text": "Verticale ruimte"': '"text": "Vertical space"',
    '"title": "Kolommen"': '"title": "Columns"',
    '"title": "Achtergrond"': '"title": "Background"',
    '"text": "Kleuren & patronen"': '"text": "Colors & patterns"',
    '"title": "Aan de slag"': '"title": "Getting Started"',
    '"text": "Eerste widget plaatsen"': '"text": "Place your first widget"',
    '"title": "API Referentie"': '"title": "API Reference"',
    '"text": "Technische documentatie"': '"text": "Technical documentation"',
    '"text": "Hulp van anderen"': '"text": "Help from others"',
    '"content": "Snelkoppelingen"': '"content": "Quick Links"',
    '"text": "Alle Features"': '"text": "All Features"',

    // ========== NAVIGATION PAGE ==========
    '"alt": "Mega menu voorbeeld"': '"alt": "Mega menu example"',
    'Het mega menu toont alle navigatie-items in een overviewelijk grid. Ideaal voor:\\n\\n- Grote websites met veel pagina\'s\\n- Uitgebreide categoriestructuren\\n- Visuele navigatie met iconen\\n- Multi-level hiërarchieën': 'The mega menu displays all navigation items in a clear grid. Ideal for:\\n\\n- Large websites with many pages\\n- Extensive category structures\\n- Visual navigation with icons\\n- Multi-level hierarchies',
    '"text": "Stap-voor-stap handleiding"': '"text": "Step-by-step guide"',
    '"alt": "Dropdown menu voorbeeld"': '"alt": "Dropdown menu example"',
    'Klassieke cascading dropdown menu\'s voor eenvoudige navigatie. Perfect voor:\\n\\n- Kleinere websites\\n- Lineaire navigatiestructuren\\n- Mobiele apparaten\\n- Snelle toegang': 'Classic cascading dropdown menus for simple navigation. Perfect for:\\n\\n- Smaller websites\\n- Linear navigation structures\\n- Mobile devices\\n- Quick access',
    'Onbeperkte niveaus van submenu\'s voor complexe structuren.': 'Unlimited levels of submenus for complex structures.',
    'Link naar externe websites met _blank target.': 'Link to external websites with _blank target.',
    'Aparte navigatie per taal voor internationale sites.': 'Separate navigation per language for international sites.',

    // ========== CUSTOMERS PAGE ==========
    '"content": "Onze Klanten & Success Stories"': '"content": "Our Customers & Success Stories"',
    'Een van Duitslands grootste universiteiten vervangt commerciële cloud met Nextcloud.\\n\\n**Resultaat:**\\n- 35.000 studenten en medewerkers\\n- Geïntegreerd met campus systemen\\n- Kostenbesparing van 60%': 'One of Germany\'s largest universities replaces commercial cloud with Nextcloud.\\n\\n**Result:**\\n- 35,000 students and staff\\n- Integrated with campus systems\\n- 60% cost savings',
    '"content": "Word Onze Next Succes Story"': '"content": "Become Our Next Success Story"',
    'Sluit u aan bij duizenden organisaties die kiezen voor open source en data soevereiniteit.': 'Join thousands of organizations choosing open source and data sovereignty.',

    // ========== API PAGE ==========
    'Integreer IntraVox met externe systemen via onze RESTful API. Alle endpoints zijn available via de Nextcloud OCS API.': 'Integrate IntraVox with external systems via our RESTful API. All endpoints are available via the Nextcloud OCS API.',
    '| Method | Endpoint | Beschrijving |\\n|--------|----------|-------------|\\n| GET | /navigation | Haal navigatie op |\\n| PUT | /navigation | Update navigatie |\\n| GET | /navigation/footer | Haal footer op |\\n| PUT | /navigation/footer | Update footer |': '| Method | Endpoint | Description |\\n|--------|----------|-------------|\\n| GET | /navigation | Get navigation |\\n| PUT | /navigation | Update navigation |\\n| GET | /navigation/footer | Get footer |\\n| PUT | /navigation/footer | Update footer |',
    '"content": "Meer Documentation"': '"content": "More Documentation"',
    '"text": "Stap-voor-stap"': '"text": "Step-by-step"',

    // ========== MARKETING PAGE ==========
    'Het Marketing team is de stem van IntraVox. Wij zorgen ervoor dat onze boodschap helder, consistent en impactvol is.\\n\\n**Wat we doen:**\\n- Merkstrategie en positionering\\n- Content marketing en storytelling\\n- Digitale campagnes\\n- Event marketing\\n- PR en media relaties': 'The Marketing team is the voice of IntraVox. We ensure our message is clear, consistent and impactful.\\n\\n**What we do:**\\n- Brand strategy and positioning\\n- Content marketing and storytelling\\n- Digital campaigns\\n- Event marketing\\n- PR and media relations',
    '"content": "Succesvolle campagnes"': '"content": "Successful campaigns"',
    '"content": "Onze Campaigns"': '"content": "Our Campaigns"',
    'View onze lopende en afgeronde marketing campagnes:': 'View our ongoing and completed marketing campaigns:',
    '"title": "Campagne Overzicht"': '"title": "Campaign Overview"',
    '"text": "Alle marketing campagnes in detail"': '"text": "All marketing campaigns in detail"',

    // ========== CAMPAIGNS PAGE ==========
    'Overzicht van onze marketing initiatieven en campagnes.': 'Overview of our marketing initiatives and campaigns.',
    '| Campagne | Periode | Resultaat |\\n|----------|---------|----------|\\n| Product Launch | Sep 2024 | 500+ nieuwe gebruikers |\\n| Summer Sale | Jul 2024 | 30% meer conversies |\\n| Partner Programma | Q2 2024 | 15 nieuwe partners |': '| Campaign | Period | Result |\\n|----------|--------|--------|\\n| Product Launch | Sep 2024 | 500+ new users |\\n| Summer Sale | Jul 2024 | 30% more conversions |\\n| Partner Program | Q2 2024 | 15 new partners |',
    '"text": "Marketing afdeling overview"': '"text": "Marketing department overview"',

    // ========== SOCIAL MEDIA PAGE ==========
    'Plan uw social media content vooruit met onze content kalender. Werk samen met het team aan:\\n\\n- Posts en stories\\n- Video content\\n- Live streams\\n- Advertenties': 'Plan your social media content ahead with our content calendar. Collaborate with the team on:\\n\\n- Posts and stories\\n- Video content\\n- Live streams\\n- Advertisements',
    '| Aspect | Richtlijn |\\n|--------|----------|\\n| Tone of Voice | Professioneel maar toegankelijk |\\n| Responstijd | Binnen 2 uur op werkdagen |\\n| Hashtags | Max 5 per post, altijd #OurBrand |\\n| Afbeeldingen | Minimaal 1080x1080px |\\n| Video | Max 60 sec voor feed, 15 sec voor stories |': '| Aspect | Guideline |\\n|--------|----------|\\n| Tone of Voice | Professional but approachable |\\n| Response time | Within 2 hours on working days |\\n| Hashtags | Max 5 per post, always #OurBrand |\\n| Images | Minimum 1080x1080px |\\n| Video | Max 60 sec for feed, 15 sec for stories |',

    // ========== IT PAGE ==========
    '"content": "Onze Diensten"': '"content": "Our Services"',
    '*24/7 available voor kritieke issues*': '*24/7 available for critical issues*',
    '**Self-solve:**\\n\\n- Password reset via portal\\n- Software Center voor apps\\n- Knowledge base artikelen\\n- Video tutorials': '**Self-solve:**\\n\\n- Password reset via portal\\n- Software Center for apps\\n- Knowledge base articles\\n- Video tutorials',

    // ========== UPDATES PAGE ==========
    '"alt": "Verbeterd zoeken"': '"alt": "Improved search"',
    '"title": "News Overzicht"': '"title": "News Overview"',
    '"text": "Alle nieuwsberichten"': '"text": "All news articles"',

    // ========== EVENTS PAGE ==========
    '"title": "Aanmelden"': '"title": "Register"',
    '"text": "Aanmelden"': '"text": "Register"',

    // ========== NEXTCLOUD PAGE ==========
    '"text": "Professionele ondersteuning"': '"text": "Professional support"',

    // ========== INSTALLATION PAGE ==========
    'Volg deze stappen om IntraVox te installeren op uw Nextcloud server. De installatie duurt ongeveer 5 minuten.': 'Follow these steps to install IntraVox on your Nextcloud server. Installation takes about 5 minutes.',
    'Voordat u begint, controleer of uw server voldoet aan de volgende eisen:\\n\\n| Component | Minimum | Aanbevolen |\\n|-----------|---------|------------|\\n| Nextcloud | 27.0 | 28.0+ |\\n| PHP | 8.0 | 8.2+ |\\n| Database | MySQL 8.0 | MariaDB 10.6+ |\\n| Geheugen | 256 MB | 512 MB+ |': 'Before you begin, verify your server meets the following requirements:\\n\\n| Component | Minimum | Recommended |\\n|-----------|---------|-------------|\\n| Nextcloud | 27.0 | 28.0+ |\\n| PHP | 8.0 | 8.2+ |\\n| Database | MySQL 8.0 | MariaDB 10.6+ |\\n| Memory | 256 MB | 512 MB+ |',
    'Download IntraVox via de Nextcloud App Store of handmatig:\\n\\n**Via App Store (aanbevolen):**\\n1. Go to Nextcloud Admin > Apps\\n2. Zoek naar \\"IntraVox\\"\\n3. Klik op \\"Download en activeer\\"\\n\\n**Handmatig:**\\n```bash\\ncd /var/www/nextcloud/apps\\nwget https://github.com/intravox/releases/latest.tar.gz\\ntar -xzf latest.tar.gz\\n```': 'Download IntraVox via the Nextcloud App Store or manually:\\n\\n**Via App Store (recommended):**\\n1. Go to Nextcloud Admin > Apps\\n2. Search for \\"IntraVox\\"\\n3. Click \\"Download and enable\\"\\n\\n**Manually:**\\n```bash\\ncd /var/www/nextcloud/apps\\nwget https://github.com/intravox/releases/latest.tar.gz\\ntar -xzf latest.tar.gz\\n```',
    'Voer de setup wizard uit:\\n\\n```bash\\nsudo -u www-data php occ intravox:setup\\n```\\n\\nDit creëert:\\n- De IntraVox groupfolder\\n- Standaard homepage\\n- Navigation structuur\\n- Initiële configuratie': 'Run the setup wizard:\\n\\n```bash\\nsudo -u www-data php occ intravox:setup\\n```\\n\\nThis creates:\\n- The IntraVox groupfolder\\n- Default homepage\\n- Navigation structure\\n- Initial configuration',
    'Voeg gebruikers toe aan de IntraVox group:\\n\\n1. Go to Users > Groups\\n2. Selecteer \\"IntraVox Users\\" of \\"IntraVox Editors\\"\\n3. Voeg de gewenste gebruikers toe\\n\\n**Rollen:**\\n- **Users**: Alleen lezen\\n- **Editors**: Lezen en bewerken\\n- **Admins**: Volledige controle': 'Add users to the IntraVox group:\\n\\n1. Go to Users > Groups\\n2. Select \\"IntraVox Users\\" or \\"IntraVox Editors\\"\\n3. Add the desired users\\n\\n**Roles:**\\n- **Users**: Read only\\n- **Editors**: Read and edit\\n- **Admins**: Full control',
    '"text": "Koppel externe systemen"': '"text": "Connect external systems"',

    // ========== TIPS PAGE ==========
    '**Tip 1:** Gebruik de links widget met iconen om snelle navigatie te maken.\\n\\n**Tip 2:** Combineer een header rij met primaire achtergrondkleur voor impactvolle pagina headers.\\n\\n**Tip 3:** Gebruik 3 kolommen voor feature overviewen en 2 kolommen voor vergelijkingen.\\n\\n**Tip 4:** Voeg altijd alt-tekst toe aan afbeeldingen voor toegankelijkheid.': '**Tip 1:** Use the links widget with icons to create quick navigation.\\n\\n**Tip 2:** Combine a header row with primary background color for impactful page headers.\\n\\n**Tip 3:** Use 3 columns for feature overviews and 2 columns for comparisons.\\n\\n**Tip 4:** Always add alt text to images for accessibility.',
    '"text": "Meer gedetailleerde instructies"': '"text": "More detailed instructions"',

    // ========== Documentation page ==========
    '"content": "Zoek Hulp"': '"content": "Find Help"',
    'Welcome bij het IntraVox documentatie centrum. Hier vindt u handleidingen, veelgestelde vragen en tips om het maximale uit IntraVox te halen.': 'Welcome to the IntraVox documentation center. Here you will find guides, frequently asked questions and tips to get the most out of IntraVox.',
    'Stap-voor-stap instructies voor alle IntraVox features. Van basis tot gevorderd.': 'Step-by-step instructions for all IntraVox features. From basic to advanced.',
    'Answers to frequently asked questions. Snel de oplossing vinden zonder te zoeken.': 'Answers to frequently asked questions. Find the solution quickly without searching.',
    'Power user tips om uw productiviteit te verhogen en IntraVox optimaal te benutten.': 'Power user tips to increase your productivity and make optimal use of IntraVox.',
    '"content": "Aan de Slag"': '"content": "Getting Started"',
    'New at IntraVox? Begin hier met de basis:\\n\\n1. **Installation** - IntraVox installeren via de Nextcloud App Store\\n2. **Configuration** - Basicinstellingen configureren\\n3. **Eerste pagina** - Uw eerste IntraVox pagina maken\\n4. **Navigation** - Menu structuur opzetten\\n5. **Publiceren** - Content delen met uw team': 'New to IntraVox? Start here with the basics:\\n\\n1. **Installation** - Install IntraVox via the Nextcloud App Store\\n2. **Configuration** - Configure basic settings\\n3. **First page** - Create your first IntraVox page\\n4. **Navigation** - Set up menu structure\\n5. **Publishing** - Share content with your team',
    '"content": "Externale Resources"': '"content": "External Resources"',
    '"text": "Platform documentatie"': '"text": "Platform documentation"',
    '"text": "Vraag de community"': '"text": "Ask the community"',
    '"text": "Bug rapportages"': '"text": "Bug reports"',
    '"text": "Direct hulp nodig?"': '"text": "Need direct help?"',

    // News page
    '"alt": "News en updates"': '"alt": "News and updates"',

    // Contact page
    '"text": "Zakelijke updates"': '"text": "Business updates"',
    '"text": "News en tips"': '"text": "News and tips"',
    '"text": "Discussies en hulp"': '"text": "Discussions and help"',

    // Events page
    '"text": "Newe features"': '"text": "New features"',
    '"text": "Alle berichten"': '"text": "All messages"',
    '"text": "Stel een vraag"': '"text": "Ask a question"',
};

function processFile(filePath, replacements) {
    let content = fs.readFileSync(filePath, 'utf8');
    let modified = false;

    for (const [search, replace] of Object.entries(replacements)) {
        if (content.includes(search)) {
            content = content.split(search).join(replace);
            modified = true;
        }
    }

    if (modified) {
        fs.writeFileSync(filePath, content);
        console.log(`   Fixed: ${path.relative(process.cwd(), filePath)}`);
        return true;
    }
    return false;
}

function processDirectory(dir, replacements) {
    let count = 0;
    const entries = fs.readdirSync(dir, { withFileTypes: true });

    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);

        if (entry.isDirectory() && entry.name !== 'images') {
            count += processDirectory(fullPath, replacements);
        } else if (entry.name.endsWith('.json')) {
            if (processFile(fullPath, replacements)) {
                count++;
            }
        }
    }

    return count;
}

console.log('Converting theme classes to CSS variables...\n');

const nlDir = path.join(__dirname, 'nl');
const enDir = path.join(__dirname, 'en');

console.log('Processing NL demo data (theme classes)...');
const nlCount = processDirectory(nlDir, classToVar);
console.log(`   Total: ${nlCount} files fixed\n`);

console.log('Processing EN demo data (theme classes)...');
const enThemeCount = processDirectory(enDir, classToVar);
console.log(`   Total: ${enThemeCount} files fixed\n`);

console.log('Processing EN demo data (translations)...');
const enTransCount = processDirectory(enDir, translations);
console.log(`   Total: ${enTransCount} files translated\n`);

console.log(`Done! Fixed ${nlCount + enThemeCount} theme files, translated ${enTransCount} EN files.`);
