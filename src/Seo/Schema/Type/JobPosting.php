<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Type;

use BackTo\Framework\Seo\Schema\SchemaType;

class JobPosting extends SchemaType
{
    public function __construct()
    {
        parent::__construct('JobPosting');
    }

    protected function getRequiredProperties(): array
    {
        return ['title', 'description', 'datePosted', 'hiringOrganization'];
    }

    /** @return $this */
    public function title(string $title): static
    {
        return $this->set('title', $title);
    }

    /** @return $this */
    public function description(string $description): static
    {
        return $this->set('description', $description);
    }

    /** @return $this */
    public function datePosted(string $date): static
    {
        return $this->set('datePosted', $date);
    }

    /** @return $this */
    public function validThrough(string $date): static
    {
        return $this->set('validThrough', $date);
    }

    /** @return $this */
    public function hiringOrganization(SchemaType $organization): static
    {
        return $this->set('hiringOrganization', $organization);
    }

    /** @return $this */
    public function jobLocation(SchemaType $location): static
    {
        return $this->set('jobLocation', $location);
    }

    /** @return $this */
    public function baseSalary(SchemaType $salary): static
    {
        return $this->set('baseSalary', $salary);
    }

    /** @return $this */
    public function employmentType(string|array $type): static
    {
        return $this->set('employmentType', $type);
    }

    /** @return $this */
    public function jobLocationType(string $type): static
    {
        return $this->set('jobLocationType', $type);
    }

    /** @return $this */
    public function applicantLocationRequirements(SchemaType $requirements): static
    {
        return $this->set('applicantLocationRequirements', $requirements);
    }

    /** @return $this */
    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    /** @return $this */
    public function identifier(SchemaType|string $identifier): static
    {
        return $this->set('identifier', $identifier);
    }

    /** @return $this */
    public function directApply(bool $direct): static
    {
        return $this->set('directApply', $direct);
    }

    /** @return $this */
    public function industry(string $industry): static
    {
        return $this->set('industry', $industry);
    }

    /** @return $this */
    public function qualifications(string $qualifications): static
    {
        return $this->set('qualifications', $qualifications);
    }

    /** @return $this */
    public function responsibilities(string $responsibilities): static
    {
        return $this->set('responsibilities', $responsibilities);
    }

    /** @return $this */
    public function skills(string $skills): static
    {
        return $this->set('skills', $skills);
    }

    /** @return $this */
    public function experienceRequirements(string|SchemaType $requirements): static
    {
        return $this->set('experienceRequirements', $requirements);
    }

    /** @return $this */
    public function educationRequirements(string|SchemaType $requirements): static
    {
        return $this->set('educationRequirements', $requirements);
    }
}
