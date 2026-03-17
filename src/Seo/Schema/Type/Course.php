<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

final class Course extends SchemaType
{
    public function __construct()
    {
        parent::__construct('Course');
    }

    protected function getRequiredProperties(): array
    {
        return ['name', 'description', 'provider'];
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
    public function provider(SchemaType $provider): static
    {
        return $this->set('provider', $provider);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @return $this */
    public function image(string|SchemaType $image): static
    {
        return $this->set('image', $image);
    }

    /** @return $this */
    public function inLanguage(string $language): static
    {
        return $this->set('inLanguage', $language);
    }

    /**
     * @param SchemaType|SchemaType[] $instances CourseInstance objects
     * @return $this
     */
    public function hasCourseInstance(SchemaType|array $instances): static
    {
        return $this->set('hasCourseInstance', $instances);
    }

    
    public function offers(SchemaType|array $offers): static
    {
        return $this->set('offers', $offers);
    }

    /** @return $this */
    public function courseCode(string $code): static
    {
        return $this->set('courseCode', $code);
    }

    /** @return $this */
    public function educationalLevel(string $level): static
    {
        return $this->set('educationalLevel', $level);
    }

    
    public function coursePrerequisites(array $prerequisites): static
    {
        return $this->set('coursePrerequisites', $prerequisites);
    }

    /** @return $this */
    public function numberOfCredits(int $credits): static
    {
        return $this->set('numberOfCredits', $credits);
    }

    /** @return $this */
    public function timeRequired(string $duration): static
    {
        return $this->set('timeRequired', $duration);
    }

    /** @return $this */
    public function aggregateRating(SchemaType $rating): static
    {
        return $this->set('aggregateRating', $rating);
    }
}
