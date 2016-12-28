<?php

declare(strict_types=1);

namespace Smochin\Instagram\Model;

class Video extends Media
{
    /**
     * @var int
     */
    private $views = 0;

    /**
     * @var string
     */
    private $thumb;

    /**
     * @param int       $id
     * @param string    $code
     * @param string    $url
     * @param string    $thumb
     * @param int       $views
     * @param Dimension $dimension
     * @param \DateTime $created
     * @param User      $user
     * @param array     $tags
     * @param int       $likes
     * @param int       $comments
     * @param bool      $ad
     * @param mixed     $caption
     * @param Location  $location
     */
    public function __construct(int $id, string $code, string $url, string $thumb, int $views, Dimension $dimension, \DateTime $created, User $user, array $tags = [], int $likes = 0, int $comments = 0, bool $ad = false, $caption = null, Location $location = null)
    {
        $this->thumb = $thumb;
        $this->views = $views;
        parent::__construct($id, $code, $url, $dimension, $created, $user, $tags, $likes, $comments, $ad, $caption, $location);
    }

    /**
     * @return int
     */
    public function getViews(): int
    {
        return $this->views;
    }

    /**
     * @return string
     */
    public function getThumb(): string
    {
        return $this->thumb;
    }
}
