@extends('admin.layout')
@section('title', isset($endpoint) ? 'Edit Endpoint' : 'Add Endpoint')
@section('heading', isset($endpoint) ? 'Edit Endpoint' : 'Add Endpoint')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2 py-3">
                <i class="bi bi-signpost-split"></i>
                {{ isset($endpoint) ? 'Edit' : 'New' }} API Endpoint
            </div>
            <div class="card-body p-4">
                <form method="POST"
                      action="{{ isset($endpoint) ? route('admin.endpoints.update', $endpoint) : route('admin.endpoints.store') }}">
                    @csrf
                    @if(isset($endpoint)) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Provider <span class="text-danger">*</span></label>
                            <select name="provider_id" class="form-select" required>
                                <option value="">Select provider...</option>
                                @foreach($providers as $p)
                                    <option value="{{ $p->id }}" {{ old('provider_id', $endpoint->provider_id ?? '') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Endpoint Name <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control"
                                   value="{{ old('name', $endpoint->name ?? '') }}"
                                   placeholder="e.g. Get Leads" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium">Method <span class="text-danger">*</span></label>
                            <select name="method" class="form-select" required>
                                @foreach(['GET','POST','PUT','PATCH','DELETE'] as $m)
                                    <option value="{{ $m }}" {{ old('method', $endpoint->method ?? 'GET') === $m ? 'selected' : '' }}>
                                        {{ $m }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-9">
                            <label class="form-label fw-medium">Endpoint Path <span class="text-danger">*</span></label>
                            <input name="endpoint" type="text" class="form-control"
                                   value="{{ old('endpoint', $endpoint->endpoint ?? '') }}"
                                   placeholder="/crm/v2/Foreign_Data" required>
                            <div class="form-text">Use {id} for path parameters, e.g. /crm/v2/Foreign_Data/{id}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Description</label>
                            <input name="description" type="text" class="form-control"
                                   value="{{ old('description', $endpoint->description ?? '') }}"
                                   placeholder="What does this endpoint do?">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Default Headers (JSON)</label>
                            <textarea name="default_headers" class="form-control json-editor" rows="4"
                                      placeholder='{"Content-Type": "application/json"}'>{{ old('default_headers', isset($endpoint) && $endpoint->default_headers ? json_encode($endpoint->default_headers, JSON_PRETTY_PRINT) : '') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Default Body (JSON)</label>
                            <textarea name="default_body" class="form-control json-editor" rows="4"
                                      placeholder='{"data": [{}]}'>{{ old('default_body', isset($endpoint) && $endpoint->default_body ? json_encode($endpoint->default_body, JSON_PRETTY_PRINT) : '') }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input name="is_active" type="hidden" value="0">
                                <input name="is_active" type="checkbox" class="form-check-input" value="1"
                                       {{ old('is_active', $endpoint->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ isset($endpoint) ? 'Update' : 'Create' }} Endpoint
                        </button>
                        <a href="{{ route('admin.endpoints.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
