@extends('admin.layout')
@section('title', 'Test API')
@section('heading', 'Test API')

@section('content')
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
                            <select id="provider_id" class="form-select" required>
                                <option value="">Select provider...</option>
                                @foreach($providers as $p)
                                    <option value="{{ $p->id }}" data-base-url="{{ $p->base_url }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Quick Fill from Endpoint</label>
                            <select id="endpoint_select" class="form-select form-select-sm" disabled>
                                <option value="">Select provider first...</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-medium">Method</label>
                            <select id="method" class="form-select">
                                <option value="GET">GET</option>
                                <option value="POST">POST</option>
                                <option value="PUT">PUT</option>
                                <option value="PATCH">PATCH</option>
                                <option value="DELETE">DELETE</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-medium">URL Path <span class="text-danger">*</span></label>
                            <input id="url" type="text" class="form-control" placeholder="/crm/v2/Foreign_Data" required>
                            <div class="form-text" id="fullUrlPreview"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Headers (JSON)</label>
                            <textarea id="headers" class="form-control json-editor" rows="3"
                                      placeholder='{"Content-Type": "application/json"}'></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-medium">Body (JSON)</label>
                            <textarea id="body" class="form-control json-editor" rows="6"
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('provider_id');
    const endpointSelect = document.getElementById('endpoint_select');
    const methodSelect = document.getElementById('method');
    const urlInput = document.getElementById('url');
    const headersInput = document.getElementById('headers');
    const bodyInput = document.getElementById('body');
    const fullUrlPreview = document.getElementById('fullUrlPreview');

    // Load endpoints when provider changes
    providerSelect.addEventListener('change', function() {
        const providerId = this.value;
        const baseUrl = this.selectedOptions[0]?.dataset?.baseUrl || '';
        updateUrlPreview();

        if (!providerId) {
            endpointSelect.innerHTML = '<option value="">Select provider first...</option>';
            endpointSelect.disabled = true;
            return;
        }

        endpointSelect.disabled = false;
        endpointSelect.innerHTML = '<option value="">Loading...</option>';

        fetch(`/admin/api-test/endpoints/${providerId}`)
            .then(r => r.json())
            .then(endpoints => {
                let html = '<option value="">— Choose endpoint (optional) —</option>';
                endpoints.forEach(ep => {
                    html += `<option value="${ep.id}" data-method="${ep.method}" data-endpoint="${ep.endpoint}">${ep.method} — ${ep.name}</option>`;
                });
                endpointSelect.innerHTML = html;
            });
    });

    // Fill form from endpoint
    endpointSelect.addEventListener('change', function() {
        const opt = this.selectedOptions[0];
        if (!opt || !opt.dataset.method) return;
        methodSelect.value = opt.dataset.method;
        urlInput.value = opt.dataset.endpoint;
        updateUrlPreview();
    });

    // URL preview
    urlInput.addEventListener('input', updateUrlPreview);
    function updateUrlPreview() {
        const baseUrl = providerSelect.selectedOptions[0]?.dataset?.baseUrl || '';
        const path = urlInput.value;
        if (baseUrl && path) {
            fullUrlPreview.textContent = baseUrl.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
        } else {
            fullUrlPreview.textContent = '';
        }
    }

    // Send request
    document.getElementById('apiTestForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const providerId = providerSelect.value;
        if (!providerId) { alert('Select a provider'); return; }
        if (!urlInput.value) { alert('Enter a URL path'); return; }

        document.getElementById('responseEmpty').style.display = 'none';
        document.getElementById('responseBody').style.display = 'none';
        document.getElementById('responseLoading').style.display = 'block';
        document.getElementById('sendBtn').disabled = true;

        const payload = {
            provider_id: providerId,
            method: methodSelect.value,
            url: urlInput.value,
            headers: headersInput.value || null,
            body: bodyInput.value || null,
        };

        fetch('/admin/api-test/execute', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(payload),
        })
        .then(r => r.json())
        .then(result => {
            document.getElementById('responseLoading').style.display = 'none';
            document.getElementById('responseBody').style.display = 'block';
            document.getElementById('sendBtn').disabled = false;

            // Meta info
            const meta = document.getElementById('responseMeta');
            meta.style.display = 'flex';
            let statusBadge = '';
            if (result.status) {
                const color = result.status < 300 ? 'success' : result.status < 400 ? 'warning' : 'danger';
                statusBadge = `<span class="badge bg-${color}">${result.status}</span>`;
            }
            const timeBadge = result.duration_ms ? `<span class="badge bg-secondary">${result.duration_ms}ms</span>` : '';
            meta.innerHTML = statusBadge + timeBadge;

            // Body
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
