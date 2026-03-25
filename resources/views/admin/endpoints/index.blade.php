@extends('admin.layout')
@section('title', 'API Endpoints')
@section('heading', 'API Endpoints')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Define API endpoints for each provider</p>
    </div>
    <a href="{{ route('admin.endpoints.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Endpoint
    </a>
</div>

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="d-flex align-items-center gap-3">
            <label class="form-label mb-0 text-muted small fw-medium">Filter by Provider:</label>
            <select name="provider_id" class="form-select form-select-sm" style="width: 250px;" onchange="this.form.submit()">
                <option value="">All Providers</option>
                @foreach($providers as $p)
                    <option value="{{ $p->id }}" {{ $selectedProviderId == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

@if($endpoints->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-signpost-split text-muted" style="font-size: 3rem;"></i>
            <p class="text-muted mt-3 mb-0">No endpoints defined yet.</p>
            <a href="{{ route('admin.endpoints.create') }}" class="btn btn-primary mt-3">
                <i class="bi bi-plus-lg me-1"></i> Add Your First Endpoint
            </a>
        </div>
    </div>
@else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th>Name</th>
                        <th>Endpoint</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($endpoints as $ep)
                        <tr>
                            <td>
                                <span class="badge badge-method method-{{ $ep->method }} rounded-pill">{{ $ep->method }}</span>
                            </td>
                            <td class="fw-medium">{{ $ep->name }}</td>
                            <td>
                                <code class="text-muted small">{{ $ep->endpoint }}</code>
                                @if($ep->description)
                                    <br><small class="text-muted">{{ $ep->description }}</small>
                                @endif
                            </td>
                            <td><span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $ep->provider->name }}</span></td>
                            <td>
                                <span class="badge {{ $ep->is_active ? 'bg-success' : 'bg-secondary' }} bg-opacity-10 {{ $ep->is_active ? 'text-success' : 'text-secondary' }}">
                                    {{ $ep->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('admin.endpoints.edit', $ep) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.endpoints.destroy', $ep) }}"
                                          onsubmit="return confirm('Delete this endpoint?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
