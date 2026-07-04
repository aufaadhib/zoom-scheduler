<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Show the settings page with a specific active tab.
     */
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'integrations');
        return view('settings.index', compact('tab'));
    }
}
