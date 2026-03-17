<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class CourseInstance extends SchemaType
{
    public function __construct()
    {
        parent::__construct('CourseInstance');
    }

    /** @return $this */
    public function courseMode(string $mode): static
    {
        return $this->set('courseMode', $mode);
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
    public function instructor(SchemaType $instructor): static
    {
        return $this->set('instructor', $instructor);
    }

    /** @return $this */
    public function inLanguage(string $language): static
    {
        return $this->set('inLanguage', $language);
    }

    
    public function offers(SchemaType|array $offers): static
    {
        return $this->set('offers', $offers);
    }

    /** @return $this */
    public function courseSchedule(SchemaType $schedule): static
    {
        return $this->set('courseSchedule', $schedule);
    }
}
