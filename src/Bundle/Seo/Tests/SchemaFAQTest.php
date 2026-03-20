<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;
use BackTo\Framework\Bundle\Seo\Schema\Type\Answer;
use BackTo\Framework\Bundle\Seo\Schema\Type\FAQPage;
use BackTo\Framework\Bundle\Seo\Schema\Type\Question;
use PHPUnit\Framework\TestCase;

class SchemaFAQTest extends TestCase
{
    public function testFAQPageFactory(): void
    {
        $faq = Schema::faqPage();
        $this->assertInstanceOf(FAQPage::class, $faq);
        $this->assertSame('FAQPage', $faq->getType());
    }

    public function testQuestionFactory(): void
    {
        $question = Schema::question();
        $this->assertInstanceOf(Question::class, $question);
        $this->assertSame('Question', $question->getType());
    }

    public function testAnswerFactory(): void
    {
        $answer = Schema::answer();
        $this->assertInstanceOf(Answer::class, $answer);
        $this->assertSame('Answer', $answer->getType());
    }

    public function testAnswerText(): void
    {
        $array = Schema::answer()->text('Voici la réponse.')->toArray();
        $this->assertSame('Voici la réponse.', $array['text']);
    }

    public function testQuestionName(): void
    {
        $array = Schema::question()->name('Comment ça marche ?')->toArray();
        $this->assertSame('Comment ça marche ?', $array['name']);
    }

    public function testQuestionAcceptedAnswer(): void
    {
        $array = Schema::question()
            ->acceptedAnswer(Schema::answer()->text('Comme ceci.'))
            ->toArray();

        $this->assertSame('Answer', $array['acceptedAnswer']['@type']);
        $this->assertSame('Comme ceci.', $array['acceptedAnswer']['text']);
    }

    public function testQuestionSuggestedAnswer(): void
    {
        $array = Schema::question()
            ->suggestedAnswer([
                Schema::answer()->text('Option A'),
                Schema::answer()->text('Option B'),
            ])
            ->toArray();

        $this->assertCount(2, $array['suggestedAnswer']);
        $this->assertSame('Answer', $array['suggestedAnswer'][0]['@type']);
        $this->assertSame('Option A', $array['suggestedAnswer'][0]['text']);
        $this->assertSame('Option B', $array['suggestedAnswer'][1]['text']);
    }

    public function testFAQPageMainEntity(): void
    {
        $array = Schema::faqPage()
            ->mainEntity([
                Schema::question()->name('Q1')->acceptedAnswer(Schema::answer()->text('R1')),
                Schema::question()->name('Q2')->acceptedAnswer(Schema::answer()->text('R2')),
            ])
            ->toArray();

        $this->assertSame('FAQPage', $array['@type']);
        $this->assertCount(2, $array['mainEntity']);
        $this->assertSame('Question', $array['mainEntity'][0]['@type']);
        $this->assertSame('Q1', $array['mainEntity'][0]['name']);
        $this->assertSame('Answer', $array['mainEntity'][0]['acceptedAnswer']['@type']);
        $this->assertSame('R1', $array['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function testFullFAQComposition(): void
    {
        $faq = Schema::faqPage()->mainEntity([
            Schema::question()
                ->name('Quels sont vos horaires ?')
                ->acceptedAnswer(Schema::answer()->text('Nous sommes ouverts du lundi au vendredi de 9h à 18h.')),
            Schema::question()
                ->name('Livrez-vous à l\'international ?')
                ->acceptedAnswer(Schema::answer()->text('Oui, nous livrons dans toute l\'Europe.')),
            Schema::question()
                ->name('Comment retourner un produit ?')
                ->acceptedAnswer(Schema::answer()->text('Vous avez 30 jours pour retourner un produit. Contactez notre service client.')),
        ]);

        $array = $faq->toArray();

        $this->assertSame('FAQPage', $array['@type']);
        $this->assertCount(3, $array['mainEntity']);

        // Verify each Q&A pair
        $this->assertSame('Quels sont vos horaires ?', $array['mainEntity'][0]['name']);
        $this->assertStringContainsString('lundi au vendredi', $array['mainEntity'][0]['acceptedAnswer']['text']);

        $this->assertSame('Livrez-vous à l\'international ?', $array['mainEntity'][1]['name']);
        $this->assertStringContainsString('Europe', $array['mainEntity'][1]['acceptedAnswer']['text']);

        $this->assertSame('Comment retourner un produit ?', $array['mainEntity'][2]['name']);
        $this->assertStringContainsString('30 jours', $array['mainEntity'][2]['acceptedAnswer']['text']);
    }

    public function testFAQRendersValidJsonLd(): void
    {
        $manager = new SchemaManager();
        $manager->add(
            Schema::faqPage()->mainEntity([
                Schema::question()->name('Q?')->acceptedAnswer(Schema::answer()->text('A.')),
            ])
        );

        $output = $manager->render();

        preg_match('/<script type="application\/ld\+json">\n(.+)\n<\/script>/s', $output, $matches);
        $decoded = json_decode($matches[1], true);

        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('FAQPage', $decoded['@type']);
        $this->assertCount(1, $decoded['mainEntity']);
        $this->assertSame('Question', $decoded['mainEntity'][0]['@type']);
        $this->assertSame('Q?', $decoded['mainEntity'][0]['name']);
        $this->assertSame('Answer', $decoded['mainEntity'][0]['acceptedAnswer']['@type']);
        $this->assertSame('A.', $decoded['mainEntity'][0]['acceptedAnswer']['text']);
    }
}
