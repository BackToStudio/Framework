<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaManager;
use BackTo\Framework\Seo\Schema\Type\JobPosting;
use PHPUnit\Framework\TestCase;

class SchemaJobPostingTest extends TestCase
{
    public function testJobPostingFactory(): void
    {
        $job = Schema::jobPosting();
        $this->assertInstanceOf(JobPosting::class, $job);
        $this->assertSame('JobPosting', $job->getType());
    }

    public function testJobTitle(): void
    {
        $array = Schema::jobPosting()->title('Développeur PHP Senior')->toArray();
        $this->assertSame('Développeur PHP Senior', $array['title']);
    }

    public function testJobDescription(): void
    {
        $array = Schema::jobPosting()->description('Rejoignez notre équipe technique.')->toArray();
        $this->assertSame('Rejoignez notre équipe technique.', $array['description']);
    }

    public function testJobDatePosted(): void
    {
        $array = Schema::jobPosting()->datePosted('2026-03-15')->toArray();
        $this->assertSame('2026-03-15', $array['datePosted']);
    }

    public function testJobValidThrough(): void
    {
        $array = Schema::jobPosting()->validThrough('2026-06-15')->toArray();
        $this->assertSame('2026-06-15', $array['validThrough']);
    }

    public function testJobHiringOrganization(): void
    {
        $array = Schema::jobPosting()
            ->hiringOrganization(Schema::organization()->name('Acme Corp')->url('https://acme.com'))
            ->toArray();

        $this->assertSame('Organization', $array['hiringOrganization']['@type']);
        $this->assertSame('Acme Corp', $array['hiringOrganization']['name']);
    }

    public function testJobLocation(): void
    {
        $array = Schema::jobPosting()
            ->jobLocation(
                Schema::place()->address(
                    Schema::postalAddress()
                        ->addressLocality('Lyon')
                        ->addressRegion('Auvergne-Rhône-Alpes')
                        ->addressCountry('FR')
                )
            )
            ->toArray();

        $this->assertSame('Place', $array['jobLocation']['@type']);
        $this->assertSame('PostalAddress', $array['jobLocation']['address']['@type']);
        $this->assertSame('Lyon', $array['jobLocation']['address']['addressLocality']);
    }

    public function testJobBaseSalary(): void
    {
        $array = Schema::jobPosting()
            ->baseSalary(
                Schema::monetaryAmount()
                    ->currency('EUR')
                    ->value(
                        Schema::type('QuantitativeValue')
                            ->set('value', 55000)
                            ->set('unitText', 'YEAR')
                    )
            )
            ->toArray();

        $this->assertSame('MonetaryAmount', $array['baseSalary']['@type']);
        $this->assertSame('EUR', $array['baseSalary']['currency']);
        $this->assertSame('QuantitativeValue', $array['baseSalary']['value']['@type']);
        $this->assertSame(55000, $array['baseSalary']['value']['value']);
    }

    public function testJobBaseSalaryRange(): void
    {
        $array = Schema::jobPosting()
            ->baseSalary(
                Schema::monetaryAmount()
                    ->currency('EUR')
                    ->minValue(45000)
                    ->maxValue(65000)
            )
            ->toArray();

        $this->assertSame(45000, $array['baseSalary']['minValue']);
        $this->assertSame(65000, $array['baseSalary']['maxValue']);
    }

    public function testJobEmploymentTypeString(): void
    {
        $array = Schema::jobPosting()->employmentType('FULL_TIME')->toArray();
        $this->assertSame('FULL_TIME', $array['employmentType']);
    }

    public function testJobEmploymentTypeArray(): void
    {
        $array = Schema::jobPosting()->employmentType(['FULL_TIME', 'CONTRACTOR'])->toArray();
        $this->assertSame(['FULL_TIME', 'CONTRACTOR'], $array['employmentType']);
    }

    public function testJobLocationType(): void
    {
        $array = Schema::jobPosting()->jobLocationType('TELECOMMUTE')->toArray();
        $this->assertSame('TELECOMMUTE', $array['jobLocationType']);
    }

    public function testJobUrl(): void
    {
        $array = Schema::jobPosting()->url('https://acme.com/jobs/dev-php')->toArray();
        $this->assertSame('https://acme.com/jobs/dev-php', $array['url']);
    }

    public function testJobDirectApply(): void
    {
        $array = Schema::jobPosting()->directApply(true)->toArray();
        $this->assertTrue($array['directApply']);
    }

    public function testJobIdentifierString(): void
    {
        $array = Schema::jobPosting()->identifier('JOB-2026-001')->toArray();
        $this->assertSame('JOB-2026-001', $array['identifier']);
    }

    public function testJobIndustry(): void
    {
        $array = Schema::jobPosting()->industry('Informatique')->toArray();
        $this->assertSame('Informatique', $array['industry']);
    }

    public function testJobQualifications(): void
    {
        $array = Schema::jobPosting()->qualifications('5 ans d\'expérience PHP')->toArray();
        $this->assertSame('5 ans d\'expérience PHP', $array['qualifications']);
    }

    public function testJobResponsibilities(): void
    {
        $array = Schema::jobPosting()->responsibilities('Développer et maintenir l\'API')->toArray();
        $this->assertSame('Développer et maintenir l\'API', $array['responsibilities']);
    }

    public function testJobSkills(): void
    {
        $array = Schema::jobPosting()->skills('PHP, Symfony, Docker')->toArray();
        $this->assertSame('PHP, Symfony, Docker', $array['skills']);
    }

    public function testJobExperienceRequirements(): void
    {
        $array = Schema::jobPosting()->experienceRequirements('5 ans minimum')->toArray();
        $this->assertSame('5 ans minimum', $array['experienceRequirements']);
    }

    public function testJobEducationRequirements(): void
    {
        $array = Schema::jobPosting()->educationRequirements('Bac+5 en informatique')->toArray();
        $this->assertSame('Bac+5 en informatique', $array['educationRequirements']);
    }

    public function testJobApplicantLocationRequirements(): void
    {
        $array = Schema::jobPosting()
            ->applicantLocationRequirements(
                Schema::type('Country')->set('name', 'France')
            )
            ->toArray();

        $this->assertSame('Country', $array['applicantLocationRequirements']['@type']);
        $this->assertSame('France', $array['applicantLocationRequirements']['name']);
    }

    public function testFullJobPostingComposition(): void
    {
        $job = Schema::jobPosting()
            ->title('Développeur WordPress Senior')
            ->description('Rejoignez notre équipe pour développer des thèmes et plugins WordPress.')
            ->datePosted('2026-03-15')
            ->validThrough('2026-06-15')
            ->hiringOrganization(
                Schema::organization()
                    ->name('Agence Web Acme')
                    ->url('https://acme-web.fr')
                    ->logo('https://acme-web.fr/logo.png')
            )
            ->jobLocation(
                Schema::place()->address(
                    Schema::postalAddress()
                        ->streetAddress('10 rue de la Paix')
                        ->addressLocality('Lyon')
                        ->postalCode('69002')
                        ->addressCountry('FR')
                )
            )
            ->baseSalary(
                Schema::monetaryAmount()
                    ->currency('EUR')
                    ->minValue(45000)
                    ->maxValue(60000)
            )
            ->employmentType('FULL_TIME')
            ->industry('Développement Web')
            ->skills('PHP, WordPress, JavaScript, Docker')
            ->qualifications('5 ans d\'expérience en développement WordPress')
            ->directApply(true)
            ->url('https://acme-web.fr/jobs/dev-wp-senior');

        $array = $job->toArray();

        $this->assertSame('JobPosting', $array['@type']);
        $this->assertSame('Développeur WordPress Senior', $array['title']);
        $this->assertSame('2026-03-15', $array['datePosted']);
        $this->assertSame('2026-06-15', $array['validThrough']);
        $this->assertSame('Organization', $array['hiringOrganization']['@type']);
        $this->assertSame('Agence Web Acme', $array['hiringOrganization']['name']);
        $this->assertSame('Place', $array['jobLocation']['@type']);
        $this->assertSame('Lyon', $array['jobLocation']['address']['addressLocality']);
        $this->assertSame('MonetaryAmount', $array['baseSalary']['@type']);
        $this->assertSame(45000, $array['baseSalary']['minValue']);
        $this->assertSame(60000, $array['baseSalary']['maxValue']);
        $this->assertSame('FULL_TIME', $array['employmentType']);
        $this->assertTrue($array['directApply']);
    }

    public function testRemoteJobPosting(): void
    {
        $job = Schema::jobPosting()
            ->title('Développeur Remote')
            ->jobLocationType('TELECOMMUTE')
            ->applicantLocationRequirements(
                Schema::type('Country')->set('name', 'France')
            )
            ->hiringOrganization(Schema::organization()->name('Remote Corp'));

        $array = $job->toArray();

        $this->assertSame('TELECOMMUTE', $array['jobLocationType']);
        $this->assertSame('Country', $array['applicantLocationRequirements']['@type']);
    }

    public function testJobPostingRendersValidJsonLd(): void
    {
        $manager = new SchemaManager();
        $manager->add(
            Schema::jobPosting()
                ->title('Dev PHP')
                ->hiringOrganization(Schema::organization()->name('Acme'))
                ->datePosted('2026-03-15')
        );

        $output = $manager->render();

        preg_match('/<script type="application\/ld\+json">\n(.+)\n<\/script>/s', $output, $matches);
        $decoded = json_decode($matches[1], true);

        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('JobPosting', $decoded['@type']);
        $this->assertSame('Dev PHP', $decoded['title']);
        $this->assertSame('Organization', $decoded['hiringOrganization']['@type']);
    }
}
