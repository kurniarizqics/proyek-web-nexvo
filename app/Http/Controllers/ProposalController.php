<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProposalController extends Controller
{
    // Menampilkan daftar pengajuan dana
    public function index()
    {
        $user = Auth::user();
        
        // Koordiv hanya melihat pengajuannya sendiri; role lain melihat semua pengajuan
        $proposals = ($user->role === 'koordiv')
            ? Proposal::where('user_id', $user->id)->latest()->get()
            : Proposal::with('user')->latest()->get();

        return view('proposals.index', compact('proposals'));
    }

    // Form pengajuan dana baru (Khusus Koordiv)
    public function create()
    {
        return view('proposals.create');
    }

    // Menyimpan pengajuan dana baru ke database
    public function store(Request $request)
    {
        $request->validate([
            'title'  => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string',
        ]);

        Proposal::create([
            'user_id' => Auth::id(),
            'title'   => $request->title,
            'amount'  => $request->amount,
            'reason'  => $request->reason,
            'status'  => 'pending',
        ]);

        return redirect()->route('proposals.index')
                         ->with('success', 'Pengajuan dana berhasil dikirim.');
    }

    // Mengubah status pengajuan (Approved / Rejected oleh Ketua atau Bendahara)
    public function updateStatus(Request $request, Proposal $proposal)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $proposal->update([
            'status' => $request->status,
        ]);

        return redirect()->route('proposals.index')
                         ->with('success', 'Status pengajuan dana berhasil diperbarui.');
    }
}