<?php

declare(strict_types=1);

namespace Smochin\Instagram;

use Smochin\Instagram\Model\Location;
use Smochin\Instagram\Model\Media;
use Smochin\Instagram\Model\Tag;
use Smochin\Instagram\Model\User;
use Smochin\Instagram\Model\Profile;
use Smochin\Instagram\Model\Coordinate;
use PHPUnit\Framework\TestCase;

class CrawlerTest extends TestCase
{
    private $crawler;

    protected function setUp()
    {
        $this->crawler = new Crawler();
    }

    public function testGetMediaByTag()
    {
        $media = $this->crawler->getMediaByTag('php');
        $this->assertGreaterThan(0, count($media));
    }

    public function testGetMediaByLocation()
    {
        $media = $this->crawler->getMediaByLocation(225963881);
        $this->assertGreaterThan(0, count($media));
    }

    public function testGetMediaByUser()
    {
        $media = $this->crawler->getMediaByUser('instagram');
        $this->assertGreaterThan(0, count($media));
    }

    public function testGetMedia()
    {
        $media = $this->crawler->getMedia('BgOJQliAc6d');
        $this->assertInstanceOf(Media::class, $media);
        $this->assertEquals(1733363628813438621, $media->getId());
        $this->assertNotNull($media->getUrl());
        $this->assertInstanceOf(\DateTime::class, $media->getCreated());
        $this->assertInstanceOf(User::class, $media->getUser());
        $this->assertNull($media->getLocation());
    }

    public function testGetUser()
    {
        $user = $this->crawler->getUser('jamersonweb');
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(204496727, $user->getId());
        $this->assertInstanceOf(Profile::class, $user->getProfile());
        $this->assertEquals(false, $user->getProfile()->isPrivated());
        $this->assertEquals(false, $user->getProfile()->isVerified());
    }

    public function testGetLocation()
    {
        $location = $this->crawler->getLocation(225963881);
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals('Recife - Pernambuco', $location->getName());
        $this->assertInstanceOf(Coordinate::class, $location->getCoordinate());
        $this->assertEquals(-8.67597444337, $location->getCoordinate()->getLatitude());
        $this->assertEquals(-35.5767717627, $location->getCoordinate()->getLongitude());
    }

    public function testGetTag()
    {
        $tag = $this->crawler->getTag('php');
        $this->assertInstanceOf(Tag::class, $tag);
        $this->assertGreaterThan(0, $tag->getCount());
    }

    public function testSearch()
    {
        $result = $this->crawler->search('instagram');
        $this->assertGreaterThan(0, count($result));
    }
}
