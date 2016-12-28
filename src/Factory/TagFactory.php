<?php

namespace Smochin\Instagram\Factory;

use Smochin\Instagram\Model\Tag;

class TagFactory
{
    public static function create(string $name, int $count = 0): Tag
    {
        return new Tag($name, $count);
    }
}
