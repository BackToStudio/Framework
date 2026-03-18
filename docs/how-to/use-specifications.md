# How to use Specifications for querying posts and terms

Specifications are named, reusable query criteria. They encapsulate filtering logic in small, composable classes.

## Querying posts with built-in specifications

Use the `matching()` method on the query builder:

```php
use BackTo\Framework\PostType\Specification\PublishedPosts;
use BackTo\Framework\PostType\Specification\RecentPosts;

// All published posts
$posts = $repository->query()->matching(new PublishedPosts())->get();

// 5 most recent published posts
$posts = $repository->query()->matching(new RecentPosts(5))->get();
```

## Combining specifications

Use `AndPostSpecification` to combine multiple criteria:

```php
use BackTo\Framework\PostType\Specification\AndPostSpecification;
use BackTo\Framework\PostType\Specification\PostsByType;
use BackTo\Framework\PostType\Specification\PostsInTaxonomy;
use BackTo\Framework\PostType\Specification\RecentPosts;

$spec = new AndPostSpecification(
    new PostsByType('article'),
    new PostsInTaxonomy('category', [12, 34]),
    new RecentPosts(10),
);

$posts = $repository->query()->matching($spec)->get();
```

## Filtering by meta

```php
use BackTo\Framework\PostType\Specification\PostsWithMeta;
use BackTo\Framework\Query\MetaCompare;

// Posts where a meta key exists
$spec = new PostsWithMeta('featured');

// Posts where a meta key has a specific value
$spec = new PostsWithMeta('price', 100, MetaCompare::GREATER_THAN);
```

## Querying terms

The same pattern applies to taxonomies:

```php
use BackTo\Framework\Taxonomy\Specification\AndTermSpecification;
use BackTo\Framework\Taxonomy\Specification\TermsInTaxonomy;
use BackTo\Framework\Taxonomy\Specification\TopLevelTerms;
use BackTo\Framework\Taxonomy\Specification\NonEmptyTerms;

$spec = new AndTermSpecification(
    new TermsInTaxonomy('category'),
    new TopLevelTerms(),
    new NonEmptyTerms(),
);

$terms = $termRepository->query()->matching($spec)->get();
```

## Creating a custom specification

Implement `PostSpecification` or `TermSpecification`:

```php
use BackTo\Framework\PostType\Specification\PostSpecification;
use BackTo\Framework\PostType\Repository\PostQueryBuilder;

final class FeaturedArticles implements PostSpecification
{
    public function apply(PostQueryBuilder $builder): PostQueryBuilder
    {
        return $builder
            ->postType('article')
            ->status(PostStatus::Publish)
            ->whereMetaExists('featured');
    }
}
```

Then use it like any other specification:

```php
$posts = $repository->query()->matching(new FeaturedArticles())->get();
```

## Mixing specifications with the query builder

Specifications and fluent methods can be combined freely:

```php
$posts = $repository->query()
    ->matching(new PublishedPosts())
    ->postType('article')
    ->orderBy('title', SortDirection::ASC)
    ->limit(20)
    ->get();
```
