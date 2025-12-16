<?php

namespace App\Policies;

use App\Models\OcrJob;
use App\Models\User;

class OcrJobPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->can('view any ocr job') || $user->can('view department ocr job')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OcrJob $ocrJob): bool
    {
        if ($user->can('view any ocr job')) {
            return true;
        }

        if ($user->can('view department ocr job') && $user->departments->pluck('id')->contains($ocrJob->documentVersion->document->department_id)) {
            return true;
        }

        if ($user->can('view own ocr job') && $user->id === $ocrJob->documentVersion->document->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create ocr job');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OcrJob $ocrJob): bool
    {
        if ($user->cannot('update ocr job')) {
            return false;
        }

        if ($user->can('view any ocr job')) {
            return true;
        }

        if ($user->can('view department ocr job') && $user->departments->pluck('id')->contains($ocrJob->documentVersion->document->department_id)) {
            return true;
        }

        if ($user->can('view own ocr job') && $user->id === $ocrJob->documentVersion->document->created_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OcrJob $ocrJob): bool
    {
        if ($user->cannot('delete ocr job')) {
            return false;
        }

        if ($user->can('view any ocr job')) {
            return true;
        }

        if ($user->can('view department ocr job') && $user->departments->pluck('id')->contains($ocrJob->documentVersion->document->department_id)) {
            return true;
        }

        if ($user->can('view own ocr job') && $user->id === $ocrJob->documentVersion->document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OcrJob $ocrJob): bool
    {
        if ($user->cannot('restore ocr job')) {
            return false;
        }

        if ($user->can('view any ocr job')) {
            return true;
        }

        if ($user->can('view department ocr job') && $user->departments->pluck('id')->contains($ocrJob->documentVersion->document->department_id)) {
            return true;
        }

        if ($user->can('view own ocr job') && $user->id === $ocrJob->documentVersion->document->created_by) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OcrJob $ocrJob): bool
    {
        if ($user->cannot('forceDelete ocr job')) {
            return false;
        }

        if ($user->can('view any ocr job')) {
            return true;
        }

        if ($user->can('view department ocr job') && $user->departments->pluck('id')->contains($ocrJob->documentVersion->document->department_id)) {
            return true;
        }

        if ($user->can('view own ocr job') && $user->id === $ocrJob->documentVersion->document->created_by) {
            return true;
        }
        return false;
    }
}
