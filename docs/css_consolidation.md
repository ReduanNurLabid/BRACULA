# CSS Consolidation

## Overview
As part of our project restructuring efforts, we have consolidated all CSS files to be located in the `/public/css/` directory. The `/css/` directory at the root level now contains only a redirect script that points to the `/public/css/` directory for backward compatibility.

## Guidelines for Developers

1. **Use only the `/public/css/` directory for CSS files**
   - All CSS development should be done in the `/public/css/` directory
   - Do not add or modify files in the root `/css/` directory
   - The root `/css/` directory is maintained only for backward compatibility

2. **CSS File Locations**
   - Main stylesheet: `/public/css/style.css`
   - Feature-specific stylesheets: `/public/css/feature-name.css` (e.g., `/public/css/accommodation.css`)

3. **HTML References**
   - All HTML files should reference CSS files using the relative path to the public directory:
     ```html
     <link href="../public/css/style.css" rel="stylesheet">
     <link href="../public/css/feature-name.css" rel="stylesheet">
     ```

4. **PHP Redirect**
   - The `/css/index.php` file contains a redirect to ensure backward compatibility
   - This redirect will be maintained but should not be relied upon for new development

## Technical Details

The redirect in `/css/index.php` works as follows:

```php
<?php
// Redirect CSS requests to the new location
$file = basename($_SERVER["REQUEST_URI"]);
if ($file != "index.php") {
    header("Location: /public/css/" . $file);
    exit;
}
?>
```

This ensures that any existing references to `/css/file.css` will be redirected to `/public/css/file.css`. 