<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\Type\Event;
use BackTo\Framework\Seo\Schema\Type\Offer;
use BackTo\Framework\Seo\Schema\Type\Place;
use BackTo\Framework\Seo\Schema\Type\VirtualLocation;
use PHPUnit\Framework\TestCase;

class SchemaEventTest extends TestCase
{
    public function testEventFactory(): void
    {
        $event = Schema::event();
        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame('Event', $event->getType());
    }

    public function testPhysicalEvent(): void
    {
        $event = Schema::event()
            ->name('Conférence WordPress')
            ->description('Une journée dédiée à WordPress')
            ->startDate('2026-06-15T09:00:00+02:00')
            ->endDate('2026-06-15T18:00:00+02:00')
            ->location(
                Schema::place()
                    ->name('Palais des Congrès')
                    ->address(
                        Schema::postalAddress()
                            ->streetAddress('2 Place de la Porte Maillot')
                            ->addressLocality('Paris')
                            ->postalCode('75017')
                            ->addressCountry('FR')
                    )
                    ->geo(Schema::geoCoordinates()->latitude(48.8789)->longitude(2.2831))
            )
            ->organizer(Schema::organization()->name('WP France'))
            ->image('https://example.com/event.jpg')
            ->url('https://example.com/events/wp-conf')
            ->offers(
                Schema::offer()
                    ->price('49.99')
                    ->priceCurrency('EUR')
                    ->availability('https://schema.org/InStock')
                    ->validFrom('2026-01-01')
                    ->url('https://example.com/events/wp-conf/tickets')
            )
            ->eventStatus('https://schema.org/EventScheduled')
            ->eventAttendanceMode('https://schema.org/OfflineEventAttendanceMode');

        $array = $event->toArray();

        $this->assertSame('Event', $array['@type']);
        $this->assertSame('Conférence WordPress', $array['name']);
        $this->assertSame('2026-06-15T09:00:00+02:00', $array['startDate']);
        $this->assertSame('Place', $array['location']['@type']);
        $this->assertSame('PostalAddress', $array['location']['address']['@type']);
        $this->assertSame('GeoCoordinates', $array['location']['geo']['@type']);
        $this->assertSame(48.8789, $array['location']['geo']['latitude']);
        $this->assertSame('Organization', $array['organizer']['@type']);
        $this->assertSame('Offer', $array['offers']['@type']);
        $this->assertSame('49.99', $array['offers']['price']);
    }

    public function testOnlineEvent(): void
    {
        $event = Schema::event()
            ->name('Webinaire SEO')
            ->startDate('2026-04-01T14:00:00+02:00')
            ->endDate('2026-04-01T15:30:00+02:00')
            ->location(Schema::virtualLocation()->url('https://zoom.us/j/123456'))
            ->eventAttendanceMode('https://schema.org/OnlineEventAttendanceMode')
            ->isAccessibleForFree(true);

        $array = $event->toArray();

        $this->assertSame('VirtualLocation', $array['location']['@type']);
        $this->assertSame('https://zoom.us/j/123456', $array['location']['url']);
        $this->assertTrue($array['isAccessibleForFree']);
    }

    public function testEventWithMultipleOffers(): void
    {
        $event = Schema::event()
            ->name('Festival')
            ->startDate('2026-07-01')
            ->offers([
                Schema::offer()->price('25')->priceCurrency('EUR')->category('Early Bird'),
                Schema::offer()->price('45')->priceCurrency('EUR')->category('Standard'),
                Schema::offer()->price('80')->priceCurrency('EUR')->category('VIP'),
            ]);

        $array = $event->toArray();

        $this->assertCount(3, $array['offers']);
        $this->assertSame('25', $array['offers'][0]['price']);
        $this->assertSame('VIP', $array['offers'][2]['category']);
    }

    public function testPlaceFactory(): void
    {
        $place = Schema::place();
        $this->assertInstanceOf(Place::class, $place);
        $this->assertSame('Place', $place->getType());
    }

    public function testVirtualLocationFactory(): void
    {
        $location = Schema::virtualLocation();
        $this->assertInstanceOf(VirtualLocation::class, $location);
        $this->assertSame('VirtualLocation', $location->getType());
    }

    public function testOfferFactory(): void
    {
        $offer = Schema::offer();
        $this->assertInstanceOf(Offer::class, $offer);
        $this->assertSame('Offer', $offer->getType());
    }

    public function testEventRendersValidJsonLd(): void
    {
        $manager = new Schema\SchemaManager();
        $manager->add(
            Schema::event()
                ->name('Test Event')
                ->startDate('2026-06-15')
        );

        $output = $manager->render();

        $this->assertStringContainsString('"@type": "Event"', $output);
        $this->assertStringContainsString('"name": "Test Event"', $output);
    }
}
