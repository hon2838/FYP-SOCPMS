<?php

namespace App\Http\Controllers;

use App\Models\Paperwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaperworkController extends Controller
{
    public function index()
    {
        $paperworks = Paperwork::with('user')->latest()->paginate(10);
        return view('paperworks.index', compact('paperworks'));
    }

    public function create()
    {
        return view('paperworks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ppw_type' => 'required|string',
            'session' => 'required|string',
            'project_name' => 'required|string',
            'objective' => 'required|string',
            'purpose' => 'required|string',
            'background' => 'required|string',
            'aim' => 'required|string',
            'startdate' => 'required|date',
            'end_date' => 'required|date',
            'pgrm_involve' => 'required|integer',
            'external_sponsor' => 'required|integer'
        ]);

        $paperwork = new Paperwork($validated);
        $paperwork->user_id = Auth::id();
        $paperwork->save();

        return redirect()->route('paperworks.index')
            ->with('success', 'Paperwork created successfully.');
    }

    public function show(Paperwork $paperwork)
    {
        return view('paperworks.show', compact('paperwork'));
    }

    public function approve(Request $request, $id)
    {
        $paperwork = Paperwork::findOrFail($id);
        $paperwork->status = 1;
        $paperwork->save();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Paperwork approved successfully');
    }

    public function adminDashboard()
    {
        $paperworks = Paperwork::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.dashboard', compact('paperworks'));
    }

    public function userDashboard()
    {
        $paperworks = Paperwork::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('user.dashboard', compact('paperworks'));
    }
}
