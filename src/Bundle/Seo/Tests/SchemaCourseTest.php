<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\Type\AggregateRating;
use BackTo\Framework\Bundle\Seo\Schema\Type\Course;
use BackTo\Framework\Bundle\Seo\Schema\Type\CourseInstance;
use PHPUnit\Framework\TestCase;

class SchemaCourseTest extends TestCase
{
    public function testCourseFactory(): void
    {
        $course = Schema::course();
        $this->assertInstanceOf(Course::class, $course);
        $this->assertSame('Course', $course->getType());
    }

    public function testCourseInstanceFactory(): void
    {
        $instance = Schema::courseInstance();
        $this->assertInstanceOf(CourseInstance::class, $instance);
        $this->assertSame('CourseInstance', $instance->getType());
    }

    public function testAggregateRatingFactory(): void
    {
        $rating = Schema::aggregateRating();
        $this->assertInstanceOf(AggregateRating::class, $rating);
        $this->assertSame('AggregateRating', $rating->getType());
    }

    public function testFullCourseComposition(): void
    {
        $course = Schema::course()
            ->name('Formation WordPress Avancé')
            ->description('Maîtrisez les hooks, les custom post types et le développement de thèmes.')
            ->provider(Schema::organization()->name('Acme Formation')->url('https://acme-formation.fr'))
            ->url('https://acme-formation.fr/formations/wordpress-avance')
            ->inLanguage('fr')
            ->courseCode('WP-ADV-01')
            ->educationalLevel('Intermediate')
            ->coursePrerequisites(['Bases PHP', 'Connaissance HTML/CSS'])
            ->timeRequired('PT40H')
            ->hasCourseInstance([
                Schema::courseInstance()
                    ->courseMode('onsite')
                    ->startDate('2026-09-01')
                    ->endDate('2026-09-05')
                    ->location(
                        Schema::place()
                            ->name('Centre de formation Acme')
                            ->address(
                                Schema::postalAddress()
                                    ->streetAddress('10 rue de la Paix')
                                    ->addressLocality('Lyon')
                                    ->postalCode('69002')
                                    ->addressCountry('FR')
                            )
                    )
                    ->instructor(Schema::person()->name('Marie Martin'))
                    ->offers(
                        Schema::offer()
                            ->price('1500')
                            ->priceCurrency('EUR')
                            ->availability('https://schema.org/InStock')
                    ),
                Schema::courseInstance()
                    ->courseMode('online')
                    ->startDate('2026-10-15')
                    ->endDate('2026-10-19')
                    ->location(Schema::virtualLocation()->url('https://acme-formation.fr/live'))
                    ->instructor(Schema::person()->name('Marie Martin'))
                    ->offers(
                        Schema::offer()
                            ->price('990')
                            ->priceCurrency('EUR')
                    ),
            ])
            ->aggregateRating(
                Schema::aggregateRating()
                    ->ratingValue(4.7)
                    ->bestRating(5.0)
                    ->ratingCount(128)
                    ->reviewCount(45)
            );

        $array = $course->toArray();

        $this->assertSame('Course', $array['@type']);
        $this->assertSame('Formation WordPress Avancé', $array['name']);
        $this->assertSame('Organization', $array['provider']['@type']);
        $this->assertSame('fr', $array['inLanguage']);
        $this->assertSame('WP-ADV-01', $array['courseCode']);
        $this->assertSame(['Bases PHP', 'Connaissance HTML/CSS'], $array['coursePrerequisites']);
        $this->assertSame('PT40H', $array['timeRequired']);

        // Course instances
        $this->assertCount(2, $array['hasCourseInstance']);
        $this->assertSame('CourseInstance', $array['hasCourseInstance'][0]['@type']);
        $this->assertSame('onsite', $array['hasCourseInstance'][0]['courseMode']);
        $this->assertSame('Place', $array['hasCourseInstance'][0]['location']['@type']);
        $this->assertSame('online', $array['hasCourseInstance'][1]['courseMode']);
        $this->assertSame('VirtualLocation', $array['hasCourseInstance'][1]['location']['@type']);

        // Pricing
        $this->assertSame('1500', $array['hasCourseInstance'][0]['offers']['price']);
        $this->assertSame('990', $array['hasCourseInstance'][1]['offers']['price']);

        // Rating
        $this->assertSame('AggregateRating', $array['aggregateRating']['@type']);
        $this->assertSame(4.7, $array['aggregateRating']['ratingValue']);
        $this->assertSame(128, $array['aggregateRating']['ratingCount']);
    }

    public function testSimpleCourse(): void
    {
        $course = Schema::course()
            ->name('Initiation SEO')
            ->description('Les bases du référencement naturel')
            ->provider(Schema::organization()->name('Acme'))
            ->offers(Schema::offer()->price('0')->priceCurrency('EUR'));

        $array = $course->toArray();

        $this->assertSame('Course', $array['@type']);
        $this->assertSame('Initiation SEO', $array['name']);
        $this->assertSame('Offer', $array['offers']['@type']);
        $this->assertSame('0', $array['offers']['price']);
    }

    public function testCourseRendersValidJsonLd(): void
    {
        $manager = new Schema\SchemaManager();
        $manager->add(
            Schema::course()
                ->name('Formation Test')
                ->provider(Schema::organization()->name('Test Org'))
        );

        $output = $manager->render();

        $this->assertStringContainsString('"@type": "Course"', $output);
        $this->assertStringContainsString('"@context": "https://schema.org"', $output);
    }
}
