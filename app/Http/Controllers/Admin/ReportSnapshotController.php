<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\ReportSnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportSnapshotController extends Controller
{
    public function __construct(private readonly ReportSnapshotService $snapshots) {}

    public function index(Request $request): View
    {
        return view('admin.actions.report-snapshots', ['settings' => $this->snapshots->settings(), 'snapshots' => $this->snapshots->snapshots(), 'nextRun' => $this->snapshots->nextRun(), 'tab' => in_array($request->query('tab'), ['overview', 'snapshots', 'schedule'], true) ? $request->query('tab') : 'overview', 'lastError' => AppSetting::valueFor('report_snapshots_last_error')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['frequency' => ['required', 'in:daily,weekly,monthly'], 'time' => ['required', 'date_format:H:i'], 'weekday' => ['required', 'integer', 'between:0,6'], 'month_day' => ['required', 'integer', 'between:1,28'], 'keep' => ['required', 'integer', 'between:1,100']]);
        AppSetting::putMany(['report_snapshots_enabled' => $request->boolean('enabled') ? '1' : '0', 'report_snapshots_frequency' => $data['frequency'], 'report_snapshots_time' => $data['time'], 'report_snapshots_weekday' => (string) $data['weekday'], 'report_snapshots_month_day' => (string) $data['month_day'], 'report_snapshots_keep' => (string) $data['keep']]);
        return redirect()->route('admin.report-snapshots.index', ['tab' => 'schedule'])->with('status', 'Report snapshot settings saved.');
    }

    public function store(): RedirectResponse
    {
        $this->snapshots->create('manual');
        return redirect()->route('admin.report-snapshots.index')->with('status', 'Report snapshot created.');
    }

    public function download(string $snapshot, string $file)
    {
        return response()->download($this->snapshots->file($snapshot, $file));
    }

    public function downloadZip(string $snapshot)
    {
        return response()->download($this->snapshots->zip($snapshot), 'landpay-'.$snapshot.'.zip')->deleteFileAfterSend();
    }
}
