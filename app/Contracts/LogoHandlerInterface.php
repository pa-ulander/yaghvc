<?php

declare(strict_types=1);

namespace App\Contracts;

use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

/**
 * Interface for logo processing handlers in the Chain of Responsibility pattern.
 *
 * Each handler can either:
 * 1. Process the request and return a LogoResult
 * 2. Return null if the logo is invalid
 * 3. Pass to the next handler in the chain
 */
interface LogoHandlerInterface
{
    /**
     * Handle the logo request.
     *
     * @param LogoRequest $request The logo request to process
     * @return LogoResult|null The processed logo result, or null if invalid/unhandled
     */
    public function handle(LogoRequest $request): ?LogoResult;

    /**
     * Set the next handler in the chain.
     *
     * @param LogoHandlerInterface $handler The next handler
     * @return LogoHandlerInterface Returns the next handler for fluent chaining
     */
    public function setNext(LogoHandlerInterface $handler): LogoHandlerInterface;
}
