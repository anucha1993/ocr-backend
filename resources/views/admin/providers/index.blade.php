@extends('admin.layout')
@section('title', 'API Providers')
@section('heading', 'API Providers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Manage your external API provider connections</p>
    </div>
    <a href="{{ route('admin.providers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Provider
    </a>
</div>

@if($providers->isEmpty())
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-cloud-slash text-muted" style="font-size: 3rem;"></i>
            <p class="text-muted mt-3 mb-0">No API providers configured yet.</p>
            <a href="{{ route('admin.providers.create') }}" class="btn btn-primary mt-3">
                <i class="bi bi-plus-lg me-1"></i> Add Your First Provider
            </a>
        </div>
    </div>
@else
    <div class="row g-4">
        @foreach($providers as $provider)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-2 d-flex align-items-center justify-content-center {{ $provider->is_active ? 'bg-success bg-opacity-10' : 'bg-secondary bg-opacity-10' }}" style="width:40px;height:40px;">
                                    <i class="bi bi-cloud {{ $provider->is_active ? 'text-success' : 'text-secondary' }}"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $provider->name }}</h6>
                                    <small class="text-muted">{{ $provider->slug }}</small>
                                </div>
                            </div>
                            <span class="badge {{ $provider->is_active ? 'bg-success' : 'bg-secondary' }} bg-opacity-10 {{ $provider->is_active ? 'text-success' : 'text-secondary' }}">
                                {{ $provider->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="small text-muted mb-2">
                            <i class="bi bi-link-45deg me-1"></i>{{ $provider->base_url }}
                        </div>

                        @if($provider->token_url)
                            <div class="small text-muted mb-2">
                                <i class="bi bi-key me-1"></i>{{ $provider->token_url }}
                            </div>
                        @endif

                        <div class="d-flex gap-2 mt-3 mb-3">
                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-signpost-split me-1"></i>{{ $provider->endpoints_count }} endpoints
                            </span>
                            @if($provider->token_expires_at)
                                <span class="badge {{ $provider->isTokenExpired() ? 'bg-danger' : 'bg-success' }} bg-opacity-10 {{ $provider->isTokenExpired() ? 'text-danger' : 'text-success' }}">
                                    <i class="bi bi-clock me-1"></i>Token {{ $provider->isTokenExpired() ? 'Expired' : 'Valid' }}
                                </span>
                            @endif
                        </div>

                        <hr>
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.providers.edit', $provider) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </a>
                            <form method="POST" action="{{ route('admin.providers.test', $provider) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-wifi me-1"></i>Test
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.providers.destroy', $provider) }}"
                                  onsubmit="return confirm('Delete this provider and all its endpoints?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection
