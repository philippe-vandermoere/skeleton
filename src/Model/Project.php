<?php
/**
 * @author Philippe VANDERMOERE <philippe@wizaplace.com>
 * @copyright Copyright (C) Philippe VANDERMOERE
 * @license MIT
 */

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

class Project
{
    protected const NAME_REGEX = '[a-zA-Z0-9-_]{4,}';

    protected string $name;
    protected string $directory;
    protected string $url;
    protected string $organization;
    protected string $description;

    public function __construct(
        string $name,
        string $directory,
        string $url = null,
        string $organization = null,
        string $description = ''
    ) {
        $organization = $organization ?? $name;
        $url = $url ?? $name;

        $this
            ->validateName($name)
            ->validateDirectory($directory)
            ->validateUrl($url)
            ->validateName($organization)
        ;

        $this->name = $name;
        $this->directory = $directory;
        $this->url = $url;
        $this->organization = $organization;
        $this->description = $description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getOrganization(): string
    {
        return $this->organization;
    }

    protected function validateName(string $name): self
    {
        if (1 !== \preg_match('/' . static::NAME_REGEX . '/', $name)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'The project name `%s` must be respect regex `%s`.',
                    $name,
                    static::NAME_REGEX
                )
            );
        }

        return $this;
    }

    protected function validateDirectory(string $directory): self
    {
        (new Filesystem)->mkdir($directory, 0755);

        if (0 !== (new Finder())->files()->in($directory)->ignoreDotFiles(false)->count()) {
            throw new \RuntimeException(
                sprintf(
                    'The project directory `%s` is not empty.',
                    $directory
                )
            );
        }

        return $this;
    }

    protected function validateUrl(string $url): self
    {
        if (false === \filter_var($url, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new \InvalidArgumentException(
                \sprintf(
                    'The project url `%s` must be a valid domain.',
                    $url
                )
            );
        }

        return $this;
    }
}
