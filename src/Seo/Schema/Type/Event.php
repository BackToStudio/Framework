<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

class Event extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Event');
    }

    /** @return $this */
    public function name(string $name): static
    {
        return $this->set('name', $name);
    }

    /** @return $this */
    public function description(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @return $this */
    public function startDate(string $date): static
    {
        return $this->set('startDate', $date);
    }

    /** @return $this */
    public function endDate(string $date): static
    {
        return $this->set('endDate', $date);
    }

    /** @return $this */
    public function location(SchemaType|string $location): static
    {
        return $this->set('location', $location);
    }

    /** @return $this */
    public function organizer(SchemaType $organizer): static
    {
        return $this->set('organizer', $organizer);
    }

    /** @return $this */
    public function performer(SchemaType $performer): static
    {
        return $this->set('performer', $performer);
    }

    /** @return $this */
    public function image(string|SchemaType $image): static
    {
        return $this->set('image', $image);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    /**
     * @param SchemaType|SchemaType[] $offers
     * @return $this
     */
    public function offers(SchemaType|array $offers): static
    {
        return $this->set('offers', $offers);
    }

    /** @return $this */
    public function eventStatus(string $status): static
    {
        return $this->set('eventStatus', $status);
    }

    /** @return $this */
    public function eventAttendanceMode(string $mode): static
    {
        return $this->set('eventAttendanceMode', $mode);
    }

    /** @return $this */
    public function previousStartDate(string $date): static
    {
        return $this->set('previousStartDate', $date);
    }

    /** @return $this */
    public function doorTime(string $time): static
    {
        return $this->set('doorTime', $time);
    }

    /** @return $this */
    public function inLanguage(string $language): static
    {
        return $this->set('inLanguage', $language);
    }

    /** @return $this */
    public function isAccessibleForFree(bool $free): static
    {
        return $this->set('isAccessibleForFree', $free);
    }
}
