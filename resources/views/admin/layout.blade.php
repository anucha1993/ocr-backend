<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'API Management') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #1e293b;
            --primary: #3b82f6;
        }
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }

        .sidebar {
            position: fixed; top: 0; left: 0; bottom: 0; width: var(--sidebar-width);
            background: var(--sidebar-bg); color: #fff; z-index: 1040;
            display: flex; flex-direction: column;
        }
        .sidebar-brand {
            padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex; align-items: center; gap: .75rem;
        }
        .sidebar-brand-icon {
            width: 36px; height: 36px; background: var(--primary); border-radius: .5rem;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .75rem;
        }
        .sidebar-brand h6 { margin: 0; font-size: .875rem; }
        .sidebar-brand small { color: #94a3b8; font-size: .7rem; }

        .sidebar-nav { flex: 1; padding: 1rem .75rem; overflow-y: auto; }
        .sidebar-nav .nav-label {
            font-size: .65rem; text-transform: uppercase; letter-spacing: .05em;
            color: #64748b; padding: .5rem .75rem; margin-top: .5rem;
        }
        .sidebar-nav .nav-link {
            color: #cbd5e1; border-radius: .5rem; padding: .625rem .75rem;
            font-size: .875rem; display: flex; align-items: center; gap: .625rem;
            transition: all .15s;
        }
        .sidebar-nav .nav-link:hover { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar-nav .nav-link.active { background: var(--primary); color: #fff; font-weight: 500; }
        .sidebar-nav .nav-link i { font-size: 1.1rem; width: 1.25rem; text-align: center; }

        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; }
        .topbar {
            height: 64px; background: #fff; border-bottom: 1px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.5rem; position: sticky; top: 0; z-index: 1030;
        }
        .content-body { padding: 1.5rem; }

        .card { border: 1px solid #e2e8f0; border-radius: .75rem; box-shadow: none; }
        .card-header { background: #fff; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
        .table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; color: #64748b; font-weight: 600; }
        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: #2563eb; border-color: #2563eb; }

        .badge-method { font-size: .7rem; font-weight: 600; min-width: 52px; display: inline-block; text-align: center; }
        .method-GET { background: #dcfce7; color: #166534; }
        .method-POST { background: #dbeafe; color: #1e40af; }
        .method-PUT { background: #fef3c7; color: #92400e; }
        .method-PATCH { background: #fce7f3; color: #9d174d; }
        .method-DELETE { background: #fee2e2; color: #991b1b; }

        .json-editor {
            font-family: 'Cascadia Code', 'Fira Code', monospace; font-size: .8rem;
            background: #1e293b; color: #e2e8f0; border: none; border-radius: .5rem;
            padding: 1rem; resize: vertical; min-height: 120px;
        }
        .response-area {
            background: #0f172a; color: #22d3ee; border-radius: .5rem;
            padding: 1rem; font-family: monospace; font-size: .8rem;
            max-height: 500px; overflow: auto; white-space: pre-wrap;
        }

        /* Tab styling */
        .nav-tabs .nav-link {
            font-weight: 500; color: #64748b; border: none;
            padding: .75rem 1.25rem; font-size: .9rem;
        }
        .nav-tabs .nav-link.active {
            color: var(--primary); border: none;
            border-bottom: 2px solid var(--primary); background: transparent;
        }
        .nav-tabs .nav-link:hover { color: var(--primary); }
        .nav-tabs { border-bottom: 1px solid #e2e8f0; }

        .provider-icon {
            width: 42px; height: 42px; border-radius: .625rem;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }

        .stat-card {
            background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem;
            padding: 1.25rem; text-align: center;
        }
        .stat-card .stat-value { font-size: 1.75rem; font-weight: 700; color: #0f172a; }
        .stat-card .stat-label { font-size: .75rem; color: #64748b; margin-top: .25rem; }
    </style>
    @stack('styles')
</head>
<body>
    {{-- Sidebar --}}
    <div class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">API</div>
            <div>
                <h6>API Management</h6>
                <small>Integration Console</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-label">Management</div>
            <a href="{{ route('admin.dashboard') }}"
               class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               data-tab="providers">
                <i class="bi bi-cloud"></i> API Providers
            </a>
            <a href="{{ route('admin.dashboard') }}#endpoints"
               class="nav-link" data-tab="endpoints">
                <i class="bi bi-signpost-split"></i> Endpoints
            </a>
            <div class="nav-label">Tools</div>
            <a href="{{ route('admin.dashboard') }}#test"
               class="nav-link" data-tab="test">
                <i class="bi bi-send"></i> Test API
            </a>
        </nav>
    </div>

    {{-- Main --}}
    <div class="main-content">
        <div class="topbar">
            <h5 class="mb-0 fw-semibold">@yield('heading', 'Dashboard')</h5>
            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">Admin</span>
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
                    <i class="bi bi-person text-primary"></i>
                </div>
            </div>
        </div>

        <div class="content-body">
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
