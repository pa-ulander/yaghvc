<?php

declare(strict_types=1);

namespace Yagvc\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
use function strtolower;

/**
 * Forbids using named arguments with known-problematic PHP functions.
 *
 * @implements Rule<FuncCall>
 */
final class NoNamedArgumentsOnFunctionsRule implements Rule
{
    /**
     * @var array<string, bool>
     */
    private const DENYLIST = [
        'max' => true,
        'min' => true,
        'array_merge' => true,
        'array_replace' => true,
        'array_intersect' => true,
        'array_diff' => true,
        'sprintf' => true,
        'printf' => true,
        'implode' => true,
        'join' => true,
        'strtr' => true,
        'preg_replace' => true,
        'preg_replace_callback' => true,
        'filter_var' => true,
        'config' => true,
        'env' => true,
        'route' => true,
        'view' => true,
        'cache' => true,
        'session' => true,
        '__' => true,
        'trans' => true,
    ];

    /**
     * @return class-string<Node>
     */
    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /**
     * @param FuncCall $node
     * @return array<int, RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Name) {
            return [];
        }

        if (! $this->hasNamedArguments($node)) {
            return [];
        }

        $functionName = strtolower($node->name->toString());
        if (! isset(self::DENYLIST[$functionName])) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf('Named arguments are not allowed when calling %s(); use positional arguments instead.', $functionName)
            )->build(),
        ];
    }

    /**
     * Determine if the given call expression contains any named arguments.
     */
    private function hasNamedArguments(FuncCall $node): bool
    {
        foreach ($node->args as $argument) {
            if ($argument->name !== null) {
                return true;
            }
        }

        return false;
    }
}
