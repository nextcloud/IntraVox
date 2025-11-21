# Internationalization (i18n) for IntraVox

IntraVox supports multiple languages through Nextcloud's translation system using `@nextcloud/l10n`.

## Supported Languages

IntraVox currently supports the following languages:

- **Dutch (nl)** - Nederlands
- **English (en)** - English (fallback language)
- **German (de)** - Deutsch
- **French (fr)** - Français

## How it Works

The app automatically displays in the language configured in the user's Nextcloud personal settings.

### Translation Files

Translation files are stored in two formats:

1. **PO files** (`l10n/*.po`) - Standard gettext format used by Transifex
2. **JSON files** (`l10n/*.json`) - Used by Nextcloud at runtime

### Translation Template

The master translation template is located at:
- `translationfiles/templates/intravox.pot`

This file contains all translatable strings used in the application.

## For Developers

### Adding New Translatable Strings

When adding new text to the UI, wrap it with the translation function:

```javascript
// In Vue components
{{ t('Your translatable text') }}

// With variables
{{ t('Hello {name}', { name: userName }) }}

// In component methods
methods: {
  t(key, vars = {}) {
    return t('intravox', key, vars);
  }
}
```

### Updating Translations

1. Add the new string to `translationfiles/templates/intravox.pot`
2. Update all language files in `l10n/*.po`
3. Convert PO files to JSON format in `l10n/*.json`
4. Rebuild the app with `npm run build`

## For Translators

### Using Transifex

IntraVox translations can be managed through Transifex (when configured):

1. Import the POT template to Transifex
2. Translate strings through the Transifex web interface
3. Export completed translations as PO files
4. Convert PO files to JSON format
5. Commit both PO and JSON files

### Manual Translation

To manually add a new language:

1. Copy `l10n/en.po` to `l10n/<language_code>.po`
2. Update the header information (Language, Language-Team, etc.)
3. Translate all `msgstr` values
4. Create corresponding JSON file in `l10n/<language_code>.json`
5. Rebuild and deploy the app

### JSON Translation Format

Example JSON structure:

```json
{
  "translations": {
    "Edit": "Bewerken",
    "Save": "Opslaan",
    "Cancel": "Annuleren"
  },
  "pluralForm": "nplurals=2; plural=(n != 1);"
}
```

## Testing Translations

1. Change your Nextcloud language in Settings → Personal → Language
2. Reload IntraVox
3. The interface should display in your selected language

## Translation Coverage

All user-facing text in the following components is translated:

- App.vue (main navigation and messages)
- PageEditor.vue (page editing interface)
- PageListModal.vue (page list dialog)
- WidgetEditor.vue (widget editing dialog)
- WidgetPicker.vue (widget selection dialog)

## Technical Details

The implementation uses:
- `@nextcloud/l10n` package for translation functions
- Standard Nextcloud translation patterns
- Gettext PO/POT format for source translations
- JSON format for runtime translations
- Automatic language detection from user settings

## Future Enhancements

To add more languages:

1. Create new PO file for the language code
2. Translate all strings
3. Generate corresponding JSON file
4. Update this README with the new language

## References

- [Nextcloud App Translation Guide](https://docs.nextcloud.com/server/latest/developer_manual/app_publishing_maintenance/l10n.html)
- [@nextcloud/l10n Documentation](https://nextcloud-libraries.github.io/nextcloud-l10n/)
- [Gettext PO Format](https://www.gnu.org/software/gettext/manual/html_node/PO-Files.html)
