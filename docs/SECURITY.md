# IntraVox Security - SVG Upload Protection

## Overview

IntraVox supports SVG uploads with comprehensive sanitization to prevent security attacks. This document explains the security measures implemented to safely handle SVG files.

## Why SVG Files Need Sanitization

SVG (Scalable Vector Graphics) files are XML-based and can contain:
- JavaScript code via `<script>` tags
- Event handlers (onclick, onload, etc.)
- External entity references (XXE attacks)
- HTML injection via `<foreignObject>` elements
- Remote resource loading (privacy/tracking concerns)

Without proper sanitization, malicious SVG files could execute JavaScript in users' browsers, leading to XSS (Cross-Site Scripting) attacks.

## Attack Vectors Prevented

| Attack Vector | Risk Level | How We Prevent It |
|--------------|-----------|-------------------|
| **XSS via `<script>` tags** | Critical | Removed by svg-sanitize library |
| **Event handlers** | Critical | Stripped (onclick, onload, onerror, etc.) |
| **XXE attacks** | High | DOCTYPE declarations rejected |
| **HTML injection via `<foreignObject>`** | High | foreignObject elements removed |
| **Remote resource loading** | Medium | External references stripped |

## Implementation

### Server-Side Sanitization

**Library**: `enshrined/svg-sanitize` v0.20+
- Industry-standard PHP library
- Used by WordPress, Drupal, and other major platforms
- MIT licensed, actively maintained

**Process**:
1. File upload received
2. MIME type validated via PHP `finfo_file()` (not file extension)
3. SVG content sanitized through library
4. Additional DOCTYPE check (XXE prevention)
5. Sanitized content stored to disk

**Code Location**:
- `lib/Service/PageService.php` - `sanitizeSVG()` method (lines 3632-3662)
- Integration in `uploadMedia()` (line 1692-1698)
- Integration in `uploadMediaWithOriginalName()` (line 3780-3786)

### Defense-in-Depth Layers

1. **MIME Type Validation**: Server-side detection using `finfo_file()` prevents file extension spoofing
2. **Content Sanitization**: Removes malicious elements and attributes
3. **DOCTYPE Rejection**: Blocks XML external entity (XXE) attacks
4. **img Tag Rendering**: Frontend renders SVG via `<img>` tag (scripts won't execute even if sanitization bypassed)
5. **File Size Limits**: Maximum 50MB prevents DoS attacks
6. **Permission Checks**: Existing Nextcloud permission system respected

### What Gets Removed

The sanitizer removes or neutralizes:
- `<script>` elements
- Event handler attributes (onclick, onmouseover, onerror, onload, etc.)
- `<foreignObject>` elements
- `<iframe>` elements
- External stylesheets (`<link>`)
- Data URIs in certain contexts
- JavaScript protocol handlers (`javascript:`)
- DOCTYPE declarations

### What Is Preserved

Safe SVG features are preserved:
- Basic shapes (rect, circle, path, polygon, etc.)
- Gradients and patterns
- Text elements
- Transformations and animations (SMIL, not JavaScript-based)
- Internal styles (style attribute, `<style>` elements with CSS only)
- Accessibility attributes (aria-*, role)

## Security Best Practices

### For Users

1. **Source Trust**: Only upload SVG files from trusted sources
2. **Review Before Upload**: Check SVG content if received from external parties
3. **Report Issues**: If an SVG upload fails, contact your administrator (file may contain malicious content)

### For Administrators

1. **Keep Updated**: Regularly update IntraVox to get latest security patches
2. **Monitor Logs**: Check Nextcloud logs for SVG sanitization errors
3. **Composer Dependencies**: Keep `enshrined/svg-sanitize` library updated
4. **File Permissions**: Ensure proper Nextcloud ACLs for _resources folder

## Testing

### Valid SVG Examples

These will pass sanitization:
```xml
<!-- Simple shapes -->
<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100">
  <circle cx="50" cy="50" r="40" fill="blue"/>
</svg>

<!-- Gradients -->
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="100">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="0%">
      <stop offset="0%" style="stop-color:rgb(255,255,0);stop-opacity:1" />
      <stop offset="100%" style="stop-color:rgb(255,0,0);stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="200" height="100" fill="url(#grad)" />
</svg>
```

### Malicious SVG Examples (Will Be Rejected/Sanitized)

These will fail or be stripped:
```xml
<!-- Script injection (REMOVED) -->
<svg xmlns="http://www.w3.org/2000/svg">
  <script>alert('XSS')</script>
</svg>

<!-- Event handler (REMOVED) -->
<svg xmlns="http://www.w3.org/2000/svg">
  <circle onclick="alert('XSS')" cx="50" cy="50" r="40"/>
</svg>

<!-- XXE attack (REJECTED) -->
<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>
<svg xmlns="http://www.w3.org/2000/svg">
  <text>&xxe;</text>
</svg>
```

## Technical Details

### Sanitization Method

```php
private function sanitizeSVG(string $svgContent): string {
    try {
        $sanitizer = new Sanitizer();
        $sanitizer->removeRemoteReferences(true);

        $cleanSvg = $sanitizer->sanitize($svgContent);

        if ($cleanSvg === false || empty($cleanSvg)) {
            throw new \Exception('SVG sanitization failed - file may contain malicious content');
        }

        // Additional security: reject DOCTYPE (XXE attack vector)
        if (stripos($cleanSvg, '<!DOCTYPE') !== false) {
            throw new \Exception('SVG contains DOCTYPE declaration (not allowed)');
        }

        return $cleanSvg;
    } catch (\Exception $e) {
        $this->logger->error('SVG sanitization error: ' . $e->getMessage());
        throw new \Exception('Invalid SVG file: ' . $e->getMessage());
    }
}
```

### Error Handling

When SVG sanitization fails:
1. Error logged to Nextcloud log file
2. Exception thrown with clear message
3. Upload rejected (file not saved)
4. User receives error notification

## Known Limitations

1. **Complex Animations**: JavaScript-based SVG animations will be removed (use CSS/SMIL instead)
2. **Interactive Features**: onclick handlers and similar interactivity stripped
3. **External Resources**: Remote images, fonts, and stylesheets removed
4. **DOCTYPE**: Any DOCTYPE declaration causes rejection (security measure)

## Compliance

This implementation follows:
- **OWASP Top 10**: Protection against XSS and XXE attacks
- **CSP Best Practices**: img tag rendering prevents script execution
- **Industry Standards**: Same approach used by WordPress, Drupal, GitHub

## References

- [OWASP XSS Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [enshrined/svg-sanitize Library](https://github.com/darylldoyle/svg-sanitizer)
- [SVG Security Best Practices](https://www.w3.org/TR/SVG/security.html)

## Version History

- **v0.8.0** (2025-12-16): Initial SVG support with sanitization
  - enshrined/svg-sanitize v0.20
  - Server-side sanitization
  - DOCTYPE rejection
  - Multi-layer defense approach

## Contact

For security concerns or bug reports:
- **GitHub Issues**: https://github.com/shalution/intravox/issues
- **Security Email**: security@shalution.com

---

**Last Updated**: 2025-12-16
**Document Version**: 1.0
