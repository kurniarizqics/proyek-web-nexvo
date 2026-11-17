<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    // READ: Menampilkan daftar seluruh transaksi
    public function index()
    {
        $transactions = Transaction::with(['category', 'user'])->latest()->get();
        return view('transactions.index', compact('transactions'));
    }

    // CREATE (Form): Menampilkan form tambah transaksi baru
    public function create()
    {
        $categories = Category::all();
        return view('transactions.create', compact('categories'));
    }

    // CREATE (Proses): Menyimpan data transaksi baru ke database
    public function store(Request $request)
    {
        $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'amount'           => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'description'      => 'nullable|string',
        ]);

        Transaction::create([
            'category_id'      => $request->category_id,
            'user_id'          => Auth::id(), // Otomatis mengambil ID user yang sedang login
            'amount'           => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description'      => $request->description,
        ]);

        return redirect()->route('transactions.index')
                         ->with('success', 'Data transaksi berhasil ditambahkan.');
    }

    // UPDATE (Form): Menampilkan form edit transaksi
    public function edit(Transaction $transaction)
    {
        $categories = Category::all();
        return view('transactions.edit', compact('transaction', 'categories'));
    }

    // UPDATE (Proses): Memperbarui data transaksi di database
    public function update(Request $request, Transaction $transaction)
    {
        $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'amount'           => 'required|numeric|min:1',
            'transaction_date' => 'required|date',
            'description'      => 'nullable|string',
        ]);

        $transaction->update([
            'category_id'      => $request->category_id,
            'amount'           => $request->amount,
            'transaction_date' => $request->transaction_date,
            'description'      => $request->description,
        ]);

        return redirect()->route('transactions.index')
                         ->with('success', 'Data transaksi berhasil diperbarui.');
    }

    // DELETE: Menghapus data transaksi
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return redirect()->route('transactions.index')
                         ->with('success', 'Data transaksi berhasil dihapus.');
    }
}