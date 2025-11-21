# Demo Data Deployment Notes

## Belangrijke informatie voor deployment naar test server

### Groupfolder IDs
Op de test server (145.38.191.31) zijn er meerdere groupfolders:
- **Groupfolder 4** = "IntraVox" (de JUISTE folder voor demo data!)
- **Groupfolder 6** = "TEST" (NIET gebruiken voor IntraVox demo data)

### Correcte deployment procedure

1. **Upload demo data naar server:**
   ```bash
   cd /Users/rikdekker/Documents/Development/IntraVox
   tar -czf /tmp/intravox-demo-en.tar.gz -C demo-data en
   scp -i ~/.ssh/sur /tmp/intravox-demo-en.tar.gz rdekker@145.38.191.31:/tmp/
   ```

2. **Extract en kopieer naar de JUISTE groupfolder (4, niet 6!):**
   ```bash
   ssh -i ~/.ssh/sur rdekker@145.38.191.31 "cd /tmp && tar -xzf intravox-demo-en.tar.gz && sudo cp -r en /var/www/nextcloud/data/__groupfolders/4/files/ && sudo chown -R www-data:www-data /var/www/nextcloud/data/__groupfolders/4/files/en"
   ```

3. **Verwijder macOS metadata files:**
   ```bash
   ssh -i ~/.ssh/sur rdekker@145.38.191.31 "sudo find /var/www/nextcloud/data/__groupfolders/4/files/en -name '._*' -delete"
   ```

4. **Scan de IntraVox groupfolder (ID 4):**
   ```bash
   ssh -i ~/.ssh/sur rdekker@145.38.191.31 "cd /var/www/nextcloud && sudo -u www-data php occ groupfolders:scan 4"
   ```

5. **Clear caches (indien nodig):**
   ```bash
   ssh -i ~/.ssh/sur rdekker@145.38.191.31 "sudo service apache2 restart"
   ssh -i ~/.ssh/sur rdekker@145.38.191.31 "cd /var/www/nextcloud && sudo -u www-data php occ maintenance:mode --on && sleep 1 && sudo -u www-data php occ maintenance:mode --off"
   ```

6. **Hard refresh browser:**
   - Windows/Linux: Ctrl+Shift+R
   - Mac: Cmd+Shift+R

### Veelvoorkomende problemen

#### Probleem: EN folder niet zichtbaar in Files
**Oorzaak:** Demo data is naar verkeerde groupfolder (6) gekopieerd in plaats van de IntraVox groupfolder (4)

**Oplossing:**
```bash
# Check welke groupfolder IntraVox is:
ssh -i ~/.ssh/sur rdekker@145.38.191.31 "cd /var/www/nextcloud && sudo -u www-data php occ groupfolders:list"

# Kopieer van verkeerde naar juiste groupfolder:
ssh -i ~/.ssh/sur rdekker@145.38.191.31 "sudo cp -r /var/www/nextcloud/data/__groupfolders/6/files/en /var/www/nextcloud/data/__groupfolders/4/files/ && sudo chown -R www-data:www-data /var/www/nextcloud/data/__groupfolders/4/files/en"

# Scan de juiste groupfolder:
ssh -i ~/.ssh/sur rdekker@145.38.191.31 "cd /var/www/nextcloud && sudo -u www-data php occ groupfolders:scan 4"
```

#### Probleem: Caching issues
**Oorzaak:** APCu cache of browser cache

**Oplossing:**
1. Restart Apache: `sudo service apache2 restart`
2. Toggle maintenance mode om Nextcloud caches te clearen
3. Hard refresh browser

### Verificatie commands

**Check of bestanden er zijn:**
```bash
ssh -i ~/.ssh/sur rdekker@145.38.191.31 "sudo ls -la /var/www/nextcloud/data/__groupfolders/4/files/en/"
```

**Check groupfolder scan (verbose):**
```bash
ssh -i ~/.ssh/sur rdekker@145.38.191.31 "cd /var/www/nextcloud && sudo -u www-data php occ groupfolders:scan 4 -vvv"
```

**Check groupfolder lijst:**
```bash
ssh -i ~/.ssh/sur rdekker@145.38.191.31 "cd /var/www/nextcloud && sudo -u www-data php occ groupfolders:list"
```

### Demo Data Structuur

De demo data moet in deze structuur staan:
```
en/
├── home.json
├── navigation.json
├── images/
│   └── (gedeelde afbeeldingen)
├── about/
│   ├── about.json
│   └── images/
├── contact/
│   ├── contact.json
│   └── images/
├── (... andere page folders)
```

### Toegang na deployment

Na succesvolle deployment is de demo data beschikbaar op:
- URL: https://145.38.191.31/apps/intravox
- Taal: EN
- Locatie: IntraVox groupfolder → en/

## Laatst bijgewerkt
16 november 2025
