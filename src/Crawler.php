<?php

declare(strict_types = 1);

namespace Smochin\Instagram;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Exception\GuzzleException;
use Smochin\Instagram\Model\Location;
use Smochin\Instagram\Model\Media;
use Smochin\Instagram\Model\Tag;
use Smochin\Instagram\Model\User;
use Smochin\Instagram\Factory\LocationFactory;
use Smochin\Instagram\Factory\UserFactory;
use Smochin\Instagram\Factory\MediaFactory;
use Smochin\Instagram\Factory\TagFactory;

/**
 * This class provides an access api to public Instagram data
 *
 * @author Jamerson Silva <jamersonweb@gmail.com>
 * @copyright 2016 Smochin
 */
class Crawler
{

    const BASE_URI = 'https://www.instagram.com';
    const QUERY = ['__a' => 1];
    const TAG_ENDPOINT = '/explore/tags/%s';
    const LOCATION_ENDPOINT = '/explore/locations/%d';
    const USER_ENDPOINT = '/%s';
    const MEDIA_ENDPOINT = '/p/%s';
    const SEARCH_ENDPOINT = '/web/search/topsearch';
    const SEARCH_CONTEXT_PARAM = 'blended';

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Initializes a new object
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'query' => self::QUERY,
        ]);
    }

    /**
     * Get a list of recently tagged media
     *
     * @param string $name The name of the hashtag
     * @return array A list of media
     * @throws GuzzleException
     */
    public function getMediaByTag(string $name): array
    {
        $response = $this->client->request('GET', sprintf(self::TAG_ENDPOINT, $name));
        $body = json_decode($response->getBody()->getContents(), true);

        return $this->getMediaAsync(array_column($body['tag']['media']['nodes'], 'code'));
    }

    /**
     * Get a list of recent media objects from a given location
     *
     * @param int $id Identification of the location
     * @return array A list of media
     * @throws GuzzleException
     */
    public function getMediaByLocation(int $id): array
    {
        $response = $this->client->request('GET', sprintf(self::LOCATION_ENDPOINT, $id));
        $body = json_decode($response->getBody()->getContents(), true);

        return $this->getMediaAsync(array_column($body['location']['media']['nodes'], 'code'));
    }

    /**
     * Get the most recent media published by a user
     *
     * @param string $username The username of a user
     * @return array A list of media
     * @throws GuzzleException
     */
    public function getMediaByUser(string $username): array
    {
        $response = $this->client->request('GET', sprintf(self::USER_ENDPOINT, $username));
        $body = json_decode($response->getBody()->getContents(), true);

        return $this->getMediaAsync(array_column($body['user']['media']['nodes'], 'code'));
    }

    /**
     * Gets media asynchronously
     *
     * @param array $codes A list of media codes
     * @return array A list of media
     */
    private function getMediaAsync(array $codes): array
    {
        $promises = array_map(function($code): PromiseInterface {
            return $this->client->requestAsync('GET', sprintf(self::MEDIA_ENDPOINT, $code));
        }, $codes);
        $results = Promise\settle($promises)->wait();

        $list = [];
        foreach ($results as $r) {
            if ($r['state'] != PromiseInterface::FULFILLED) {
                continue;
            }

            $media = json_decode($r['value']->getBody()->getContents(), true)['media'];
            $list[] = $this->loadMedia($media);
        }

        return $list;
    }

    /**
     * Get information about a media object
     *
     * @param string $code The code of a media
     * @return Media The media
     * @throws GuzzleException
     */
    public function getMedia(string $code): Media
    {
        $response = $this->client->request('GET', sprintf(self::MEDIA_ENDPOINT, $code));
        $media = json_decode($response->getBody()->getContents(), true)['media'];

        return $this->loadMedia($media);
    }

    private function loadMedia(array $media): Media
    {
        $location = null;
        if ($media['location']) {
            $location = LocationFactory::create(
                (int) $media['location']['id'],
                $media['location']['name'],
                $media['location']['slug']
            );
        }
        $user = UserFactory::create(
            (int) $media['owner']['id'],
            $media['owner']['username'],
            $media['owner']['profile_pic_url'],
            $media['owner']['full_name'],
            $media['owner']['is_private']
        );
        if ($media['is_video']) {
            return MediaFactory::createVideo(
                (int) $media['id'],
                $media['code'],
                $media['video_url'],
                $media['display_src'],
                $media['video_views'],
                $media['dimensions'],
                $media['date'],
                $user,
                $media['likes']['count'],
                $media['comments']['count'],
                $media['is_ad'],
                $media['caption'] ?? null,
                $location
            );
        }

        return MediaFactory::createPhoto(
            (int) $media['id'],
            $media['code'],
            $media['display_src'],
            $media['dimensions'],
            $media['date'],
            $user,
            $media['likes']['count'],
            $media['comments']['count'],
            $media['is_ad'],
            $media['caption'] ?? null,
            $location
        );
    }

    /**
     * Get information about a user
     *
     * @param string $username The username of a user
     * @return User A user
     * @throws GuzzleException
     */
    public function getUser(string $username): User
    {
        $response = $this->client->request('GET', sprintf(self::USER_ENDPOINT, $username));
        $user = json_decode($response->getBody()->getContents(), true)['user'];

        return UserFactory::create(
            (int) $user['id'],
            $user['username'],
            $user['profile_pic_url'],
            $user['full_name'],
            $user['is_private'],
            $user['is_verified'],
            $user['biography'],
            $user['external_url'],
            $user['followed_by']['count'],
            $user['follows']['count'],
            $user['media']['count']
        );
    }

    /**
     * Get information about a location
     *
     * @param int $id Identification of the location
     * @return Location A location
     * @throws GuzzleException
     */
    public function getLocation(int $id): Location
    {
        $response = $this->client->request('GET', sprintf(self::LOCATION_ENDPOINT, $id));
        $location = json_decode($response->getBody()->getContents(), true)['location'];

        return LocationFactory::create(
            (int) $location['id'],
            $location['name'],
            $location['slug'],
            $location['lat'],
            $location['lng']
        );
    }

    /**
     * Get information about a tag object
     *
     * @param string $name The name of the hashtag
     * @return Tag A hashtag
     * @throws GuzzleException
     */
    public function getTag(string $name): Tag
    {
        $response = $this->client->request('GET', sprintf(self::TAG_ENDPOINT, $name));
        $tag = json_decode($response->getBody()->getContents(), true)['tag'];

        return TagFactory::create($tag['name'], $tag['media']['count']);
    }

    /**
     * Search for hashtags, locations, and users
     *
     * @param string $query The term to be searched
     * @return array The result of the search
     * @throws GuzzleException
     */
    public function search(string $query): array
    {
        $response = $this->client->request('GET', self::SEARCH_ENDPOINT, [
            'query' => [
                'query' => $query,
                'context' => self::SEARCH_CONTEXT_PARAM
            ]
        ]);
        $body = json_decode($response->getBody()->getContents(), true);

        return $this->loadSearch($body);
    }

    /**
     * Creates the data structure of a search
     *
     * @param array $response The search response
     * @return array The result of the search
     */
    private function loadSearch(array $response): array
    {
        $result = ['tags' => [], 'locations' => [], 'users' => [],];
        foreach ($response['hashtags'] as $t) {
            $result['tags'][] = new Tag($t['hashtag']['name'], $t['hashtag']['media_count']);
        }
        foreach ($response['places'] as $p) {
            $result['locations'][] = LocationFactory::create(
                $p['place']['location']['pk'],
                $p['place']['title'],
                $p['place']['slug'],
                $p['place']['location']['lat'],
                $p['place']['location']['lng']
            );
        }
        foreach ($response['users'] as $u) {
            $result['users'][] = UserFactory::create(
                $u['user']['pk'],
                $u['user']['username'],
                $u['user']['profile_pic_url'],
                $u['user']['full_name'],
                $u['user']['is_private'],
                $u['user']['is_verified'],
                $u['user']['follower_count']
            );
        }

        return $result;
    }

}
