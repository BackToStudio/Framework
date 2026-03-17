<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaManager;
use BackTo\Framework\Seo\Schema\Type\Clip;
use BackTo\Framework\Seo\Schema\Type\VideoObject;
use PHPUnit\Framework\TestCase;

class SchemaVideoTest extends TestCase
{
    public function testVideoObjectFactory(): void
    {
        $video = Schema::videoObject();
        $this->assertInstanceOf(VideoObject::class, $video);
        $this->assertSame('VideoObject', $video->getType());
    }

    public function testClipFactory(): void
    {
        $clip = Schema::clip();
        $this->assertInstanceOf(Clip::class, $clip);
        $this->assertSame('Clip', $clip->getType());
    }

    public function testVideoName(): void
    {
        $array = Schema::videoObject()->name('Ma vidéo')->toArray();
        $this->assertSame('Ma vidéo', $array['name']);
    }

    public function testVideoDescription(): void
    {
        $array = Schema::videoObject()->description('Description de la vidéo')->toArray();
        $this->assertSame('Description de la vidéo', $array['description']);
    }

    public function testVideoThumbnailUrlString(): void
    {
        $array = Schema::videoObject()->thumbnailUrl('https://example.com/thumb.jpg')->toArray();
        $this->assertSame('https://example.com/thumb.jpg', $array['thumbnailUrl']);
    }

    public function testVideoThumbnailUrlArray(): void
    {
        $urls = ['https://example.com/thumb1.jpg', 'https://example.com/thumb2.jpg'];
        $array = Schema::videoObject()->thumbnailUrl($urls)->toArray();
        $this->assertSame($urls, $array['thumbnailUrl']);
    }

    public function testVideoUploadDate(): void
    {
        $array = Schema::videoObject()->uploadDate('2026-03-15T08:00:00+02:00')->toArray();
        $this->assertSame('2026-03-15T08:00:00+02:00', $array['uploadDate']);
    }

    public function testVideoDuration(): void
    {
        $array = Schema::videoObject()->duration('PT5M30S')->toArray();
        $this->assertSame('PT5M30S', $array['duration']);
    }

    public function testVideoContentUrl(): void
    {
        $array = Schema::videoObject()->contentUrl('https://example.com/video.mp4')->toArray();
        $this->assertSame('https://example.com/video.mp4', $array['contentUrl']);
    }

    public function testVideoEmbedUrl(): void
    {
        $array = Schema::videoObject()->embedUrl('https://youtube.com/embed/abc123')->toArray();
        $this->assertSame('https://youtube.com/embed/abc123', $array['embedUrl']);
    }

    public function testVideoExpires(): void
    {
        $array = Schema::videoObject()->expires('2027-01-01')->toArray();
        $this->assertSame('2027-01-01', $array['expires']);
    }

    public function testVideoRegionsAllowed(): void
    {
        $array = Schema::videoObject()->regionsAllowed(['FR', 'BE', 'CH'])->toArray();
        $this->assertSame(['FR', 'BE', 'CH'], $array['regionsAllowed']);
    }

    public function testVideoInteractionStatistic(): void
    {
        $stat = Schema::type('InteractionCounter')
            ->set('interactionType', 'https://schema.org/WatchAction')
            ->set('userInteractionCount', 15000);

        $array = Schema::videoObject()->interactionStatistic($stat)->toArray();

        $this->assertSame('InteractionCounter', $array['interactionStatistic']['@type']);
        $this->assertSame(15000, $array['interactionStatistic']['userInteractionCount']);
    }

    public function testVideoPublication(): void
    {
        $broadcast = Schema::type('BroadcastEvent')->set('isLiveBroadcast', true);
        $array = Schema::videoObject()->publication($broadcast)->toArray();

        $this->assertSame('BroadcastEvent', $array['publication']['@type']);
        $this->assertTrue($array['publication']['isLiveBroadcast']);
    }

    public function testClipName(): void
    {
        $array = Schema::clip()->name('Introduction')->toArray();
        $this->assertSame('Introduction', $array['name']);
    }

    public function testClipStartOffset(): void
    {
        $array = Schema::clip()->startOffset(30)->toArray();
        $this->assertSame(30, $array['startOffset']);
    }

    public function testClipEndOffset(): void
    {
        $array = Schema::clip()->endOffset(90)->toArray();
        $this->assertSame(90, $array['endOffset']);
    }

    public function testClipUrl(): void
    {
        $array = Schema::clip()->url('https://example.com/video#t=30')->toArray();
        $this->assertSame('https://example.com/video#t=30', $array['url']);
    }

    public function testVideoWithKeyMoments(): void
    {
        $video = Schema::videoObject()
            ->name('Tutoriel WordPress')
            ->hasPart([
                Schema::clip()->name('Introduction')->startOffset(0)->endOffset(30),
                Schema::clip()->name('Installation')->startOffset(30)->endOffset(120),
                Schema::clip()->name('Configuration')->startOffset(120)->endOffset(300),
            ]);

        $array = $video->toArray();

        $this->assertCount(3, $array['hasPart']);
        $this->assertSame('Clip', $array['hasPart'][0]['@type']);
        $this->assertSame('Introduction', $array['hasPart'][0]['name']);
        $this->assertSame(0, $array['hasPart'][0]['startOffset']);
        $this->assertSame(30, $array['hasPart'][0]['endOffset']);
        $this->assertSame('Installation', $array['hasPart'][1]['name']);
        $this->assertSame(30, $array['hasPart'][1]['startOffset']);
    }

    public function testFullVideoComposition(): void
    {
        $video = Schema::videoObject()
            ->name('Formation SEO 2026')
            ->description('Apprenez les bases du SEO en 2026')
            ->thumbnailUrl('https://example.com/seo-thumb.jpg')
            ->uploadDate('2026-03-01')
            ->duration('PT45M')
            ->contentUrl('https://example.com/seo.mp4')
            ->embedUrl('https://youtube.com/embed/xyz789')
            ->hasPart([
                Schema::clip()->name('Intro SEO')->startOffset(0)->endOffset(300)->url('https://youtube.com/watch?v=xyz789&t=0'),
                Schema::clip()->name('On-page SEO')->startOffset(300)->endOffset(1200)->url('https://youtube.com/watch?v=xyz789&t=300'),
            ]);

        $array = $video->toArray();

        $this->assertSame('VideoObject', $array['@type']);
        $this->assertSame('Formation SEO 2026', $array['name']);
        $this->assertSame('PT45M', $array['duration']);
        $this->assertSame('https://example.com/seo.mp4', $array['contentUrl']);
        $this->assertSame('https://youtube.com/embed/xyz789', $array['embedUrl']);
        $this->assertCount(2, $array['hasPart']);
    }

    public function testVideoRendersValidJsonLd(): void
    {
        $manager = new SchemaManager();
        $manager->add(
            Schema::videoObject()
                ->name('Test Video')
                ->thumbnailUrl('https://example.com/thumb.jpg')
                ->uploadDate('2026-03-15')
        );

        $output = $manager->render();

        preg_match('/<script type="application\/ld\+json">\n(.+)\n<\/script>/s', $output, $matches);
        $decoded = json_decode($matches[1], true);

        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertSame('VideoObject', $decoded['@type']);
        $this->assertSame('Test Video', $decoded['name']);
        $this->assertSame('https://example.com/thumb.jpg', $decoded['thumbnailUrl']);
    }
}
