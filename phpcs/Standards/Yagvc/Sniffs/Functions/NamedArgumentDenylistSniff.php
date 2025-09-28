<?php

declare(strict_types=1);

namespace Yagvc\Standards\Sniffs\Functions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * Flags named arguments for selected functions where they are unsafe.
 */
final class NamedArgumentDenylistSniff implements Sniff
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
     * @return array<int, int>
     */
    public function register(): array
    {
        return [T_STRING];
    }

    /**
     * @param File $phpcsFile
     * @param int $stackPtr
     * @return void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $functionName = strtolower($tokens[$stackPtr]['content']);
        if (! isset(self::DENYLIST[$functionName])) {
            return;
        }

        $next = $phpcsFile->findNext([T_WHITESPACE, T_COMMENT], $stackPtr + 1, null, true, null, true);
        if ($next === false || $tokens[$next]['code'] !== T_OPEN_PARENTHESIS) {
            return;
        }

        $prev = $phpcsFile->findPrevious([T_WHITESPACE, T_COMMENT], $stackPtr - 1, null, true, null, true);
        if ($prev !== false && in_array($tokens[$prev]['code'], [T_FUNCTION, T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_NEW], true)) {
            return;
        }

        $openParen = $next;
        $closeParen = $tokens[$openParen]['parenthesis_closer'] ?? null;
        if ($closeParen === null) {
            return;
        }

        if (! $this->containsNamedArgument($phpcsFile, $openParen, $closeParen)) {
            return;
        }

        $phpcsFile->addError(
            sprintf('Named arguments are not allowed when calling %s(); use positional arguments instead.', $functionName),
            $stackPtr,
            'NamedArgumentDenylist'
        );
    }

    /**
     * @param File $phpcsFile
     * @param int $openPtr
     * @param int $closePtr
     * @return bool
     */
    private function containsNamedArgument(File $phpcsFile, int $openPtr, int $closePtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        for ($i = $openPtr + 1; $i < $closePtr; $i++) {
            if ($tokens[$i]['code'] !== T_COLON) {
                continue;
            }

            $prev = $phpcsFile->findPrevious([T_WHITESPACE, T_COMMENT], $i - 1, $openPtr, true, null, true);
            if ($prev === false) {
                continue;
            }

            if (! in_array($tokens[$prev]['code'], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_NAME_RELATIVE], true)) {
                continue;
            }

            $beforePrev = $phpcsFile->findPrevious([T_WHITESPACE, T_COMMENT], $prev - 1, $openPtr, true, null, true);
            if ($beforePrev !== false && $tokens[$beforePrev]['code'] === T_INLINE_THEN) {
                // Ternary operator branch; skip.
                continue;
            }

            return true;
        }

        return false;
    }
}
