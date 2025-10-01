<?php

declare(strict_types=1);

namespace App\ValueObjects;

/**
 * Immutable composite value object representing a complete badge request.
 *
 * Combines profile identification, badge configuration, and base count
 * into a single cohesive request object that can be passed through
 * the rendering pipeline.
 */
readonly class BadgeRequest
{
    /**
     * @param ProfileIdentifier $profile The GitHub profile or repository identifier
     * @param BadgeConfiguration $config Badge rendering configuration
     * @param int $baseCount Optional base count to add to the stored visit count (default: 0)
     */
    public function __construct(
        public ProfileIdentifier $profile,
        public BadgeConfiguration $config,
        public int $baseCount = 0,
    ) {
        // All validation is handled by composed value objects
    }

    /**
     * Create BadgeRequest from validated request data.
     *
     * This factory method extracts the relevant data and creates the
     * composed value objects, simplifying controller logic.
     *
     * @param array<string, mixed> $data Validated request data
     * @return self
     */
    public static function fromValidatedData(array $data): self
    {
        // Extract username (required)
        $username = is_string($data['username'] ?? null) ? $data['username'] : '';

        // Extract repository (optional)
        $repository = null;
        if (isset($data['repository']) && is_string($data['repository'])) {
            $repository = $data['repository'];
        }

        // Extract base count (optional, default to 0)
        $baseCount = 0;
        if (isset($data['base'])) {
            if (is_int($data['base'])) {
                $baseCount = $data['base'];
            } elseif (is_string($data['base']) && $data['base'] !== '') {
                $baseCount = (int) $data['base'];
            }
        }

        return new self(
            profile: new ProfileIdentifier(
                username: $username,
                repository: $repository
            ),
            config: BadgeConfiguration::fromValidatedRequest($data),
            baseCount: $baseCount,
        );
    }
}
