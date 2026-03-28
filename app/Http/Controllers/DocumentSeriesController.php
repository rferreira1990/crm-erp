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
        $this->authorize('viewAny', DocumentSeries::class);

        $series = DocumentSeries::query()
            ->orderByDesc('year')
            ->get();

        return view('document-series.index', compact('series'));
    }

    public function create()
    {
        $this->authorize('create', DocumentSeries::class);

        return view('document-series.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', DocumentSeries::class);

        $data = $request->validate([
            'document_type' => 'required|string',
            'prefix' => 'required|string|max:10',
            'name' => 'required|string|max:20',
            'year' => 'required|integer',
            'is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data) {
            if (! empty($data['is_active'])) {
                DocumentSeries::query()
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
        $this->authorize('update', $documentSeries);

        return view('document-series.edit', compact('documentSeries'));
    }

    public function update(Request $request, DocumentSeries $documentSeries)
    {
        $this->authorize('update', $documentSeries);

        $data = $request->validate([
            'prefix' => 'required|string|max:10',
            'name' => 'required|string|max:20',
            'year' => 'required|integer',
            'is_active' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($data, $documentSeries) {
            if (! empty($data['is_active'])) {
                DocumentSeries::query()
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

    public function destroy(DocumentSeries $documentSeries)
    {
        $this->authorize('delete', $documentSeries);

        $hasBudgets = \App\Models\Budget::where('document_series_id', $documentSeries->id)->exists();

        if ($hasBudgets) {
            return redirect()
                ->route('document-series.index')
                ->with('error', 'Não é possível apagar uma série que já foi utilizada.');
        }

        $documentSeries->delete();

        return redirect()
            ->route('document-series.index')
            ->with('success', 'Série apagada com sucesso.');
    }
}
