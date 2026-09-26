<?php

declare(strict_types=1);

namespace PhelWeb\ErrorReference;

use Phel\Shared\Exceptions\ErrorCode;

/**
 * Generates the error-code reference page (content/documentation/reference/errors.md)
 * from Phel's ErrorCode enum (the authoritative list of which codes exist) merged
 * with curated cause/fix prose. If Phel adds a code without prose here, generation
 * fails, forcing the docs to be updated alongside the bump.
 *
 * @psalm-type TEntry = array{meaning: string, cause: string, fix: string, learnMore?: string}
 */
final class ErrorReferenceGenerator
{
    /** Code-range prefixes mapped to a category title and blurb. */
    private const CATEGORIES = [
        'PHEL0' => ['Analyzer errors', 'Raised while analyzing forms: undefined names, wrong arity, type and binding problems. The bulk of day-to-day errors.'],
        'PHEL1' => ['Parser errors', 'Raised while parsing tokens into forms, almost always an unbalanced or unterminated bracket.'],
        'PHEL2' => ['Reader errors', 'Raised while reading quote / quasiquote forms.'],
        'PHEL3' => ['Lexer errors', 'Raised while turning source text into tokens: invalid characters or unterminated strings.'],
        'PHEL4' => ['Runtime errors', 'Raised while compiled code runs, not while it compiles: the program was well formed and the values it met were not.'],
    ];

    /**
     * Curated explanation per error code. Every ErrorCode case MUST have an entry
     * (enforced by missingCodes() and the unit test).
     *
     * @var array<string, TEntry>
     */
    private const ENTRIES = [
        'PHEL001' => [
            'meaning' => 'A symbol could not be resolved to a definition in the current scope.',
            'cause' => 'A typo, a missing `(:require ...)` for the namespace the symbol lives in, an alias that does not match, or using a binding before it is defined.',
            'fix' => 'Check the spelling, require the namespace (e.g. `(:require phel\string :as str)` for `str/...`), or move the definition above its first use. The error message suggests near matches.',
        ],
        'PHEL002' => [
            'meaning' => 'A function was called with the wrong number of arguments.',
            'cause' => 'The call site passes more or fewer arguments than any of the function\'s arities accept.',
            'fix' => 'Match the call to a declared arity. For variadic functions use `& rest` in the parameter vector.',
        ],
        'PHEL003' => [
            'meaning' => 'A form received a value of the wrong type.',
            'cause' => 'For example attaching metadata that is not a String, Keyword or Map, or passing a non-collection where a collection is required.',
            'fix' => 'Pass the type the form expects. The message names the value it got.',
        ],
        'PHEL004' => [
            'meaning' => '`def` was used somewhere it is not allowed.',
            'cause' => '`def` defines a top-level var, so it cannot appear nested inside a function body or another expression.',
            'fix' => 'Move the `def` to the top level of the namespace. For function-local values use `let`.',
        ],
        'PHEL005' => [
            'meaning' => 'A macro threw while expanding.',
            'cause' => 'The macro received arguments it did not expect, or its own body raised during expansion.',
            'fix' => 'Check the arguments at the call site and inspect the expansion with `(macroexpand \'(your-form ...))`.',
            'learnMore' => '[Macros](/documentation/language/macros/).',
        ],
        'PHEL006' => [
            'meaning' => 'An inline-expanded function failed to expand.',
            'cause' => 'A function declared with an `:inline` implementation produced an invalid expansion for the given call.',
            'fix' => 'Call the function within the shape its `:inline` definition supports, or report it upstream if it is a core function.',
        ],
        'PHEL007' => [
            'meaning' => 'A special form was written in an invalid shape.',
            'cause' => 'A core form such as `if`, `let`, `fn`, `do` or `quote` was given the wrong structure (missing or extra parts).',
            'fix' => 'Match the form\'s grammar, e.g. `(if test then else?)`, `(let [bindings*] body*)`.',
        ],
        'PHEL008' => [
            'meaning' => 'A binding vector is invalid.',
            'cause' => 'An odd number of binding forms in `let`/`loop`, or a binding target that cannot be destructured.',
            'fix' => 'Provide an even number of `name value` pairs and use valid destructuring targets (symbols, vectors, maps).',
            'learnMore' => '[Destructuring](/documentation/language/destructuring/), [Global and local bindings](/documentation/language/global-and-local-bindings/).',
        ],
        'PHEL009' => [
            'meaning' => 'An interface or protocol definition (or its implementation) is invalid.',
            'cause' => 'A malformed `definterface`/`defprotocol`, or trying to implement a `defprotocol` inline in `defstruct` (only `definterface` can be implemented inline).',
            'fix' => 'Use `definterface` for inline implementation, or `defprotocol` plus `extend-type` per struct.',
            'learnMore' => '[Interfaces](/documentation/language/interfaces/).',
        ],
        'PHEL010' => [
            'meaning' => '`recur` was used incorrectly.',
            'cause' => '`recur` appeared outside a `loop`/`fn` tail position, or with an argument count that does not match the recursion point.',
            'fix' => 'Use `recur` only in tail position, with as many arguments as the enclosing `loop`/`fn` binds.',
            'learnMore' => '[Functions and recursion](/documentation/language/functions-and-recursion/).',
        ],
        'PHEL011' => [
            'meaning' => 'A value that is not a function was called.',
            'cause' => 'A non-callable value (a number, string, keyword used wrongly) sits in the head position of a list, often an extra pair of parentheses.',
            'fix' => 'Remove the stray parentheses, or put a function in the call position.',
        ],
        'PHEL012' => [
            'meaning' => 'A form Phel now says another way was used as source.',
            'cause' => '`php/new`, `php/->`, `php/::` or `set-var` was written by hand. The compiler still emits them, but they are no longer accepted in source.',
            'fix' => 'Use the replacement the message names: `(new \\Foo arg)`, `(.method obj arg)` / `(.-field obj)`, `(\\Foo/method arg)` / `\\Foo/CONST`, and `(alter-var-root (var v) f)`.',
            'learnMore' => '[PHP interop](/documentation/php-interop/).',
        ],
        'PHEL100' => [
            'meaning' => 'A list was not closed.',
            'cause' => 'A missing `)`.',
            'fix' => 'Balance the parentheses. Editor rainbow-brackets or `phel format` help spot it.',
        ],
        'PHEL101' => [
            'meaning' => 'A vector was not closed.',
            'cause' => 'A missing `]`.',
            'fix' => 'Balance the brackets.',
        ],
        'PHEL102' => [
            'meaning' => 'A map was not closed.',
            'cause' => 'A missing `}`, or an odd number of key/value forms.',
            'fix' => 'Close the brace and ensure every key has a value.',
        ],
        'PHEL103' => [
            'meaning' => 'A table literal was not closed.',
            'cause' => 'A missing closing brace on a `@{ ... }` table literal.',
            'fix' => 'Close the table literal.',
        ],
        'PHEL110' => [
            'meaning' => 'A token appeared where the parser did not expect one.',
            'cause' => 'A stray closing bracket, or a reader macro applied to nothing.',
            'fix' => 'Remove or complete the offending token.',
        ],
        'PHEL120' => [
            'meaning' => 'A general parser error.',
            'cause' => 'The token stream could not be assembled into valid forms for a reason not covered by a more specific code.',
            'fix' => 'Check the indicated location for malformed structure.',
        ],
        'PHEL202' => [
            'meaning' => 'A splicing unquote (`~@`) is invalid.',
            'cause' => '`~@` was used outside a quasiquote, or in a position where a sequence cannot be spliced.',
            'fix' => 'Use `~@` inside a quasiquote, splicing into a list or vector.',
        ],
        'PHEL210' => [
            'meaning' => 'A general reader error.',
            'cause' => 'A reader macro could not be read for a reason not covered by a more specific code.',
            'fix' => 'Check the quote/quasiquote forms at the indicated location.',
        ],
        'PHEL301' => [
            'meaning' => 'A string was not closed.',
            'cause' => 'A missing closing `"`, sometimes from an unescaped quote inside the string.',
            'fix' => 'Close the string and escape interior quotes as `\\"`.',
        ],
        'PHEL310' => [
            'meaning' => 'A general lexer error.',
            'cause' => 'The source could not be tokenized for a reason not covered by a more specific code.',
            'fix' => 'Check the indicated location for stray or invalid characters.',
        ],
        'PHEL400' => [
            'meaning' => 'A value that is not a function was called at runtime.',
            'cause' => 'The head of the list is a name or an expression the analyzer could not check, and it evaluated to a non-callable value.',
            'fix' => 'Call a function, or read the value with an accessor such as `get`, `nth` or `first`.',
        ],
        'PHEL401' => [
            'meaning' => 'A function was called with the wrong number of arguments at runtime.',
            'cause' => 'The callee is a value the analyzer has no arity for, such as a local closure, so the mismatch only surfaces when it runs.',
            'fix' => 'Pass one argument per declared parameter.',
        ],
        'PHEL402' => [
            'meaning' => 'A value of the wrong type reached a PHP function or method.',
            'cause' => 'Most of these come from `php/` interop calls whose callee declares a type the argument does not satisfy.',
            'fix' => 'Convert the value to the type the callee declares before passing it.',
            'learnMore' => '[PHP interop](/documentation/php-interop/).',
        ],
        'PHEL403' => [
            'meaning' => 'An indexed read asked for a position the collection does not have.',
            'cause' => '`nth` (and other indexed reads) past the end of a collection. `get` returns `nil` instead of throwing.',
            'fix' => 'Check the index against `(count coll)`, or use `get` with a default.',
        ],
        'PHEL404' => [
            'meaning' => 'A division or remainder had zero on the right.',
            'cause' => '`/`, `%`, `rem` and `php/intdiv` all raise it when the divisor is zero.',
            'fix' => 'Guard the divisor before dividing.',
        ],
    ];

    public function __construct(
        private readonly string $outputFile,
    ) {
    }

    /**
     * @return list<string> ErrorCode values that have no curated entry
     */
    public static function missingCodes(): array
    {
        $missing = [];
        foreach (ErrorCode::cases() as $case) {
            if (!isset(self::ENTRIES[$case->value])) {
                $missing[] = $case->value;
            }
        }

        return $missing;
    }

    public function generate(): void
    {
        $missing = self::missingCodes();
        if ($missing !== []) {
            throw new \RuntimeException(
                'No curated explanation for error code(s): ' . implode(', ', $missing)
                . '. Add them to ' . self::class . '::ENTRIES.',
            );
        }

        file_put_contents($this->outputFile, $this->render());
    }

    private function render(): string
    {
        $out = <<<'MD'
            +++
            title = "Error Reference"
            weight = 3
            description = "Every Phel error code (PHEL001-PHEL404), what it means, and how to fix it."
            +++

            > This page is the canonical index of error **codes**. To learn how to `throw`, `catch`, and attach data to errors in your own code, see [Error handling](/documentation/language/error-handling/).

            Phel errors are tagged with a stable code like `[PHEL001]`. The code
            survives wording changes, so it is the reliable thing to search for. An error
            prints as the code, a message, the source location, a snippet of the offending
            code, and often a hint:

            ```text
            [PHEL001] Cannot resolve symbol 'maap'. Did you mean 'map'?
            in src/app.phel:12
            ```

            Codes are grouped by the stage that raises them. Run `phel explain PHEL001`
            to print the same explanation in the terminal.

            MD;

        $byCategory = [];
        foreach (ErrorCode::cases() as $case) {
            $prefix = substr($case->value, 0, 5);
            $byCategory[$prefix][] = $case;
        }

        foreach (self::CATEGORIES as $prefix => [$title, $blurb]) {
            if (empty($byCategory[$prefix])) {
                continue;
            }
            $out .= "\n## {$title}\n\n{$blurb}\n";
            foreach ($byCategory[$prefix] as $case) {
                $entry = self::ENTRIES[$case->value];
                $name = $this->humanize($case->name);
                $out .= "\n### {$case->value} : {$name}\n\n";
                $out .= "{$entry['meaning']}\n\n";
                $out .= "**Common cause:** {$entry['cause']}\n\n";
                $out .= "**Fix:** {$entry['fix']}\n";
                if (isset($entry['learnMore'])) {
                    $out .= "\n**Learn more:** {$entry['learnMore']}\n";
                }
            }
        }

        return $out;
    }

    private function humanize(string $caseName): string
    {
        return ucfirst(strtolower(str_replace('_', ' ', $caseName)));
    }
}
