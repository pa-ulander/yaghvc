<?php

declare(strict_types=1);

namespace App\Services\LogoHandlers;

use App\Contracts\LogoHandlerInterface;
use App\ValueObjects\LogoRequest;
use App\ValueObjects\LogoResult;

/**
 * Abstract base class for logo handlers implementing Chain of Responsibility.
 *
 * Provides chain management and a template for concrete handlers.
 * Each handler should override canHandle() and process() methods.
 */
abstract class AbstractLogoHandler implements LogoHandlerInterface
{
    /**
     * The next handler in the chain.
     */
    protected ?LogoHandlerInterface $nextHandler = null;

    /**
     * Set the next handler in the chain.
     *
     * @param LogoHandlerInterface $handler The next handler
     * @return LogoHandlerInterface Returns the next handler for fluent chaining
     */
    public function setNext(LogoHandlerInterface $handler): LogoHandlerInterface
    {
        $this->nextHandler = $handler;
        return $handler;
    }

    /**
     * Handle the logo request.
     *
     * Template method that checks if this handler can process the request,
     * processes it if possible, or passes to the next handler.
     *
     * @param LogoRequest $request The logo request to process
     * @return LogoResult|null The processed logo result, or null if invalid/unhandled
     */
    public function handle(LogoRequest $request): ?LogoResult
    {
        if ($this->canHandle($request)) {
            $result = $this->process($request);
            if ($result !== null) {
                return $result;
            }
        }

        return $this->handleNext($request);
    }

    /**
     * Pass the request to the next handler in the chain.
     *
     * @param LogoRequest $request The logo request to pass
     * @return LogoResult|null The result from the next handler, or null if no more handlers
     */
    protected function handleNext(LogoRequest $request): ?LogoResult
    {
        if ($this->nextHandler !== null) {
            return $this->nextHandler->handle($request);
        }

        return null;
    }

    /**
     * Determine if this handler can process the given request.
     *
     * @param LogoRequest $request The logo request to check
     * @return bool True if this handler can process the request
     */
    abstract protected function canHandle(LogoRequest $request): bool;

    /**
     * Process the logo request.
     *
     * @param LogoRequest $request The logo request to process
     * @return LogoResult|null The processed result, or null to continue chain
     */
    abstract protected function process(LogoRequest $request): ?LogoResult;
}
