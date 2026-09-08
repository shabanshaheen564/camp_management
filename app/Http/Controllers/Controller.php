<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use App\Models\Guardian;

abstract class Controller
{
    protected function denyCampAccess(bool $expectsJson = false): never
    {
        if ($expectsJson) {
            // The operation is denied, but the API returns a normal JSON response
            // so mobile clients can show the Arabic message instead of a 403 screen.
            abort(response()->json([
                'authorized' => false,
                'message' => 'غير مصرح لك بالوصول إلى هذا المخيم',
            ], 200));
        }

        // Web users should get a normal application message, not Laravel's 403 page.
        abort(
            redirect()->back()->with(
                'error',
                'غير مصرح لك بالوصول إلى هذا المخيم'
            )
        );
    }

    protected function authorizeCampAccess(int $campId, bool $expectsJson = false): void
    {
        if (!auth()->user()->canAccessCamp($campId)) {
            $this->denyCampAccess($expectsJson);
        }
    }

    protected function authorizeGuardianAccess(Guardian $guardian, bool $expectsJson = false): void
    {
        $this->authorizeCampAccess((int) $guardian->camp_id, $expectsJson);
    }

    protected function authorizeFamilyMemberAccess(FamilyMember $member, bool $expectsJson = false): void
    {
        $guardian = $member->guardian ?? Guardian::find($member->guardian_id);

        if (!$guardian) {
            $this->denyCampAccess($expectsJson);
        }

        $this->authorizeGuardianAccess($guardian, $expectsJson);
    }

    /**
     * @return array{first_name: string, second_name: string, third_name: string, family_name: string}
     */
    protected function parseFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = array_shift($parts) ?? '';
        $familyName = count($parts) > 0 ? (string) array_pop($parts) : '';
        $secondName = $parts[0] ?? '';
        $thirdName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        return [
            'first_name'  => $firstName,
            'second_name' => $secondName,
            'third_name'  => $thirdName,
            'family_name' => $familyName,
        ];
    }

    protected function markNotificationsRead(string $section): void
    {
        if (!auth()->check()) {
            return;
        }

        app(\App\Services\NotificationCenter::class)->markSectionRead(auth()->user(), $section);
    }

    protected function notifyAdmins(\Illuminate\Notifications\Notification $notification): void
    {
        app(\App\Services\NotificationCenter::class)->notifyAdmins($notification);
    }
}
