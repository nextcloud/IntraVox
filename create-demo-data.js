#!/usr/bin/env node

/**
 * Script to create demo navigation and rich homepage content for all languages
 */

const fs = require('fs');
const path = require('path');

const SUPPORTED_LANGUAGES = ['nl', 'en', 'de', 'fr'];

// Translations for navigation items
const navTranslations = {
  nl: {
    products: 'Producten',
    services: 'Diensten',
    about: 'Over ons',
    contact: 'Contact',
    resources: 'Bronnen',
    software: 'Software',
    hardware: 'Hardware',
    cloud: 'Cloud Oplossingen',
    consulting: 'Consultancy',
    support: 'Ondersteuning',
    training: 'Training',
    company: 'Bedrijf',
    team: 'Ons Team',
    careers: 'Werken bij ons',
    documentation: 'Documentatie',
    downloads: 'Downloads',
    faq: 'Veelgestelde vragen'
  },
  en: {
    products: 'Products',
    services: 'Services',
    about: 'About Us',
    contact: 'Contact',
    resources: 'Resources',
    software: 'Software',
    hardware: 'Hardware',
    cloud: 'Cloud Solutions',
    consulting: 'Consulting',
    support: 'Support',
    training: 'Training',
    company: 'Company',
    team: 'Our Team',
    careers: 'Careers',
    documentation: 'Documentation',
    downloads: 'Downloads',
    faq: 'FAQ'
  },
  de: {
    products: 'Produkte',
    services: 'Dienstleistungen',
    about: 'Über uns',
    contact: 'Kontakt',
    resources: 'Ressourcen',
    software: 'Software',
    hardware: 'Hardware',
    cloud: 'Cloud-Lösungen',
    consulting: 'Beratung',
    support: 'Support',
    training: 'Schulung',
    company: 'Unternehmen',
    team: 'Unser Team',
    careers: 'Karriere',
    documentation: 'Dokumentation',
    downloads: 'Downloads',
    faq: 'FAQ'
  },
  fr: {
    products: 'Produits',
    services: 'Services',
    about: 'À propos',
    contact: 'Contact',
    resources: 'Ressources',
    software: 'Logiciel',
    hardware: 'Matériel',
    cloud: 'Solutions Cloud',
    consulting: 'Conseil',
    support: 'Support',
    training: 'Formation',
    company: 'Entreprise',
    team: 'Notre équipe',
    careers: 'Carrières',
    documentation: 'Documentation',
    downloads: 'Téléchargements',
    faq: 'FAQ'
  }
};

// Generate demo navigation with 100 items across multiple levels
function generateDemoNavigation(lang) {
  const t = navTranslations[lang];
  const items = [];
  let idCounter = 1;

  // Create 10 top-level items
  for (let i = 1; i <= 10; i++) {
    const topItem = {
      id: `nav_demo_${idCounter++}`,
      title: `${t.products} ${i}`,
      url: null,
      pageId: null,
      children: []
    };

    // Add 5 second-level items
    for (let j = 1; j <= 5; j++) {
      const secondItem = {
        id: `nav_demo_${idCounter++}`,
        title: `${t.services} ${i}.${j}`,
        url: null,
        pageId: null,
        children: []
      };

      // Add 3 third-level items to some second-level items
      if (j <= 3) {
        for (let k = 1; k <= 3; k++) {
          secondItem.children.push({
            id: `nav_demo_${idCounter++}`,
            title: `${t.resources} ${i}.${j}.${k}`,
            url: `#item-${i}-${j}-${k}`,
            pageId: null,
            children: []
          });
        }
      }

      topItem.children.push(secondItem);
    }

    items.push(topItem);
  }

  return {
    type: 'megamenu',
    items: items
  };
}

// Homepage translations
const homepageTranslations = {
  nl: {
    title: 'Welkom bij IntraVox - Uw Moderne Intranet Platform',
    intro: 'Welkom bij IntraVox',
    subtitle: 'Het ultieme intranet platform voor moderne organisaties',
    description: 'IntraVox transformeert hoe uw team samenwerkt, communiceert en informatie deelt. Met een intuïtieve interface en krachtige functies biedt IntraVox alles wat u nodig heeft voor een succesvol digitaal werkplein.',
    featuresTitle: 'Belangrijkste Functies',
    feature1Title: 'Flexibele Pagina Editor',
    feature1Text: 'Creëer en beheer prachtige pagina\'s met onze gebruiksvriendelijke drag-and-drop editor. Voeg tekst, afbeeldingen, lijsten en meer toe zonder technische kennis.',
    feature2Title: 'Meertalige Ondersteuning',
    feature2Text: 'IntraVox ondersteunt meerdere talen out-of-the-box. Schakel naadloos tussen Nederlands, Engels, Duits en Frans.',
    feature3Title: 'Team Folder Integratie',
    feature3Text: 'Ingebouwde integratie met Nextcloud Team Folders zorgt voor veilige en georganiseerde bestandsopslag met granulaire toegangscontrole.',
    feature4Title: 'Geavanceerde Navigatie',
    feature4Text: 'Kies tussen dropdown en megamenu navigatie stijlen. Organiseer content in tot 3 niveaus voor optimale gebruikerservaring.',
    feature5Title: 'Real-time Bewerking',
    feature5Text: 'Wijzigingen worden direct opgeslagen en zijn meteen zichtbaar voor uw team. Geen complexe publicatie workflows nodig.',
    feature6Title: 'Responsive Design',
    feature6Text: 'Uw intranet ziet er perfect uit op elk apparaat - desktop, tablet of mobiel.',
    howItWorksTitle: 'Hoe Het Werkt',
    step1: 'Maak pagina\'s aan met de intuïtieve editor',
    step2: 'Organiseer content met widgets en layouts',
    step3: 'Configureer navigatie naar uw wensen',
    step4: 'Deel kennis en communiceer effectief',
    getStartedTitle: 'Aan de Slag',
    getStartedText: 'Klik op de "Bewerken" knop rechtsboven om deze pagina aan te passen. Gebruik "+ Nieuwe Pagina" om meer content toe te voegen. De mogelijkheden zijn eindeloos!',
    techTitle: 'Technologie',
    techText: 'Gebouwd op Nextcloud met Vue.js voor een moderne, veilige en schaalbare ervaring.'
  },
  en: {
    title: 'Welcome to IntraVox - Your Modern Intranet Platform',
    intro: 'Welcome to IntraVox',
    subtitle: 'The ultimate intranet platform for modern organizations',
    description: 'IntraVox transforms how your team collaborates, communicates, and shares information. With an intuitive interface and powerful features, IntraVox provides everything you need for a successful digital workplace.',
    featuresTitle: 'Key Features',
    feature1Title: 'Flexible Page Editor',
    feature1Text: 'Create and manage beautiful pages with our user-friendly drag-and-drop editor. Add text, images, lists, and more without technical knowledge.',
    feature2Title: 'Multi-language Support',
    feature2Text: 'IntraVox supports multiple languages out-of-the-box. Switch seamlessly between Dutch, English, German, and French.',
    feature3Title: 'Team Folder Integration',
    feature3Text: 'Built-in integration with Nextcloud Team Folders ensures secure and organized file storage with granular access control.',
    feature4Title: 'Advanced Navigation',
    feature4Text: 'Choose between dropdown and megamenu navigation styles. Organize content up to 3 levels deep for optimal user experience.',
    feature5Title: 'Real-time Editing',
    feature5Text: 'Changes are automatically saved and immediately visible to your team. No complex publication workflows needed.',
    feature6Title: 'Responsive Design',
    feature6Text: 'Your intranet looks perfect on any device - desktop, tablet, or mobile.',
    howItWorksTitle: 'How It Works',
    step1: 'Create pages with the intuitive editor',
    step2: 'Organize content with widgets and layouts',
    step3: 'Configure navigation to your needs',
    step4: 'Share knowledge and communicate effectively',
    getStartedTitle: 'Get Started',
    getStartedText: 'Click the "Edit" button in the top right to modify this page. Use "+ New Page" to add more content. The possibilities are endless!',
    techTitle: 'Technology',
    techText: 'Built on Nextcloud with Vue.js for a modern, secure, and scalable experience.'
  },
  de: {
    title: 'Willkommen bei IntraVox - Ihre Moderne Intranet-Plattform',
    intro: 'Willkommen bei IntraVox',
    subtitle: 'Die ultimative Intranet-Plattform für moderne Organisationen',
    description: 'IntraVox transformiert, wie Ihr Team zusammenarbeitet, kommuniziert und Informationen teilt. Mit einer intuitiven Benutzeroberfläche und leistungsstarken Funktionen bietet IntraVox alles, was Sie für einen erfolgreichen digitalen Arbeitsplatz benötigen.',
    featuresTitle: 'Hauptfunktionen',
    feature1Title: 'Flexibler Seiten-Editor',
    feature1Text: 'Erstellen und verwalten Sie schöne Seiten mit unserem benutzerfreundlichen Drag-and-Drop-Editor. Fügen Sie Text, Bilder, Listen und mehr ohne technische Kenntnisse hinzu.',
    feature2Title: 'Mehrsprachige Unterstützung',
    feature2Text: 'IntraVox unterstützt mehrere Sprachen out-of-the-box. Wechseln Sie nahtlos zwischen Niederländisch, Englisch, Deutsch und Französisch.',
    feature3Title: 'Team-Ordner-Integration',
    feature3Text: 'Die integrierte Integration mit Nextcloud Team-Ordnern sorgt für sichere und organisierte Dateispeicherung mit granularer Zugriffskontrolle.',
    feature4Title: 'Erweiterte Navigation',
    feature4Text: 'Wählen Sie zwischen Dropdown- und Megamenü-Navigationsstilen. Organisieren Sie Inhalte bis zu 3 Ebenen tief für optimale Benutzererfahrung.',
    feature5Title: 'Echtzeit-Bearbeitung',
    feature5Text: 'Änderungen werden automatisch gespeichert und sind sofort für Ihr Team sichtbar. Keine komplexen Veröffentlichungsworkflows erforderlich.',
    feature6Title: 'Responsives Design',
    feature6Text: 'Ihr Intranet sieht auf jedem Gerät perfekt aus - Desktop, Tablet oder Mobil.',
    howItWorksTitle: 'Wie Es Funktioniert',
    step1: 'Erstellen Sie Seiten mit dem intuitiven Editor',
    step2: 'Organisieren Sie Inhalte mit Widgets und Layouts',
    step3: 'Konfigurieren Sie die Navigation nach Ihren Bedürfnissen',
    step4: 'Teilen Sie Wissen und kommunizieren Sie effektiv',
    getStartedTitle: 'Erste Schritte',
    getStartedText: 'Klicken Sie auf die Schaltfläche "Bearbeiten" oben rechts, um diese Seite anzupassen. Verwenden Sie "+ Neue Seite", um weitere Inhalte hinzuzufügen. Die Möglichkeiten sind endlos!',
    techTitle: 'Technologie',
    techText: 'Aufgebaut auf Nextcloud mit Vue.js für eine moderne, sichere und skalierbare Erfahrung.'
  },
  fr: {
    title: 'Bienvenue sur IntraVox - Votre Plateforme Intranet Moderne',
    intro: 'Bienvenue sur IntraVox',
    subtitle: 'La plateforme intranet ultime pour les organisations modernes',
    description: 'IntraVox transforme la façon dont votre équipe collabore, communique et partage des informations. Avec une interface intuitive et des fonctionnalités puissantes, IntraVox fournit tout ce dont vous avez besoin pour un espace de travail numérique réussi.',
    featuresTitle: 'Fonctionnalités Clés',
    feature1Title: 'Éditeur de Page Flexible',
    feature1Text: 'Créez et gérez de belles pages avec notre éditeur glisser-déposer convivial. Ajoutez du texte, des images, des listes et plus encore sans connaissances techniques.',
    feature2Title: 'Support Multilingue',
    feature2Text: 'IntraVox prend en charge plusieurs langues dès le départ. Basculez en toute transparence entre le néerlandais, l\'anglais, l\'allemand et le français.',
    feature3Title: 'Intégration de Dossier d\'Équipe',
    feature3Text: 'L\'intégration intégrée avec les dossiers d\'équipe Nextcloud garantit un stockage de fichiers sécurisé et organisé avec un contrôle d\'accès granulaire.',
    feature4Title: 'Navigation Avancée',
    feature4Text: 'Choisissez entre les styles de navigation déroulante et mégamenu. Organisez le contenu jusqu\'à 3 niveaux de profondeur pour une expérience utilisateur optimale.',
    feature5Title: 'Édition en Temps Réel',
    feature5Text: 'Les modifications sont automatiquement enregistrées et immédiatement visibles pour votre équipe. Aucun flux de publication complexe requis.',
    feature6Title: 'Design Responsive',
    feature6Text: 'Votre intranet s\'affiche parfaitement sur n\'importe quel appareil - bureau, tablette ou mobile.',
    howItWorksTitle: 'Comment Ça Marche',
    step1: 'Créez des pages avec l\'éditeur intuitif',
    step2: 'Organisez le contenu avec des widgets et des mises en page',
    step3: 'Configurez la navigation selon vos besoins',
    step4: 'Partagez les connaissances et communiquez efficacement',
    getStartedTitle: 'Commencer',
    getStartedText: 'Cliquez sur le bouton "Modifier" en haut à droite pour modifier cette page. Utilisez "+ Nouvelle Page" pour ajouter plus de contenu. Les possibilités sont infinies!',
    techTitle: 'Technologie',
    techText: 'Construit sur Nextcloud avec Vue.js pour une expérience moderne, sécurisée et évolutive.'
  }
};

// Generate rich homepage content
function generateRichHomepage(lang) {
  const t = homepageTranslations[lang];

  return {
    id: 'home',
    title: t.title,
    layout: {
      columns: 2,
      rows: [
        {
          widgets: [
            {
              type: 'heading',
              content: t.intro,
              level: 1,
              column: 1,
              order: 1
            },
            {
              type: 'heading',
              content: t.subtitle,
              level: 3,
              column: 1,
              order: 2
            },
            {
              type: 'text',
              content: `<p>${t.description}</p>`,
              column: 1,
              order: 3
            }
          ]
        },
        {
          widgets: [
            {
              type: 'heading',
              content: t.featuresTitle,
              level: 2,
              column: 1,
              order: 1
            },
            {
              type: 'text',
              content: `<p><strong>${t.feature1Title}</strong></p><p>${t.feature1Text}</p>`,
              column: 1,
              order: 2
            },
            {
              type: 'text',
              content: `<p><strong>${t.feature2Title}</strong></p><p>${t.feature2Text}</p>`,
              column: 1,
              order: 3
            },
            {
              type: 'text',
              content: `<p><strong>${t.feature3Title}</strong></p><p>${t.feature3Text}</p>`,
              column: 1,
              order: 4
            },
            {
              type: 'text',
              content: `<p><strong>${t.feature4Title}</strong></p><p>${t.feature4Text}</p>`,
              column: 2,
              order: 2
            },
            {
              type: 'text',
              content: `<p><strong>${t.feature5Title}</strong></p><p>${t.feature5Text}</p>`,
              column: 2,
              order: 3
            },
            {
              type: 'text',
              content: `<p><strong>${t.feature6Title}</strong></p><p>${t.feature6Text}</p>`,
              column: 2,
              order: 4
            }
          ]
        },
        {
          widgets: [
            {
              type: 'heading',
              content: t.howItWorksTitle,
              level: 2,
              column: 1,
              order: 1
            },
            {
              type: 'text',
              content: `<ol><li>${t.step1}</li><li>${t.step2}</li><li>${t.step3}</li><li>${t.step4}</li></ol>`,
              column: 1,
              order: 2
            },
            {
              type: 'heading',
              content: t.getStartedTitle,
              level: 2,
              column: 2,
              order: 1
            },
            {
              type: 'text',
              content: `<p>${t.getStartedText}</p>`,
              column: 2,
              order: 2
            }
          ]
        },
        {
          widgets: [
            {
              type: 'heading',
              content: t.techTitle,
              level: 2,
              column: 1,
              order: 1
            },
            {
              type: 'text',
              content: `<p><em>${t.techText}</em></p>`,
              column: 1,
              order: 2
            }
          ]
        }
      ]
    },
    created: Math.floor(Date.now() / 1000),
    modified: Math.floor(Date.now() / 1000)
  };
}

// Main function
function main() {
  const outputDir = path.join(__dirname, 'demo-data');

  // Create output directory
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  console.log('Generating demo data for all languages...');

  SUPPORTED_LANGUAGES.forEach(lang => {
    console.log(`\nProcessing ${lang}...`);

    // Create language directory
    const langDir = path.join(outputDir, lang);
    if (!fs.existsSync(langDir)) {
      fs.mkdirSync(langDir, { recursive: true });
    }

    // Generate and save demo navigation
    const demoNav = generateDemoNavigation(lang);
    fs.writeFileSync(
      path.join(langDir, 'navigation-demo.json'),
      JSON.stringify(demoNav, null, 2)
    );
    console.log(`  ✓ Created navigation-demo.json (${demoNav.items.length} top-level items)`);

    // Generate and save rich homepage
    const richHome = generateRichHomepage(lang);
    fs.writeFileSync(
      path.join(langDir, 'home.json'),
      JSON.stringify(richHome, null, 2)
    );
    console.log(`  ✓ Created home.json`);

    // Also create empty navigation.json
    const emptyNav = { type: 'dropdown', items: [] };
    fs.writeFileSync(
      path.join(langDir, 'navigation.json'),
      JSON.stringify(emptyNav, null, 2)
    );
    console.log(`  ✓ Created navigation.json (empty)`);
  });

  console.log('\n✅ Demo data generation complete!');
  console.log(`📁 Output directory: ${outputDir}`);
}

main();
