<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\PlanClosing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VestingGuideService
{
    public const PREFIX = 'closing-defaults/vesting/';

    public function current(): ?array
    {
        return json_decode(AppSetting::valueFor('closing_vesting_guide', 'null'), true);
    }

    // Each closing keeps an immutable file reference. Replacing the default never changes that reference.
    public function assign(PlanClosing $closing): array
    {
        $guide = $this->current();
        app(ClosingWorkflowService::class)->require($guide && Storage::disk('local')->exists($guide['path']),
            'Upload a default vesting PDF in Settings > Closing defaults first.');
        $this->removeReferences($closing);
        $closing->documents()->create(['kind' => 'vesting', 'name' => $guide['name'], 'path' => $guide['path']]);

        return $guide;
    }

    public function ensureAssigned(PlanClosing $closing): void
    {
        if ($closing->status !== 'active' || $closing->documents()->where('kind', 'vesting')->exists() || ! $this->current()) {
            return;
        }
        DB::transaction(function () use ($closing) {
            $locked = PlanClosing::whereKey($closing->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'active' || $locked->documents()->where('kind', 'vesting')->exists()) {
                return;
            }
            $guide = $this->current();
            if (! $guide || ! Storage::disk('local')->exists($guide['path'])) {
                return;
            }
            $assigned = $this->assign($locked);
            $locked->version++;
            $locked->save();
            app(ClosingWorkflowService::class)->log($locked, 'Missing vesting guide assigned', $assigned);
        });
        $closing->refresh()->load('documents');
    }

    public function removeReferences(PlanClosing $closing): void
    {
        foreach ($closing->documents()->where('kind', 'vesting')->get() as $document) {
            $path = $document->path;
            $document->delete();
            if (! str_starts_with($path, self::PREFIX)) {
                DB::afterCommit(fn () => Storage::disk('local')->delete($path));
            }
        }
    }
}
