# Generate schema from ACF custom fields

Pull structured data from ACF fields dynamically:

```php
<?php

namespace MyTheme\Seo;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class CourseSchemaGenerator
{
    public function __construct(
        private readonly ContentQueryInterface $contentQuery,
    ) {}

    public function generate(?int $postId = null): ?SchemaType
    {
        $post = $this->contentQuery->getPost($postId);
        if ($post === null) {
            return null;
        }

        // Read ACF fields
        $provider = get_field('course_provider', $post->ID);
        $price = get_field('course_price', $post->ID);
        $startDate = get_field('course_start_date', $post->ID);
        $mode = get_field('course_delivery_mode', $post->ID); // 'online' or 'onsite'

        $course = Schema::type('Course')
            ->set('name', $post->post_title)
            ->set('description', $this->contentQuery->getTheExcerpt($post))
            ->set('url', $this->contentQuery->getPermalink($post->ID))
            ->set('provider', Schema::organization()->name($provider ?: get_bloginfo('name')));

        if ($startDate) {
            $course->set('hasCourseInstance',
                Schema::type('CourseInstance')
                    ->set('courseMode', $mode === 'online' ? 'Online' : 'Onsite')
                    ->set('startDate', $startDate)
                    ->set('offers', Schema::offer()
                        ->price($price ?: 0)
                        ->priceCurrency('EUR')
                        ->availability('https://schema.org/InStock'))
            );
        }

        return $course;
    }
}
```
