<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Immutable value object representing a GitHub profile or repository identifier.
 *
 * Encapsulates username and optional repository name, providing validation
 * and helper methods for identifying scope and generating cache keys.
 */
readonly class ProfileIdentifier
{
    /**
     * @param string $username GitHub username (validated format)
     * @param string|null $repository Optional repository name for scoped counting
     */
    public function __construct(
        public string $username,
        public ?string $repository = null,
    ) {
        $this->validateUsername();
        if ($this->repository !== null) {
            $this->validateRepository();
        }
    }

    /**
     * Convert to string representation suitable for cache keys and identification.
     *
     * Format: "username" for profile views, "username:repository" for repo views.
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->repository !== null) {
            return "{$this->username}:{$this->repository}";
        }

        return $this->username;
    }

    /**
     * Check if this identifier is scoped to a specific repository.
     *
     * @return bool True if repository is set, false for profile-only views
     */
    public function isRepositoryScoped(): bool
    {
        return $this->repository !== null;
    }

    /**
     * Validate username follows GitHub username format.
     *
     * GitHub usernames:
     * - Can only contain alphanumeric characters and hyphens
     * - Cannot have consecutive hyphens
     * - Cannot begin or end with a hyphen
     * - Maximum 39 characters
     *
     * @throws InvalidArgumentException If username is invalid
     */
    private function validateUsername(): void
    {
        if ($this->username === '') {
            throw new InvalidArgumentException('Username cannot be empty');
        }

        if (strlen($this->username) > 39) {
            throw new InvalidArgumentException('Username cannot exceed 39 characters');
        }

        // GitHub username pattern: alphanumeric and hyphens, no consecutive hyphens, no leading/trailing hyphens
        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/i', $this->username)) {
            throw new InvalidArgumentException(
                "Invalid username format: '{$this->username}'. Must contain only alphanumeric characters and hyphens, no consecutive hyphens, no leading/trailing hyphens"
            );
        }
    }

    /**
     * Validate repository name follows GitHub repository format.
     *
     * GitHub repository names:
     * - Can contain alphanumeric characters, hyphens, underscores, and periods
     * - Cannot start with a period
     * - Maximum 100 characters (typical limit)
     *
     * @throws InvalidArgumentException If repository is invalid
     */
    private function validateRepository(): void
    {
        // This method is only called when repository is not null (checked in constructor)
        assert($this->repository !== null);

        if ($this->repository === '') {
            throw new InvalidArgumentException('Repository cannot be empty string');
        }

        if (strlen($this->repository) > 100) {
            throw new InvalidArgumentException('Repository name cannot exceed 100 characters');
        }

        // Repository can contain alphanumeric, hyphens, underscores, periods (but not start with period)
        if (! preg_match('/^[a-z0-9][a-z0-9._-]*$/i', $this->repository)) {
            throw new InvalidArgumentException(
                "Invalid repository format: '{$this->repository}'. Must start with alphanumeric character and contain only alphanumeric, hyphens, underscores, and periods"
            );
        }
    }
}
