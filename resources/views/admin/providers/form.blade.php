@extends('admin.layout')
@section('title', isset($provider) ? 'Edit Provider' : 'Add Provider')
@section('heading', isset($provider) ? 'Edit Provider' : 'Add Provider')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2 py-3">
                <i class="bi bi-cloud"></i>
                {{ isset($provider) ? 'Edit' : 'New' }} API Provider
            </div>
            <div class="card-body p-4">
                <form method="POST"
                      action="{{ isset($provider) ? route('admin.providers.update', $provider) : route('admin.providers.store') }}">
                    @csrf
                    @if(isset($provider)) @method('PUT') @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Provider Name <span class="text-danger">*</span></label>
                            <input name="name" type="text" class="form-control"
                                   value="{{ old('name', $provider->name ?? '') }}"
                                   placeholder="e.g. Zoho CRM" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Base URL <span class="text-danger">*</span></label>
                            <input name="base_url" type="url" class="form-control"
                                   value="{{ old('base_url', $provider->base_url ?? '') }}"
                                   placeholder="https://www.zohoapis.com" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-medium">Token URL</label>
                            <input name="token_url" type="url" class="form-control"
                                   value="{{ old('token_url', $provider->token_url ?? '') }}"
                                   placeholder="https://accounts.zoho.com/oauth/v2/token">
                            <div class="form-text">OAuth2 token endpoint for automatic refresh.</div>
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-md-12">
                            <label class="form-label fw-medium">Client ID</label>
                            <input name="client_id" type="text" class="form-control"
                                   value="{{ old('client_id', $provider->client_id ?? '') }}"
                                   placeholder="Enter Client ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Client Secret</label>
                            <input name="client_secret" type="password" class="form-control"
                                   placeholder="{{ isset($provider) ? '••••••••••••  (leave blank to keep)' : 'Enter Client Secret' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-medium">Refresh Token</label>
                            <input name="refresh_token" type="password" class="form-control"
                                   placeholder="{{ isset($provider) ? '••••••••••••  (leave blank to keep)' : 'Enter Refresh Token' }}">
                        </div>

                        <div class="col-12"><hr class="my-1"></div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input name="is_active" type="hidden" value="0">
                                <input name="is_active" type="checkbox" class="form-check-input" value="1"
                                       {{ old('is_active', $provider->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>{{ isset($provider) ? 'Update' : 'Create' }} Provider
                        </button>
                        <a href="{{ route('admin.providers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
