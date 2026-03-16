<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo;

use BackTo\Framework\Seo\Schema\SchemaType;
use BackTo\Framework\Seo\Schema\Type\AggregateRating;
use BackTo\Framework\Seo\Schema\Type\Article;
use BackTo\Framework\Seo\Schema\Type\BreadcrumbList;
use BackTo\Framework\Seo\Schema\Type\Course;
use BackTo\Framework\Seo\Schema\Type\CourseInstance;
use BackTo\Framework\Seo\Schema\Type\Event;
use BackTo\Framework\Seo\Schema\Type\GeoCoordinates;
use BackTo\Framework\Seo\Schema\Type\ImageObject;
use BackTo\Framework\Seo\Schema\Type\ListItem;
use BackTo\Framework\Seo\Schema\Type\LocalBusiness;
use BackTo\Framework\Seo\Schema\Type\Offer;
use BackTo\Framework\Seo\Schema\Type\Organization;
use BackTo\Framework\Seo\Schema\Type\Person;
use BackTo\Framework\Seo\Schema\Type\Place;
use BackTo\Framework\Seo\Schema\Type\PostalAddress;
use BackTo\Framework\Seo\Schema\Type\SearchAction;
use BackTo\Framework\Seo\Schema\Type\VirtualLocation;
use BackTo\Framework\Seo\Schema\Type\WebSite;

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

    /**
     * Create a generic schema type by name.
     */
    public static function type(string $type): SchemaType
    {
        return new SchemaType($type);
    }
}
