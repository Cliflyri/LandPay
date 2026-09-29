<?php

namespace App\Services;

use App\Http\Controllers\Admin\ReportController;
use App\Models\AppSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;
use ZipArchive;

class ReportSnapshotService
{
    public function __construct(private readonly ReportController $reports) {}

    public function settings(): array
    {
        return [
            'enabled' => AppSetting::valueFor('report_snapshots_enabled', '0') === '1',
            'frequency' => AppSetting::valueFor('report_snapshots_frequency', 'daily'),
            'time' => AppSetting::valueFor('report_snapshots_time', '02:00'),
            'weekday' => (int) AppSetting::valueFor('report_snapshots_weekday', '1'),
            'month_day' => (int) AppSetting::valueFor('report_snapshots_month_day', '1'),
            'keep' => (int) AppSetting::valueFor('report_snapshots_keep', '5'),
        ];
    }

    public function snapshots(): array
    {
        if (!File::isDirectory($this->root())) return [];
        return collect(File::directories($this->root()))
            ->filter(fn ($path) => str_starts_with(basename($path), 'snapshot-'))
            ->map(function ($path): array {
                $manifest = json_decode((string) @file_get_contents($path.DIRECTORY_SEPARATOR.'manifest.json'), true) ?: [];
                $files = collect(File::files($path))->filter(fn ($file) => $file->getExtension() === 'csv')->map(fn ($file) => ['name' => $file->getFilename(), 'size' => $file->getSize()])->values()->all();
                return ['id' => basename($path), 'created_at' => $manifest['created_at'] ?? date(DATE_ATOM, File::lastModified($path)), 'trigger' => $manifest['trigger'] ?? 'scheduled', 'files' => $files, 'size' => collect($files)->sum('size')];
            })->sortByDesc('created_at')->values()->all();
    }

    public function create(string $trigger = 'scheduled'): array
    {
        File::ensureDirectoryExists($this->root());
        $id = 'snapshot-'.now()->format('Ymd-His');
        $temporary = $this->root().DIRECTORY_SEPARATOR.'.'.$id.'.partial';
        $final = $this->root().DIRECTORY_SEPARATOR.$id;
        File::ensureDirectoryExists($temporary);
        try {
            $rows = [];
            foreach (ReportController::REPORTS as $report) $rows[$report] = $this->reports->writeCsv($report, $temporary.DIRECTORY_SEPARATOR.$report.'.csv');
            $manifest = ['created_at' => now()->toAtomString(), 'trigger' => $trigger, 'reports' => $rows];
            File::put($temporary.DIRECTORY_SEPARATOR.'manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            File::moveDirectory($temporary, $final);
            AppSetting::putMany(['report_snapshots_last_success' => $manifest['created_at'], 'report_snapshots_last_error' => '']);
            $this->prune();
            return $manifest + ['id' => $id];
        } catch (Throwable $e) {
            File::deleteDirectory($temporary);
            AppSetting::putMany(['report_snapshots_last_error' => now()->toAtomString().' — '.$e->getMessage()]);
            throw $e;
        }
    }

    public function isDue(?Carbon $now = null): bool
    {
        $settings = $this->settings();
        if (!$settings['enabled']) return false;
        $now ??= now();
        $due = $now->copy()->setTimeFromTimeString($settings['time']);
        if ($settings['frequency'] === 'weekly') $due->startOfWeek(Carbon::SUNDAY)->addDays($settings['weekday'])->setTimeFromTimeString($settings['time']);
        if ($settings['frequency'] === 'monthly') $due->startOfMonth()->addDays($settings['month_day'] - 1)->setTimeFromTimeString($settings['time']);
        $last = AppSetting::valueFor('report_snapshots_last_success');
        return $now->greaterThanOrEqualTo($due) && (!$last || Carbon::parse($last)->lessThan($due));
    }

    public function nextRun(): ?Carbon
    {
        $settings = $this->settings();
        if (!$settings['enabled']) return null;
        $now = now();
        $next = $now->copy()->setTimeFromTimeString($settings['time']);
        if ($settings['frequency'] === 'weekly') $next->startOfWeek(Carbon::SUNDAY)->addDays($settings['weekday'])->setTimeFromTimeString($settings['time']);
        if ($settings['frequency'] === 'monthly') $next->startOfMonth()->addDays($settings['month_day'] - 1)->setTimeFromTimeString($settings['time']);
        if ($next->lessThanOrEqualTo($now)) $next->{$settings['frequency'] === 'daily' ? 'addDay' : ($settings['frequency'] === 'weekly' ? 'addWeek' : 'addMonth')}();
        return $next;
    }

    public function file(string $snapshot, string $file): string
    {
        abort_unless(preg_match('/^snapshot-\d{8}-\d{6}$/', $snapshot) && preg_match('/^[a-z-]+\.csv$/', $file), 404);
        $path = $this->root().DIRECTORY_SEPARATOR.$snapshot.DIRECTORY_SEPARATOR.$file;
        abort_unless(File::isFile($path), 404);
        return $path;
    }

    public function zip(string $snapshot): string
    {
        abort_unless(preg_match('/^snapshot-\d{8}-\d{6}$/', $snapshot), 404);
        $directory = $this->root().DIRECTORY_SEPARATOR.$snapshot;
        abort_unless(File::isDirectory($directory), 404);
        $path = storage_path('app/'.$snapshot.'.zip');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Unable to create snapshot ZIP.');
        foreach (File::files($directory) as $file) $zip->addFile($file->getPathname(), $file->getFilename());
        $zip->close();
        return $path;
    }

    private function prune(): void
    {
        foreach (array_slice($this->snapshots(), max(1, $this->settings()['keep'])) as $snapshot) File::deleteDirectory($this->root().DIRECTORY_SEPARATOR.$snapshot['id']);
    }

    private function root(): string
    {
        return (string) (config('landpay.report_snapshots_path') ?: base_path('saved-reports'));
    }
}
