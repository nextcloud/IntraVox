# Footer Implementation Debug Notes #FooterCheck

## Problem
Footer component niet zichtbaar op de pagina, 404 error bij `/api/footer` endpoint.

## Files Created/Modified

### Backend Files
1. **lib/Service/FooterService.php** - Service voor footer management
   - Handles per-language footer content
   - Stores in `{language}/footer.json`
   - Permission checking via IntraVox Admins group
   - Content sanitization met htmlspecialchars

2. **lib/Controller/FooterController.php** - REST API controller
   - GET /api/footer - Haal footer op
   - POST /api/footer - Sla footer op
   - Gebruikt JSONResponse (niet DataResponse)
   - @NoAdminRequired en @NoCSRFRequired annotations

3. **appinfo/routes.php** - Routes toegevoegd:
   ```php
   ['name' => 'footer#get', 'url' => '/api/footer', 'verb' => 'GET'],
   ['name' => 'footer#save', 'url' => '/api/footer', 'verb' => 'POST'],
   ```

4. **lib/AppInfo/Application.php** - Dependency injection registratie:
   ```php
   // Register FooterService
   $context->registerService(\OCA\IntraVox\Service\FooterService::class, function ($c) {
       return new \OCA\IntraVox\Service\FooterService(
           $c->get(\OCP\Files\IRootFolder::class),
           $c->get(\OCP\IUserSession::class),
           $c->get(\OCA\IntraVox\Service\SetupService::class),
           $c->get(\OCP\IConfig::class),
           $c->get(\OCP\IUserSession::class)->getUser()?->getUID()
       );
   });
   ```

### Frontend Files
5. **src/App.vue** - Footer integratie
   - Import Footer component
   - Add `footerContent` en `canEditFooter` data properties
   - Add `loadFooter()` en `handleFooterSave()` methods
   - Call `loadFooter()` in mounted()
   - Footer component in template (regel 128-134)

6. **src/components/Footer.vue** - Footer UI component
   - Editable footer met InlineTextEditor
   - Edit button alleen zichtbaar voor admins op homepage
   - Save/Cancel buttons tijdens editing

## Attempts Made

### Attempt 1: Initial Implementation
- Created FooterService.php and FooterController.php
- Added routes to routes.php
- Integrated Footer component in App.vue
- **Result**: 404 error - FooterController not found

### Attempt 2: Dependency Injection Registration
- Added FooterService registration to Application.php
- **Result**: Still 404 error

### Attempt 3: Changed Response Type
- Changed from DataResponse to JSONResponse in FooterController
- Matched NavigationController pattern
- **Result**: Still 404 error

## Current Status
- All files deployed successfully to server
- Routes defined correctly
- FooterService registered in DI container
- Still getting 404 error on `/api/footer` endpoint
- Footer component renders but API call fails

## Next Steps to Try
1. Check if FooterController class is being autoloaded correctly
2. Verify route naming convention matches Nextcloud expectations
3. Check server logs for specific error about FooterController
4. Consider manually flushing Nextcloud's route cache
5. Verify controller namespace and class name exactly match

## Reference Commands
```bash
# Deploy to server
./deploy-dev.sh

# Check local files
ls -la lib/Controller/FooterController.php
ls -la lib/Service/FooterService.php

# View logs (need SSH access)
ssh rdekker@145.38.191.31 'sudo tail -100 /var/www/nextcloud/data/nextcloud.log | grep -i footer'
```

## Code Comparison with Working NavigationController
NavigationController works with same pattern:
- routes.php: `['name' => 'navigation#get', ...]`
- Controller: NavigationController extends Controller
- Returns: JSONResponse
- Service: NavigationService registered in Application.php

Footer should work identically but currently doesn't.

## Date
2025-11-09
