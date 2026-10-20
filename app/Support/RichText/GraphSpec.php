<?php

namespace App\Support\RichText;

/**
 * Server-side validator of ```graph blocks (spec v1). PHP twin of
 * validateGraphSpec() in resources/js/Utils/graph/spec.js — same keys,
 * limits and (Uzbek) messages. A spec that passes here renders on the
 * client; one that fails is refused on save (and AI drafts are rejected).
 *
 * Spec reference for teachers: docs/exam/RASM_VA_GRAFIK.md.
 */
final class GraphSpec
{
    public const COLORS = ['blue', 'red', 'green', 'orange', 'purple', 'gray'];

    public const LIMITS = [
        'json' => 8000,
        'abs' => 1e6,
        'label' => 40,
        'functions' => 8,
        'polylines' => 10,
        'polylinePoints' => 200,
        'points' => 50,
        'segments' => 50,
        'vectors' => 30,
        'polygons' => 20,
        'polygonPoints' => 50,
        'circles' => 20,
        'labels' => 50,
        'angles' => 20,
        'ticksPerAxis' => 100,
    ];

    private const TOP_KEYS = ['x', 'y', 'grid', 'ticks', 'axes', 'equal', 'width', 'functions', 'polylines', 'points', 'segments', 'vectors', 'polygons', 'circles', 'labels', 'angles'];

    private const ITEM_KEYS = [
        'functions' => ['expr', 'domain', 'dashed', 'label', 'color'],
        'polylines' => ['points', 'dashed', 'label', 'color'],
        'points' => ['x', 'y', 'label', 'color', 'hollow'],
        'segments' => ['from', 'to', 'dashed', 'label', 'color'],
        'vectors' => ['from', 'to', 'label', 'color'],
        'polygons' => ['points', 'dashed', 'fill', 'label', 'color'],
        'circles' => ['center', 'r', 'dashed', 'fill', 'label', 'color'],
        'labels' => ['x', 'y', 'text', 'color'],
        'angles' => ['vertex', 'from', 'to', 'label', 'right', 'color'],
    ];

    private const ITEM_NAMES = [
        'functions' => 'Funksiya', 'polylines' => 'Siniq chiziq', 'points' => 'Nuqta', 'segments' => 'Kesma', 'vectors' => 'Vektor',
        'polygons' => "Ko'pburchak", 'circles' => 'Aylana', 'labels' => 'Yozuv', 'angles' => 'Burchak',
    ];

    /** Errors of the JSON text inside a ```graph block (empty = valid). */
    public static function validateJson(string $json): array
    {
        $json = trim($json);
        if (strlen($json) > self::LIMITS['json']) {
            return ['Grafik juda katta (ko\'pi bilan '.self::LIMITS['json'].' belgi)'];
        }
        try {
            $spec = json_decode($json, false, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ["Grafik JSON noto'g'ri yozilgan"];
        }

        return self::validate($spec);
    }

    /**
     * Errors of a decoded spec. Pass objects as decoded with json_decode(…, false)
     * so {} and [] stay distinguishable; arrays from json_decode(…, true) also work.
     */
    public static function validate(mixed $spec): array
    {
        $spec = self::toObjectTree($spec);
        if (! $spec instanceof \stdClass) {
            return ["Grafik obyekt ({…}) bo'lishi kerak"];
        }
        $errors = [];
        $s = (array) $spec;

        foreach (array_keys($s) as $key) {
            if (! in_array($key, self::TOP_KEYS, true)) {
                $errors[] = 'Noma\'lum kalit: "'.$key.'"';
            }
        }
        foreach (['x', 'y'] as $axis) {
            if (! self::isRange($s[$axis] ?? null)) {
                $errors[] = "\"{$axis}\" oralig'i [min, max] ko'rinishida, min < max bo'lsin";
            }
        }
        if (array_key_exists('grid', $s) && ! is_bool($s['grid'])) {
            $errors[] = '"grid" true yoki false bo\'lsin';
        }
        if (array_key_exists('equal', $s) && ! is_bool($s['equal'])) {
            $errors[] = '"equal" true yoki false bo\'lsin';
        }
        if (array_key_exists('ticks', $s)) {
            $t = $s['ticks'];
            $ok = (self::isNum($t) && $t > 0) || (self::isPair($t) && $t[0] > 0 && $t[1] > 0);
            if (! $ok) {
                $errors[] = '"ticks" musbat son yoki [x qadami, y qadami] bo\'lsin';
            } elseif (self::isRange($s['x'] ?? null) && self::isRange($s['y'] ?? null)) {
                [$tx, $ty] = is_array($t) ? $t : [$t, $t];
                if (($s['x'][1] - $s['x'][0]) / $tx > self::LIMITS['ticksPerAxis'] || ($s['y'][1] - $s['y'][0]) / $ty > self::LIMITS['ticksPerAxis']) {
                    $errors[] = '"ticks" juda kichik (bir o\'qda ko\'pi bilan '.self::LIMITS['ticksPerAxis'].' bo\'linma)';
                }
            }
        }
        if (array_key_exists('axes', $s)) {
            $a = $s['axes'];
            if (! $a instanceof \stdClass || array_diff(array_keys((array) $a), ['x', 'y'])) {
                $errors[] = '"axes" {"x": "…", "y": "…"} ko\'rinishida bo\'lsin';
            } else {
                foreach (['x', 'y'] as $k) {
                    if (property_exists($a, $k)) {
                        self::checkLabel($a->{$k}, "\"axes.{$k}\"", $errors);
                    }
                }
            }
        }
        if (array_key_exists('width', $s) && ! (self::isNum($s['width']) && $s['width'] >= 20 && $s['width'] <= 100)) {
            $errors[] = '"width" 20 dan 100 gacha foiz bo\'lsin';
        }

        foreach (self::ITEM_KEYS as $list => $keys) {
            if (! array_key_exists($list, $s)) {
                continue;
            }
            $items = $s[$list];
            if (! is_array($items)) {
                $errors[] = "\"{$list}\" ro'yxat ([…]) bo'lsin";

                continue;
            }
            if (count($items) > self::LIMITS[$list]) {
                $errors[] = "\"{$list}\": ko'pi bilan ".self::LIMITS[$list].' ta';
            }
            foreach (array_slice($items, 0, self::LIMITS[$list]) as $i => $item) {
                $where = self::ITEM_NAMES[$list].' '.($i + 1);
                if (! $item instanceof \stdClass) {
                    $errors[] = "{$where}: obyekt bo'lsin";

                    continue;
                }
                $item = (array) $item;
                foreach (array_keys($item) as $k) {
                    if (! in_array($k, $keys, true)) {
                        $errors[] = "{$where}: noma'lum kalit \"{$k}\"";
                    }
                }
                self::checkItem($list, $item, $where, $errors);
            }
        }

        return $errors;
    }

    /** Arrays from json_decode(…, true) → the same tree json_decode(…, false) gives. */
    private static function toObjectTree(mixed $v): mixed
    {
        if ($v instanceof \stdClass) {
            return $v;
        }
        if (is_array($v)) {
            return array_is_list($v) && $v !== []
                ? array_map([self::class, 'toObjectTree'], $v)
                : ($v === [] ? [] : (object) array_map([self::class, 'toObjectTree'], $v));
        }

        return $v;
    }

    private static function isNum(mixed $v): bool
    {
        return (is_int($v) || is_float($v)) && is_finite((float) $v) && abs($v) <= self::LIMITS['abs'];
    }

    private static function isPair(mixed $v): bool
    {
        return is_array($v) && array_is_list($v) && count($v) === 2 && self::isNum($v[0]) && self::isNum($v[1]);
    }

    private static function isRange(mixed $v): bool
    {
        return self::isPair($v) && $v[0] < $v[1];
    }

    private static function checkLabel(mixed $v, string $where, array &$errors, bool $required = false): void
    {
        if ($v === null) {
            if ($required) {
                $errors[] = "{$where}: matn kerak";
            }

            return;
        }
        if (! is_string($v)) {
            $errors[] = "{$where}: matn bo'lsin";
        } elseif (mb_strlen($v) > self::LIMITS['label']) {
            $errors[] = "{$where}: ko'pi bilan ".self::LIMITS['label'].' belgi';
        } elseif ($required && trim($v) === '') {
            $errors[] = "{$where}: matn kerak";
        }
    }

    private static function checkPoints(mixed $points, int $max, int $min, string $where, array &$errors): void
    {
        if (! is_array($points) || count($points) < $min) {
            $errors[] = "{$where}: kamida {$min} ta nuqta [[x, y], …] kerak";

            return;
        }
        if (count($points) > $max) {
            $errors[] = "{$where}: ko'pi bilan {$max} ta nuqta";
        }
        foreach ($points as $p) {
            if (! self::isPair($p)) {
                $errors[] = "{$where}: har nuqta [x, y] sonlar juftligi bo'lsin";

                return;
            }
        }
    }

    private static function checkItem(string $list, array $item, string $where, array &$errors): void
    {
        if (array_key_exists('color', $item) && ! in_array($item['color'], self::COLORS, true)) {
            $errors[] = "{$where}: rang ".implode(', ', self::COLORS).' dan biri bo\'lsin';
        }
        foreach (['dashed', 'fill', 'hollow', 'right'] as $flag) {
            if (array_key_exists($flag, $item) && ! is_bool($item[$flag])) {
                $errors[] = "{$where}: \"{$flag}\" true yoki false bo'lsin";
            }
        }
        if (array_key_exists('label', $item)) {
            self::checkLabel($item['label'], $where, $errors);
        }

        switch ($list) {
            case 'functions':
                $err = is_string($item['expr'] ?? null) ? GraphExpression::error($item['expr']) : 'ifoda (expr) kerak';
                if ($err) {
                    $errors[] = "{$where}: {$err}";
                }
                if (array_key_exists('domain', $item) && ! self::isRange($item['domain'])) {
                    $errors[] = "{$where}: \"domain\" [a, b], a < b bo'lsin";
                }
                break;
            case 'polylines':
                self::checkPoints($item['points'] ?? null, self::LIMITS['polylinePoints'], 2, $where, $errors);
                break;
            case 'polygons':
                self::checkPoints($item['points'] ?? null, self::LIMITS['polygonPoints'], 3, $where, $errors);
                break;
            case 'points':
            case 'labels':
                if (! self::isNum($item['x'] ?? null) || ! self::isNum($item['y'] ?? null)) {
                    $errors[] = "{$where}: \"x\" va \"y\" son bo'lsin";
                }
                if ($list === 'labels') {
                    self::checkLabel($item['text'] ?? null, $where, $errors, true);
                }
                break;
            case 'segments':
            case 'vectors':
                if (! self::isPair($item['from'] ?? null) || ! self::isPair($item['to'] ?? null)) {
                    $errors[] = "{$where}: \"from\" va \"to\" [x, y] bo'lsin";
                }
                break;
            case 'circles':
                if (! self::isPair($item['center'] ?? null)) {
                    $errors[] = "{$where}: \"center\" [x, y] bo'lsin";
                }
                if (! (self::isNum($item['r'] ?? null) && $item['r'] > 0)) {
                    $errors[] = "{$where}: radius \"r\" musbat son bo'lsin";
                }
                break;
            case 'angles':
                if (! self::isPair($item['vertex'] ?? null) || ! self::isPair($item['from'] ?? null) || ! self::isPair($item['to'] ?? null)) {
                    $errors[] = "{$where}: \"vertex\", \"from\", \"to\" [x, y] bo'lsin";
                }
                break;
        }
    }
}
