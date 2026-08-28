# Attaching media to your models

## The model side

Add the trait. Nothing else — no interface, no migration of your own.

```php
use Kreetancraft\Media\Concerns\HasMediaAttachments;

class Article extends Model
{
    use HasMediaAttachments;
}
```

This is a **many-to-many** link, unlike Spatie's `HasMedia` which models 1:1
ownership. One image can be attached to many models, and deleting a model drops
its attachments without deleting the shared file.

### Collections

A collection is just a string. Use whatever names your domain wants —
`featured`, `gallery`, `banner`, `downloads`. There is no registration step.

```php
$article->attachMedia($mediaId, 'featured');
$article->attachMedia($mediaId, 'gallery', sortOrder: 3);

$article->attachedMedia('gallery');        // Collection<Media>, sorted
$article->firstAttachedMedia('featured');  // ?Media
$article->attachedUrl('gallery');          // ?string
$article->featuredUrl();                   // ?string — shorthand for 'featured'
$article->featuredUrl('webp');             // a named conversion

$article->detachMedia($mediaId, 'gallery');
$article->syncAttachedMedia([$a, $b, $c], 'gallery');   // replaces, preserves order
```

`syncAttachedMedia()` sets sort order from array position, so reordering a
gallery is one call.

### Always eager load

```php
// Good
Article::with('mediaAttachments.media')->get();

// Silently N+1 — see getting-started.md
Article::all();
```

## The form side

Drop the picker into any Livewire component:

```blade
<livewire:media.picker wire:model="imageId" collection="featured" />
```

```php
class EditArticle extends Component
{
    public ?int $imageId = null;

    public function save(): void
    {
        $this->article->syncAttachedMedia(
            array_filter([$this->imageId]),
            'featured',
        );
    }
}
```

## Uploads

Enforced in two places on purpose: as validation rules so the user gets a real
error message, and again inside `UploadMediaAction` so a caller that skips
validation cannot write an arbitrary file type.

```php
// config/media.php
'uploads' => [
    'max_size_kb'        => 10240,
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'],
    'allowed_mimes'      => ['image/jpeg', 'image/png', 'application/pdf'],
],
```

Both the picker and the gallery read one `UploadRules` definition, so they
cannot drift apart — an earlier version had the gallery restricting extensions
while the picker accepted anything.

**On SVG:** allowed by default, but `FileController` serves it as a download
rather than inline, so it cannot execute script in your origin. Remove it from
`allowed_extensions` if you would rather not accept it at all.

## URLs

```php
use Kreetancraft\Media\Support\MediaUrl;

MediaUrl::publicFor($media);          // disk URL, or the folder-scoped route
MediaUrl::publicFor($media, 'webp');  // a named conversion
```

Media attached to a folder is served through `/assets/{path}` so the hierarchy
appears in the URL. Everything else uses the disk URL. If the asset route is
disabled or missing, it falls back to the disk URL rather than throwing — a
missing route should not take a page down over one image.

## WebP

Every uploaded image gets a WebP variant, queued. Configure it:

```php
'webp' => [
    'enabled'   => true,
    'queue'     => null,     // queue name, or null for the default
    'quality'   => 85,
    'max_width' => 2400,
],
```

Backfill an existing library:

```bash
php artisan media:reconvert-webp
```
