<?php

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Notifications\FamilyDeletedNotification;
use App\Notifications\FamilyForceDeletedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class FamilyDeletionController extends Controller
{
    public function destroy(Guardian $family): RedirectResponse
    {
        $this->authorizeGuardianAccess($family);

        $familyName = $family->full_name;
        $camp = $family->camp;
        $campName = $camp?->name;
        $timestamp = now();

        DB::table('guardians')
            ->where('id', $family->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $timestamp]);

        DB::table('family_members')
            ->where('guardian_id', $family->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => $timestamp]);

        $isTrashed = DB::table('guardians')
            ->where('id', $family->id)
            ->whereNotNull('deleted_at')
            ->exists();

        if (!$isTrashed) {
            return back()->withErrors([
                'family' => 'تعذر نقل العائلة إلى سلة المحذوفات.',
            ]);
        }

        $camp?->updateOccupancy();

        // Keep the deployment path active on Render Free; deletion remains fully isolated from Eloquent transactions.
        app(\App\Services\NotificationCenter::class)->notifyAdmins(
            new FamilyDeletedNotification($familyName, $campName)
        );

        return back()->with('success', 'تم حذف العائلة وجميع أفرادها ونقلها إلى سلة المحذوفات');
    }

    public function forceDelete($id): RedirectResponse
    {
        $family = Guardian::onlyTrashed()->findOrFail($id);
        $this->authorizeGuardianAccess($family);

        $familyName = $family->full_name;
        $campName = $family->camp?->name;

        try {
            DB::table('family_aid_allocations')
                ->where('guardian_id', $family->id)
                ->delete();

            DB::table('family_members')
                ->where('guardian_id', $family->id)
                ->delete();

            DB::table('guardians')
                ->where('id', $family->id)
                ->whereNotNull('deleted_at')
                ->delete();
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'trash' => 'تعذر الحذف النهائي للعائلة بسبب سجلات مرتبطة بها في قاعدة البيانات.',
            ]);
        }

        app(\App\Services\NotificationCenter::class)->notifyAdmins(
            new FamilyForceDeletedNotification($familyName, $campName)
        );

        return back()->with('success', 'تم الحذف النهائي للعائلة وجميع أفرادها');
    }

    public function forceDeleteAll(): RedirectResponse
    {
        $user = auth()->user();

        $query = Guardian::onlyTrashed()->select('id');
        if (!$user->isAdmin()) {
            $query->where('camp_id', $user->camp_id);
        }

        $familyIds = $query->pluck('id')->all();
        $total = count($familyIds);

        if ($total === 0) {
            return back()->with('success', 'سلة المحذوفات فارغة بالفعل');
        }

        $deletedCount = 0;
        $failedCount = 0;

        foreach (array_chunk($familyIds, 100) as $ids) {
            try {
                DB::table('family_aid_allocations')
                    ->whereIn('guardian_id', $ids)
                    ->delete();

                DB::table('family_members')
                    ->whereIn('guardian_id', $ids)
                    ->delete();

                $deletedCount += DB::table('guardians')
                    ->whereIn('id', $ids)
                    ->whereNotNull('deleted_at')
                    ->delete();
            } catch (Throwable $e) {
                report($e);
                $failedCount += count($ids);
            }
        }

        if ($failedCount > 0) {
            return back()->withErrors([
                'trash' => "تم حذف {$deletedCount} عائلة، وتعذر حذف {$failedCount} عائلة نهائيًا بسبب سجلات مرتبطة بها.",
            ]);
        }

        return back()->with('success', "تم الحذف النهائي لـ {$deletedCount} عائلة وجميع أفرادها بنجاح");
    }
}
