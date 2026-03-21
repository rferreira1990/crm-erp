<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title mb-0">Anexos do artigo</h3>
    </div>

    <div class="card-body">
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        @endif

        <form action="{{ route('items.files.store', $item) }}" method="POST" enctype="multipart/form-data" class="mb-4">
            @csrf

            <div class="row">
                <div class="col-md-9 mb-3">
                    <label for="files" class="form-label">Carregar imagens e PDF</label>
                    <input
                        type="file"
                        name="files[]"
                        id="files"
                        class="form-control @error('files') is-invalid @enderror @error('files.*') is-invalid @enderror"
                        multiple
                        accept=".jpg,.jpeg,.png,.webp,.pdf"
                    >

                    <div class="form-text">
                        Permitidos: JPG, JPEG, PNG, WEBP e PDF. Máximo 10 ficheiros por envio e 10 MB por ficheiro.
                    </div>

                    @error('files')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror

                    @error('files.*')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        Carregar ficheiros
                    </button>
                </div>
            </div>
        </form>

        <hr>

        <h4 class="mb-3">Imagens</h4>

        @if ($item->images->count())
            <div class="row">
                @foreach ($item->images as $image)
                    <div class="col-md-3 mb-4">
                        <div class="card h-100">
                            <a href="{{ $image->url }}" target="_blank" rel="noopener">
                                <img
                                    src="{{ $image->thumb_url }}"
                                    alt="{{ $image->original_name }}"
                                    class="card-img-top"
                                    style="height: 180px; object-fit: cover;"
                                    loading="lazy"
                                >
                            </a>

                            <div class="card-body">
                                <div class="small text-muted mb-2 text-break">
                                    {{ $image->original_name }}
                                </div>

                                <div class="small text-muted mb-2">
                                    {{ $image->readable_size }}
                                </div>

                                @if ($image->is_primary)
                                    <span class="badge bg-success mb-2">Imagem principal</span>
                                @endif
                            </div>

                            <div class="card-footer bg-white">
                                <div class="d-grid gap-2">
                                    <a href="{{ $image->url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                        Ver imagem
                                    </a>

                                    @if (! $image->is_primary)
                                        <form action="{{ route('items.files.primary', [$item, $image]) }}" method="POST">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                                Definir como principal
                                            </button>
                                        </form>
                                    @endif

                                    <form action="{{ route('items.files.destroy', [$item, $image]) }}" method="POST" onsubmit="return confirm('Remover este ficheiro?');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                            Apagar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted mb-4">Ainda não existem imagens associadas a este artigo.</p>
        @endif

        <hr>

        <h4 class="mb-3">Documentos PDF</h4>

        @if ($item->documents->count())
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Tamanho</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($item->documents as $document)
                            <tr>
                                <td>{{ $document->original_name }}</td>
                                <td>PDF</td>
                                <td>{{ $document->readable_size }}</td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="{{ $document->url }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                            Abrir
                                        </a>

                                        <form action="{{ route('items.files.destroy', [$item, $document]) }}" method="POST" onsubmit="return confirm('Remover este ficheiro?');">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Apagar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">Ainda não existem documentos PDF associados a este artigo.</p>
        @endif
    </div>
</div>
