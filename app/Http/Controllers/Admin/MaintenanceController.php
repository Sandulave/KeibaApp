<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function edit(): View
    {
        return view('admin.maintenance', [
            'enabled' => AppSetting::maintenanceEnabled(),
            'message' => AppSetting::maintenanceMessage(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:255'],
        ]);

        AppSetting::query()->updateOrCreate([
            'key' => 'maintenance_enabled',
        ], [
            'value' => $validated['enabled'] ? '1' : '0',
        ]);

        AppSetting::query()->updateOrCreate([
            'key' => 'maintenance_message',
        ], [
            'value' => $validated['message'] ?: null,
        ]);

        return redirect()->route('admin.maintenance.edit')->with('status', 'maintenance-updated');
    }
}
