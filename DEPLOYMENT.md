# IntraVox Deployment Guide

## Overview

IntraVox heeft verschillende deployment scripts voor verschillende omgevingen:

- **deploy-dev.sh** - Deploy naar development server (145.38.191.66)
- **deploy.sh** - Deploy naar production server (145.38.193.69)
- **deploy-demo-data.sh** - Deploy demo data naar development server

## Prerequisites

- Node.js en npm geïnstalleerd
- SSH toegang tot de server met key `~/.ssh/sur`
- Toegang tot de Nextcloud server

## Development Deployment

### 1. Deploy de applicatie

```bash
./deploy-dev.sh
```

Dit script:
1. Build de frontend vanuit `intravox-deploy/` directory
2. Installeert npm dependencies indien nodig
3. Kopieert de gebouwde bestanden naar de `js/` directory
4. Maakt een deployment package
5. Upload en installeert het package op de development server
6. Enabled de IntraVox app

### 2. Deploy demo data (optioneel)

```bash
./deploy-demo-data.sh
```

Dit script:
1. Kopieert demo data naar de server
2. Zet de juiste file permissions
3. Runt het import command (`intravox:import`)
4. Scant de groupfolder om nieuwe files te registreren

**Belangrijk**: Demo data overschrijft bestaande content!

## Production Deployment

Voor production deployment:

```bash
./deploy.sh
```

**Let op**: Dit deploy naar de production server (145.38.193.69)!

## Frontend Development

De frontend code staat in de `intravox-deploy/` directory. Dit is een aparte directory met:
- Vue 3 componenten in `src/`
- Webpack build configuratie
- Eigen `package.json` met dependencies

### Build proces

De deploy scripts:
1. Gaan naar `intravox-deploy/` directory
2. Installeren dependencies indien nodig (`npm install`)
3. Runnen `npm run build`
4. Kopiëren de gebouwde JS bestanden naar `../js/`

Dit zorgt ervoor dat de laatste frontend code altijd wordt gedeployd.

### Handmatig builden

Als je alleen de frontend wilt builden zonder te deployen:

```bash
cd intravox-deploy
npm install  # Alleen eerste keer nodig
npm run build
cp -r js/* ../js/
```

## Troubleshooting

### Build errors

Als je build errors krijgt:
```bash
cd intravox-deploy
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Permission errors op server

Als files niet leesbaar zijn:
```bash
ssh -i ~/.ssh/sur rdekker@145.38.191.66
sudo chown -R www-data:www-data /var/www/nextcloud/apps/intravox
sudo chmod -R 755 /var/www/nextcloud/apps/intravox
```

### Demo data permission errors

Als de import command faalt:
```bash
ssh -i ~/.ssh/sur rdekker@145.38.191.66
find /tmp/intravox-deploy/demo-data -type f -exec chmod 644 {} \;
find /tmp/intravox-deploy/demo-data -type d -exec chmod 755 {} \;
```

## Server URLs

- **Development**: https://intravoxdev.hvanextcloudpoc.src.surf-hosted.nl
- **Production**: https://145.38.193.69

## File Structure

```
IntraVox/
├── intravox-deploy/          # Frontend development directory
│   ├── src/                  # Vue 3 source files
│   │   ├── components/       # Vue components
│   │   └── App.vue          # Main app component
│   ├── js/                   # Built JavaScript files
│   ├── package.json          # Frontend dependencies
│   └── webpack.config.js     # Webpack configuration
├── js/                       # Deployed JavaScript (copied from intravox-deploy/js/)
├── lib/                      # PHP backend
├── demo-data/               # Demo data files
│   └── en/                  # English demo data
│       ├── home.json
│       ├── navigation.json
│       ├── footer.json
│       ├── images/          # Root images
│       └── [page-folders]/  # Page-specific folders
├── deploy-dev.sh            # Development deployment script
├── deploy.sh                # Production deployment script
└── deploy-demo-data.sh      # Demo data deployment script
```

## Key Changes (uniqueId Migration)

IntraVox gebruikt nu overal `uniqueId` in plaats van `id`:

### Frontend (Vue components)
- `page.id` → `page.uniqueId`
- Updated in: PageEditor.vue, PageViewer.vue, PageListModal.vue

### Backend (PHP)
- API stuurt alleen `uniqueId`, geen backwards compatibility met `id`
- Home page wordt gedetecteerd via uniqueId in home.json

Dit zorgt voor consistentie en voorkomt `undefined` errors in image URLs.
