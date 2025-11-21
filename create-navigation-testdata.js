#!/usr/bin/env node

/**
 * Create test navigation data with 100 items across 3 levels
 * Includes external links with target option
 */

const navigation = {
  type: 'dropdown', // or 'megamenu'
  items: []
};

// Level 1: Main categories (10 items)
const mainCategories = [
  { id: 'company', title: 'Bedrijf', children: [] },
  { id: 'products', title: 'Producten', children: [] },
  { id: 'services', title: 'Diensten', children: [] },
  { id: 'knowledge', title: 'Kennisbank', children: [] },
  { id: 'hr', title: 'HR & Personeel', children: [] },
  { id: 'it', title: 'IT & Support', children: [] },
  { id: 'projects', title: 'Projecten', children: [] },
  { id: 'customers', title: 'Klanten', children: [] },
  { id: 'partners', title: 'Partners', children: [] },
  { id: 'external', title: 'Externe Links', children: [] }
];

let itemCount = 10;

// Add subcategories to each main category
mainCategories.forEach((category, catIndex) => {
  const numSubcats = Math.floor(Math.random() * 5) + 3; // 3-7 subcategories

  for (let i = 0; i < numSubcats && itemCount < 100; i++) {
    const subcategory = {
      id: `${category.id}_sub${i}`,
      title: `${category.title} ${i + 1}`,
      pageId: null,
      url: null,
      target: null,
      children: []
    };

    // Some subcategories link to pages, others have children
    if (Math.random() > 0.5 && itemCount < 90) {
      // Add level 3 items
      const numLevel3 = Math.floor(Math.random() * 4) + 2; // 2-5 items

      for (let j = 0; j < numLevel3 && itemCount < 100; j++) {
        const level3Type = Math.random();
        let level3Item;

        if (level3Type < 0.3) {
          // External link (new tab)
          level3Item = {
            id: `${subcategory.id}_ext${j}`,
            title: `Externe Resource ${j + 1}`,
            pageId: null,
            url: `https://example.com/${category.id}/${i}/${j}`,
            target: '_blank',
            children: []
          };
        } else if (level3Type < 0.6) {
          // External link (same tab)
          level3Item = {
            id: `${subcategory.id}_link${j}`,
            title: `Link ${j + 1}`,
            pageId: null,
            url: `https://internal.example.com/${category.id}/${i}/${j}`,
            target: '_self',
            children: []
          };
        } else {
          // Internal page link
          level3Item = {
            id: `${subcategory.id}_page${j}`,
            title: `Pagina ${j + 1}`,
            pageId: 'home', // placeholder
            url: null,
            target: null,
            children: []
          };
        }

        subcategory.children.push(level3Item);
        itemCount++;
      }
    } else {
      // Link directly to a page or URL
      if (Math.random() > 0.5) {
        subcategory.pageId = 'home';
      } else {
        subcategory.url = `https://example.com/${category.id}/${i}`;
        subcategory.target = '_blank';
      }
    }

    category.children.push(subcategory);
    itemCount++;
  }

  navigation.items.push(category);
});

// Add some direct links at level 1
while (itemCount < 100) {
  navigation.items[0].children.push({
    id: `extra_${itemCount}`,
    title: `Extra Item ${itemCount}`,
    pageId: 'home',
    url: null,
    target: null,
    children: []
  });
  itemCount++;
}

console.log(JSON.stringify(navigation, null, 2));
console.log(`\n✅ Created navigation with ${itemCount} items total`);

// Count items per level
let level1 = navigation.items.length;
let level2 = 0;
let level3 = 0;

navigation.items.forEach(item => {
  level2 += item.children.length;
  item.children.forEach(child => {
    level3 += child.children.length;
  });
});

console.log(`\n📊 Structure:`);
console.log(`  Level 1: ${level1} items`);
console.log(`  Level 2: ${level2} items`);
console.log(`  Level 3: ${level3} items`);
console.log(`  Total: ${level1 + level2 + level3} items`);

// Save to file
const fs = require('fs');
fs.writeFileSync(
  '/tmp/navigation-testdata.json',
  JSON.stringify(navigation, null, 2)
);
console.log(`\n💾 Saved to /tmp/navigation-testdata.json`);
