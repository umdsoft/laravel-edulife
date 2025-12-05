<?php

namespace App\Services;

use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadDeviceLock;
use App\Models\SecurityViolation;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\ViolationAppeal;
use Illuminate\Support\Facades\Log;

class AntiCheatService
{
    /**
     * Register/update device fingerprint
     */
    public function registerDevice(string $userId, array $fingerprintData): UserDevice
    {
        $fingerprint = $this->generateFingerprint($fingerprintData);

        $device = UserDevice::updateOrCreate(
            ['device_fingerprint' => $fingerprint],
            array_merge($fingerprintData, [
                'user_id' => $userId,
                'last_seen_at' => now(),
            ])
        );

        if (!$device->first_seen_at) {
            $device->first_seen_at = now();
            $device->save();
        }

        return $device;
    }

    /**
     * Generate device fingerprint from components
     */
    public function generateFingerprint(array $data): string
    {
        $components = [
            $data['canvas_fingerprint'] ?? '',
            $data['webgl_fingerprint'] ?? '',
            $data['audio_fingerprint'] ?? '',
            $data['fonts_hash'] ?? '',
            $data['screen_resolution'] ?? '',
            $data['timezone'] ?? '',
            $data['platform'] ?? '',
            $data['hardware_concurrency'] ?? '',
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Lock device for olympiad exam
     */
    public function lockDevice(string $olympiadId, string $userId, string $deviceFingerprint): OlympiadDeviceLock
    {
        $device = UserDevice::where('device_fingerprint', $deviceFingerprint)->first();

        if (!$device) {
            throw new \Exception('Device not registered');
        }

        if ($device->is_blocked) {
            throw new \Exception('Device is blocked: ' . $device->block_reason);
        }

        // Check for existing lock
        $existingLock = OlympiadDeviceLock::where('olympiad_id', $olympiadId)
            ->where('user_id', $userId)
            ->first();

        if ($existingLock) {
            if ($existingLock->device_id !== $device->id) {
                // Different device trying to access
                $this->recordViolation($olympiadId, $userId, null, $device->id, SecurityViolation::TYPE_MULTIPLE_DEVICE, [
                    'original_device_id' => $existingLock->device_id,
                    'new_device_fingerprint' => $deviceFingerprint,
                ]);

                throw new \Exception('Exam already started on another device');
            }

            return $existingLock;
        }

        return OlympiadDeviceLock::create([
            'olympiad_id' => $olympiadId,
            'user_id' => $userId,
            'device_id' => $device->id,
            'locked_at' => now(),
            'locked_ip' => request()->ip(),
            'lock_reason' => OlympiadDeviceLock::REASON_EXAM_START,
            'status' => OlympiadDeviceLock::STATUS_ACTIVE,
        ]);
    }

    /**
     * Verify device during exam
     */
    public function verifyDevice(OlympiadAttempt $attempt, string $deviceFingerprint): bool
    {
        $lock = $attempt->deviceLock;

        if (!$lock || !$lock->is_active) {
            return false;
        }

        return $lock->matchesDevice($deviceFingerprint);
    }

    /**
     * Record a security violation
     */
    public function recordViolation(
        string $olympiadId,
        string $userId,
        ?string $attemptId,
        ?string $deviceId,
        string $type,
        array $evidence = []
    ): SecurityViolation {
        // Check for existing violation of same type
        $existing = SecurityViolation::where('olympiad_id', $olympiadId)
            ->where('user_id', $userId)
            ->where('violation_type', $type)
            ->where('is_resolved', false)
            ->first();

        if ($existing) {
            $existing->incrementCount();
            $existing->evidence = array_merge($existing->evidence ?? [], $evidence);
            $existing->severity = SecurityViolation::determineSeverity($type, $existing->occurrence_count);
            $existing->save();

            $this->processViolation($existing);
            return $existing;
        }

        $violation = SecurityViolation::create([
            'olympiad_id' => $olympiadId,
            'user_id' => $userId,
            'attempt_id' => $attemptId,
            'device_id' => $deviceId,
            'violation_type' => $type,
            'occurrence_count' => 1,
            'severity' => SecurityViolation::determineSeverity($type, 1),
            'evidence' => $evidence,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $this->processViolation($violation);
        return $violation;
    }

    /**
     * Process violation and take action
     */
    private function processViolation(SecurityViolation $violation): void
    {
        $olympiad = Olympiad::find($violation->olympiad_id);
        $config = $olympiad->anti_cheat_config ?? [];

        $action = null;

        switch ($violation->violation_type) {
            case SecurityViolation::TYPE_MULTIPLE_DEVICE:
                // Always critical - immediate disqualification
                $action = SecurityViolation::ACTION_DISQUALIFIED;
                break;

            case SecurityViolation::TYPE_TAB_SWITCH:
                $maxAllowed = $config['max_tab_switches'] ?? 3;
                if ($violation->occurrence_count > $maxAllowed) {
                    $action = SecurityViolation::ACTION_DISQUALIFIED;
                } else {
                    $action = SecurityViolation::ACTION_WARNING_SENT;
                }
                break;

            case SecurityViolation::TYPE_FULLSCREEN_EXIT:
                $maxAllowed = $config['max_fullscreen_exits'] ?? 2;
                if ($violation->occurrence_count > $maxAllowed) {
                    $action = SecurityViolation::ACTION_DISQUALIFIED;
                } else {
                    $action = SecurityViolation::ACTION_WARNING_SENT;
                }
                break;

            case SecurityViolation::TYPE_VPN_DETECTED:
                if ($config['block_vpn'] ?? true) {
                    $action = SecurityViolation::ACTION_DISQUALIFIED;
                } else {
                    $action = SecurityViolation::ACTION_WARNING_SENT;
                }
                break;

            default:
                if ($violation->severity === SecurityViolation::SEVERITY_CRITICAL) {
                    $action = SecurityViolation::ACTION_DISQUALIFIED;
                } else {
                    $action = SecurityViolation::ACTION_WARNING_SENT;
                }
        }

        $violation->action_taken = $action;
        $violation->save();

        if ($action === SecurityViolation::ACTION_DISQUALIFIED) {
            $this->disqualifyUser($violation);
        }

        // Log for monitoring
        Log::warning('Security violation', [
            'violation_id' => $violation->id,
            'user_id' => $violation->user_id,
            'type' => $violation->violation_type,
            'action' => $action,
        ]);
    }

    /**
     * Disqualify user from olympiad
     */
    private function disqualifyUser(SecurityViolation $violation): void
    {
        $attempt = OlympiadAttempt::where('olympiad_id', $violation->olympiad_id)
            ->where('user_id', $violation->user_id)
            ->first();

        if ($attempt && !$attempt->is_disqualified) {
            $attempt->disqualify("Security violation: {$violation->violation_type}");
        }

        // Mark device lock as violated
        $lock = OlympiadDeviceLock::where('olympiad_id', $violation->olympiad_id)
            ->where('user_id', $violation->user_id)
            ->first();

        if ($lock) {
            $lock->markViolated();
        }
    }

    /**
     * Submit appeal for violation
     */
    public function submitAppeal(SecurityViolation $violation, string $reason, ?array $evidence = null): ViolationAppeal
    {
        if (!$violation->can_appeal) {
            throw new \Exception('Cannot submit appeal for this violation');
        }

        return ViolationAppeal::create([
            'violation_id' => $violation->id,
            'olympiad_id' => $violation->olympiad_id,
            'user_id' => $violation->user_id,
            'appeal_reason' => $reason,
            'supporting_evidence' => $evidence,
            'status' => ViolationAppeal::STATUS_PENDING,
            'submitted_at' => now(),
            'deadline_at' => now()->addHours(24),
        ]);
    }

    /**
     * Get active violations for monitoring
     */
    public function getActiveViolations(string $olympiadId): \Illuminate\Database\Eloquent\Collection
    {
        return SecurityViolation::where('olympiad_id', $olympiadId)
            ->where('is_resolved', false)
            ->with(['user', 'device'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Check heartbeat and detect missed heartbeats
     */
    public function checkHeartbeat(OlympiadAttempt $attempt, int $lastHeartbeat): bool
    {
        $now = now()->timestamp;
        $gap = $now - $lastHeartbeat;
        $maxGap = 30; // 30 seconds max between heartbeats

        if ($gap > $maxGap) {
            $this->recordViolation(
                $attempt->olympiad_id,
                $attempt->user_id,
                $attempt->id,
                null,
                SecurityViolation::TYPE_HEARTBEAT_MISS,
                ['gap_seconds' => $gap, 'last_heartbeat' => $lastHeartbeat]
            );
            return false;
        }

        return true;
    }

    /**
     * Detect suspicious answer patterns
     */
    public function detectSuspiciousPatterns(OlympiadAttempt $attempt): bool
    {
        $answers = $attempt->answers()->get();

        // Check for impossibly fast answers
        $fastAnswers = $answers->filter(fn($a) => $a->time_spent_seconds < 2)->count();
        if ($fastAnswers > 5) {
            $this->recordViolation(
                $attempt->olympiad_id,
                $attempt->user_id,
                $attempt->id,
                null,
                SecurityViolation::TYPE_SUSPICIOUS_PATTERN,
                ['fast_answers' => $fastAnswers, 'type' => 'impossibly_fast']
            );
            return true;
        }

        return false;
    }
}
