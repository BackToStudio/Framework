<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Type;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class VideoObject extends SchemaType
{
    public function __construct()
    {
        parent::__construct('VideoObject');
    }

    protected function getRequiredProperties(): array
    {
        return ['name', 'thumbnailUrl', 'uploadDate'];
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
    public function thumbnailUrl(string|array $url): static
    {
        return $this->set('thumbnailUrl', $url);
    }

    /** @return $this */
    public function uploadDate(string $date): static
    {
        return $this->set('uploadDate', $date);
    }

    /** @return $this */
    public function duration(string $duration): static
    {
        return $this->set('duration', $duration);
    }

    /** @return $this */
    public function contentUrl(string $url): static
    {
        return $this->set('contentUrl', $url);
    }

    /** @return $this */
    public function embedUrl(string $url): static
    {
        return $this->set('embedUrl', $url);
    }

    /** @return $this */
    public function expires(string $date): static
    {
        return $this->set('expires', $date);
    }

    /** @return $this */
    public function interactionStatistic(SchemaType $stat): static
    {
        return $this->set('interactionStatistic', $stat);
    }

    /**
     * @param SchemaType|SchemaType[] $parts Clip instances for key moments
     * @return $this
     */
    public function hasPart(SchemaType|array $parts): static
    {
        return $this->set('hasPart', $parts);
    }

    /** @return $this */
    public function publication(SchemaType $event): static
    {
        return $this->set('publication', $event);
    }

    /** @return $this */
    public function regionsAllowed(string|array $regions): static
    {
        return $this->set('regionsAllowed', $regions);
    }
}
