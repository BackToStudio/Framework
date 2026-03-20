<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;
use BackTo\Framework\Bundle\Seo\Schema\Type\Brand;
use BackTo\Framework\Bundle\Seo\Schema\Type\MerchantReturnPolicy;
use BackTo\Framework\Bundle\Seo\Schema\Type\MonetaryAmount;
use BackTo\Framework\Bundle\Seo\Schema\Type\OfferShippingDetails;
use BackTo\Framework\Bundle\Seo\Schema\Type\Product;
use PHPUnit\Framework\TestCase;

class SchemaProductTest extends TestCase
{
    public function testProductFactory(): void
    {
        $product = Schema::product();
        $this->assertInstanceOf(Product::class, $product);
        $this->assertSame('Product', $product->getType());
    }

    public function testBrandFactory(): void
    {
        $brand = Schema::brand();
        $this->assertInstanceOf(Brand::class, $brand);
        $this->assertSame('Brand', $brand->getType());
    }

    public function testOfferShippingDetailsFactory(): void
    {
        $details = Schema::offerShippingDetails();
        $this->assertInstanceOf(OfferShippingDetails::class, $details);
        $this->assertSame('OfferShippingDetails', $details->getType());
    }

    public function testMerchantReturnPolicyFactory(): void
    {
        $policy = Schema::merchantReturnPolicy();
        $this->assertInstanceOf(MerchantReturnPolicy::class, $policy);
        $this->assertSame('MerchantReturnPolicy', $policy->getType());
    }

    public function testMonetaryAmountFactory(): void
    {
        $amount = Schema::monetaryAmount();
        $this->assertInstanceOf(MonetaryAmount::class, $amount);
        $this->assertSame('MonetaryAmount', $amount->getType());
    }

    public function testProductName(): void
    {
        $array = Schema::product()->name('Widget Pro')->toArray();
        $this->assertSame('Widget Pro', $array['name']);
    }

    public function testProductDescription(): void
    {
        $array = Schema::product()->description('Un widget performant')->toArray();
        $this->assertSame('Un widget performant', $array['description']);
    }

    public function testProductImage(): void
    {
        $array = Schema::product()->image('https://example.com/img.jpg')->toArray();
        $this->assertSame('https://example.com/img.jpg', $array['image']);
    }

    public function testProductImageArray(): void
    {
        $images = ['https://example.com/1.jpg', 'https://example.com/2.jpg'];
        $array = Schema::product()->image($images)->toArray();
        $this->assertSame($images, $array['image']);
    }

    public function testProductUrl(): void
    {
        $array = Schema::product()->url('https://example.com/product')->toArray();
        $this->assertSame('https://example.com/product', $array['url']);
    }

    public function testProductSku(): void
    {
        $array = Schema::product()->sku('WDG-001')->toArray();
        $this->assertSame('WDG-001', $array['sku']);
    }

    public function testProductGtin(): void
    {
        $array = Schema::product()->gtin('0123456789012')->toArray();
        $this->assertSame('0123456789012', $array['gtin']);
    }

    public function testProductGtin13(): void
    {
        $array = Schema::product()->gtin13('0123456789012')->toArray();
        $this->assertSame('0123456789012', $array['gtin13']);
    }

    public function testProductMpn(): void
    {
        $array = Schema::product()->mpn('MPN-123')->toArray();
        $this->assertSame('MPN-123', $array['mpn']);
    }

    public function testProductColor(): void
    {
        $array = Schema::product()->color('Rouge')->toArray();
        $this->assertSame('Rouge', $array['color']);
    }

    public function testProductSize(): void
    {
        $array = Schema::product()->size('XL')->toArray();
        $this->assertSame('XL', $array['size']);
    }

    public function testProductMaterial(): void
    {
        $array = Schema::product()->material('Coton bio')->toArray();
        $this->assertSame('Coton bio', $array['material']);
    }

    public function testProductCategory(): void
    {
        $array = Schema::product()->category('Vêtements')->toArray();
        $this->assertSame('Vêtements', $array['category']);
    }

    public function testProductBrand(): void
    {
        $array = Schema::product()
            ->brand(Schema::brand()->name('Acme'))
            ->toArray();

        $this->assertSame('Brand', $array['brand']['@type']);
        $this->assertSame('Acme', $array['brand']['name']);
    }

    public function testBrandProperties(): void
    {
        $array = Schema::brand()
            ->name('Acme')
            ->url('https://acme.com')
            ->logo('https://acme.com/logo.png')
            ->toArray();

        $this->assertSame('Acme', $array['name']);
        $this->assertSame('https://acme.com', $array['url']);
        $this->assertSame('https://acme.com/logo.png', $array['logo']);
    }

    public function testProductSingleOffer(): void
    {
        $array = Schema::product()
            ->offers(Schema::offer()->price('29.99')->priceCurrency('EUR'))
            ->toArray();

        $this->assertSame('Offer', $array['offers']['@type']);
        $this->assertSame('29.99', $array['offers']['price']);
        $this->assertSame('EUR', $array['offers']['priceCurrency']);
    }

    public function testProductMultipleOffers(): void
    {
        $array = Schema::product()
            ->offers([
                Schema::offer()->price('29.99')->priceCurrency('EUR'),
                Schema::offer()->price('39.99')->priceCurrency('EUR'),
            ])
            ->toArray();

        $this->assertCount(2, $array['offers']);
        $this->assertSame('29.99', $array['offers'][0]['price']);
        $this->assertSame('39.99', $array['offers'][1]['price']);
    }

    public function testProductAggregateRating(): void
    {
        $array = Schema::product()
            ->aggregateRating(Schema::aggregateRating()->ratingValue(4.5)->reviewCount(120))
            ->toArray();

        $this->assertSame('AggregateRating', $array['aggregateRating']['@type']);
        $this->assertSame(4.5, $array['aggregateRating']['ratingValue']);
        $this->assertSame(120, $array['aggregateRating']['reviewCount']);
    }

    public function testProductReview(): void
    {
        $array = Schema::product()
            ->review(Schema::review()->author(Schema::person()->name('Jean'))->reviewBody('Super produit'))
            ->toArray();

        $this->assertSame('Review', $array['review']['@type']);
        $this->assertSame('Super produit', $array['review']['reviewBody']);
    }

    public function testOfferShippingDetailsProperties(): void
    {
        $array = Schema::offerShippingDetails()
            ->shippingRate(Schema::monetaryAmount()->currency('EUR')->value(5.99))
            ->shippingDestination(Schema::type('DefinedRegion')->set('addressCountry', 'FR'))
            ->deliveryTime(Schema::type('ShippingDeliveryTime')->set('handlingTime', Schema::type('QuantitativeValue')->set('minValue', 0)->set('maxValue', 1)))
            ->toArray();

        $this->assertSame('OfferShippingDetails', $array['@type']);
        $this->assertSame('MonetaryAmount', $array['shippingRate']['@type']);
        $this->assertSame('EUR', $array['shippingRate']['currency']);
        $this->assertSame(5.99, $array['shippingRate']['value']);
    }

    public function testMerchantReturnPolicyProperties(): void
    {
        $array = Schema::merchantReturnPolicy()
            ->applicableCountry('FR')
            ->returnPolicyCategory('https://schema.org/MerchantReturnFiniteReturnWindow')
            ->merchantReturnDays(30)
            ->returnMethod('https://schema.org/ReturnByMail')
            ->returnFees('https://schema.org/FreeReturn')
            ->toArray();

        $this->assertSame('MerchantReturnPolicy', $array['@type']);
        $this->assertSame('FR', $array['applicableCountry']);
        $this->assertSame(30, $array['merchantReturnDays']);
        $this->assertSame('https://schema.org/FreeReturn', $array['returnFees']);
    }

    public function testMonetaryAmountProperties(): void
    {
        $array = Schema::monetaryAmount()
            ->currency('EUR')
            ->value(1500)
            ->minValue(1200)
            ->maxValue(1800)
            ->toArray();

        $this->assertSame('MonetaryAmount', $array['@type']);
        $this->assertSame('EUR', $array['currency']);
        $this->assertSame(1500, $array['value']);
        $this->assertSame(1200, $array['minValue']);
        $this->assertSame(1800, $array['maxValue']);
    }

    public function testFullProductComposition(): void
    {
        $product = Schema::product()
            ->name('T-Shirt Acme')
            ->description('T-shirt 100% coton bio')
            ->image(['https://example.com/front.jpg', 'https://example.com/back.jpg'])
            ->url('https://example.com/t-shirt-acme')
            ->sku('TSH-001')
            ->gtin13('3760000000001')
            ->brand(Schema::brand()->name('Acme'))
            ->color('Bleu')
            ->size('M')
            ->material('Coton bio')
            ->category('Vêtements')
            ->offers(
                Schema::offer()
                    ->price('29.99')
                    ->priceCurrency('EUR')
                    ->availability('https://schema.org/InStock')
                    ->url('https://example.com/t-shirt-acme')
            )
            ->aggregateRating(
                Schema::aggregateRating()->ratingValue(4.8)->reviewCount(256)->ratingCount(300)
            )
            ->review([
                Schema::review()
                    ->author(Schema::person()->name('Marie'))
                    ->reviewRating(Schema::rating()->ratingValue(5)->bestRating(5))
                    ->reviewBody('Excellent produit !')
                    ->datePublished('2026-03-10'),
            ]);

        $array = $product->toArray();

        $this->assertSame('Product', $array['@type']);
        $this->assertSame('T-Shirt Acme', $array['name']);
        $this->assertSame('TSH-001', $array['sku']);
        $this->assertSame('Brand', $array['brand']['@type']);
        $this->assertSame('Bleu', $array['color']);
        $this->assertSame('Offer', $array['offers']['@type']);
        $this->assertSame('29.99', $array['offers']['price']);
        $this->assertSame(4.8, $array['aggregateRating']['ratingValue']);
        $this->assertCount(1, $array['review']);
        $this->assertSame('Review', $array['review'][0]['@type']);
        $this->assertSame('Rating', $array['review'][0]['reviewRating']['@type']);
    }

    public function testProductRendersValidJsonLd(): void
    {
        $manager = new SchemaManager();
        $manager->add(
            Schema::product()
                ->name('Widget')
                ->offers(Schema::offer()->price('9.99')->priceCurrency('EUR'))
        );

        $output = $manager->render();

        preg_match('/<script type="application\/ld\+json">\n(.+)\n<\/script>/s', $output, $matches);
        $decoded = json_decode($matches[1], true);

        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('Product', $decoded['@type']);
        $this->assertSame('Widget', $decoded['name']);
        $this->assertSame('Offer', $decoded['offers']['@type']);
    }
}
