<?php 

namespace App\Services;

/**
 * Class BadgeService
 *
 * This class represents a service for generating badge information.
 */
class BadgeService
{
    /**
     * @var string The user agent.
     */
    private string $userAgent;

    /**
     * @var string The username.
     */
    private string $username;

    /**
     * @var string|null The badge label.
     */
    private ?string $badgeLabel;

    /**
     * @var string|null The badge color.
     */
    private ?string $badgeColor;

    /**
     * @var string|null The badge style.
     */
    private ?string $badgeStyle;

    /**
     * @var string|null The base count.
     */
    private ?string $baseCount;

    /**
     * @var bool The flag indicating whether the count is abbreviated.
     */
    private bool $isCountAbbreviated;

    /**
     * BadgeService constructor.
     *
     * @param string $userAgent The user agent.
     * @param string $username The username.
     * @param string|null $badgeLabel The badge label.
     * @param string|null $badgeColor The badge color.
     * @param string|null $badgeStyle The badge style.
     * @param string|null $baseCount The base count.
     * @param bool $isCountAbbreviated The flag indicating whether the count is abbreviated.
     */
    public function __construct(
        string $userAgent,
        string $username,
        ?string $badgeLabel,
        ?string $badgeColor,
        ?string $badgeStyle,
        ?string $baseCount,
        bool $isCountAbbreviated
    ) {
        $this->userAgent = $userAgent;
        $this->username = $username;
        $this->badgeLabel = $badgeLabel;
        $this->badgeColor = $badgeColor;
        $this->badgeStyle = $badgeStyle;
        $this->baseCount = $baseCount;
        $this->isCountAbbreviated = $isCountAbbreviated;
    }

    /**
     * Creates a new instance of BadgeService based on the server and get parameters.
     *
     * @param array $server The server parameters.
     * @param array $get The get parameters.
     * @return BadgeService The new instance of BadgeService.
     */
    public static function of(
        array $server,
        array $get
    ): self {
        $get = array_map(function ($input) {
            return htmlspecialchars($input, ENT_NOQUOTES, 'UTF-8', false);
        }, $get);

        return new self(
            $server['HTTP_USER_AGENT'] ?? '',
            $get['username'] ?? '',
            $get['label'] ?? null,
            $get['color'] ?? null,
            $get['style'] ?? null,
            $get['base'] ?? null,
            boolval($get['abbreviated'] ?? false),
        );
    }

    /**
     * Get the user agent.
     *
     * @return string The user agent.
     */
    public function userAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * Get the username.
     *
     * @return string The username.
     */
    public function username(): string
    {
        return $this->username;
    }

    /**
     * Get the badge label.
     *
     * @return string|null The badge label.
     */
    public function badgeLabel(): ?string
    {
        return $this->badgeLabel;
    }

    /**
     * Get the badge color.
     *
     * @return string|null The badge color.
     */
    public function badgeColor(): ?string
    {
        return $this->badgeColor;
    }

    /**
     * Get the badge style.
     *
     * @return string|null The badge style.
     */
    public function badgeStyle(): ?string
    {
        return $this->badgeStyle;
    }

    /**
     * Get the base count.
     *
     * @return string|null The base count.
     */
    public function baseCount(): ?string
    {
        return $this->baseCount;
    }

    /**
     * Check if the count is abbreviated.
     *
     * @return bool True if the count is abbreviated, false otherwise.
     */
    public function isCountAbbreviated(): bool
    {
        return $this->isCountAbbreviated;
    }
}