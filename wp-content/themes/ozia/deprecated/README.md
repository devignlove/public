# Deprecated Code & Features

This folder contains code and features that have been deprecated and are no longer actively used in the theme. They are preserved for historical reference and potential future implementation.

## Archived Files

### threejshome.css
- **Status**: Deprecated (v1.0 - 2.0 refactor)
- **Purpose**: CSS for a 3D text effect feature on the homepage (using Three.js)
- **Issue**: Feature was incomplete and not integrated into the active codebase
- **Last Modified**: [See Git History]
- **Notes**: 
  - File appears to be empty or contains placeholder styles
  - No corresponding implementation in current theme
  - Consider removing if not planning to revive feature

### threejshome.js
- **Status**: Deprecated (v1.0 - 2.0 refactor)
- **Purpose**: JavaScript for 3D text effect feature (Three.js library integration)
- **Issue**: Feature incomplete, not wired into theme, no active maintenance
- **Last Modified**: [See Git History]
- **Notes**:
  - Only contains comments, no functional code visible
  - Three.js library never officially added to theme dependencies
  - Would require significant refactoring to implement properly

---

## Migration Timeline

| Version | Action | Notes |
|---------|--------|-------|
| v1.0    | Initial Creation | Feature planned but never completed |
| v2.0    | Deprecation | Moved to `/deprecated/` folder during code refactoring; no longer enqueued |

---

## Future Considerations

### If Reviving `threejshome` Feature:

1. **Add Three.js Dependency**
   ```php
   wp_enqueue_script( 'three-js', '//cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js', [], '128' );
   wp_enqueue_script( 'ozi-threejs', get_stylesheet_directory_uri() . '/deprecated/threejshome.js', ['three-js'], filemtime(...), true );
   ```

2. **Create Proper Implementation**
   - Implement complete 3D text rendering logic
   - Ensure accessibility (fallback text, proper ARIA labels)
   - Optimize for mobile/touch devices
   - Performance test with Lighthouse

3. **Update Theme Class**
   - Add to `OZI_Theme_Setup::enqueue_scripts()` if always needed
   - Or create new `OZI_ThreeJS` class for optional feature toggle

4. **Move Back from `/deprecated/`**
   - Move files back to `assets/` once production-ready
   - Update documentation and version number

---

## Cleanup

To completely remove deprecated code:

```bash
# Remove deprecated folder
rm -rf deprecated/

# Or selectively remove specific files
rm deprecated/threejshome.css
rm deprecated/threejshome.js
```

**Note**: Ensure changes are committed to version control before deletion.
