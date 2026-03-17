<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaManager;
use BackTo\Framework\Seo\Schema\SchemaType;
use PHPUnit\Framework\TestCase;

class SchemaValidationTest extends TestCase
{
    // --- SchemaType base ---

    public function testGenericSchemaTypeHasNoRequiredProperties(): void
    {
        $schema = new SchemaType('Thing');
        $this->assertSame([], $schema->validate());
        $this->assertTrue($schema->isValid());
    }

    // --- Product ---

    public function testProductValidWithAllRequired(): void
    {
        $product = Schema::product()
            ->name('Widget')
            ->image('https://example.com/img.jpg')
            ->offers(Schema::offer()->price('10')->priceCurrency('EUR'));

        $this->assertTrue($product->isValid());
        $this->assertSame([], $product->validate());
    }

    public function testProductInvalidMissingAll(): void
    {
        $product = Schema::product();
        $missing = $product->validate();

        $this->assertFalse($product->isValid());
        $this->assertContains('name', $missing);
        $this->assertContains('image', $missing);
        $this->assertContains('offers', $missing);
    }

    public function testProductInvalidMissingPartial(): void
    {
        $product = Schema::product()->name('Widget');
        $missing = $product->validate();

        $this->assertNotContains('name', $missing);
        $this->assertContains('image', $missing);
        $this->assertContains('offers', $missing);
    }

    // --- Article ---

    public function testArticleValidWithAllRequired(): void
    {
        $article = Schema::article()
            ->headline('Title')
            ->author(Schema::person()->name('John'))
            ->datePublished('2026-03-15');

        $this->assertTrue($article->isValid());
    }

    public function testArticleInvalidMissingRequired(): void
    {
        $article = Schema::article()->headline('Title');
        $missing = $article->validate();

        $this->assertFalse($article->isValid());
        $this->assertContains('author', $missing);
        $this->assertContains('datePublished', $missing);
        $this->assertNotContains('headline', $missing);
    }

    // --- Event ---

    public function testEventValidWithAllRequired(): void
    {
        $event = Schema::event()
            ->name('Concert')
            ->startDate('2026-06-15')
            ->location(Schema::place()->name('Salle X'));

        $this->assertTrue($event->isValid());
    }

    public function testEventInvalidMissingRequired(): void
    {
        $event = Schema::event()->name('Concert');
        $missing = $event->validate();

        $this->assertContains('startDate', $missing);
        $this->assertContains('location', $missing);
    }

    // --- Course ---

    public function testCourseValidWithAllRequired(): void
    {
        $course = Schema::course()
            ->name('Formation PHP')
            ->description('Apprenez PHP')
            ->provider(Schema::organization()->name('Acme'));

        $this->assertTrue($course->isValid());
    }

    public function testCourseInvalidMissingRequired(): void
    {
        $course = Schema::course()->name('Formation PHP');
        $missing = $course->validate();

        $this->assertContains('description', $missing);
        $this->assertContains('provider', $missing);
    }

    // --- JobPosting ---

    public function testJobPostingValidWithAllRequired(): void
    {
        $job = Schema::jobPosting()
            ->title('Dev PHP')
            ->description('Poste de dev')
            ->datePosted('2026-03-15')
            ->hiringOrganization(Schema::organization()->name('Acme'));

        $this->assertTrue($job->isValid());
    }

    public function testJobPostingInvalidMissingRequired(): void
    {
        $job = Schema::jobPosting()->title('Dev PHP');
        $missing = $job->validate();

        $this->assertContains('description', $missing);
        $this->assertContains('datePosted', $missing);
        $this->assertContains('hiringOrganization', $missing);
    }

    // --- VideoObject ---

    public function testVideoObjectValidWithAllRequired(): void
    {
        $video = Schema::videoObject()
            ->name('Ma vidéo')
            ->thumbnailUrl('https://example.com/thumb.jpg')
            ->uploadDate('2026-03-15');

        $this->assertTrue($video->isValid());
    }

    public function testVideoObjectInvalidMissingRequired(): void
    {
        $video = Schema::videoObject()->name('Ma vidéo');
        $missing = $video->validate();

        $this->assertContains('thumbnailUrl', $missing);
        $this->assertContains('uploadDate', $missing);
    }

    // --- FAQPage ---

    public function testFAQPageValidWithAllRequired(): void
    {
        $faq = Schema::faqPage()->mainEntity([
            Schema::question()->name('Q?')->acceptedAnswer(Schema::answer()->text('A.')),
        ]);

        $this->assertTrue($faq->isValid());
    }

    public function testFAQPageInvalidMissingRequired(): void
    {
        $faq = Schema::faqPage();
        $missing = $faq->validate();

        $this->assertContains('mainEntity', $missing);
    }

    // --- Review ---

    public function testReviewValidWithAllRequired(): void
    {
        $review = Schema::review()
            ->itemReviewed(Schema::product()->name('Widget'))
            ->reviewRating(Schema::rating()->ratingValue(5))
            ->author(Schema::person()->name('Jean'));

        $this->assertTrue($review->isValid());
    }

    public function testReviewInvalidMissingRequired(): void
    {
        $review = Schema::review()->reviewBody('Super');
        $missing = $review->validate();

        $this->assertContains('itemReviewed', $missing);
        $this->assertContains('reviewRating', $missing);
        $this->assertContains('author', $missing);
    }

    // --- LocalBusiness ---

    public function testLocalBusinessValidWithAllRequired(): void
    {
        $biz = Schema::localBusiness()
            ->name('Chez Paul')
            ->address(Schema::postalAddress()->addressLocality('Paris'));

        $this->assertTrue($biz->isValid());
    }

    public function testLocalBusinessInvalidMissingRequired(): void
    {
        $biz = Schema::localBusiness();
        $missing = $biz->validate();

        $this->assertContains('name', $missing);
        $this->assertContains('address', $missing);
    }

    // --- Offer ---

    public function testOfferValidWithAllRequired(): void
    {
        $offer = Schema::offer()->price('10')->priceCurrency('EUR');
        $this->assertTrue($offer->isValid());
    }

    public function testOfferInvalidMissingRequired(): void
    {
        $offer = Schema::offer()->price('10');
        $missing = $offer->validate();

        $this->assertContains('priceCurrency', $missing);
    }

    // --- SchemaManager.validate() ---

    public function testSchemaManagerValidateReturnsEmptyWhenAllValid(): void
    {
        $manager = new SchemaManager();
        $manager->add(Schema::article()->headline('T')->author('A')->datePublished('2026-01-01'));
        $manager->add(Schema::faqPage()->mainEntity([Schema::question()->name('Q')]));

        $this->assertSame([], $manager->validate());
    }

    public function testSchemaManagerValidateReturnsErrorsForInvalid(): void
    {
        $manager = new SchemaManager();
        $manager->add(Schema::product()->name('Widget')); // missing image, offers
        $manager->add(Schema::event()); // missing name, startDate, location

        $errors = $manager->validate();

        $this->assertArrayHasKey('Product', $errors);
        $this->assertContains('image', $errors['Product']);
        $this->assertContains('offers', $errors['Product']);

        $this->assertArrayHasKey('Event', $errors);
        $this->assertContains('name', $errors['Event']);
        $this->assertContains('startDate', $errors['Event']);
    }

    public function testSchemaManagerValidateSkipsValidSchemas(): void
    {
        $manager = new SchemaManager();
        $manager->add(Schema::product()->name('W')->image('i.jpg')->offers(Schema::offer()->price('1')->priceCurrency('EUR')));
        $manager->add(Schema::event()); // invalid

        $errors = $manager->validate();

        $this->assertArrayNotHasKey('Product', $errors);
        $this->assertArrayHasKey('Event', $errors);
    }
}
