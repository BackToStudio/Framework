<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo;

use BackTo\Framework\Bundle\Seo\Schema\SchemaRef;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;
use BackTo\Framework\Bundle\Seo\Schema\Type\AggregateRating;
use BackTo\Framework\Bundle\Seo\Schema\Type\Answer;
use BackTo\Framework\Bundle\Seo\Schema\Type\Article;
use BackTo\Framework\Bundle\Seo\Schema\Type\Brand;
use BackTo\Framework\Bundle\Seo\Schema\Type\BreadcrumbList;
use BackTo\Framework\Bundle\Seo\Schema\Type\Clip;
use BackTo\Framework\Bundle\Seo\Schema\Type\Course;
use BackTo\Framework\Bundle\Seo\Schema\Type\CourseInstance;
use BackTo\Framework\Bundle\Seo\Schema\Type\Event;
use BackTo\Framework\Bundle\Seo\Schema\Type\FAQPage;
use BackTo\Framework\Bundle\Seo\Schema\Type\GeoCoordinates;
use BackTo\Framework\Bundle\Seo\Schema\Type\ImageObject;
use BackTo\Framework\Bundle\Seo\Schema\Type\JobPosting;
use BackTo\Framework\Bundle\Seo\Schema\Type\ListItem;
use BackTo\Framework\Bundle\Seo\Schema\Type\LocalBusiness;
use BackTo\Framework\Bundle\Seo\Schema\Type\MerchantReturnPolicy;
use BackTo\Framework\Bundle\Seo\Schema\Type\MonetaryAmount;
use BackTo\Framework\Bundle\Seo\Schema\Type\Offer;
use BackTo\Framework\Bundle\Seo\Schema\Type\OfferShippingDetails;
use BackTo\Framework\Bundle\Seo\Schema\Type\Organization;
use BackTo\Framework\Bundle\Seo\Schema\Type\Person;
use BackTo\Framework\Bundle\Seo\Schema\Type\Place;
use BackTo\Framework\Bundle\Seo\Schema\Type\PostalAddress;
use BackTo\Framework\Bundle\Seo\Schema\Type\Product;
use BackTo\Framework\Bundle\Seo\Schema\Type\Question;
use BackTo\Framework\Bundle\Seo\Schema\Type\Rating;
use BackTo\Framework\Bundle\Seo\Schema\Type\Review;
use BackTo\Framework\Bundle\Seo\Schema\Type\SearchAction;
use BackTo\Framework\Bundle\Seo\Schema\Type\VideoObject;
use BackTo\Framework\Bundle\Seo\Schema\Type\VirtualLocation;
use BackTo\Framework\Bundle\Seo\Schema\Type\WebSite;

/**
 * Static factory for creating schema.org types.
 *
 * Usage:
 *   Schema::organization()->name('Acme')->url('https://acme.com');
 *   Schema::article()->headline('Title')->author(Schema::person()->name('John'));
 */
final class Schema
{
    public static function organization(): Organization
    {
        return new Organization();
    }

    public static function localBusiness(): LocalBusiness
    {
        return new LocalBusiness();
    }

    public static function article(): Article
    {
        return new Article();
    }

    public static function breadcrumbList(): BreadcrumbList
    {
        return new BreadcrumbList();
    }

    public static function listItem(): ListItem
    {
        return new ListItem();
    }

    public static function person(): Person
    {
        return new Person();
    }

    public static function postalAddress(): PostalAddress
    {
        return new PostalAddress();
    }

    public static function webSite(): WebSite
    {
        return new WebSite();
    }

    public static function imageObject(): ImageObject
    {
        return new ImageObject();
    }

    public static function searchAction(): SearchAction
    {
        return new SearchAction();
    }

    public static function event(): Event
    {
        return new Event();
    }

    public static function course(): Course
    {
        return new Course();
    }

    public static function courseInstance(): CourseInstance
    {
        return new CourseInstance();
    }

    public static function offer(): Offer
    {
        return new Offer();
    }

    public static function place(): Place
    {
        return new Place();
    }

    public static function virtualLocation(): VirtualLocation
    {
        return new VirtualLocation();
    }

    public static function geoCoordinates(): GeoCoordinates
    {
        return new GeoCoordinates();
    }

    public static function aggregateRating(): AggregateRating
    {
        return new AggregateRating();
    }

    public static function product(): Product
    {
        return new Product();
    }

    public static function brand(): Brand
    {
        return new Brand();
    }

    public static function offerShippingDetails(): OfferShippingDetails
    {
        return new OfferShippingDetails();
    }

    public static function merchantReturnPolicy(): MerchantReturnPolicy
    {
        return new MerchantReturnPolicy();
    }

    public static function monetaryAmount(): MonetaryAmount
    {
        return new MonetaryAmount();
    }

    public static function review(): Review
    {
        return new Review();
    }

    public static function rating(): Rating
    {
        return new Rating();
    }

    public static function videoObject(): VideoObject
    {
        return new VideoObject();
    }

    public static function clip(): Clip
    {
        return new Clip();
    }

    public static function faqPage(): FAQPage
    {
        return new FAQPage();
    }

    public static function question(): Question
    {
        return new Question();
    }

    public static function answer(): Answer
    {
        return new Answer();
    }

    public static function jobPosting(): JobPosting
    {
        return new JobPosting();
    }

    /**
     * Create a generic schema type by name.
     */
    public static function type(string $type): SchemaType
    {
        return new SchemaType($type);
    }

    /**
     * Create a reference to another schema node by @id.
     *
     * Usage: Schema::ref('#organization') produces {"@id": "#organization"}
     */
    public static function ref(string $id): SchemaRef
    {
        return new SchemaRef($id);
    }
}
