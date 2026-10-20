<?php

namespace App\Support\RichText;

/**
 * Validates (and evaluates, for tests and the seeder's answer checks) the
 * function expressions of a ```graph block. PHP twin of
 * resources/js/Utils/graph/expr.js — same grammar, names and limits:
 *
 *   expr    := term (('+' | '-') term)*
 *   term    := unary (('*' | '/') unary | <implicit ×> unary)*
 *   unary   := ('-' | '+') unary | power
 *   power   := primary ('^' unary)?
 *   primary := number | x | t | pi | π | e | func '(' expr (',' expr)? ')' | '(' expr ')' | '|' expr '|'
 *
 * Nothing is ever passed to eval(); the AST is walked by evaluate().
 */
final class GraphExpression
{
    public const MAX_LENGTH = 200;

    private const MAX_DEPTH = 40;

    public const FUNCTIONS_1 = [
        'sin', 'cos', 'tan', 'cot', 'asin', 'acos', 'atan', 'arcsin', 'arccos', 'arctan',
        'sinh', 'cosh', 'tanh', 'sqrt', 'cbrt', 'abs', 'ln', 'log', 'lg', 'log2',
        'exp', 'floor', 'ceil', 'round', 'sign',
    ];

    public const FUNCTIONS_2 = ['min', 'max'];

    private const CONSTANTS = ['pi' => M_PI, 'π' => M_PI, 'e' => M_E];

    private const VARIABLES = ['x', 't'];

    /** @var list<array{type:string, value?:mixed}> */
    private array $tokens = [];

    private int $pos = 0;

    private int $depth = 0;

    private int $absDepth = 0;

    /** Null when valid, else the (Uzbek) error message. */
    public static function error(string $src): ?string
    {
        try {
            self::parse($src);

            return null;
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
    }

    /** @throws \InvalidArgumentException */
    public static function parse(string $src): array
    {
        $p = new self;
        $p->tokens = $p->tokenize($src);
        $ast = $p->expr();
        if ($p->pos < count($p->tokens)) {
            $t = $p->tokens[$p->pos];
            throw new \InvalidArgumentException($t['type'] === ')' ? 'Ortiqcha yopuvchi qavs' : 'Kutilmagan belgi: "'.($t['value'] ?? $t['type']).'"');
        }

        return $ast;
    }

    /** f(x) for a parsed expression (NAN outside the domain). */
    public static function evaluate(array $node, float $x): float
    {
        return match ($node['n']) {
            'num' => $node['v'],
            'var' => $x,
            'neg' => -self::evaluate($node['a'], $x),
            'fn' => self::call($node['f'], array_map(fn ($a) => self::evaluate($a, $x), $node['args'])),
            'bin' => self::binary($node['op'], self::evaluate($node['a'], $x), self::evaluate($node['b'], $x)),
        };
    }

    private static function binary(string $op, float $a, float $b): float
    {
        return match ($op) {
            '+' => $a + $b,
            '-' => $a - $b,
            '*' => $a * $b,
            '/' => $b == 0.0 ? ($a == 0.0 ? NAN : ($a > 0 ? INF : -INF)) : $a / $b,
            '^' => self::pow($a, $b),
        };
    }

    private static function pow(float $base, float $exp): float
    {
        if ($base < 0 && floor($exp) != $exp) {
            $inv = 1 / $exp;
            if (abs($inv - round($inv)) < 1e-9 && ((int) round($inv)) % 2 !== 0) {
                return -((-$base) ** $exp);
            }

            return NAN;
        }

        return $base ** $exp;
    }

    private static function call(string $f, array $a): float
    {
        $v = $a[0];
        $nan = fn (bool $ok, callable $fn) => $ok ? $fn() : NAN;

        return match ($f) {
            'sin' => sin($v), 'cos' => cos($v), 'tan' => tan($v), 'cot' => 1 / tan($v),
            'asin', 'arcsin' => $nan(abs($v) <= 1, fn () => asin($v)),
            'acos', 'arccos' => $nan(abs($v) <= 1, fn () => acos($v)),
            'atan', 'arctan' => atan($v),
            'sinh' => sinh($v), 'cosh' => cosh($v), 'tanh' => tanh($v),
            'sqrt' => $nan($v >= 0, fn () => sqrt($v)),
            'cbrt' => $v < 0 ? -((-$v) ** (1 / 3)) : $v ** (1 / 3),
            'abs' => abs($v),
            'ln' => $nan($v > 0, fn () => log($v)),
            'log', 'lg' => $nan($v > 0, fn () => log10($v)),
            'log2' => $nan($v > 0, fn () => log($v, 2)),
            'exp' => exp($v),
            'floor' => floor($v), 'ceil' => ceil($v), 'round' => round($v),
            'sign' => $v <=> 0,
            'min' => min($a[0], $a[1]),
            'max' => max($a[0], $a[1]),
        };
    }

    // ==================== parser ====================

    private function tokenize(string $s): array
    {
        if (trim($s) === '') {
            throw new \InvalidArgumentException("Ifoda bo'sh");
        }
        if (mb_strlen($s) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException('Ifoda juda uzun (ko\'pi bilan '.self::MAX_LENGTH.' belgi)');
        }

        $chars = mb_str_split($s);
        $tokens = [];
        $i = 0;
        $n = count($chars);
        while ($i < $n) {
            $c = $chars[$i];
            if (preg_match('/\s/u', $c)) {
                $i++;

                continue;
            }
            $rest = implode('', array_slice($chars, $i, 64));
            if (preg_match('/^[0-9.]/', $c)) {
                if (! preg_match('/^(\d+\.?\d*|\.\d+)([eE][+-]?\d+)?/', $rest, $m)) {
                    throw new \InvalidArgumentException('Noto\'g\'ri son: "'.mb_substr($rest, 0, 8).'"');
                }
                $tokens[] = ['type' => 'num', 'value' => (float) $m[0]];
                $i += strlen($m[0]);

                continue;
            }
            if (preg_match('/^(π|[a-zA-Z][a-zA-Z0-9]*)/u', $rest, $m)) {
                $tokens[] = ['type' => 'id', 'value' => mb_strtolower($m[0])];
                $i += mb_strlen($m[0]);

                continue;
            }
            if (str_contains('+-*/^(),|', $c)) {
                $tokens[] = ['type' => $c];
                $i++;

                continue;
            }
            if ($c === '·' || $c === '×') {
                $tokens[] = ['type' => '*'];
                $i++;

                continue;
            }
            if ($c === '−') {
                $tokens[] = ['type' => '-'];
                $i++;

                continue;
            }
            throw new \InvalidArgumentException('Ruxsat etilmagan belgi: "'.$c.'"');
        }

        return $this->splitIdentifiers($tokens);
    }

    private function splitIdentifiers(array $tokens): array
    {
        $known = array_merge(self::FUNCTIONS_1, self::FUNCTIONS_2, array_keys(self::CONSTANTS), self::VARIABLES);
        usort($known, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $out = [];
        foreach ($tokens as $tok) {
            if ($tok['type'] !== 'id' || $this->isKnown($tok['value'])) {
                $out[] = $tok;

                continue;
            }
            $rest = $tok['value'];
            while ($rest !== '') {
                $name = null;
                foreach ($known as $k) {
                    if (str_starts_with($rest, $k)) {
                        $name = $k;
                        break;
                    }
                }
                if ($name !== null) {
                    $out[] = ['type' => 'id', 'value' => $name];
                    $rest = substr($rest, strlen($name));
                } elseif (preg_match('/^\d+/', $rest, $m)) {
                    $out[] = ['type' => 'num', 'value' => (float) $m[0]];
                    $rest = substr($rest, strlen($m[0]));
                } else {
                    throw new \InvalidArgumentException('Noma\'lum nom: "'.$tok['value'].'"');
                }
            }
        }

        return $out;
    }

    private function isKnown(string $name): bool
    {
        return in_array($name, self::FUNCTIONS_1, true) || in_array($name, self::FUNCTIONS_2, true)
            || array_key_exists($name, self::CONSTANTS) || in_array($name, self::VARIABLES, true);
    }

    private function peek(): ?array
    {
        return $this->tokens[$this->pos] ?? null;
    }

    private function next(): ?array
    {
        return $this->tokens[$this->pos++] ?? null;
    }

    private function expect(string $type): void
    {
        $t = $this->next();
        if (! $t || $t['type'] !== $type) {
            throw new \InvalidArgumentException($type === ')' ? 'Qavs yopilmagan' : '"'.$type.'" kutilgan edi');
        }
    }

    private function enter(): void
    {
        if (++$this->depth > self::MAX_DEPTH) {
            throw new \InvalidArgumentException('Ifoda juda chuqur');
        }
    }

    private function startsPrimary(?array $t): bool
    {
        return $t !== null && (in_array($t['type'], ['num', 'id', '('], true) || ($t['type'] === '|' && $this->absDepth === 0));
    }

    private function expr(): array
    {
        $this->enter();
        $node = $this->term();
        while (($t = $this->peek()) && ($t['type'] === '+' || $t['type'] === '-')) {
            $this->next();
            $node = ['n' => 'bin', 'op' => $t['type'], 'a' => $node, 'b' => $this->term()];
        }
        $this->depth--;

        return $node;
    }

    private function term(): array
    {
        $node = $this->unary();
        while (true) {
            $t = $this->peek();
            if ($t && ($t['type'] === '*' || $t['type'] === '/')) {
                $this->next();
                $node = ['n' => 'bin', 'op' => $t['type'], 'a' => $node, 'b' => $this->unary()];
            } elseif ($this->startsPrimary($t)) {
                $node = ['n' => 'bin', 'op' => '*', 'a' => $node, 'b' => $this->power()];
            } else {
                return $node;
            }
        }
    }

    private function unary(): array
    {
        $t = $this->peek();
        if ($t && ($t['type'] === '-' || $t['type'] === '+')) {
            $this->next();
            $this->enter();
            $a = $this->unary();
            $this->depth--;

            return $t['type'] === '-' ? ['n' => 'neg', 'a' => $a] : $a;
        }

        return $this->power();
    }

    private function power(): array
    {
        $base = $this->primary();
        if (($t = $this->peek()) && $t['type'] === '^') {
            $this->next();
            $this->enter();
            $exp = $this->unary();
            $this->depth--;

            return ['n' => 'bin', 'op' => '^', 'a' => $base, 'b' => $exp];
        }

        return $base;
    }

    private function primary(): array
    {
        $t = $this->next();
        if (! $t) {
            throw new \InvalidArgumentException('Ifoda chala tugagan');
        }
        if ($t['type'] === 'num') {
            return ['n' => 'num', 'v' => $t['value']];
        }
        if ($t['type'] === '(') {
            $node = $this->expr();
            $this->expect(')');

            return $node;
        }
        if ($t['type'] === '|') {
            $this->absDepth++;
            $node = $this->expr();
            $this->absDepth--;
            $this->expect('|');

            return ['n' => 'fn', 'f' => 'abs', 'args' => [$node]];
        }
        if ($t['type'] === 'id') {
            $name = $t['value'];
            if (in_array($name, self::VARIABLES, true)) {
                return ['n' => 'var'];
            }
            if (array_key_exists($name, self::CONSTANTS)) {
                return ['n' => 'num', 'v' => self::CONSTANTS[$name]];
            }
            $isOne = in_array($name, self::FUNCTIONS_1, true);
            if ($isOne || in_array($name, self::FUNCTIONS_2, true)) {
                $arity = $isOne ? 1 : 2;
                if (($this->peek()['type'] ?? null) !== '(') {
                    throw new \InvalidArgumentException("{$name} dan keyin qavs kerak: {$name}(x)");
                }
                $this->next();
                $args = [$this->expr()];
                while (($this->peek()['type'] ?? null) === ',') {
                    $this->next();
                    $args[] = $this->expr();
                }
                $this->expect(')');
                if (count($args) !== $arity) {
                    throw new \InvalidArgumentException("{$name} {$arity} ta argument oladi");
                }

                return ['n' => 'fn', 'f' => $name, 'args' => $args];
            }
        }

        throw new \InvalidArgumentException('Kutilmagan belgi: "'.($t['value'] ?? $t['type']).'"');
    }
}
