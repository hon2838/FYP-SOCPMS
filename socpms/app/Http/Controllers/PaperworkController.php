<?php

namespace App\Http\Controllers;

use App\Models\Paperwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PaperworkController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        Log::info('PaperworkController initialized', [
            'user_id' => Auth::id() ?? 'unauthenticated'
        ]);
    }

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

    public function approve(Paperwork $paperwork)
    {
        Log::info('Paperwork approval attempt', [
            'user_id' => Auth::id(),
            'paperwork_id' => $paperwork->id
        ]);

        try {
            if (!Gate::allows('admin-access')) {
                Log::warning('Unauthorized paperwork approval attempt', [
                    'user_id' => Auth::id(),
                    'paperwork_id' => $paperwork->id
                ]);
                abort(403);
            }

            $paperwork->status = 1;
            $paperwork->save();

            Log::info('Paperwork approved successfully', [
                'paperwork_id' => $paperwork->id,
                'approved_by' => Auth::id()
            ]);

            return redirect()->route('admin.dashboard')
                ->with('success', 'Paperwork approved successfully');
        } catch (\Exception $e) {
            Log::error('Error approving paperwork', [
                'paperwork_id' => $paperwork->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error approving paperwork');
        }
    }

    public function adminDashboard()
    {
        Log::info('Admin dashboard accessed', [
            'user_id' => Auth::id(),
            'user_type' => Auth::user()->user_type
        ]);

        if (!Gate::allows('admin-access')) {
            Log::warning('Unauthorized admin dashboard access attempt', [
                'user_id' => Auth::id(),
                'user_type' => Auth::user()->user_type
            ]);
            abort(403);
        }

        try {
            $paperworks = Paperwork::with('user')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            Log::info('Admin dashboard paperworks loaded', [
                'count' => $paperworks->count(),
                'total' => $paperworks->total()
            ]);

            return view('admin.dashboard', compact('paperworks'));
        } catch (\Exception $e) {
            Log::error('Error loading admin dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Error loading dashboard data');
        }
    }

    public function userDashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!Gate::allows('user-access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $paperworks = Paperwork::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return view('user.dashboard', compact('paperworks'));
        } catch (\Exception $e) {
            Log::error('Error in user dashboard', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Unable to load dashboard');
        }
    }
}
