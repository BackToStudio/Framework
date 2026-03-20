# Add a VideoObject schema

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $video = Schema::videoObject()
        ->name('Product Overview')
        ->description('A walkthrough of our latest product features.')
        ->thumbnailUrl('https://example.com/images/video-thumb.jpg')
        ->uploadDate('2025-03-01T10:00:00+00:00')
        ->duration('PT5M30S')
        ->contentUrl('https://example.com/videos/overview.mp4')
        ->embedUrl('https://www.youtube.com/embed/abc123')
        ->hasPart([
            Schema::clip()
                ->name('Introduction')
                ->startOffset(0)
                ->endOffset(30)
                ->url('https://example.com/videos/overview.mp4?t=0'),
            Schema::clip()
                ->name('Demo')
                ->startOffset(30)
                ->endOffset(180)
                ->url('https://example.com/videos/overview.mp4?t=30'),
        ]);

    $manager->add($video);
});
```
