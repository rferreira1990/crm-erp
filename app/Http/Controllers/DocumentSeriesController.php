<?php

namespace App\Http\Controllers;

use App\Models\DocumentSeries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentSeriesController extends Controller
{
    public function index()
    {
        $series = DocumentSeries::where('owner_id', Auth::id())
            ->orderByDesc('year')
            ->get();

        return view('document-series.index', compact('series'));
    }

    public function create()
    {
        return view('document-series.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_type' => 'required|string',
            'prefix' => 'required|string|max:10',
            'name' => 'required|string|max:20',
            'year' => 'required|integer',
            'is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data) {

            if (!empty($data['is_active'])) {
                DocumentSeries::where('owner_id', Auth::id())
                    ->where('document_type', $data['document_type'])
                    ->update(['is_active' => false]);
            }

            DocumentSeries::create([
                ...$data,
                'owner_id' => Auth::id(),
                'next_number' => 1,
                'is_active' => $data['is_active'] ?? false,
            ]);
        });

        return redirect()
            ->route('document-series.index')
            ->with('success', 'Série criada com sucesso.');
    }

    public function edit(DocumentSeries $documentSeries)
    {
        return view('document-series.edit', compact('documentSeries'));
    }

    public function update(Request $request, DocumentSeries $documentSeries)
    {
        $data = $request->validate([
            'prefix' => 'required|string|max:10',
            'name' => 'required|string|max:20',
            'year' => 'required|integer',
            'is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data, $documentSeries) {

            if (!empty($data['is_active'])) {
                DocumentSeries::where('owner_id', Auth::id())
                    ->where('document_type', $documentSeries->document_type)
                    ->update(['is_active' => false]);
            }

            $documentSeries->update([
                ...$data,
                'is_active' => $data['is_active'] ?? false,
            ]);
        });

        return redirect()
            ->route('document-series.index')
            ->with('success', 'Série atualizada com sucesso.');
    }
}
