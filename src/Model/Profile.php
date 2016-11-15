<?php

declare(strict_types = 1);

namespace Smochin\Instagram\Model;

class Profile
{

    /**
     * @var string
     */
    private $biography;

    /**
     * @var string
     */
    private $website;

    /**
     * @var bool
     */
    private $privated = false;

    /**
     * @var bool
     */
    private $verified;

    /**
     * @param bool $privated
     * @param mixed $verified
     * @param mixed $biography
     * @param mixed $website
     */
    public function __construct(
        bool $privated = false, 
        $verified = null, 
        $biography = null, 
        $website = null
    ) {
        $this->privated = $privated;
        $this->verified = $verified;
        $this->biography = $biography;
        $this->website = $website;
    }

    /**
     * @return bool
     */
    public function isPrivated(): bool
    {
        return $this->privated;
    }

    /**
     * @return bool
     * @throws \UnexpectedValueException
     */
    public function isVerified(): bool
    {
        if (!is_bool($this->verified)) {
            throw new \UnexpectedValueException('Profile can not be verified');
        }

        return $this->verified;
    }

    /**
     * @return mixed
     */
    public function getBiography()
    {
        return $this->biography;
    }

    /**
     * @return mixed
     */
    public function getWebsite()
    {
        return $this->website;
    }

}
