# PWA Icons

This directory should contain PWA icon files for the mobile app manifest.

## Required Icon Sizes

Create PNG icons in the following sizes:

- `icon-72.png` (72x72 pixels)
- `icon-96.png` (96x96 pixels)
- `icon-128.png` (128x128 pixels)
- `icon-144.png` (144x144 pixels)
- `icon-152.png` (152x152 pixels)
- `icon-192.png` (192x192 pixels)
- `icon-384.png` (384x384 pixels)
- `icon-512.png` (512x512 pixels)

## Creating Icons

### Option 1: Using Image Editor

1. Create a square image (e.g., 512x512)
2. Design your icon/logo
3. Export at all required sizes
4. Save as `icon-{size}.png` in this directory

### Option 2: Using Online Tools

Use PWA icon generators:
- [PWA Asset Generator](https://github.com/onderceylan/pwa-asset-generator)
- [RealFaviconGenerator](https://realfavicongenerator.net/)
- [App Icon Generator](https://www.appicon.co/)

### Option 3: Using Command Line (ImageMagick)

If ImageMagick is installed:

```bash
# Create base icon (512x512)
convert -size 512x512 xc:#4285F4 -gravity center -pointsize 200 -fill white -annotate +0+0 "PWA" icon-512.png

# Generate all sizes
for size in 72 96 128 144 152 192 384 512; do
    convert icon-512.png -resize ${size}x${size} icon-${size}.png
done
```

### Option 4: Using PHP GD (if enabled)

Run the provided script (requires GD extension):

```bash
php generate-icons.php
```

## Icon Requirements

- **Format**: PNG
- **Shape**: Square (1:1 aspect ratio)
- **Background**: Can be transparent or solid color
- **Content**: Should be recognizable at small sizes
- **Maskable**: For Android, consider creating maskable icons

## Best Practices

1. Use high-quality source images
2. Ensure icons are clear at small sizes
3. Test icons on actual devices
4. Use appropriate colors and contrast
5. Consider platform-specific requirements

## Temporary Placeholders

If icons are not yet available, the component will use default placeholders. Replace these with your branded icons before production deployment.

