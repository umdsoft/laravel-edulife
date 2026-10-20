<?php

namespace App\Services\Exam;

/**
 * Public names on exam leaderboards (spec section 11: a real name only with
 * consent). With consent: "Ali V."; otherwise the student's own alias, or a
 * nickname derived from the user id, so it is stable across weeks and pages
 * but reveals nothing about the child.
 */
class LeaderboardNames
{
    private const ADJECTIVES = [
        'Chaqqon', 'Dono', 'Zukko', 'Mard', 'Tezkor', 'Sabrli', 'Bilimdon', 'Yorqin',
        'Quvnoq', 'Jasur', 'Ziyrak', 'Hushyor', 'Kuchli', 'Mohir', 'Aqlli', 'Sokin',
    ];

    private const NOUNS = [
        'Burgut', 'Lochin', 'Yo\'lbars', 'Arslon', 'Bulut', 'Yulduz', 'Shunqor', 'Qoplon',
        'Tulpor', 'Kamalak', 'Chaqmoq', 'Sher', 'Oqqush', 'Kit', 'Delfin', 'Sayyora',
    ];

    public static function nickname(string $userId): string
    {
        $hash = crc32('exam-nick:'.$userId);
        $adjective = self::ADJECTIVES[$hash % count(self::ADJECTIVES)];
        $noun = self::NOUNS[intdiv($hash, count(self::ADJECTIVES)) % count(self::NOUNS)];
        $number = 10 + intdiv($hash, count(self::ADJECTIVES) * count(self::NOUNS)) % 90;

        return "{$adjective} {$noun} {$number}";
    }

    /**
     * @param  array{first_name?: string|null, last_name?: string|null}  $user
     */
    public static function display(string $userId, array $user, bool $consent, ?string $alias): string
    {
        if ($consent) {
            $first = trim((string) ($user['first_name'] ?? ''));
            $last = trim((string) ($user['last_name'] ?? ''));
            if ($first !== '') {
                return $last !== '' ? $first.' '.mb_substr($last, 0, 1).'.' : $first;
            }
        }

        $alias = trim((string) $alias);

        return $alias !== '' ? $alias : self::nickname($userId);
    }
}
