<?php

declare(strict_types=1);

namespace Smochin\Instagram\Factory;

use Smochin\Instagram\Model\Coordinate;
use Smochin\Instagram\Model\Location;

class LocationFactory
{
    /**
     * @param int    $id
     * @param string $name
     * @param string $slug
     * @param mixed  $latitude
     * @param mixed  $longitude
     *
     * @return Location
     */
    public static function create(
            int $id,
            string $name,
            string $slug,
            $latitude = null,
            $longitude = null
    ): Location {
        $coordinate = null;
        if ($latitude && $longitude) {
            $coordinate = new Coordinate($latitude, $longitude);
        }

        return new Location($id, $name, $slug, $coordinate);
    }
}
