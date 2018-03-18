<?php

declare(strict_types=1);

namespace Smochin\Instagram;

use PHPUnit\Framework\TestCase;
use Smochin\Instagram\Model\Coordinate;
use Smochin\Instagram\Model\Location;
use Smochin\Instagram\Model\Media;
use Smochin\Instagram\Model\Profile;
use Smochin\Instagram\Model\Tag;
use Smochin\Instagram\Model\User;

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
        $this->assertFalse($media->isAd());
        $this->assertEquals('#30 Fairy💕@v_fairy_v', $media->getCaption());
        $this->assertEquals('BgOJQliAc6d', $media->getCode());
        $this->assertGreaterThan(0, count($media->getTags()));
        $this->assertEquals(1080, $media->getDimension()->getWidth());
        $this->assertEquals(1133, $media->getDimension()->getHeight());
        $this->assertGreaterThan(0, $media->getLikesCount());
        $this->assertGreaterThan(0, $media->getCommentsCount());
    }

    public function testGetMediaOnVideo()
    {
        $media = $this->crawler->getMedia('BgWhnmalRwU');
        $this->assertInstanceOf(Media::class, $media);
        $this->assertGreaterThan(0, $media->getViews());
        $this->assertNotEquals('', $media->getThumb());
    }

    public function testGetUser()
    {
        $user = $this->crawler->getUser('jamersonweb');
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(204496727, $user->getId());
        $this->assertEquals('Jamerson Silva', $user->getName());
        $this->assertEquals('jamersonweb', $user->getUserName());
        $this->assertNotEquals('', $user->getPicture());
        $this->assertInstanceOf(Profile::class, $user->getProfile());
        $this->assertFalse($user->getProfile()->isPrivated());
        $this->assertFalse($user->getProfile()->isVerified());
        $this->assertNotEquals('', $user->getProfile()->getBiography());
        $this->assertEquals('', $user->getProfile()->getWebsite());
        $this->assertGreaterThan(0, $user->getProfile()->getFollowersCount());
        $this->assertGreaterThan(0, $user->getProfile()->getFollowsCount());
        $this->assertGreaterThan(0, $user->getProfile()->getMediaCount());
    }

    public function testGetLocation()
    {
        $location = $this->crawler->getLocation(225963881);
        $this->assertInstanceOf(Location::class, $location);
        $this->assertEquals(225963881, $location->getId());
        $this->assertEquals('recife-pernambuco', $location->getSlug());
        $this->assertTrue($location->hasCoordinate());
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

    public function testSearchOnHashTags()
    {
        $result = $this->crawler->search('taipei');
        $this->assertGreaterThan(0, count($result));
        $this->assertEquals('taipei', ($result['tags'][0])->getName());
        $this->assertGreaterThan(8739744, ($result['tags'][0])->getCount());
    }
}
