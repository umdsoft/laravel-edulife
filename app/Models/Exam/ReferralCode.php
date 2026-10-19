<?php

namespace App\Models\Exam;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Personal invite code of a user (one per user), used in
 * t.me/<bot>?start=ref_<code> links.
 */
class ReferralCode extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'referral_codes';

    protected $fillable = [
        'owner_id',
        'code',
        'uses_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'uses_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ReferralCode $code) {
            $code->code ??= self::generateUniqueCode();
            $code->code = strtoupper($code->code);
        });
    }

    /** Unambiguous alphabet: no 0/O, 1/I/L. */
    public static function generateUniqueCode(int $length = 8): string
    {
        $alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class, 'referral_code_id');
    }

    public function startPayload(): string
    {
        return 'ref_'.Str::upper($this->code);
    }
}
