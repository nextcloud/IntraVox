#!/usr/bin/env node
/**
 * Translate EN demo data from Dutch to English
 * Updates all page content, titles, and navigation
 */

const fs = require('fs');
const path = require('path');

const enDir = path.join(__dirname, 'en');

// Translation dictionary - comprehensive Dutch to English
const translations = {
    // ============================================
    // LONG SENTENCES AND PARAGRAPHS (must be first)
    // ============================================

    // Layouts page content
    'Pagina Layouts': 'Page Layouts',
    'Flexibele layouts': 'Flexible layouts',
    'Flexibele Pagina Layouts': 'Flexible Page Layouts',
    'Creëer professionele pagina-indelingen met ons krachtige grid systeem. Van eenvoudige single-column tot complexe multi-column layouts.': 'Create professional page layouts with our powerful grid system. From simple single-column to complex multi-column layouts.',
    '1 Kolom Layout': '1 Column Layout',
    'Perfect voor lange artikelen, documentatie en blogs. De volledige breedte zorgt voor optimale leesbaarheid. Ideaal voor content-focused pagina\'s waar tekst centraal staat.\n\n> **Pro tip:** Gebruik een 1-kolom layout voor belangrijke aankondigingen en nieuws items.': 'Perfect for long articles, documentation and blogs. The full width ensures optimal readability. Ideal for content-focused pages where text is central.\n\n> **Pro tip:** Use a 1-column layout for important announcements and news items.',
    '2 Kolommen Layout': '2 Column Layout',
    '2 kolommen voorbeeld': '2 column example',
    'Balanceer tekst en media naast elkaar. Ideaal voor:\n\n- Product features\n- Vergelijkingen\n- Afbeelding + tekst combinaties\n- Team introductions': 'Balance text and media side by side. Ideal for:\n\n- Product features\n- Comparisons\n- Image + text combinations\n- Team introductions',
    'De 2-kolom layout verdeelt de ruimte gelijkmatig, waardoor je content en visuals mooi kunt combineren.': 'The 2-column layout divides the space evenly, allowing you to beautifully combine content and visuals.',
    'Eerste service met uitgebreide beschrijving en alle features.': 'First service with detailed description and all features.',
    'Tweede service met eigen unieke kenmerken en voordelen.': 'Second service with its own unique characteristics and benefits.',
    'Derde service die het aanbod completeert.': 'Third service that completes the offering.',
    '4+ Kolommen': '4+ Columns',
    'Voor feature grids, team overviewen en icon-based navigatie.': 'For feature grids, team overviews and icon-based navigation.',
    'Snelheid': 'Speed',
    'Razendsnelle laadtijden': 'Lightning-fast load times',
    'Veilig': 'Secure',
    'Enterprise beveiliging': 'Enterprise security',
    'Flexibel': 'Flexible',
    'Volledig aanpasbaar': 'Fully customizable',
    'Schaalbaar': 'Scalable',
    'Groeit met u mee': 'Grows with you',
    'Open': 'Open',
    '100% open source': '100% open source',
    'Side Columns': 'Side Columns',
    'Zijkolommen': 'Side Columns',
    'Voeg optionele linker- of rechterzijkolommen toe voor:\n\n| Gebruik | Voorbeeld |\n|---------|----------|\n| Navigatie | Inhoudsopgave, menu\'s |\n| Sidebar | Gerelateerde content |\n| Info | Contact, hulp |\n| Advertenties | Promoties, events |': 'Add optional left or right side columns for:\n\n| Use | Example |\n|---------|----------|\n| Navigation | Table of contents, menus |\n| Sidebar | Related content |\n| Info | Contact, help |\n| Advertisements | Promotions, events |',
    'Meer Layout Opties': 'More Layout Options',
    'Alle beschikbare widgets': 'All available widgets',
    'Menu configuratie': 'Menu configuration',
    'Stap-voor-stap tutorials': 'Step-by-step tutorials',
    'Overzicht alle functies': 'Overview of all features',
    'Back to Features': 'Back to Features',

    // Customers page content
    'Nextcloud wordt vertrouwd door **400.000+ servers** en **miljoenen gebruikers** wereldwijd, waaronder overheden, universiteiten en Fortune 500 bedrijven.': 'Nextcloud is trusted by **400,000+ servers** and **millions of users** worldwide, including governments, universities and Fortune 500 companies.',
    'De Duitse federale overheid gebruikt Nextcloud voor veilige documentuitwisseling tussen ministeries.\n\n**Resultaat:**\n- 300.000 ambtenaren connected\n- GDPR compliant\n- Volledige data soevereiniteit': 'The German federal government uses Nextcloud for secure document exchange between ministries.\n\n**Result:**\n- 300,000 officials connected\n- GDPR compliant\n- Full data sovereignty',
    'Het Europese laboratorium voor deeltjesfysica gebruikt Nextcloud voor wetenschappelijke samenwerking.\n\n**Resultaat:**\n- 12.000 onderzoekers\n- Petabytes aan data\n- Wereldwijde samenwerking': 'The European laboratory for particle physics uses Nextcloud for scientific collaboration.\n\n**Result:**\n- 12,000 researchers\n- Petabytes of data\n- Worldwide collaboration',

    // FAQ page content
    'IntraVox is een open source intranet applicatie voor Nextcloud. Het biedt SharePoint-achtige functionaliteit voor het maken van interne websites, pagina\'s en content management - volledig geïntegreerd met uw Nextcloud omgeving.': 'IntraVox is an open source intranet application for Nextcloud. It offers SharePoint-like functionality for creating internal websites, pages and content management - fully integrated with your Nextcloud environment.',
    'Yes! IntraVox is 100% open source en gratis te gebruiken onder de AGPL licentie. Er zijn geen verborgen kosten of premium features. Alle functionaliteit is available voor iedereen.': 'Yes! IntraVox is 100% open source and free to use under the AGPL license. There are no hidden costs or premium features. All functionality is available to everyone.',
    'IntraVox vereist Nextcloud 27 of hoger. We raden aan om altijd de nieuwste stabiele versie van Nextcloud te gebruiken voor de beste ervaring en beveiligingsupdates.': 'IntraVox requires Nextcloud 27 or higher. We recommend always using the latest stable version of Nextcloud for the best experience and security updates.',
    'Kan ik IntraVox aanpassen?': 'Can I customize IntraVox?',
    'Yes! Als open source software kunt u IntraVox volledig aanpassen aan uw behoeften. U kunt:\n- Custom widgets ontwikkelen\n- Styling aanpassen\n- Newe features toevoegen\n- Vertalingen bijdragen': 'Yes! As open source software you can fully customize IntraVox to your needs. You can:\n- Develop custom widgets\n- Customize styling\n- Add new features\n- Contribute translations',

    // Installation page content
    'Voeg gebruikers toe aan de IntraVox group:\n\n1. Go to Users > Groups\n2. Selecteer \"IntraVox Users\" of \"IntraVox Editors\"\n3. Voeg de gewenste gebruikers toe\n\n**Rollen:**\n- **Users**: Alleen lezen\n- **Editors**: Lezen en bewerken\n- **Admins**: Volledige controle': 'Add users to the IntraVox group:\n\n1. Go to Users > Groups\n2. Select "IntraVox Users" or "IntraVox Editors"\n3. Add the desired users\n\n**Roles:**\n- **Users**: Read only\n- **Editors**: Read and edit\n- **Admins**: Full control',
    'Maak uw eerste pagina': 'Create your first page',

    // Guides page content
    '**Newe pagina aanmaken:**\n1. Klik op \'Newe Pagina\' in de zijbalk\n2. Voer een titel in\n3. Klik \'Aanmaken\'\n4. Begin met het toevoegen van widgets\n\n**Tip:** Gebruik beschrijvende titels voor betere vindbaarheid.': '**Create new page:**\n1. Click "New Page" in the sidebar\n2. Enter a title\n3. Click "Create"\n4. Start adding widgets\n\n**Tip:** Use descriptive titles for better findability.',
    '**Widget toevoegen:**\n1. Klik op \'Widget Toevoegen\' in een rij\n2. Kies het widget type\n3. Configureer de widget inhoud\n4. Klik \'Save\'\n\n**Beschikbare widgets:** Tekst, Heading, Image, Links, Divider, Spacer': '**Add widget:**\n1. Click "Add Widget" in a row\n2. Choose the widget type\n3. Configure the widget content\n4. Click "Save"\n\n**Available widgets:** Text, Heading, Image, Links, Divider, Spacer',
    '**Menu bewerken:**\n1. Klik op \'Navigation Edit\'\n2. Kies menu type (Mega Menu of Dropdown)\n3. Voeg items toe met \'Item Toevoegen\'\n4. Sleep items om te ordenen\n5. Klik \'Save\'\n\n**Sub-items:** Maak geneste menu\'s voor complexe structuren.': '**Edit menu:**\n1. Click "Navigation Edit"\n2. Choose menu type (Mega Menu or Dropdown)\n3. Add items with "Add Item"\n4. Drag items to reorder\n5. Click "Save"\n\n**Sub-items:** Create nested menus for complex structures.',
    '**Kolommen instellen:**\n- 1-4 kolommen per rij\n- Side Columns links/rechts\n- Background colors per row\n\n**Header rij:**\nVoeg een header rij toe voor pagina breed banner effect met achtergrondkleur.': '**Set columns:**\n- 1-4 columns per row\n- Side columns left/right\n- Background colors per row\n\n**Header row:**\nAdd a header row for page-wide banner effect with background color.',
    'View onze video handleidingen voor een visuele uitleg van alle functies.': 'View our video guides for a visual explanation of all features.',

    // Tips page content
    'Bespaar tijd door widgets te dupliceren:\n1. Hover over een widget\n2. Klik op het kopieer icoon\n3. De widget wordt direct gedupliceerd\n4. Pas de inhoud aan': 'Save time by duplicating widgets:\n1. Hover over a widget\n2. Click the copy icon\n3. The widget is immediately duplicated\n4. Adjust the content',
    'Kleurgebruik': 'Color usage',
    '**Tip 1:** Gebruik de links widget met iconen om snelle navigatie te maken.\n\n**Tip 2:** Combineer een header rij met primaire achtergrondkleur voor impactvolle pagina headers.\n\n**Tip 3:** Gebruik 3 kolommen voor feature overviewen en 2 kolommen voor vergelijkingen.\n\n**Tip 4:** Voeg altijd alt-tekst toe aan afbeeldingen voor toegankelijkheid.': '**Tip 1:** Use the links widget with icons to create quick navigation.\n\n**Tip 2:** Combine a header row with primary background color for impactful page headers.\n\n**Tip 3:** Use 3 columns for feature overviews and 2 columns for comparisons.\n\n**Tip 4:** Always add alt text to images for accessibility.',

    // API page content
    'Alle endpoints gebruiken deze basis URL:\n\n```\nhttps://your-nextcloud.com/ocs/v2.php/apps/intravox/api/v1\n```\n\n**Headers:**\n```\nOCS-APIRequest: true\nContent-Type: application/json\n```': 'All endpoints use this base URL:\n\n```\nhttps://your-nextcloud.com/ocs/v2.php/apps/intravox/api/v1\n```\n\n**Headers:**\n```\nOCS-APIRequest: true\nContent-Type: application/json\n```',
    '| Method | Endpoint | Beschrijving |\n|--------|----------|-------------|\n| GET | /pages | Lijst alle pagina\'s |\n| GET | /pages/{id} | Haal specifieke pagina op |\n| POST | /pages | Maak nieuwe pagina |\n| PUT | /pages/{id} | Update bestaande pagina |\n| DELETE | /pages/{id} | Verwijder pagina |': '| Method | Endpoint | Description |\n|--------|----------|-------------|\n| GET | /pages | List all pages |\n| GET | /pages/{id} | Get specific page |\n| POST | /pages | Create new page |\n| PUT | /pages/{id} | Update existing page |\n| DELETE | /pages/{id} | Delete page |',
    'De API heeft de volgende limieten:\n\n- **100 requests per minuut** per gebruiker\n- **1000 requests per uur** per IP\n\nBij overschrijding ontvangt u HTTP 429 (Too Many Requests).': 'The API has the following limits:\n\n- **100 requests per minute** per user\n- **1000 requests per hour** per IP\n\nExceeding limits returns HTTP 429 (Too Many Requests).',

    // Documentation overview page
    'Alles wat u nodig heeft om IntraVox succesvol te gebruiken.': 'Everything you need to successfully use IntraVox.',
    'New at IntraVox? Begin hier met de basis:\n\n1. **Installation** - IntraVox installeren via de Nextcloud App Store\n2. **Configuration** - Basicinstellingen configureren\n3. **Eerste pagina** - Uw eerste IntraVox pagina maken\n4. **Navigation** - Menu structuur opzetten\n5. **Publiceren** - Content delen met uw team': 'New to IntraVox? Start here with the basics:\n\n1. **Installation** - Install IntraVox via the Nextcloud App Store\n2. **Configuration** - Configure basic settings\n3. **First page** - Create your first IntraVox page\n4. **Navigation** - Set up menu structure\n5. **Publish** - Share content with your team',

    // Features page content
    'Creëer dynamische pagina-indelingen met:\n\n**Kolom opties:**\n- 1, 2, 3 of 4 kolommen per rij\n- Linker en rechter zijkolommen\n- Background colors per row\n\n**Layout elementen:**\n- Scheidingslijnen\n- Ruimte/spacer widgets\n- Header rijen': 'Create dynamic page layouts with:\n\n**Column options:**\n- 1, 2, 3 or 4 columns per row\n- Left and right side columns\n- Background colors per row\n\n**Layout elements:**\n- Divider lines\n- Space/spacer widgets\n- Header rows',
    'Ideaal voor uitgebreide navigatie met veel pagina\'s en categorieën. Toont alle items in een overviewelijk raster.': 'Ideal for extensive navigation with many pages and categories. Shows all items in a clear grid.',
    'Zoekfunctie': 'Search function',
    'Doorzoek alle pagina\'s': 'Search all pages',

    // Navigation page content
    'Configureer krachtige navigatiesystemen voor uw intranet. Kies tussen mega menu\'s en dropdown menu\'s voor de perfecte gebruikerservaring.': 'Configure powerful navigation systems for your intranet. Choose between mega menus and dropdown menus for the perfect user experience.',
    'Het mega menu toont alle navigatie-items in een overviewelijk grid. Ideaal voor:\n\n- Grote websites met veel pagina\'s\n- Uitgebreide categoriestructuren\n- Visuele navigatie met iconen\n- Multi-level hiërarchieën': 'The mega menu displays all navigation items in a clear grid. Ideal for:\n\n- Large websites with many pages\n- Extensive category structures\n- Visual navigation with icons\n- Multi-level hierarchies',
    'Naast de hoofdnavigatie ondersteunt IntraVox ook:\n\n- **Breadcrumbs**: Automatische broodkruimelnavigatie gebaseerd op de paginastructuur\n- **Footer navigatie**: Aparte footer links voor belangrijke pagina\'s\n- **Zoekfunctie**: Doorzoek alle pagina\'s met full-text search': 'Besides main navigation, IntraVox also supports:\n\n- **Breadcrumbs**: Automatic breadcrumb navigation based on page structure\n- **Footer navigation**: Separate footer links for important pages\n- **Search function**: Search all pages with full-text search',

    // Widgets page content
    'Discover alle beschikbare widgets om uw pagina\'s tot leven te brengen. Van tekst tot afbeeldingen, van links tot interactieve elementen.': 'Discover all available widgets to bring your pages to life. From text to images, from links to interactive elements.',
    'Tips voor betere pagina\'s': 'Tips for better pages',

    // Sales pages
    'Overzicht alle afdelingen': 'Overview of all departments',

    // IT page
    'De IT-afdeling ondersteunt alle technologische aspecten van onze organisatie. Van helpdesk tot infrastructuur, wij staan voor u klaar.': 'The IT department supports all technological aspects of our organization. From helpdesk to infrastructure, we are here for you.',

    // Customers page - Additional
    'Nextcloud Gebruikers Wereldwijd': 'Nextcloud Users Worldwide',
    'Gebruikers': 'Users',
    'Frankrijk kiest voor open source met Nextcloud als digitale werkplek.\n\n**Resultaat:**\n- Ministeries geconnecteerd\n- Onafhankelijk van Big Tech\n- Europese data soevereiniteit': 'France chooses open source with Nextcloud as digital workplace.\n\n**Result:**\n- Ministries connected\n- Independent from Big Tech\n- European data sovereignty',

    // Installation page - Additional
    'IntraVox kan worden geïnstalleerd via de Nextcloud App Store:\n1. Go to Apps in uw Nextcloud admin panel\n2. Zoek naar \'IntraVox\'\n3. Klik \'Download en activeer\'\n4. Configureer de app in de instellingen': 'IntraVox can be installed via the Nextcloud App Store:\n1. Go to Apps in your Nextcloud admin panel\n2. Search for \'IntraVox\'\n3. Click \'Download and activate\'\n4. Configure the app in settings',
    'Activeer de app via de command line:\n\n```bash\nsudo -u www-data php occ app:enable intravox\n```\n\nOf via de web interface:\n1. Go to Settings > Apps\n2. Zoek IntraVox in de lijst\n3. Klik op \"': 'Activate the app via the command line:\n\n```bash\nsudo -u www-data php occ app:enable intravox\n```\n\nOr via the web interface:\n1. Go to Settings > Apps\n2. Find IntraVox in the list\n3. Click on \"',
    'Stap 4: Gebruikers Toevoegen': 'Step 4: Add Users',
    'Widgets Toevoegen': 'Adding Widgets',
    'Widget Dupliceren': 'Duplicate Widget',
    'Design Tips': 'Design Tips',

    // Tips page - Additional
    'Gebruik Markdown voor snelle opmaak:\n- `**vet**` voor **vet**\n- `*cursief*` voor *cursief*\n- `- item` voor lijsten\n- `| col |` voor tabellen': 'Use Markdown for quick formatting:\n- `**bold**` for **bold**\n- `*italic*` for *italic*\n- `- item` for lists\n- `| col |` for tables',
    'Gebruik achtergrondkleuren strategisch:\n- **Primair**: Voor belangrijke secties\n- **Light**: Voor subtiele accenten\n- **Hover**: Voor afwisselende secties': 'Use background colors strategically:\n- **Primary**: For important sections\n- **Light**: For subtle accents\n- **Hover**: For alternating sections',
    'Gebruik spacers en dividers:\n- Spacer voor ademruimte\n- Divider om secties te scheiden\n- Combineer voor professionele look': 'Use spacers and dividers:\n- Spacer for breathing room\n- Divider to separate sections\n- Combine for professional look',
    'Houd uw design consistent:\n- Dezelfde heading levels\n- Vergelijkbare kolomindelingen\n- Herkenbare kleurpatronen': 'Keep your design consistent:\n- Same heading levels\n- Similar column layouts\n- Recognizable color patterns',

    // Branding page
    '**Do\'s:**\n- Gebruik het logo met voldoende witruimte\n- Gebruik alleen goedgekeurde kleurvariaties\n- Houd de verhoudingen intact\n\n**Don\'ts:**\n- Verander de kleuren niet\n- Rek het logo niet uit\n- Voeg geen effecten toe': '**Do\'s:**\n- Use the logo with sufficient whitespace\n- Use only approved color variations\n- Keep proportions intact\n\n**Don\'ts:**\n- Don\'t change the colors\n- Don\'t stretch the logo\n- Don\'t add effects',
    '**Headlines:** Inter Bold\nGebruik voor koppen en titels\n\n**Body text:** Inter Regular\nVoor lopende tekst en content\n\n**Code:** JetBrains Mono\nTechnische documentatie': '**Headlines:** Inter Bold\nUse for headings and titles\n\n**Body text:** Inter Regular\nFor running text and content\n\n**Code:** JetBrains Mono\nTechnical documentation',

    // CRM page
    '**Elk klantcontact wordt gelogd:**\n\n- Phonegesprekken\n- Emails\n- Meetings\n- Support tickets\n- Demo sessies\n\n*Tip: Gebruik tags voor snelle filtering!*': '**Every customer contact is logged:**\n\n- Phone calls\n- Emails\n- Meetings\n- Support tickets\n- Demo sessions\n\n*Tip: Use tags for quick filtering!*',
    '- Update klantgegevens binnen 24 uur na contact\n- Voeg notities toe bij elke interactie\n- Plan follow-ups direct in\n- Gebruik de mobiele app voor onderweg\n- Controleer duplicaten maandelijks': '- Update customer data within 24 hours after contact\n- Add notes to every interaction\n- Schedule follow-ups immediately\n- Use the mobile app on the go\n- Check for duplicates monthly',

    // Features page - Additional
    'Discover de verschillende afdelingen binnen onze organisatie en hun specialisaties.': 'Discover the different departments within our organization and their specializations.',

    // IT page - Additional
    'De IT-afdeling biedt een breed scala aan services:\n\n- **Helpdesk**: Eerste lijn support voor alle IT-vragen\n- **Netwerk**: Beheer van netwerk infrastructuur\n- **Security**: Beveiliging en compliance\n- **Development**: Software ontwikkeling\n- **Cloud**: Cloud diensten en migraties': 'The IT department offers a wide range of services:\n\n- **Helpdesk**: First line support for all IT questions\n- **Network**: Network infrastructure management\n- **Security**: Security and compliance\n- **Development**: Software development\n- **Cloud**: Cloud services and migrations',

    // Marketing page - Additional
    'De Marketing afdeling is verantwoordelijk voor onze merkidentiteit, communicatie en klantenwerving.\n\n**Focusgebieden:**\n- Digitale marketing\n- Content creatie\n- Merkstrategie\n- Campagne management': 'The Marketing department is responsible for our brand identity, communication and customer acquisition.\n\n**Focus areas:**\n- Digital marketing\n- Content creation\n- Brand strategy\n- Campaign management',

    // Pipeline page
    '**Belangrijke regels:**\n\n- Update status dagelijks\n- Voeg expected close date toe\n- Schat dealwaarde realistisch in\n- Noteer next steps bij elke deal': '**Important rules:**\n\n- Update status daily\n- Add expected close date\n- Estimate deal value realistically\n- Note next steps for every deal',
    '**Wekelijkse rapportages:**\n\n- Maandag: Pipeline review\n- Woensdag: Forecast update\n- Vrijdag: Win/loss analyse': '**Weekly reports:**\n\n- Monday: Pipeline review\n- Wednesday: Forecast update\n- Friday: Win/loss analysis',

    // CRM page - Additional
    '**Newe klanten registreren:**\n\n1. Log in op het CRM portaal\n2. Klik op \'Newe Klant\'\n3. Vul alle verplichte velden in\n4. Wijs een Account Manager toe\n5. Sla op en start het onboarding proces': '**Register new customers:**\n\n1. Log in to the CRM portal\n2. Click "New Customer"\n3. Fill in all required fields\n4. Assign an Account Manager\n5. Save and start the onboarding process',

    // Onboarding page
    '**Planning:**\n\n- Maandag: Introductie & setup\n- Dinsdag: Product training\n- Woensdag: Team meetings\n- Donderdag: Klant demo\'s bijwonen\n- Vrijdag: Feedback & vragen': '**Planning:**\n\n- Monday: Introduction & setup\n- Tuesday: Product training\n- Wednesday: Team meetings\n- Thursday: Attend client demos\n- Friday: Feedback & questions',

    // IT page - Additional
    '**Systeem Status:**\nAlle systemen operationeel\n\n**Gepland onderhoud:**\nZaterdag 02:00-06:00': '**System Status:**\nAll systems operational\n\n**Planned maintenance:**\nSaturday 02:00-06:00',

    // Widgets page - Additional
    '**Tip:** Sleep widgets naar de gewenste positie voor perfecte layouts.': '**Tip:** Drag widgets to the desired position for perfect layouts.',
    'Creëer navigeerbare link collecties met iconen en beschrijvingen.': 'Create navigable link collections with icons and descriptions.',
    'Heading Widget': 'Heading Widget',
    'Image Widget': 'Image Widget',

    // Features page - Additional
    'Creëer dynamische pagina-indelingen met:\n\n**Kolom opties:**\n- 1, 2, 3 of 4 kolommen per rij\n- Linker en rechter zijkolommen\n- Background colors per row\n\n**Layout elementen:**\n- Scheidingslijnen\n- Ruimte/spacer widgets\n- Header rijen': 'Create dynamic page layouts with:\n\n**Column options:**\n- 1, 2, 3 or 4 columns per row\n- Left and right side columns\n- Background colors per row\n\n**Layout elements:**\n- Divider lines\n- Space/spacer widgets\n- Header rows',

    // HR page - Additional
    'Alle belangrijke regels en richtlijnen voor medewerkers van IntraVox.': 'All important rules and guidelines for IntraVox employees.',
    'Get in touch met het HR-team:\n\n**Email:** hr@company.nl\n**Phone:** 020-123 4567\n**Office:** Kamer 2.01\n\n**Opening hours:**\nMaandag t/m vrijdag\n09:00 - 17:00': 'Get in touch with the HR team:\n\n**Email:** hr@company.nl\n**Phone:** 020-123 4567\n**Office:** Room 2.01\n\n**Opening hours:**\nMonday to Friday\n09:00 - 17:00',

    // About page - Additional
    'Een wereld waarin elke organisatie toegang heeft tot **professionele intranet functionaliteit** zonder afhankelijk te zijn van dure, gesloten systemen.\n\nIntraVox maakt dit mogelijk door:\n- Integratie met Nextcloud\n- Moderne webtechnologieën\n- Community-gedreven ontwikkeling\n- Transparante roadmap': 'A world where every organization has access to **professional intranet functionality** without relying on expensive, closed systems.\n\nIntraVox makes this possible through:\n- Integration with Nextcloud\n- Modern web technologies\n- Community-driven development\n- Transparent roadmap',

    // Social media page
    'Download goedgekeurde templates en assets voor uw social posts:\n\n- Logo variaties\n- Kleurenpaletten\n- Fonts en typografie\n- Template designs': 'Download approved templates and assets for your social posts:\n\n- Logo variations\n- Color palettes\n- Fonts and typography\n- Template designs',

    // Home page content
    'IntraVox - Uw Moderne Intranet Oplossing': 'IntraVox - Your Modern Intranet Solution',
    '**Transformeer uw organisatie** met een krachtig intranet platform gebouwd op Nextcloud. Open source, veilig en volledig aanpasbaar.': '**Transform your organization** with a powerful intranet platform built on Nextcloud. Open source, secure and fully customizable.',
    'Wat is IntraVox?': 'What is IntraVox?',
    'IntraVox is een **moderne intranet applicatie** voor Nextcloud die SharePoint-achtige content management naar de open-source wereld brengt.': 'IntraVox is a **modern intranet application** for Nextcloud that brings SharePoint-like content management to the open-source world.',
    '**Waarom kiezen voor IntraVox?**': '**Why choose IntraVox?**',
    'Creëer prachtige pagina\'s met een visuele editor': 'Create beautiful pages with a visual editor',
    'Organiseer content met multi-level navigatie': 'Organize content with multi-level navigation',
    'Ondersteuning voor meerdere talen': 'Support for multiple languages',
    'Veilige team samenwerking': 'Secure team collaboration',
    'Volledige integratie met Nextcloud': 'Full integration with Nextcloud',
    'Krachtige Functies': 'Powerful Features',
    'Maak professionele pagina\'s met tekst, afbeeldingen, links en meer. De drag-and-drop editor maakt het eenvoudig om content te beheren.': 'Create professional pages with text, images, links and more. The drag-and-drop editor makes it easy to manage content.',
    'Naadloze Samenwerking': 'Seamless Collaboration',
    'Werk samen met uw team in real-time. Deel kennis, bouw mooie intranets en houd iedereen verbonden.': 'Work together with your team in real-time. Share knowledge, build beautiful intranets and keep everyone connected.',
    '100% Open Source': '100% Open Source',
    'Geen vendor lock-in, geen verborgen kosten. IntraVox is volledig open source onder de AGPL licentie.': 'No vendor lock-in, no hidden costs. IntraVox is fully open source under the AGPL license.',
    'Snelle Links': 'Quick Links',
    'Ontdek onze missie en visie': 'Discover our mission and vision',
    'Bekijk alle mogelijkheden': 'View all possibilities',
    'Leer meer over het platform': 'Learn more about the platform',
    'Neem contact met ons op': 'Contact us',
    'Modern & Intuïtief': 'Modern & Intuitive',
    'Een prachtige, gebruiksvriendelijke interface waar uw team van zal houden. Gebouwd met moderne webtechnologieën voor de beste gebruikerservaring.': 'A beautiful, user-friendly interface your team will love. Built with modern web technologies for the best user experience.',
    '**Kenmerken:**': '**Features:**',
    'Responsive design voor alle apparaten': 'Responsive design for all devices',
    'Intuïtieve drag-and-drop editor': 'Intuitive drag-and-drop editor',
    'Real-time preview': 'Real-time preview',
    'Veilig by Design': 'Secure by Design',
    'Uw data blijft op uw eigen servers. Gebouwd op Nextcloud\'s bewezen beveiligingsarchitectuur met enterprise-grade bescherming.': 'Your data stays on your own servers. Built on Nextcloud\'s proven security architecture with enterprise-grade protection.',
    '**Beveiliging:**': '**Security:**',
    'End-to-end encryptie': 'End-to-end encryption',
    'GDPR compliant': 'GDPR compliant',
    'On-premise hosting': 'On-premise hosting',
    'Externe Bronnen': 'External Resources',
    'Ontdek meer over Nextcloud en de open source community:': 'Discover more about Nextcloud and the open source community:',
    'Officiële Nextcloud website': 'Official Nextcloud website',
    'Bekijk de broncode': 'View the source code',
    'Nextcloud handleidingen': 'Nextcloud guides',
    'Nieuwste Updates': 'Latest Updates',
    'Blijf op de hoogte van het laatste nieuws, evenementen en aankondigingen van onze organisatie.': 'Stay up to date with the latest news, events and announcements from our organization.',
    'Laatste updates en aankondigingen': 'Latest updates and announcements',
    'Informatie per afdeling': 'Information per department',
    'Snel Navigeren': 'Quick Navigation',
    'Over Ons': 'About Us',
    'Hulp Nodig?': 'Need Help?',
    'Onze support is beschikbaar op werkdagen van 9:00 - 17:00': 'Our support is available on weekdays from 9:00 - 17:00',
    'Support Portal': 'Support Portal',
    'Team samenwerking met IntraVox': 'Team collaboration with IntraVox',

    // Nextcloud page
    'Nextcloud - Het Fundament van IntraVox': 'Nextcloud - The Foundation of IntraVox',
    'IntraVox is gebouwd op Nextcloud, het toonaangevende open source platform voor veilige samenwerking.': 'IntraVox is built on Nextcloud, the leading open source platform for secure collaboration.',
    'Wat is Nextcloud?': 'What is Nextcloud?',
    'Nextcloud is een **self-hosted productiviteitsplatform** dat de meest populaire cloud functionaliteiten combineert:': 'Nextcloud is a **self-hosted productivity platform** that combines the most popular cloud functionalities:',
    '**Bestandssynchronisatie** - Sync uw bestanden tussen alle apparaten': '**File synchronization** - Sync your files across all devices',
    '**Delen** - Deel bestanden veilig binnen en buiten uw organisatie': '**Sharing** - Share files securely within and outside your organization',
    '**Samenwerking** - Real-time document bewerking': '**Collaboration** - Real-time document editing',
    '**Communicatie** - Chat, videobellen en agenda\'s': '**Communication** - Chat, video calls and calendars',
    '**Extensies** - Honderden apps in de App Store': '**Extensions** - Hundreds of apps in the App Store',
    'Data Soevereiniteit': 'Data Sovereignty',
    'Uw data blijft op **uw servers**. Geen Amerikaanse cloud providers, volledige controle over waar uw gegevens worden opgeslagen.': 'Your data stays on **your servers**. No American cloud providers, full control over where your data is stored.',
    'Enterprise Ready': 'Enterprise Ready',
    'Gebruikt door **overheden**, **universiteiten** en **bedrijven** wereldwijd. Schaalbaar van 10 tot 100.000+ gebruikers.': 'Used by **governments**, **universities** and **companies** worldwide. Scalable from 10 to 100,000+ users.',
    'GDPR Compliant': 'GDPR Compliant',
    'Volledig **AVG/GDPR compliant**. Ideaal voor Europese organisaties die waarde hechten aan privacy en compliance.': 'Fully **GDPR compliant**. Ideal for European organizations that value privacy and compliance.',
    'Nextcloud Hub': 'Nextcloud Hub',
    'Nextcloud Hub integreert alle tools die uw team nodig heeft:': 'Nextcloud Hub integrates all the tools your team needs:',
    '**Files** - Bestanden opslaan en delen': '**Files** - Store and share files',
    '**Talk** - Videovergaderingen en chat': '**Talk** - Video meetings and chat',
    '**Groupware** - Agenda, mail en contacten': '**Groupware** - Calendar, mail and contacts',
    '**Office** - Document bewerking': '**Office** - Document editing',
    'App Ecosysteem': 'App Ecosystem',
    'De Nextcloud App Store bevat honderden extensies:': 'The Nextcloud App Store contains hundreds of extensions:',
    '**IntraVox** - Uw intranet oplossing': '**IntraVox** - Your intranet solution',
    '**Deck** - Kanban projectborden': '**Deck** - Kanban project boards',
    '**Forms** - Online formulieren': '**Forms** - Online forms',
    '**Polls** - Stemmingen en enquêtes': '**Polls** - Votes and surveys',
    'Nextcloud Resources': 'Nextcloud Resources',
    'Nextcloud Partners': 'Nextcloud Partners',
    'Nextcloud wordt ondersteund door een wereldwijd netwerk van partners die implementatie, hosting en support bieden. Wilt u Nextcloud implementeren? Neem contact met ons op voor advies.': 'Nextcloud is supported by a worldwide network of partners offering implementation, hosting and support. Want to implement Nextcloud? Contact us for advice.',

    // Events page
    'Evenementen & Webinars': 'Events & Webinars',
    'Mis geen enkel evenement! Van interne workshops tot externe conferenties - blijf verbonden met de community.': 'Don\'t miss any event! From internal workshops to external conferences - stay connected with the community.',
    'Aankomende Evenementen': 'Upcoming Events',
    'Webinar: IntraVox voor Beginners': 'Webinar: IntraVox for Beginners',
    '**Datum:** 15 december 2024': '**Date:** December 15, 2024',
    '**Tijd:** 14:00 - 15:30': '**Time:** 2:00 PM - 3:30 PM',
    '**Locatie:** Online (Zoom)': '**Location:** Online (Zoom)',
    'Leer de basis van IntraVox in 90 minuten. Perfect voor nieuwe gebruikers die snel productief willen worden.': 'Learn the basics of IntraVox in 90 minutes. Perfect for new users who want to become productive quickly.',
    'Workshop: Geavanceerde Layouts': 'Workshop: Advanced Layouts',
    '**Datum:** 20 december 2024': '**Date:** December 20, 2024',
    '**Tijd:** 10:00 - 12:00': '**Time:** 10:00 AM - 12:00 PM',
    '**Locatie:** Kantoor Amsterdam': '**Location:** Amsterdam Office',
    'Duik dieper in layout mogelijkheden, zijkolommen en achtergrondkleuren voor professionele pagina\'s.': 'Dive deeper into layout options, side columns and background colors for professional pages.',
    'Evenementen Kalender': 'Events Calendar',
    'Afgelopen Evenementen': 'Past Events',
    '| Datum | Evenement | Opname |': '| Date | Event | Recording |',
    '| Nov 2024 | Webinar: Dark Mode | Beschikbaar |': '| Nov 2024 | Webinar: Dark Mode | Available |',
    '| Okt 2024 | Workshop: Multi-taal | Beschikbaar |': '| Oct 2024 | Workshop: Multi-language | Available |',
    '| Sep 2024 | Lancering 0.4.0 | Beschikbaar |': '| Sep 2024 | Launch 0.4.0 | Available |',
    '| Aug 2024 | Zomer BBQ | - |': '| Aug 2024 | Summer BBQ | - |',
    'Gerelateerd': 'Related',
    'Volgende Event': 'Next Event',
    '**Webinar: Beginners**': '**Webinar: Beginners**',
    '15 december 2024': 'December 15, 2024',
    '14:00 - 15:30': '2:00 PM - 3:30 PM',
    'Online via Zoom': 'Online via Zoom',
    'Nieuwsbrief': 'Newsletter',
    'Ontvang event updates in uw inbox.': 'Receive event updates in your inbox.',

    // Product Updates page
    'Blijf op de hoogte van de nieuwste features en verbeteringen. Elke maand brengen we updates die uw ervaring verbeteren.': 'Stay up to date with the latest features and improvements. Every month we release updates that improve your experience.',
    'November 2024': 'November 2024',
    'Dark Mode': 'Dark Mode',
    'Nieuw: Donker thema voor IntraVox! Volgt automatisch uw systeemvoorkeuren of stel handmatig in.': 'New: Dark theme for IntraVox! Automatically follows your system preferences or set manually.',
    'Sneller Zoeken': 'Faster Search',
    'Zoekresultaten worden nu 10x sneller geladen dankzij onze nieuwe index engine.': 'Search results now load 10x faster thanks to our new index engine.',
    'Mobiel Friendly': 'Mobile Friendly',
    'Responsive design verbeterd voor tablets en smartphones. Bewerk pagina\'s onderweg!': 'Responsive design improved for tablets and smartphones. Edit pages on the go!',
    'Oktober 2024': 'October 2024',
    'Multi-taal Ondersteuning': 'Multi-language Support',
    'Beheer content in meerdere talen vanuit één dashboard. Automatische taaldetectie voor bezoekers.': 'Manage content in multiple languages from one dashboard. Automatic language detection for visitors.',
    '**Ondersteunde talen:**': '**Supported languages:**',
    '- Nederlands': '- Dutch',
    '- Engels': '- English',
    '- Duits': '- German',
    '- Frans': '- French',
    '- Spaans': '- Spanish',
    'Verbeterde Editor': 'Improved Editor',
    'De drag-and-drop editor is volledig vernieuwd:': 'The drag-and-drop editor has been completely redesigned:',
    '- Real-time preview': '- Real-time preview',
    '- Undo/redo ondersteuning': '- Undo/redo support',
    '- Keyboard shortcuts': '- Keyboard shortcuts',
    '- Auto-save functie': '- Auto-save function',
    '- Verbeterde prestaties': '- Improved performance',
    'Changelog Archief': 'Changelog Archive',
    '| Versie | Datum | Highlights |': '| Version | Date | Highlights |',
    '| 0.5.0 | Nov 2024 | Dark mode, sneller zoeken |': '| 0.5.0 | Nov 2024 | Dark mode, faster search |',
    '| 0.4.5 | Okt 2024 | Multi-taal, nieuwe editor |': '| 0.4.5 | Oct 2024 | Multi-language, new editor |',
    '| 0.4.0 | Sep 2024 | Mega menu, sidebar layouts |': '| 0.4.0 | Sep 2024 | Mega menu, sidebar layouts |',
    '| 0.3.5 | Aug 2024 | Image optimization |': '| 0.3.5 | Aug 2024 | Image optimization |',
    '| 0.3.0 | Jul 2024 | Links widget, dividers |': '| 0.3.0 | Jul 2024 | Links widget, dividers |',
    'Meer Nieuws': 'More News',

    // News page
    'Nieuws & Updates': 'News & Updates',
    'Blijf op de hoogte van de laatste ontwikkelingen bij IntraVox en Nextcloud.': 'Stay up to date with the latest developments at IntraVox and Nextcloud.',
    'Laatste Nieuws': 'Latest News',
    'IntraVox v1.0 Gelanceerd!': 'IntraVox v1.0 Launched!',
    '**29 november 2024** - We zijn verheugd om de eerste stabiele versie van IntraVox aan te kondigen! Na maanden van ontwikkeling en testen is IntraVox nu beschikbaar voor alle Nextcloud gebruikers.': '**November 29, 2024** - We are excited to announce the first stable version of IntraVox! After months of development and testing, IntraVox is now available for all Nextcloud users.',
    '**Highlights:**': '**Highlights:**',
    '- Visuele pagina editor met drag-and-drop': '- Visual page editor with drag-and-drop',
    '- Ondersteuning voor meerdere talen': '- Support for multiple languages',
    '- Mega menu en dropdown navigatie': '- Mega menu and dropdown navigation',
    '- Integratie met Nextcloud Group Folders': '- Integration with Nextcloud Group Folders',
    'Nextcloud Conference 2024 Recap': 'Nextcloud Conference 2024 Recap',
    '**15 oktober 2024** - IntraVox was aanwezig op de Nextcloud Conference in Berlijn. We hebben een live demo gegeven van de nieuwste functies en waardevolle feedback ontvangen van de community.': '**October 15, 2024** - IntraVox was present at the Nextcloud Conference in Berlin. We gave a live demo of the latest features and received valuable feedback from the community.',
    '[Bekijk de presentatie slides](https://nextcloud.com/conf/)': '[View the presentation slides](https://nextcloud.com/conf/)',
    'Beta Programma Gesloten': 'Beta Program Closed',
    '**1 september 2024** - Ons beta programma is succesvol afgerond met meer dan 50 organisaties die IntraVox hebben getest. Bedankt aan alle beta testers voor hun waardevolle feedback!': '**September 1, 2024** - Our beta program has been successfully completed with more than 50 organizations that tested IntraVox. Thanks to all beta testers for their valuable feedback!',
    'Webinar: IntraVox Intro': 'Webinar: IntraVox Intro',
    '**Datum:** 15 december 2024\\n**Tijd:** 14:00 - 15:00 CET': '**Date:** December 15, 2024\\n**Time:** 2:00 PM - 3:00 PM CET',
    'Een introductie tot IntraVox voor nieuwe gebruikers. Leer de basis en stel vragen aan het development team.': 'An introduction to IntraVox for new users. Learn the basics and ask questions to the development team.',
    'FOSDEM 2025': 'FOSDEM 2025',
    '**Datum:** 1-2 februari 2025\\n**Locatie:** Brussel, België': '**Date:** February 1-2, 2025\\n**Location:** Brussels, Belgium',
    'Bezoek ons op FOSDEM! We zijn aanwezig in de Nextcloud devroom met een presentatie over IntraVox.': 'Visit us at FOSDEM! We will be present in the Nextcloud devroom with a presentation about IntraVox.',
    'Blijf Op De Hoogte': 'Stay Informed',

    // Contact page
    'Wij staan klaar om uw vragen te beantwoorden en u te helpen met IntraVox.': 'We are ready to answer your questions and help you with IntraVox.',
    'Neem Contact Op': 'Get In Touch',
    'Heeft u vragen over IntraVox? Wilt u een demo? Of heeft u technische ondersteuning nodig?': 'Do you have questions about IntraVox? Would you like a demo? Or do you need technical support?',
    '**E-mail:** info@intravox.nl': '**Email:** info@intravox.nl',
    '**Telefoon:** +31 (0)20 123 4567': '**Phone:** +31 (0)20 123 4567',
    '**Support:** support@intravox.nl': '**Support:** support@intravox.nl',
    '**Openingstijden:**': '**Opening hours:**',
    'Maandag - Vrijdag: 09:00 - 17:00 CET': 'Monday - Friday: 09:00 - 17:00 CET',
    'Locatie': 'Location',
    '**IntraVox BV**': '**IntraVox BV**',
    'Sciencepark 402': 'Sciencepark 402',
    '1098 XH Amsterdam': '1098 XH Amsterdam',
    'Nederland': 'The Netherlands',
    '*Makkelijk bereikbaar met OV*': '*Easy to reach by public transport*',
    'Station Amsterdam Science Park: 5 min lopen': 'Amsterdam Science Park station: 5 min walk',
    'Support': 'Support',
    'Onze support team staat voor u klaar.': 'Our support team is ready for you.',
    '**Community:** GitHub Issues': '**Community:** GitHub Issues',
    '**Priority:** Enterprise support': '**Priority:** Enterprise support',
    '**Docs:** docs.intravox.nl': '**Docs:** docs.intravox.nl',
    'Contact Opties': 'Contact Options',
    'Demo Aanvragen': 'Request Demo',
    'Wilt u IntraVox in actie zien? Vraag een **gratis demo** aan!': 'Would you like to see IntraVox in action? Request a **free demo**!',
    'Onze product specialisten nemen binnen 24 uur contact met u op om een demo in te plannen. Tijdens de demo laten we u zien hoe IntraVox uw interne communicatie kan verbeteren.': 'Our product specialists will contact you within 24 hours to schedule a demo. During the demo, we will show you how IntraVox can improve your internal communication.',
    '**Wat u kunt verwachten:**': '**What you can expect:**',
    '- Persoonlijke rondleiding door IntraVox': '- Personal tour of IntraVox',
    '- Beantwoording van al uw vragen': '- Answers to all your questions',
    '- Advies voor uw specifieke situatie': '- Advice for your specific situation',
    '- Geen verplichtingen': '- No obligations',
    'Volg Ons': 'Follow Us',
    '© 2025 IntraVox - [Contact](#) | [Documentatie](#) | [Support](#)': '© 2025 IntraVox - [Contact](#) | [Documentation](#) | [Support](#)',

    // Pricing page
    'Prijzen & Pakketten': 'Pricing & Packages',
    'IntraVox is **100% gratis en open source**. Geen verborgen kosten, geen beperkingen.': 'IntraVox is **100% free and open source**. No hidden costs, no limitations.',
    'Open Source = Gratis': 'Open Source = Free',
    'IntraVox is volledig **open source** onder de AGPL v3 licentie. Dit betekent:': 'IntraVox is fully **open source** under the AGPL v3 license. This means:',
    '**Gratis te downloaden** en te gebruiken': '**Free to download** and use',
    '**Geen gebruikerslimiet** - onbeperkt aantal gebruikers': '**No user limit** - unlimited users',
    '**Alle functies inbegrepen** - geen premium tier': '**All features included** - no premium tier',
    '**Volledige broncode** beschikbaar op GitHub': '**Full source code** available on GitHub',
    'Community': 'Community',
    '**Gratis**': '**Free**',
    '- Alle IntraVox functies': '- All IntraVox features',
    '- Community support': '- Community support',
    '- GitHub issues': '- GitHub issues',
    '- Documentatie': '- Documentation',
    '- Forum toegang': '- Forum access',
    '**Op aanvraag**': '**On request**',
    '- Prioriteit support': '- Priority support',
    '- Training sessies': '- Training sessions',
    '- Implementatie hulp': '- Implementation help',
    '- Custom ontwikkeling': '- Custom development',
    '- SLA garantie': '- SLA guarantee',
    '**Op maat**': '**Custom**',
    '- Dedicated support': '- Dedicated support',
    '- On-premise installatie': '- On-premise installation',
    '- Custom features': '- Custom features',
    '- Security audits': '- Security audits',
    '- 24/7 support optie': '- 24/7 support option',
    'Vergelijk met Alternatieven': 'Compare with Alternatives',
    '| Aspect | IntraVox | SharePoint | Confluence |': '| Aspect | IntraVox | SharePoint | Confluence |',
    '| Licentiekosten | Gratis | €4-23/gebruiker/maand | €5-15/gebruiker/maand |': '| License costs | Free | €4-23/user/month | €5-15/user/month |',
    '| Hosting | Self-hosted | Cloud of On-premise | Cloud of On-premise |': '| Hosting | Self-hosted | Cloud or On-premise | Cloud or On-premise |',
    '| Vendor Lock-in | Nee | Ja | Ja |': '| Vendor Lock-in | No | Yes | Yes |',
    '| Open Source | Ja | Nee | Nee |': '| Open Source | Yes | No | No |',
    '| 100 gebruikers/jaar | €0 | €4.800 - €27.600 | €6.000 - €18.000 |': '| 100 users/year | €0 | €4,800 - €27,600 | €6,000 - €18,000 |',
    'Hosting Opties': 'Hosting Options',
    'Nextcloud (en dus IntraVox) kan op verschillende manieren worden gehost:': 'Nextcloud (and thus IntraVox) can be hosted in different ways:',

    // Features page
    'IntraVox Functies': 'IntraVox Features',
    'Ontdek de krachtige mogelijkheden van IntraVox voor uw organisatie.': 'Discover the powerful capabilities of IntraVox for your organization.',
    'Pagina Editor': 'Page Editor',
    'De visuele editor maakt het eenvoudig om professionele pagina\'s te maken zonder technische kennis.': 'The visual editor makes it easy to create professional pages without technical knowledge.',
    'Tekst Widget': 'Text Widget',
    'Voeg rijke tekst toe met **Markdown** ondersteuning:': 'Add rich text with **Markdown** support:',
    '- Vetgedrukt en cursief': '- Bold and italic',
    '- Lijsten en opsommingen': '- Lists and bullet points',
    '- Links en afbeeldingen': '- Links and images',
    '- Tabellen': '- Tables',
    'Afbeelding Widget': 'Image Widget',
    'Upload en toon afbeeldingen:': 'Upload and display images:',
    '- Automatische optimalisatie': '- Automatic optimization',
    '- Alt-tekst voor toegankelijkheid': '- Alt text for accessibility',
    '- Responsive weergave': '- Responsive display',
    '- Diverse formaten': '- Various formats',
    'Links Widget': 'Links Widget',
    'Creëer navigatie-elementen:': 'Create navigation elements:',
    '- Interne paginalinks': '- Internal page links',
    '- Externe URLs': '- External URLs',
    '- Pictogrammen': '- Icons',
    '- Grid layout': '- Grid layout',
    'Flexibele Layouts': 'Flexible Layouts',
    'Ontwerp uw pagina\'s met maximale flexibiliteit.': 'Design your pages with maximum flexibility.',
    'Kolom Layouts': 'Column Layouts',
    'Verdeel content over 1-4 kolommen:': 'Divide content across 1-4 columns:',
    '- Automatische responsive aanpassing': '- Automatic responsive adjustment',
    '- Gelijke of aangepaste breedtes': '- Equal or custom widths',
    '- Achtergrondkleuren per rij': '- Background colors per row',
    'Zijkolommen': 'Side Columns',
    'Voeg optionele zijkolommen toe:': 'Add optional side columns:',
    '- Links en/of rechts': '- Left and/or right',
    '- Vaste breedte': '- Fixed width',
    '- Eigen widgets': '- Custom widgets',
    '- Ideaal voor navigatie': '- Ideal for navigation',
    'Dividers': 'Dividers',
    'Structureer uw pagina met scheidingslijnen:': 'Structure your page with dividing lines:',
    '- Verschillende stijlen': '- Different styles',
    '- Aanpasbare kleur': '- Customizable color',
    '- Variabele hoogte': '- Variable height',
    'Mega Menu & Dropdown': 'Mega Menu & Dropdown',
    'Kies uit twee navigatiestijlen voor uw intranet.': 'Choose from two navigation styles for your intranet.',
    'Dropdown Menu': 'Dropdown Menu',
    'Klassieke dropdown navigatie:': 'Classic dropdown navigation:',
    '- Tot 3 niveaus diep': '- Up to 3 levels deep',
    '- Hover of click activatie': '- Hover or click activation',
    '- Compact en overzichtelijk': '- Compact and clear',
    'Mega Menu': 'Mega Menu',
    'Uitgebreide mega menu navigatie:': 'Extended mega menu navigation:',
    '- Alle opties zichtbaar': '- All options visible',
    '- Groepeer per categorie': '- Group by category',
    '- Ideaal voor grote sites': '- Ideal for large sites',
    'Navigatie Beheer': 'Navigation Management',
    'Beheer eenvoudig de navigatiestructuur met onze drag-and-drop editor. Voeg pagina\'s toe, herorden items en maak geneste menu\'s.': 'Easily manage the navigation structure with our drag-and-drop editor. Add pages, reorder items and create nested menus.',

    // Department pages
    'Welkom bij de Afdelingen': 'Welcome to the Departments',
    'Vind informatie per afdeling. Klik op een afdeling om meer te weten.': 'Find information per department. Click on a department to learn more.',
    'Kies een afdeling': 'Choose a department',
    'Creatieve oplossingen': 'Creative solutions',
    'Klantrelaties': 'Customer relations',
    'Personeelszaken': 'Personnel matters',
    'Technische ondersteuning': 'Technical support',

    // IT Department pages
    'Welkom bij IT': 'Welcome to IT',
    'Technische ondersteuning voor alle medewerkers. We staan voor je klaar!': 'Technical support for all employees. We are here for you!',
    'Helpdesk': 'Helpdesk',
    'Problemen of vragen? Maak een ticket aan.': 'Problems or questions? Create a ticket.',
    'Systemen': 'Systems',
    'Overzicht van alle software en tools.': 'Overview of all software and tools.',
    'Security': 'Security',
    'Richtlijnen voor veilig werken.': 'Guidelines for working safely.',
    'IT Support Overzicht': 'IT Support Overview',
    'Wij helpen u met al uw technische vragen en problemen.': 'We help you with all your technical questions and problems.',
    'Hoe kunnen we helpen?': 'How can we help?',
    'Contact opnemen': 'Get in touch',
    'support@intravox.io': 'support@intravox.io',
    'Werkdagen 9:00-17:00': 'Weekdays 9:00-17:00',
    'Alleen bij spoed': 'Emergencies only',
    'Uw IT verzoek wordt zo snel mogelijk behandeld.': 'Your IT request will be processed as soon as possible.',
    'Gemiddelde responstijd': 'Average response time',
    'Probeer eerst zelf': 'Try yourself first',
    'Bekijk de FAQ of zoek in de kennisbank voor snelle oplossingen.': 'Check the FAQ or search the knowledge base for quick solutions.',
    'Terug naar IT': 'Back to IT',
    'IT overzicht': 'IT overview',

    // IT Helpdesk page
    'Welkom bij de IT Helpdesk': 'Welcome to the IT Helpdesk',
    'Technische ondersteuning voor alle medewerkers bij IntraVox.': 'Technical support for all employees at IntraVox.',
    'Contact Opnemen': 'Get In Touch',
    'Maak een ticket aan via het support portaal of mail naar support@intravox.io. We reageren binnen 4 uur op werkdagen.': 'Create a ticket via the support portal or email support@intravox.io. We respond within 4 hours on working days.',
    'Zelfservice': 'Self-Service',
    'Veelvoorkomende problemen kunt u zelf oplossen via onze handleidingen en FAQ.': 'Common problems can be solved yourself via our guides and FAQ.',
    'Openingstijden': 'Opening Hours',
    'Ma-Vr: 9:00-17:00': 'Mon-Fri: 9:00-17:00',
    'Weekend: Alleen spoed': 'Weekend: Emergencies only',

    // IT Systems page
    'Systemen & Tools': 'Systems & Tools',
    'Overzicht van alle software en systemen die we gebruiken bij IntraVox.': 'Overview of all software and systems we use at IntraVox.',
    'Communicatie Tools': 'Communication Tools',
    '**Nextcloud Talk** - Videovergaderingen en chat\\n**E-mail** - Microsoft 365\\n**Slack** - Team communicatie': '**Nextcloud Talk** - Video meetings and chat\\n**Email** - Microsoft 365\\n**Slack** - Team communication',
    'Productiviteit': 'Productivity',
    '**Nextcloud Files** - Bestandsopslag\\n**Nextcloud Office** - Documenten bewerken\\n**Calendar** - Agenda beheer': '**Nextcloud Files** - File storage\\n**Nextcloud Office** - Document editing\\n**Calendar** - Calendar management',
    'Ontwikkeling': 'Development',
    '**GitHub** - Broncode beheer\\n**VS Code** - Code editor\\n**Docker** - Containers': '**GitHub** - Source code management\\n**VS Code** - Code editor\\n**Docker** - Containers',
    'Toegang Aanvragen': 'Request Access',
    'Nieuwe toegang nodig? Dien een IT ticket in met:\\n- Welk systeem\\n- Waarom toegang nodig\\n- Goedkeuring van manager': 'Need new access? Submit an IT ticket with:\\n- Which system\\n- Why access is needed\\n- Manager approval',

    // Security page
    'Richtlijnen voor veilig werken en bescherming van bedrijfsgegevens.': 'Guidelines for safe working and protection of company data.',
    'Wachtwoorden': 'Passwords',
    '**Vereisten:**\\n\\n- Minimaal 12 karakters\\n- Mix van letters, cijfers, symbolen\\n- Uniek per applicatie\\n- Wijzig elke 90 dagen\\n\\n*Gebruik een password manager!*': '**Requirements:**\\n\\n- Minimum 12 characters\\n- Mix of letters, numbers, symbols\\n- Unique per application\\n- Change every 90 days\\n\\n*Use a password manager!*',
    '2FA Authenticatie': '2FA Authentication',
    '**Verplicht voor:**\\n\\n- Nextcloud\\n- E-mail\\n- VPN\\n- Admin accounts\\n\\n*Setup via de IT Helpdesk*': '**Required for:**\\n\\n- Nextcloud\\n- Email\\n- VPN\\n- Admin accounts\\n\\n*Setup via IT Helpdesk*',
    'Phishing & Scams': 'Phishing & Scams',
    '**Let op verdachte e-mails!**\\n\\nKlik NOOIT op links in onverwachte e-mails. Twijfel je? Meld het bij IT Security: security@intravox.io': '**Watch out for suspicious emails!**\\n\\nNEVER click on links in unexpected emails. In doubt? Report it to IT Security: security@intravox.io',
    'Clean Desk': 'Clean Desk',
    '- Vergrendel je scherm (Win+L)\\n- Geen wachtwoorden op papier\\n- Gevoelige docs in de kluis\\n- Ruim je bureau op': '- Lock your screen (Win+L)\\n- No passwords on paper\\n- Sensitive docs in the vault\\n- Clean up your desk',
    'Incident Melden': 'Report Incident',
    'Beveiligingsincident?\\n\\n**Meld direct bij:**\\nsecurity@intravox.io\\n\\nof bel: +31 20 123 4911': 'Security incident?\\n\\n**Report directly to:**\\nsecurity@intravox.io\\n\\nor call: +31 20 123 4911',

    // HR Department pages
    'Welkom bij HR': 'Welcome to HR',
    'Alles over personeelszaken en arbeidsvoorwaarden bij IntraVox.': 'Everything about personnel matters and employment conditions at IntraVox.',
    'Openstaande posities': 'Open positions',
    'Bekijk onze vacatures en solliciteer direct.': 'View our vacancies and apply directly.',
    'Nieuwe medewerker?': 'New employee?',
    'Alles wat je moet weten voor een vliegende start.': 'Everything you need for a flying start.',
    'Bedrijfsbeleid': 'Company Policy',
    'Regels, richtlijnen en belangrijke documenten.': 'Rules, guidelines and important documents.',
    'HR Contact': 'HR Contact',
    'Vragen? Neem contact op met HR:\\n\\n**E-mail:** hr@intravox.io\\n**Telefoon:** +31 20 123 4568\\n\\nSpreekuur: Maandag en Woensdag 10:00-12:00': 'Questions? Contact HR:\\n\\n**Email:** hr@intravox.io\\n**Phone:** +31 20 123 4568\\n\\nOffice hours: Monday and Wednesday 10:00-12:00',
    'Terug naar HR': 'Back to HR',
    'HR overzicht': 'HR overview',

    // HR Vacatures page
    'Openstaande Vacatures': 'Open Positions',
    'Word onderdeel van ons team! Bekijk hieronder onze openstaande posities.': 'Become part of our team! View our open positions below.',
    'Huidige Vacatures': 'Current Vacancies',
    'Frontend Developer': 'Frontend Developer',
    '**Afdeling:** IT\\n**Type:** Fulltime\\n**Locatie:** Amsterdam / Hybrid\\n\\nWe zoeken een ervaren frontend developer met Vue.js kennis.': '**Department:** IT\\n**Type:** Full-time\\n**Location:** Amsterdam / Hybrid\\n\\nWe are looking for an experienced frontend developer with Vue.js knowledge.',
    'Marketing Manager': 'Marketing Manager',
    '**Afdeling:** Marketing\\n**Type:** Fulltime\\n**Locatie:** Amsterdam\\n\\nLeid ons marketing team naar nieuwe hoogtes.': '**Department:** Marketing\\n**Type:** Full-time\\n**Location:** Amsterdam\\n\\nLead our marketing team to new heights.',
    'Solliciteren': 'Apply',
    'Interesse in een van onze vacatures?\\n\\n1. Stuur je CV naar jobs@intravox.io\\n2. Vermeld de vacaturetitel\\n3. We nemen binnen 5 werkdagen contact op': 'Interested in one of our vacancies?\\n\\n1. Send your CV to jobs@intravox.io\\n2. Mention the vacancy title\\n3. We will contact you within 5 working days',

    // HR Onboarding page
    'Onboarding': 'Onboarding',
    'Welkom bij IntraVox! Alles wat je nodig hebt voor een vliegende start.': 'Welcome to IntraVox! Everything you need for a flying start.',
    'Week 1': 'Week 1',
    '**Dag 1-2: Introductie**\\n- Welkomstgesprek met HR\\n- Rondleiding kantoor\\n- IT setup en accounts\\n\\n**Dag 3-5: Team kennismaking**\\n- Ontmoet je teamleden\\n- Eerste projectbriefing\\n- Buddy toegewezen': '**Day 1-2: Introduction**\\n- Welcome meeting with HR\\n- Office tour\\n- IT setup and accounts\\n\\n**Day 3-5: Team introduction**\\n- Meet your team members\\n- First project briefing\\n- Buddy assigned',
    'Week 2-4': 'Week 2-4',
    '**Training & Development**\\n- Verplichte trainingen\\n- Productkennis sessies\\n- Eerste taken starten\\n\\n**Evaluatie**\\n- Feedback gesprek na 2 weken\\n- Doelen stellen voor proeftijd': '**Training & Development**\\n- Mandatory training\\n- Product knowledge sessions\\n- Start first tasks\\n\\n**Evaluation**\\n- Feedback meeting after 2 weeks\\n- Set goals for probation period',
    'Belangrijke Links': 'Important Links',

    // HR Policies page
    'HR Beleid': 'HR Policies',
    'Belangrijke beleidsregels en richtlijnen voor alle medewerkers.': 'Important policies and guidelines for all employees.',
    'Verlof & Vakantie': 'Leave & Vacation',
    '**Vakantiedagen:** 25 per jaar\\n**Feestdagen:** Volgens Nederlandse kalender\\n\\nVerlof aanvragen via het HR portaal, minimaal 2 weken van tevoren voor langere periodes.': '**Vacation days:** 25 per year\\n**Holidays:** According to Dutch calendar\\n\\nRequest leave via the HR portal, at least 2 weeks in advance for longer periods.',
    'Thuiswerken': 'Working from Home',
    'Hybride werken is de norm:\\n\\n- Minimaal 2 dagen per week op kantoor\\n- In overleg met je manager\\n- Goede werkplek thuis vereist\\n- Thuiswerkvergoeding beschikbaar': 'Hybrid working is the norm:\\n\\n- At least 2 days per week in the office\\n- In consultation with your manager\\n- Good home workplace required\\n- Home working allowance available',
    'Ziekteverzuim': 'Sick Leave',
    'Bij ziekte:\\n\\n1. Meld je vóór 9:00 bij je manager\\n2. Registreer in het HR systeem\\n3. Houd contact over herstel\\n\\nBij langdurig verzuim volgen we het verzuimprotocol.': 'When sick:\\n\\n1. Report to your manager before 9:00\\n2. Register in the HR system\\n3. Stay in contact about recovery\\n\\nFor long-term absence, we follow the absence protocol.',
    'Gedragscode': 'Code of Conduct',
    'We verwachten van alle medewerkers:\\n\\n- Respectvolle omgang\\n- Professioneel gedrag\\n- Integriteit\\n- Vertrouwelijkheid\\n\\nDe volledige gedragscode is beschikbaar in het HR portaal.': 'We expect from all employees:\\n\\n- Respectful interaction\\n- Professional behavior\\n- Integrity\\n- Confidentiality\\n\\nThe full code of conduct is available in the HR portal.',

    // Sales Department pages
    'Welkom bij Sales': 'Welcome to Sales',
    'Het sales team zorgt voor groei en klantrelaties.': 'The sales team drives growth and customer relationships.',
    'CRM': 'CRM',
    'Klantrelaties en contactbeheer.': 'Customer relationships and contact management.',
    'Pipeline': 'Pipeline',
    'Verkoopkansen en deals bijhouden.': 'Track sales opportunities and deals.',
    'Targets': 'Targets',
    'Doelstellingen en resultaten.': 'Goals and results.',
    'Sales Team': 'Sales Team',
    '**Team Lead:** Jan de Vries\\n**Account Managers:** 4\\n**Sales Support:** 2\\n\\nContacteer ons via sales@intravox.io': '**Team Lead:** Jan de Vries\\n**Account Managers:** 4\\n**Sales Support:** 2\\n\\nContact us via sales@intravox.io',
    'Terug naar Sales': 'Back to Sales',
    'Sales overzicht': 'Sales overview',

    // Sales CRM page
    'CRM & Klantbeheer': 'CRM & Customer Management',
    'Centraal overzicht van al onze klantrelaties en prospects.': 'Central overview of all our customer relationships and prospects.',
    'CRM Systeem': 'CRM System',
    'We gebruiken **HubSpot** als ons CRM systeem.\\n\\n**Toegang aanvragen:** Via IT ticket\\n**Training:** Beschikbaar via HubSpot Academy\\n**Support:** crm@intravox.io': 'We use **HubSpot** as our CRM system.\\n\\n**Request access:** Via IT ticket\\n**Training:** Available via HubSpot Academy\\n**Support:** crm@intravox.io',
    'Klant Categorieën': 'Customer Categories',
    '**Enterprise:** >100 users\\n**Business:** 20-100 users\\n**Starter:** <20 users\\n**Partner:** Resellers & Integrators': '**Enterprise:** >100 users\\n**Business:** 20-100 users\\n**Starter:** <20 users\\n**Partner:** Resellers & Integrators',
    'CRM Richtlijnen': 'CRM Guidelines',
    '- Update contacten binnen 24 uur na meeting\\n- Log alle communicatie\\n- Gebruik standaard deal stages\\n- Voeg notities toe bij elke interactie': '- Update contacts within 24 hours after meeting\\n- Log all communication\\n- Use standard deal stages\\n- Add notes to every interaction',

    // Sales Pipeline page
    'Sales Pipeline': 'Sales Pipeline',
    'Van lead tot klant - volg het verkoopproces.': 'From lead to customer - follow the sales process.',
    'Pipeline Stages': 'Pipeline Stages',
    '1. **Lead** - Eerste contact\\n2. **Qualified** - Interesse bevestigd\\n3. **Demo** - Product demonstratie\\n4. **Proposal** - Offerte verstuurd\\n5. **Negotiation** - In onderhandeling\\n6. **Closed Won** - Deal gesloten\\n7. **Closed Lost** - Niet doorgegaan': '1. **Lead** - First contact\\n2. **Qualified** - Interest confirmed\\n3. **Demo** - Product demonstration\\n4. **Proposal** - Quote sent\\n5. **Negotiation** - In negotiation\\n6. **Closed Won** - Deal closed\\n7. **Closed Lost** - Did not proceed',
    'Conversie Targets': 'Conversion Targets',
    '| Stage | Target |\\n|-------|--------|\\n| Lead → Qualified | 40% |\\n| Qualified → Demo | 60% |\\n| Demo → Proposal | 50% |\\n| Proposal → Won | 30% |': '| Stage | Target |\\n|-------|--------|\\n| Lead → Qualified | 40% |\\n| Qualified → Demo | 60% |\\n| Demo → Proposal | 50% |\\n| Proposal → Won | 30% |',
    'Weekly Review': 'Weekly Review',
    'Elke maandag 10:00 - Pipeline review meeting.\\n\\nBespreek:\\n- Nieuwe leads\\n- Beweging in pipeline\\n- Deals at risk\\n- Forecasting': 'Every Monday 10:00 - Pipeline review meeting.\\n\\nDiscuss:\\n- New leads\\n- Movement in pipeline\\n- Deals at risk\\n- Forecasting',

    // Sales Targets page
    'Sales Targets': 'Sales Targets',
    'Doelstellingen en resultaten voor het sales team.': 'Goals and results for the sales team.',
    'Q4 2024 Targets': 'Q4 2024 Targets',
    '**Team Target:** €500.000\\n**New Customers:** 25\\n**Upsell Revenue:** €100.000\\n\\n*Progress wordt wekelijks bijgewerkt*': '**Team Target:** €500,000\\n**New Customers:** 25\\n**Upsell Revenue:** €100,000\\n\\n*Progress is updated weekly*',
    'Individuele Targets': 'Individual Targets',
    'Targets worden maandelijks vastgesteld in overleg met de Team Lead.\\n\\n**Basis:** Ervaring en portfolio\\n**Bonus:** Bij >100% achievement\\n**Review:** Elk kwartaal': 'Targets are set monthly in consultation with the Team Lead.\\n\\n**Basis:** Experience and portfolio\\n**Bonus:** At >100% achievement\\n**Review:** Every quarter',
    'KPIs': 'KPIs',
    '- Aantal calls/week\\n- Aantal demos/week\\n- Deal close rate\\n- Average deal size\\n- Customer satisfaction': '- Number of calls/week\\n- Number of demos/week\\n- Deal close rate\\n- Average deal size\\n- Customer satisfaction',

    // Marketing Department pages
    'Welkom bij Marketing': 'Welcome to Marketing',
    'Creatief team voor branding, campagnes en communicatie.': 'Creative team for branding, campaigns and communication.',
    'Lopende en geplande marketingcampagnes.': 'Running and planned marketing campaigns.',
    'Social media kanalen en content planning.': 'Social media channels and content planning.',
    'Huisstijl, logo\'s en brand guidelines.': 'Brand identity, logos and brand guidelines.',
    'Marketing Team': 'Marketing Team',
    '**Team Lead:** Lisa van Berg\\n**Content Creators:** 2\\n**Designer:** 1\\n\\nContacteer ons via marketing@intravox.io': '**Team Lead:** Lisa van Berg\\n**Content Creators:** 2\\n**Designer:** 1\\n\\nContact us via marketing@intravox.io',
    'Terug naar Marketing': 'Back to Marketing',
    'Marketing overzicht': 'Marketing overview',

    // Marketing Campaigns page
    'Campagnes': 'Campaigns',
    'Overzicht van lopende en geplande marketingcampagnes.': 'Overview of running and planned marketing campaigns.',
    'Actieve Campagnes': 'Active Campaigns',
    '**Product Launch Q4**\\nStatus: Live\\nKanalen: LinkedIn, Email, Website\\nBudget: €15.000\\n\\n**Holiday Campaign**\\nStatus: In voorbereiding\\nKanalen: Social, Email\\nBudget: €5.000': '**Product Launch Q4**\\nStatus: Live\\nChannels: LinkedIn, Email, Website\\nBudget: €15,000\\n\\n**Holiday Campaign**\\nStatus: In preparation\\nChannels: Social, Email\\nBudget: €5,000',
    'Campagne Planning': 'Campaign Planning',
    'Nieuwe campagne ideeën? Dien een voorstel in via het marketing portaal met:\\n\\n- Doelstelling\\n- Doelgroep\\n- Kanalen\\n- Budget schatting\\n- Timeline': 'New campaign ideas? Submit a proposal via the marketing portal with:\\n\\n- Objective\\n- Target audience\\n- Channels\\n- Budget estimate\\n- Timeline',

    // Marketing Social Media page
    'Social Media': 'Social Media',
    'Onze aanwezigheid op social media platforms.': 'Our presence on social media platforms.',
    'Kanalen': 'Channels',
    '**LinkedIn** - Corporate updates\\n**Twitter/X** - Product news\\n**YouTube** - Tutorials\\n**Instagram** - Behind the scenes': '**LinkedIn** - Corporate updates\\n**Twitter/X** - Product news\\n**YouTube** - Tutorials\\n**Instagram** - Behind the scenes',
    'Content Calendar': 'Content Calendar',
    'De content kalender wordt beheerd in **Notion**.\\n\\n- Maandag: Blog post\\n- Woensdag: Product tip\\n- Vrijdag: Community highlight\\n\\nToegang aanvragen via marketing@intravox.io': 'The content calendar is managed in **Notion**.\\n\\n- Monday: Blog post\\n- Wednesday: Product tip\\n- Friday: Community highlight\\n\\nRequest access via marketing@intravox.io',
    'Social Media Guidelines': 'Social Media Guidelines',
    '- Blijf on-brand\\n- Reageer binnen 24 uur\\n- Geen controversiële topics\\n- Check spelling & grammar\\n- Gebruik goedgekeurde visuals': '- Stay on-brand\\n- Respond within 24 hours\\n- No controversial topics\\n- Check spelling & grammar\\n- Use approved visuals',

    // Marketing Branding page
    'Branding': 'Branding',
    'Huisstijl richtlijnen en brand assets.': 'Brand identity guidelines and brand assets.',
    'Logo & Kleuren': 'Logo & Colors',
    '**Primary Color:** #0082c9\\n**Secondary:** #3a3c3e\\n**Accent:** #00c9a7\\n\\nLogo varianten beschikbaar in het brand portal.': '**Primary Color:** #0082c9\\n**Secondary:** #3a3c3e\\n**Accent:** #00c9a7\\n\\nLogo variants available in the brand portal.',
    'Typography': 'Typography',
    '**Headings:** Inter Bold\\n**Body:** Inter Regular\\n**Code:** Fira Code\\n\\nFonts zijn beschikbaar via Google Fonts.': '**Headings:** Inter Bold\\n**Body:** Inter Regular\\n**Code:** Fira Code\\n\\nFonts are available via Google Fonts.',
    'Brand Assets': 'Brand Assets',
    'Download alle brand assets via het **Brand Portal**:\\n\\n- Logo\'s (PNG, SVG, EPS)\\n- Kleurpaletten\\n- Presentatie templates\\n- Social media templates\\n- Email signatures': 'Download all brand assets via the **Brand Portal**:\\n\\n- Logos (PNG, SVG, EPS)\\n- Color palettes\\n- Presentation templates\\n- Social media templates\\n- Email signatures',

    // Documentation pages
    'Documentatie Overzicht': 'Documentation Overview',
    'Alles wat u moet weten om IntraVox te gebruiken en configureren.': 'Everything you need to know to use and configure IntraVox.',
    'Installeren': 'Install',
    'Installatie handleiding voor administrators.': 'Installation guide for administrators.',
    'Gebruikers handleidingen voor dagelijks gebruik.': 'User guides for daily use.',
    'API referentie voor developers.': 'API reference for developers.',
    'Antwoorden op veelgestelde vragen.': 'Answers to frequently asked questions.',
    'Tips': 'Tips',
    'Handige tips voor efficiënt werken.': 'Useful tips for efficient work.',
    'Terug naar Documentatie': 'Back to Documentation',
    'Documentatie overzicht': 'Documentation overview',

    // Documentation Installation page
    'Installatie Handleiding': 'Installation Guide',
    'Stap voor stap IntraVox installeren op uw Nextcloud server.': 'Step by step installing IntraVox on your Nextcloud server.',
    'Vereisten': 'Requirements',
    '**Server:**\\n- Nextcloud 25 of hoger\\n- PHP 8.0+\\n- MySQL/MariaDB of PostgreSQL\\n\\n**Aanbevolen:**\\n- 2GB RAM minimum\\n- SSD opslag': '**Server:**\\n- Nextcloud 25 or higher\\n- PHP 8.0+\\n- MySQL/MariaDB or PostgreSQL\\n\\n**Recommended:**\\n- 2GB RAM minimum\\n- SSD storage',
    'Installatie Stappen': 'Installation Steps',
    '1. Download IntraVox van de App Store\\n2. Activeer de app in Nextcloud\\n3. Configureer de gedeelde map\\n4. Stel permissies in\\n5. Maak uw eerste pagina!': '1. Download IntraVox from the App Store\\n2. Activate the app in Nextcloud\\n3. Configure the shared folder\\n4. Set permissions\\n5. Create your first page!',
    'Configuratie': 'Configuration',
    'Na installatie kunt u configureren:\\n\\n- Standaard taal\\n- Navigatie type\\n- Toegangsrechten\\n- Thema instellingen': 'After installation you can configure:\\n\\n- Default language\\n- Navigation type\\n- Access rights\\n- Theme settings',

    // Documentation User Guides page
    'Handleidingen': 'User Guides',
    'Gebruikershandleidingen voor dagelijks gebruik van IntraVox.': 'User guides for daily use of IntraVox.',
    'Pagina\'s Maken': 'Creating Pages',
    '1. Klik op \"Nieuwe Pagina\"\\n2. Kies een layout\\n3. Sleep widgets naar de pagina\\n4. Vul content in\\n5. Sla op en publiceer': '1. Click "New Page"\\n2. Choose a layout\\n3. Drag widgets to the page\\n4. Fill in content\\n5. Save and publish',
    'Navigatie Beheren': 'Managing Navigation',
    '1. Ga naar Instellingen\\n2. Klik op Navigatie\\n3. Voeg menu items toe\\n4. Drag-and-drop om te herordenen\\n5. Sla wijzigingen op': '1. Go to Settings\\n2. Click Navigation\\n3. Add menu items\\n4. Drag-and-drop to reorder\\n5. Save changes',
    'Afbeeldingen Toevoegen': 'Adding Images',
    '1. Voeg een Image widget toe\\n2. Klik om een afbeelding te selecteren\\n3. Upload of kies uit Nextcloud\\n4. Pas grootte en positie aan\\n5. Voeg alt-tekst toe': '1. Add an Image widget\\n2. Click to select an image\\n3. Upload or choose from Nextcloud\\n4. Adjust size and position\\n5. Add alt text',

    // Documentation API page
    'API Documentatie': 'API Documentation',
    'API referentie voor developers die willen integreren met IntraVox.': 'API reference for developers who want to integrate with IntraVox.',
    'Authenticatie': 'Authentication',
    'Gebruik Nextcloud\'s standaard authenticatie:\\n\\n```\\nAuthorization: Bearer <token>\\n```\\n\\nOf basic auth met app password.': 'Use Nextcloud\'s standard authentication:\\n\\n```\\nAuthorization: Bearer <token>\\n```\\n\\nOr basic auth with app password.',
    'Endpoints': 'Endpoints',
    '**GET** `/apps/intravox/api/pages`\\nLijst alle pagina\'s\\n\\n**GET** `/apps/intravox/api/page/{id}`\\nHaal specifieke pagina op\\n\\n**POST** `/apps/intravox/api/page`\\nMaak nieuwe pagina': '**GET** `/apps/intravox/api/pages`\\nList all pages\\n\\n**GET** `/apps/intravox/api/page/{id}`\\nGet specific page\\n\\n**POST** `/apps/intravox/api/page`\\nCreate new page',
    'Rate Limiting': 'Rate Limiting',
    'API calls zijn gelimiteerd tot:\\n\\n- 100 requests per minuut\\n- 1000 requests per uur\\n\\nBij overschrijding krijgt u een 429 response.': 'API calls are limited to:\\n\\n- 100 requests per minute\\n- 1000 requests per hour\\n\\nExceeding limits returns a 429 response.',

    // Documentation FAQ page
    'Veelgestelde Vragen': 'Frequently Asked Questions',
    'Antwoorden op de meest gestelde vragen over IntraVox.': 'Answers to the most frequently asked questions about IntraVox.',
    'Algemene Vragen': 'General Questions',
    '**Is IntraVox gratis?**\\nJa, IntraVox is 100% gratis en open source.\\n\\n**Welke Nextcloud versie heb ik nodig?**\\nNextcloud 25 of hoger.\\n\\n**Kan ik IntraVox aanpassen?**\\nJa, de broncode is beschikbaar op GitHub.': '**Is IntraVox free?**\\nYes, IntraVox is 100% free and open source.\\n\\n**What Nextcloud version do I need?**\\nNextcloud 25 or higher.\\n\\n**Can I customize IntraVox?**\\nYes, the source code is available on GitHub.',
    'Technische Vragen': 'Technical Questions',
    '**Waar wordt content opgeslagen?**\\nIn een gedeelde map in Nextcloud Files.\\n\\n**Ondersteunt IntraVox meerdere talen?**\\nJa, Nederlands, Engels, Duits en Frans.\\n\\n**Hoe maak ik een backup?**\\nBackup de IntraVox map in Nextcloud.': '**Where is content stored?**\\nIn a shared folder in Nextcloud Files.\\n\\n**Does IntraVox support multiple languages?**\\nYes, Dutch, English, German and French.\\n\\n**How do I make a backup?**\\nBackup the IntraVox folder in Nextcloud.',
    'Problemen Oplossen': 'Troubleshooting',
    '**Pagina laadt niet**\\nControleer of de app is geactiveerd en de map bestaat.\\n\\n**Geen bewerkrechten**\\nControleer de folder permissies in Nextcloud.\\n\\n**Afbeeldingen tonen niet**\\nControleer of de images folder correct is geconfigureerd.': '**Page does not load**\\nCheck if the app is activated and the folder exists.\\n\\n**No edit rights**\\nCheck the folder permissions in Nextcloud.\\n\\n**Images do not show**\\nCheck if the images folder is correctly configured.',

    // Documentation Tips page
    'Tips & Tricks': 'Tips & Tricks',
    'Handige tips voor efficiënt werken met IntraVox.': 'Useful tips for working efficiently with IntraVox.',
    'Keyboard Shortcuts': 'Keyboard Shortcuts',
    '**Editor:**\\n- Ctrl+S - Opslaan\\n- Ctrl+Z - Ongedaan maken\\n- Ctrl+Y - Opnieuw\\n- Esc - Sluiten\\n\\n**Navigatie:**\\n- / - Zoeken\\n- H - Home': '**Editor:**\\n- Ctrl+S - Save\\n- Ctrl+Z - Undo\\n- Ctrl+Y - Redo\\n- Esc - Close\\n\\n**Navigation:**\\n- / - Search\\n- H - Home',
    'Best Practices': 'Best Practices',
    '- Gebruik duidelijke paginatitels\\n- Houd navigatie overzichtelijk (max 7 items)\\n- Optimaliseer afbeeldingen voor web\\n- Gebruik consistente layouts\\n- Test op mobile devices': '- Use clear page titles\\n- Keep navigation clear (max 7 items)\\n- Optimize images for web\\n- Use consistent layouts\\n- Test on mobile devices',
    'Verborgen Features': 'Hidden Features',
    '**Markdown in tekst widgets:**\\nGebruik `**vet**`, `*cursief*`, en meer.\\n\\n**Externe links:**\\nGebruik target=\"_blank\" om in nieuw tabblad te openen.\\n\\n**CSS variabelen:**\\nGebruik Nextcloud thema kleuren voor consistente styling.': '**Markdown in text widgets:**\\nUse `**bold**`, `*italic*`, and more.\\n\\n**External links:**\\nUse target=\"_blank" to open in new tab.\\n\\n**CSS variables:**\\nUse Nextcloud theme colors for consistent styling.',

    // About IntraVox page
    'Over IntraVox': 'About IntraVox',
    'De moderne intranet oplossing voor Nextcloud.': 'The modern intranet solution for Nextcloud.',
    'Onze Missie': 'Our Mission',
    'IntraVox maakt professionele intranets toegankelijk voor elke organisatie. Geen dure licenties, geen vendor lock-in - gewoon een krachtige, open source oplossing.': 'IntraVox makes professional intranets accessible to every organization. No expensive licenses, no vendor lock-in - just a powerful, open source solution.',
    'Waarom IntraVox?': 'Why IntraVox?',
    '**Open Source**\\nVolledig transparant en aanpasbaar.\\n\\n**Privacy First**\\nUw data blijft van u.\\n\\n**Nextcloud Native**\\nGeïntegreerd met het beste platform.\\n\\n**Community Driven**\\nGebouwd door en voor gebruikers.': '**Open Source**\\nFully transparent and customizable.\\n\\n**Privacy First**\\nYour data stays yours.\\n\\n**Nextcloud Native**\\nIntegrated with the best platform.\\n\\n**Community Driven**\\nBuilt by and for users.',
    'Het Team': 'The Team',
    'IntraVox wordt ontwikkeld door een klein team van developers die geloven in open source en privacy.\\n\\nWilt u bijdragen? Check onze GitHub repository!': 'IntraVox is developed by a small team of developers who believe in open source and privacy.\\n\\nWant to contribute? Check our GitHub repository!',

    // Customers & Cases page
    'Klanten & Cases': 'Customers & Cases',
    'Ontdek hoe andere organisaties IntraVox gebruiken.': 'Discover how other organizations use IntraVox.',
    'Succesverhalen': 'Success Stories',
    '**TechCorp BV**\\n500 medewerkers\\n\"IntraVox heeft onze interne communicatie getransformeerd.\"\\n\\n**Universiteit Amsterdam**\\n1000+ gebruikers\\n\"Perfect voor onze faculteitsportals.\"': '**TechCorp BV**\\n500 employees\\n\"IntraVox has transformed our internal communication.\"\\n\\n**University of Amsterdam**\\n1000+ users\\n\"Perfect for our faculty portals.\"',
    'Gebruik Cases': 'Use Cases',
    '- **Bedrijfsintranet** - Centraal informatieplatform\\n- **Kennisbank** - Documentatie en FAQ\\n- **Team Portals** - Per afdeling of project\\n- **Onboarding** - Nieuwe medewerkers': '- **Company Intranet** - Central information platform\\n- **Knowledge Base** - Documentation and FAQ\\n- **Team Portals** - Per department or project\\n- **Onboarding** - New employees',
    'Uw Verhaal Delen?': 'Share Your Story?',
    'Gebruikt u IntraVox? We horen graag uw ervaringen!\\n\\nMail naar: stories@intravox.io': 'Do you use IntraVox? We would love to hear your experiences!\\n\\nEmail: stories@intravox.io',

    // External Links page
    'Externe Links': 'External Links',
    'Handige externe bronnen en partners.': 'Useful external resources and partners.',
    'Nextcloud Resources': 'Nextcloud Resources',
    'Officiële Nextcloud documentatie en community.': 'Official Nextcloud documentation and community.',
    'Open Source Tools': 'Open Source Tools',
    'Andere tools die goed samenwerken met IntraVox.': 'Other tools that work well with IntraVox.',
    'Partners': 'Partners',
    'Onze partners voor implementatie en support.': 'Our partners for implementation and support.',

    // ============================================
    // SHORTER PHRASES AND WORDS
    // ============================================

    // Page titles and navigation
    'Home': 'Home',
    'Over IntraVox': 'About IntraVox',
    'Functies': 'Features',
    'Widgets': 'Widgets',
    'Layouts': 'Layouts',
    'Navigatie': 'Navigation',
    'Nextcloud Platform': 'Nextcloud Platform',
    'Prijzen': 'Pricing',
    'Klanten & Cases': 'Customers & Cases',
    'Afdelingen': 'Departments',
    'Marketing': 'Marketing',
    'Campagnes': 'Campaigns',
    'Social Media': 'Social Media',
    'Branding': 'Branding',
    'Sales': 'Sales',
    'CRM & Klantbeheer': 'CRM & Customer Management',
    'Sales Pipeline': 'Sales Pipeline',
    'Sales Targets': 'Sales Targets',
    'HR': 'HR',
    'Vacatures': 'Job Openings',
    'Onboarding': 'Onboarding',
    'HR Policies': 'HR Policies',
    'IT': 'IT',
    'IT Helpdesk': 'IT Helpdesk',
    'Systemen & Tools': 'Systems & Tools',
    'Security & Privacy': 'Security & Privacy',
    'Documentatie': 'Documentation',
    'Installatie': 'Installation',
    'Handleidingen': 'User Guides',
    'API': 'API',
    'FAQ': 'FAQ',
    'Tips & Tricks': 'Tips & Tricks',
    'Nieuws': 'News',
    'Product Updates': 'Product Updates',
    'Evenementen': 'Events',
    'Contact': 'Contact',
    'Externe Links': 'External Links',

    // Common content translations
    'Welkom': 'Welcome',
    'Lees meer': 'Read more',
    'Meer informatie': 'More information',
    'Bekijk': 'View',
    'Ontdek': 'Discover',
    'Terug naar': 'Back to',
    'overzicht': 'overview',
    'Richtlijnen': 'Guidelines',
    'Vereisten': 'Requirements',
    'Verplicht voor': 'Required for',
    'Let op': 'Note',
    'Meld': 'Report',
    'direct bij': 'directly to',
    'of bel': 'or call',
    'Openingstijden': 'Opening Hours',
    'beschikbaar': 'available',
    'Spoedgevallen': 'Emergencies',
    'Ticket Indienen': 'Submit Ticket',
    'Via e-mail': 'Via email',
    'Via portal': 'Via portal',
    'Reactietijd': 'Response time',
    'Self-Service': 'Self-Service',
    'Zelf oplossen': 'Self-solve',
    'Veelgestelde vragen': 'Frequently asked questions',
    'Wachtwoord reset': 'Password reset',
    'VPN problemen': 'VPN issues',
    'Printer setup': 'Printer setup',
    'Software installatie': 'Software installation',
    'Support beschikbaar': 'Support available',
    'Ma-Vr': 'Mon-Fri',
    'Weekend': 'Weekend',
    'Feestdagen': 'Holidays',
    'alleen spoed': 'emergencies only',
    'Communicatie': 'Communication',
    'Productiviteit': 'Productivity',
    'Development': 'Development',
    'Tools': 'Tools',
    'Toegang Aanvragen': 'Request Access',
    'Toegang tot systemen aanvragen via een IT ticket': 'Request system access via an IT ticket',
    'Vermeld welk systeem en waarom toegang nodig is': 'Specify which system and why access is needed',
    'Goedkeuring door manager vereist': 'Manager approval required',
    'Wachtwoorden': 'Passwords',
    'Minimaal 12 karakters': 'Minimum 12 characters',
    'Mix van letters, cijfers, symbolen': 'Mix of letters, numbers, symbols',
    'Uniek per applicatie': 'Unique per application',
    'Wijzig elke 90 dagen': 'Change every 90 days',
    'Gebruik een password manager': 'Use a password manager',
    '2FA Authenticatie': '2FA Authentication',
    'Setup via de IT Helpdesk': 'Setup via IT Helpdesk',
    'Phishing & Scams': 'Phishing & Scams',
    'verdachte e-mails': 'suspicious emails',
    'Klik NOOIT op links in onverwachte e-mails': 'NEVER click links in unexpected emails',
    'Twijfel je': 'In doubt',
    'Meld het bij IT Security': 'Report it to IT Security',
    'Clean Desk': 'Clean Desk',
    'Vergrendel je scherm': 'Lock your screen',
    'Geen wachtwoorden op papier': 'No passwords on paper',
    'Gevoelige docs in de kluis': 'Sensitive docs in the vault',
    'Ruim je bureau op': 'Clean up your desk',
    'Incident Melden': 'Report Incident',
    'Beveiligingsincident': 'Security incident',
    'Meld direct bij': 'Report directly to',
    'Overzicht van alle afdelingen': 'Overview of all departments',
    'Kies een afdeling': 'Choose a department',
    'Technische ondersteuning voor alle medewerkers': 'Technical support for all employees',
    'We staan voor je klaar': 'We are here to help',
    'Overzicht van alle software en systemen die we gebruiken': 'Overview of all software and systems we use',
    'bij IntraVox': 'at IntraVox',
    'Richtlijnen voor veilig werken en bescherming van bedrijfsgegevens': 'Guidelines for safe working and protection of company data',
    'Welkom bij HR': 'Welcome to HR',
    'Alles over personeelszaken': 'Everything about personnel matters',
    'Bekijk onze openstaande vacatures': 'View our open positions',
    'Word onderdeel van ons team': 'Become part of our team',
    'Nieuwe medewerkers': 'New employees',
    'Alles wat je moet weten voor een vliegende start': 'Everything you need for a flying start',
    'Bedrijfsregels en richtlijnen': 'Company rules and guidelines',
    'Belangrijke documenten': 'Important documents',
    'Welkom bij Sales': 'Welcome to Sales',
    'Het sales team': 'The sales team',
    'Klantrelaties beheren': 'Manage customer relationships',
    'CRM systeem': 'CRM system',
    'Verkoopkansen': 'Sales opportunities',
    'Van lead tot klant': 'From lead to customer',
    'Doelstellingen': 'Targets',
    'Kwartaal- en jaardoelen': 'Quarterly and annual goals',
    'Welkom bij Marketing': 'Welcome to Marketing',
    'Creatief team': 'Creative team',
    'Lopende campagnes': 'Running campaigns',
    'Campagne planning': 'Campaign planning',
    'Social media kanalen': 'Social media channels',
    'Online aanwezigheid': 'Online presence',
    'Huisstijl': 'Brand identity',
    'Logo en kleuren': 'Logo and colors',
    'Welkom bij de documentatie': 'Welcome to the documentation',
    'Alles wat je moet weten': 'Everything you need to know',
    'Installatie handleiding': 'Installation guide',
    'Stap voor stap': 'Step by step',
    'Gebruikershandleiding': 'User guide',
    'Hoe te gebruiken': 'How to use',
    'API Documentatie': 'API Documentation',
    'Voor ontwikkelaars': 'For developers',
    'Antwoorden': 'Answers',
    'Handige tips': 'Useful tips',
    'Slim werken': 'Work smart',
    'Laatste nieuws': 'Latest news',
    'Blijf op de hoogte': 'Stay informed',
    'Nieuwe functies': 'New features',
    'Updates en verbeteringen': 'Updates and improvements',
    'Aankomende evenementen': 'Upcoming events',
    'Webinars en workshops': 'Webinars and workshops',
    'Neem contact op': 'Get in touch',
    'We helpen je graag': 'We are happy to help',
    'E-mail': 'Email',
    'Telefoon': 'Phone',
    'Adres': 'Address',
    'Kantoor': 'Office',
    'Onze prijzen': 'Our pricing',
    'Transparante tarieven': 'Transparent rates',
    'Basis': 'Basic',
    'Professional': 'Professional',
    'Enterprise': 'Enterprise',
    'per maand': 'per month',
    'per jaar': 'per year',
    'Gratis': 'Free',
    'Neem contact op voor prijzen': 'Contact us for pricing',
    'IntraVox functies': 'IntraVox features',
    'Ontdek wat mogelijk is': 'Discover what is possible',
    'Widget types': 'Widget types',
    'Tekst, afbeeldingen en meer': 'Text, images and more',
    'Layout opties': 'Layout options',
    'Flexibele pagina indeling': 'Flexible page layout',
    'Navigatie types': 'Navigation types',
    'Dropdown en mega menu': 'Dropdown and mega menu',
    'Nextcloud integratie': 'Nextcloud integration',
    'Naadloze samenwerking': 'Seamless collaboration',
    'Bestanden delen': 'File sharing',
    'Veilig en eenvoudig': 'Safe and simple',
    'Onze klanten': 'Our customers',
    'Succesverhalen': 'Success stories',
    'Case studies': 'Case studies',
    'Lees hoe anderen IntraVox gebruiken': 'Read how others use IntraVox',
    'Over ons': 'About us',
    'Wie we zijn': 'Who we are',
    'Ons verhaal': 'Our story',
    'Het team': 'The team',
    'Missie en visie': 'Mission and vision',
    'Klik hier': 'Click here',
    'Meer weten': 'Learn more',
    'Ga naar': 'Go to',
    'Terug': 'Back',
    'Volgende': 'Next',
    'Vorige': 'Previous',
    'Zoeken': 'Search',
    'Filter': 'Filter',
    'Sorteren': 'Sort',
    'Nieuw': 'New',
    'Bewerken': 'Edit',
    'Verwijderen': 'Delete',
    'Opslaan': 'Save',
    'Annuleren': 'Cancel',
    'Sluiten': 'Close',
    'Ja': 'Yes',
    'Nee': 'No',
    'Intern': 'Internal',
    'Extern': 'External',
    'Beschikbaar': 'Available'
};

// Translate a string using the dictionary
function translateText(text) {
    if (!text || typeof text !== 'string') return text;

    let result = text;

    // Sort keys by length (longest first) to avoid partial replacements
    const sortedKeys = Object.keys(translations).sort((a, b) => b.length - a.length);

    for (const dutch of sortedKeys) {
        const english = translations[dutch];
        // Use word boundary matching where possible
        const regex = new RegExp(dutch.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');
        result = result.replace(regex, english);
    }

    return result;
}

// Recursively translate all string values in an object
function translateObject(obj) {
    if (typeof obj === 'string') {
        return translateText(obj);
    }

    if (Array.isArray(obj)) {
        return obj.map(item => translateObject(item));
    }

    if (typeof obj === 'object' && obj !== null) {
        const result = {};
        for (const [key, value] of Object.entries(obj)) {
            // Translate string values but keep keys unchanged
            if (key === 'title' || key === 'content' || key === 'text' || key === 'label' || key === 'placeholder' || key === 'alt') {
                result[key] = translateObject(value);
            } else if (typeof value === 'object') {
                result[key] = translateObject(value);
            } else {
                result[key] = value;
            }
        }
        return result;
    }

    return obj;
}

// Find all JSON page files recursively
function findJsonFiles(dir, files = []) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });

    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);

        if (entry.isDirectory() && entry.name !== 'images') {
            findJsonFiles(fullPath, files);
        } else if (entry.name.endsWith('.json')) {
            files.push(fullPath);
        }
    }

    return files;
}

// Main execution
console.log('🌍 Translating EN demo data to English...\n');

const jsonFiles = findJsonFiles(enDir);

console.log(`📄 Found ${jsonFiles.length} JSON files to translate\n`);

for (const file of jsonFiles) {
    const relativePath = path.relative(enDir, file);
    try {
        const content = fs.readFileSync(file, 'utf8');
        let data = JSON.parse(content);

        // Translate the content
        data = translateObject(data);

        fs.writeFileSync(file, JSON.stringify(data, null, 4));
        console.log(`   ✓ Translated: ${relativePath}`);
    } catch (err) {
        console.error(`   ✗ Error translating ${relativePath}:`, err.message);
    }
}

console.log('\n✅ Translation complete!');
console.log(`   Translated ${jsonFiles.length} files`);
