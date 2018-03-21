<?php

declare(strict_types=1);

namespace Smochin\Instagram;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Smochin\Instagram\Factory\LocationFactory;
use Smochin\Instagram\Factory\MediaFactory;
use Smochin\Instagram\Factory\TagFactory;
use Smochin\Instagram\Factory\UserFactory;
use Smochin\Instagram\Model\Location;
use Smochin\Instagram\Model\Media;
use Smochin\Instagram\Model\Tag;
use Smochin\Instagram\Model\User;

/**
 * This class provides an access api to public Instagram data.
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
     * @var string
     */
    public $endCursor;

    /**
     * Initializes a new object.
     */
    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'query'    => self::QUERY,
        ]);
    }

    /**
     * Get a list of recently tagged media.
     *
     * @param string $name The name of the hashtag
     *
     * @throws GuzzleException
     *
     * @return array A list of media
     */
    public function getMediaByTag(string $name): array
    {
        $response = $this->client->request('GET', sprintf(self::TAG_ENDPOINT, $name));
        $body = json_decode($response->getBody()->getContents(), true);

        $nodeArrays = [];
        $edgeMedia = $body['graphql']['hashtag']['edge_hashtag_to_media'];
        foreach ($edgeMedia['edges'] as $index => $node) {
            $nodeArrays[] = $node['node'];
        }

        $pageInfo = $edgeMedia['page_info'];

        $this->endCursor = $pageInfo['end_cursor'];

        return $this->getMediaAsync(array_column($nodeArrays, 'shortcode'));
    }

    /**
     * Get a list of recent media objects from a given location.
     *
     * @param int $id Identification of the location
     *
     * @throws GuzzleException
     *
     * @return array A list of media
     */
    public function getMediaByLocation(int $id): array
    {
        $response = $this->client->request('GET', sprintf(self::LOCATION_ENDPOINT, $id));
        $body = json_decode($response->getBody()->getContents(), true);

        return $this->getMediaAsync(array_column($body['location']['media']['nodes'], 'code'));
    }

    /**
     * Get the most recent media published by a user.
     *
     * @param string $username The username of a user
     *
     * @throws GuzzleException
     *
     * @return array A list of media
     */
    public function getMediaByUser(string $username): array
    {
        $response = $this->client->request('GET', sprintf(self::USER_ENDPOINT, $username));
        $body = json_decode($response->getBody()->getContents(), true);

        $nodeArrays = [];
        $edgeMedia = $body['graphql']['user']['edge_owner_to_timeline_media'];
        foreach ($edgeMedia['edges'] as $index => $node) {
            $nodeArrays[] = $node['node'];
        }

        $pageInfo = $edgeMedia['page_info'];

        $this->endCursor = $pageInfo['end_cursor'];

        return $this->getMediaAsync(array_column($nodeArrays, 'shortcode'));
    }

    /**
     * Gets media asynchronously.
     *
     * @param array $codes A list of media codes
     *
     * @return array A list of media
     */
    private function getMediaAsync(array $codes): array
    {
        $promises = array_map(function ($code): PromiseInterface {
            return $this->client->requestAsync('GET', sprintf(self::MEDIA_ENDPOINT, $code));
        }, $codes);
        $results = Promise\settle($promises)->wait();

        $list = [];
        foreach ($results as $r) {
            if ($r['state'] != PromiseInterface::FULFILLED) {
                continue;
            }

            $media = json_decode($r['value']->getBody()->getContents(), true)['graphql']['shortcode_media'];
            $list[] = $this->loadMedia($media);
        }

        return $list;
    }

    /**
     * Get information about a media object.
     *
     * @param string $code The code of a media
     *
     * @throws GuzzleException
     *
     * @return Media The media
     */
    public function getMedia(string $code): Media
    {
        $response = $this->client->request('GET', sprintf(self::MEDIA_ENDPOINT, $code));
        $media = json_decode($response->getBody()->getContents(), true)['graphql']['shortcode_media'];

        return $this->loadMedia($media);
    }

    private function loadMedia(array $media): Media
    {
        //var_dump($media['shortcode']);
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
                $media['shortcode'],
                $media['video_url'],
                $media['display_url'],
                $media['video_view_count'],
                $media['dimensions'],
                $media['taken_at_timestamp'],
                $user,
                $media['edge_media_preview_like']['count'],
                $media['edge_media_to_comment']['count'],
                $media['is_ad'],
                $media['edge_media_to_caption']['edges'][0]['node']['text'] ?? null,
                $location
            );
        }

        return MediaFactory::createPhoto(
            (int) $media['id'],
            $media['shortcode'],
            $media['display_url'],
            $media['dimensions'],
            $media['taken_at_timestamp'],
            $user,
            $media['edge_media_preview_like']['count'],
            $media['edge_media_to_comment']['count'],
            $media['is_ad'],
            $media['edge_media_to_caption']['edges'][0]['node']['text'] ?? null,
            $location
        );
    }

    /**
     * Get information about a user.
     *
     * @param string $username The username of a user
     *
     * @throws GuzzleException
     *
     * @return User A user
     */
    public function getUser(string $username): User
    {
        $response = $this->client->request('GET', sprintf(self::USER_ENDPOINT, $username));
        $user = json_decode($response->getBody()->getContents(), true)['graphql']['user'];

        return UserFactory::create(
            (int) $user['id'],
            $user['username'],
            $user['profile_pic_url'],
            $user['full_name'],
            $user['is_private'],
            $user['is_verified'],
            $user['biography'],
            $user['external_url'],
            $user['edge_followed_by']['count'],
            $user['edge_follow']['count'],
            $user['edge_owner_to_timeline_media']['count']
        );
    }

    /**
     * Get information about a location.
     *
     * @param int $id Identification of the location
     *
     * @throws GuzzleException
     *
     * @return Location A location
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
     * Get information about a tag object.
     *
     * @param string $name The name of the hashtag
     *
     * @throws GuzzleException
     *
     * @return Tag A hashtag
     */
    public function getTag(string $name): Tag
    {
        $response = $this->client->request('GET', sprintf(self::TAG_ENDPOINT, $name));
        $tag = json_decode($response->getBody()->getContents(), true)['graphql']['hashtag'];

        return TagFactory::create($tag['name'], $tag['edge_hashtag_to_media']['count']);
    }

    /**
     * Search for hashtags, locations, and users.
     *
     * @param string $query The term to be searched
     *
     * @throws GuzzleException
     *
     * @return array The result of the search
     */
    public function search(string $query): array
    {
        $response = $this->client->request('GET', self::SEARCH_ENDPOINT, [
            'query' => [
                'query'   => $query,
                'context' => self::SEARCH_CONTEXT_PARAM,
            ],
        ]);
        $body = json_decode($response->getBody()->getContents(), true);

        return $this->loadSearch($body);
    }

    /**
     * Set the Guzzle HTTP client with EndCursor
     * 
     * @return void
     */
    public function setClientOnEndCursor(): void
    {
        $this->client = new Client([
            'base_uri' => self::BASE_URI,
            'query'    => array_merge(self::QUERY, ['max_id' => $this->endCursor]),
        ]);
    }

    /**
     * Creates the data structure of a search.
     *
     * @param array $response The search response
     *
     * @return array The result of the search
     */
    private function loadSearch(array $response): array
    {
        $result = ['tags' => [], 'locations' => [], 'users' => []];
        foreach ($response['hashtags'] as $t) {
            $result['tags'][] = new Tag($t['hashtag']['name'], $t['hashtag']['media_count']);
        }
        foreach ($response['places'] as $p) {
            $result['locations'][] = LocationFactory::create(
                (int) $p['place']['location']['pk'],
                $p['place']['title'],
                $p['place']['slug'],
                $p['place']['location']['lat'],
                $p['place']['location']['lng']
            );
        }
        foreach ($response['users'] as $u) {
            $result['users'][] = UserFactory::create(
                (int) $u['user']['pk'],
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
