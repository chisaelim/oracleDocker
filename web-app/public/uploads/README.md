# Image Upload Security

This directory contains uploaded product images.

## Security Rules:
- Only authenticated users can upload images
- Only specific image types allowed (jpg, jpeg, png, gif)
- File size limits enforced
- File names are sanitized
- Images are resized for consistency

## Directory Structure:
- /uploads/products/ - Full size product images
- /uploads/products/thumbnails/ - Thumbnail images (auto-generated)

## Access:
- Images are publicly accessible for display
- Upload functionality requires authentication