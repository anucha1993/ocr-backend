@extends('admin.layout')
@section('title', 'API Management')
@section('heading', 'API Management')

@section('content')
{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-value text-primary">{{ $providers->count() }}</div>
            <div class="stat-label">API Providers</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-value text-success">{{ $endpoints->count() }}</div>
            <div class="stat-label">Endpoints</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card">
            <div class="stat-value text-info">{{ $providers->where('is_active', true)->count() }}</div>
            <div class="stat-label">Active Providers</div>
        </div>
    </div>
</div>

{{-- Tabs --}}
<ul class="nav nav-tabs mb-0" id="mainTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="providers-tab" data-bs-toggle="tab" data-bs-target="#tab-providers" type="button" role="tab">
            <i class="bi bi-cloud me-1"></i> API Providers
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="endpoints-tab" data-bs-toggle="tab" data-bs-target="#tab-endpoints" type="button" role="tab">
            <i class="bi bi-signpost-split me-1"></i> Endpoints
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="test-tab" data-bs-toggle="tab" data-bs-target="#tab-test" type="button" role="tab">
            <i class="bi bi-send me-1"></i> Test API
        </button>
    </li>
</ul>

<div class="tab-content pt-4" id="mainTabsContent">
    {{-- ============================================================ --}}
    {{-- TAB 1 — API Providers --}}
    {{-- ============================================================ --}}
    <div class="tab-pane fade show active" id="tab-providers" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <p class="text-muted mb-0">Manage your external API provider connections</p>
            <a href="{{ route('admin.providers.create') }}" class="btn btn-primary btn-sm">
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
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="provider-icon {{ $provider->is_active ? 'bg-success bg-opacity-10' : 'bg-secondary bg-opacity-10' }}">
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

                                <div class="small text-muted mb-1">
                                    <i class="bi bi-link-45deg me-1"></i>{{ $provider->base_url }}
                                </div>
                                @if($provider->token_url)
                                    <div class="small text-muted mb-1">
                                        <i class="bi bi-key me-1"></i>{{ Str::limit($provider->token_url, 45) }}
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
    </div>

    {{-- ============================================================ --}}
    {{-- TAB 2 — Endpoints --}}
    {{-- ============================================================ --}}
    <div class="tab-pane fade" id="tab-endpoints" role="tabpanel">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <p class="text-muted mb-0">Define API endpoints for each provider</p>
            <a href="{{ route('admin.endpoints.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Add Endpoint
            </a>
        </div>

        {{-- Filter --}}
        <div class="card mb-4">
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-3">
                    <label class="form-label mb-0 text-muted small fw-medium">Filter:</label>
                    <select id="endpointProviderFilter" class="form-select form-select-sm" style="width: 250px;">
                        <option value="">All Providers</option>
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
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
                        <tbody id="endpointTableBody">
                            @foreach($endpoints as $ep)
                                <tr data-provider-id="{{ $ep->provider_id }}">
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
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $ep->provider->name }}</span>
                                    </td>
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
    </div>

    {{-- ============================================================ --}}
    {{-- TAB 3 — Test API --}}
    {{-- ============================================================ --}}
    <div class="tab-pane fade" id="tab-test" role="tabpanel">
        <div class="row g-4">
            {{-- Request Panel --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center gap-2 py-3">
                        <i class="bi bi-send"></i> Request
                    </div>
                    <div class="card-body p-4">
                        <form id="apiTestForm">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Provider <span class="text-danger">*</span></label>
                                    <select id="test_provider_id" class="form-select" required>
                                        <option value="">Select provider...</option>
                                        @foreach($activeProviders as $p)
                                            <option value="{{ $p->id }}" data-base-url="{{ $p->base_url }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Quick Fill from Endpoint</label>
                                    <select id="test_endpoint_select" class="form-select form-select-sm" disabled>
                                        <option value="">Select provider first...</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Method</label>
                                    <select id="test_method" class="form-select">
                                        <option value="GET">GET</option>
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="PATCH">PATCH</option>
                                        <option value="DELETE">DELETE</option>
                                    </select>
                                </div>
                                <div class="col-md-9">
                                    <label class="form-label fw-medium">URL Path <span class="text-danger">*</span></label>
                                    <input id="test_url" type="text" class="form-control" placeholder="/crm/v2/Foreign_Data" required>
                                    <div class="form-text" id="testUrlPreview"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Headers (JSON)</label>
                                    <textarea id="test_headers" class="form-control json-editor" rows="3"
                                              placeholder='{"Content-Type": "application/json"}'></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Body (JSON)</label>
                                    <textarea id="test_body" class="form-control json-editor" rows="6"
                                              placeholder='{"data": [{"Last_Name": "Test"}]}'></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3" id="sendBtn">
                                <i class="bi bi-send me-1"></i> Send Request
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Response Panel --}}
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between py-3">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-down-circle"></i> Response
                        </div>
                        <div id="responseMeta" class="d-flex gap-2" style="display: none !important;"></div>
                    </div>
                    <div class="card-body p-4">
                        <div id="responseEmpty" class="text-center py-5">
                            <i class="bi bi-arrow-left-circle text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-3 mb-0">Send a request to see the response here</p>
                        </div>
                        <div id="responseLoading" class="text-center py-5" style="display: none;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="text-muted mt-3 mb-0">Sending request...</p>
                        </div>
                        <div id="responseBody" style="display: none;">
                            <div class="response-area" id="responseContent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ── Hash-based tab activation ──
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        const tabBtn = document.querySelector(`[data-bs-target="#tab-${hash}"]`);
        if (tabBtn) new bootstrap.Tab(tabBtn).show();
    }
    document.querySelectorAll('#mainTabs button').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function(e) {
            const id = e.target.getAttribute('data-bs-target').replace('#tab-', '');
            history.replaceState(null, '', '#' + id);
            // Update sidebar active state
            document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
            const sidebarLink = document.querySelector(`.sidebar-nav [data-tab="${id}"]`);
            if (sidebarLink) sidebarLink.classList.add('active');
        });
    });

    // Set initial sidebar active from hash
    if (hash) {
        document.querySelectorAll('.sidebar-nav .nav-link').forEach(l => l.classList.remove('active'));
        const sidebarLink = document.querySelector(`.sidebar-nav [data-tab="${hash}"]`);
        if (sidebarLink) sidebarLink.classList.add('active');
    }

    // ── Endpoint filter (Tab 2) ──
    const epFilter = document.getElementById('endpointProviderFilter');
    if (epFilter) {
        epFilter.addEventListener('change', function() {
            const val = this.value;
            document.querySelectorAll('#endpointTableBody tr').forEach(row => {
                row.style.display = (!val || row.dataset.providerId === val) ? '' : 'none';
            });
        });
    }

    // ── Test API (Tab 3) ──
    const providerSelect = document.getElementById('test_provider_id');
    const endpointSelect = document.getElementById('test_endpoint_select');
    const methodSelect = document.getElementById('test_method');
    const urlInput = document.getElementById('test_url');
    const headersInput = document.getElementById('test_headers');
    const bodyInput = document.getElementById('test_body');
    const urlPreview = document.getElementById('testUrlPreview');

    providerSelect.addEventListener('change', function() {
        const pid = this.value;
        updateUrlPreview();
        if (!pid) {
            endpointSelect.innerHTML = '<option value="">Select provider first...</option>';
            endpointSelect.disabled = true;
            return;
        }
        endpointSelect.disabled = false;
        endpointSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`/admin/api-test/endpoints/${pid}`)
            .then(r => r.json())
            .then(eps => {
                let html = '<option value="">— Choose endpoint (optional) —</option>';
                eps.forEach(ep => {
                    html += `<option value="${ep.id}" data-method="${ep.method}" data-endpoint="${ep.endpoint}">${ep.method} — ${ep.name}</option>`;
                });
                endpointSelect.innerHTML = html;
            });
    });

    endpointSelect.addEventListener('change', function() {
        const opt = this.selectedOptions[0];
        if (!opt || !opt.dataset.method) return;
        methodSelect.value = opt.dataset.method;
        urlInput.value = opt.dataset.endpoint;
        updateUrlPreview();
    });

    urlInput.addEventListener('input', updateUrlPreview);
    function updateUrlPreview() {
        const baseUrl = providerSelect.selectedOptions[0]?.dataset?.baseUrl || '';
        const path = urlInput.value;
        urlPreview.textContent = (baseUrl && path)
            ? baseUrl.replace(/\/$/, '') + '/' + path.replace(/^\//, '')
            : '';
    }

    document.getElementById('apiTestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const pid = providerSelect.value;
        if (!pid) { alert('Select a provider'); return; }
        if (!urlInput.value) { alert('Enter a URL path'); return; }

        document.getElementById('responseEmpty').style.display = 'none';
        document.getElementById('responseBody').style.display = 'none';
        document.getElementById('responseLoading').style.display = 'block';
        document.getElementById('sendBtn').disabled = true;

        fetch('/admin/api-test/execute', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                provider_id: pid,
                method: methodSelect.value,
                url: urlInput.value,
                headers: headersInput.value || null,
                body: bodyInput.value || null,
            }),
        })
        .then(r => r.json())
        .then(result => {
            document.getElementById('responseLoading').style.display = 'none';
            document.getElementById('responseBody').style.display = 'block';
            document.getElementById('sendBtn').disabled = false;

            const meta = document.getElementById('responseMeta');
            meta.style.display = 'flex';
            let statusBadge = '';
            if (result.status) {
                const c = result.status < 300 ? 'success' : result.status < 400 ? 'warning' : 'danger';
                statusBadge = `<span class="badge bg-${c}">${result.status}</span>`;
            }
            const timeBadge = result.duration_ms ? `<span class="badge bg-secondary">${result.duration_ms}ms</span>` : '';
            meta.innerHTML = statusBadge + timeBadge;

            const data = result.data || result.error || result;
            document.getElementById('responseContent').textContent = JSON.stringify(data, null, 2);
        })
        .catch(err => {
            document.getElementById('responseLoading').style.display = 'none';
            document.getElementById('responseBody').style.display = 'block';
            document.getElementById('sendBtn').disabled = false;
            document.getElementById('responseContent').textContent = 'Error: ' + err.message;
        });
    });
});
</script>
@endpush
@endsection
