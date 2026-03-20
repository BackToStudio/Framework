<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;
use BackTo\Framework\Bundle\Seo\Schema\Type\Rating;
use BackTo\Framework\Bundle\Seo\Schema\Type\Review;
use PHPUnit\Framework\TestCase;

class SchemaReviewTest extends TestCase
{
    public function testReviewFactory(): void
    {
        $review = Schema::review();
        $this->assertInstanceOf(Review::class, $review);
        $this->assertSame('Review', $review->getType());
    }

    public function testRatingFactory(): void
    {
        $rating = Schema::rating();
        $this->assertInstanceOf(Rating::class, $rating);
        $this->assertSame('Rating', $rating->getType());
    }

    public function testReviewItemReviewed(): void
    {
        $array = Schema::review()
            ->itemReviewed(Schema::product()->name('Widget'))
            ->toArray();

        $this->assertSame('Product', $array['itemReviewed']['@type']);
        $this->assertSame('Widget', $array['itemReviewed']['name']);
    }

    public function testReviewRating(): void
    {
        $array = Schema::review()
            ->reviewRating(Schema::rating()->ratingValue(4)->bestRating(5)->worstRating(1))
            ->toArray();

        $this->assertSame('Rating', $array['reviewRating']['@type']);
        $this->assertSame(4.0, $array['reviewRating']['ratingValue']);
        $this->assertSame(5.0, $array['reviewRating']['bestRating']);
        $this->assertSame(1.0, $array['reviewRating']['worstRating']);
    }

    public function testReviewAuthorAsSchemaType(): void
    {
        $array = Schema::review()
            ->author(Schema::person()->name('Jean Dupont'))
            ->toArray();

        $this->assertSame('Person', $array['author']['@type']);
        $this->assertSame('Jean Dupont', $array['author']['name']);
    }

    public function testReviewAuthorAsString(): void
    {
        $array = Schema::review()->author('Jean Dupont')->toArray();
        $this->assertSame('Jean Dupont', $array['author']);
    }

    public function testReviewDatePublished(): void
    {
        $array = Schema::review()->datePublished('2026-03-15')->toArray();
        $this->assertSame('2026-03-15', $array['datePublished']);
    }

    public function testReviewBody(): void
    {
        $array = Schema::review()->reviewBody('Excellent service !')->toArray();
        $this->assertSame('Excellent service !', $array['reviewBody']);
    }

    public function testReviewName(): void
    {
        $array = Schema::review()->name('Mon avis')->toArray();
        $this->assertSame('Mon avis', $array['name']);
    }

    public function testReviewPublisher(): void
    {
        $array = Schema::review()
            ->publisher(Schema::organization()->name('Le Monde'))
            ->toArray();

        $this->assertSame('Organization', $array['publisher']['@type']);
        $this->assertSame('Le Monde', $array['publisher']['name']);
    }

    public function testRatingValue(): void
    {
        $array = Schema::rating()->ratingValue(4.5)->toArray();
        $this->assertSame(4.5, $array['ratingValue']);
    }

    public function testRatingValueAsString(): void
    {
        $array = Schema::rating()->ratingValue('4.5')->toArray();
        $this->assertSame('4.5', $array['ratingValue']);
    }

    public function testRatingBestRating(): void
    {
        $array = Schema::rating()->bestRating(5)->toArray();
        $this->assertSame(5.0, $array['bestRating']);
    }

    public function testRatingWorstRating(): void
    {
        $array = Schema::rating()->worstRating(1)->toArray();
        $this->assertSame(1.0, $array['worstRating']);
    }

    public function testFullReviewComposition(): void
    {
        $review = Schema::review()
            ->name('Excellent restaurant')
            ->itemReviewed(Schema::localBusiness()->name('Chez Paul'))
            ->reviewRating(Schema::rating()->ratingValue(5)->bestRating(5))
            ->author(Schema::person()->name('Marie Martin'))
            ->datePublished('2026-03-01')
            ->reviewBody('Cuisine raffinée et service impeccable.')
            ->publisher(Schema::organization()->name('Guide Resto'));

        $array = $review->toArray();

        $this->assertSame('Review', $array['@type']);
        $this->assertSame('Excellent restaurant', $array['name']);
        $this->assertSame('LocalBusiness', $array['itemReviewed']['@type']);
        $this->assertSame('Rating', $array['reviewRating']['@type']);
        $this->assertSame(5.0, $array['reviewRating']['ratingValue']);
        $this->assertSame('Person', $array['author']['@type']);
        $this->assertSame('2026-03-01', $array['datePublished']);
        $this->assertSame('Cuisine raffinée et service impeccable.', $array['reviewBody']);
        $this->assertSame('Organization', $array['publisher']['@type']);
    }

    public function testReviewRendersValidJsonLd(): void
    {
        $manager = new SchemaManager();
        $manager->add(
            Schema::review()
                ->itemReviewed(Schema::product()->name('Widget'))
                ->reviewRating(Schema::rating()->ratingValue(4))
                ->author(Schema::person()->name('Test'))
        );

        $output = $manager->render();

        preg_match('/<script type="application\/ld\+json">\n(.+)\n<\/script>/s', $output, $matches);
        $decoded = json_decode($matches[1], true);

        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('Review', $decoded['@type']);
        $this->assertSame('Product', $decoded['itemReviewed']['@type']);
    }
}
