<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <!-- Meta Information -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('/vendor/folks/favicon.png') }}">

    <title>Folks {{ config('app.name') ? ' - ' . config('app.name') : '' }}</title>

    <!-- Style sheets-->
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="{{ asset(mix($cssFile, 'vendor/folks')) }}" rel="stylesheet">
</head>
<body>
<div id="folks" v-cloak>
    <alert :message="alert.message"
           :type="alert.type"
           :auto-close="alert.autoClose"
           :confirmation-proceed="alert.confirmationProceed"
           :confirmation-cancel="alert.confirmationCancel"
           v-if="alert.type"></alert>

    <div class="container mb-5">
        <div class="d-flex align-items-center py-4 header">
            <svg xmlns="http://www.w3.org/2000/svg" class="bi bi-translate logo" viewBox="0 0 24 24">
                <path class="fill-primary" d="M10 2h4v4h-4V2zM3 7h18v2h-6v13h-2v-6h-2v6H9V9H3V7z"/>
            </svg>

            <h4 class="mb-0 ml-2">
                <strong>Codewiser</strong> Folks {{ config('app.name') ? ' - ' . config('app.name') : '' }}</h4>
        </div>

        <div class="row mt-4">
            <div class="col-2 sidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <router-link active-class="active" to="/dashboard" class="nav-link d-flex align-items-center pt-0">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-caret-up-square" viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                <path d="M3.544 10.705A.5.5 0 0 0 4 11h8a.5.5 0 0 0 .374-.832l-4-4.5a.5.5 0 0 0-.748 0l-4 4.5a.5.5 0 0 0-.082.537z"/>
                            </svg>
                            <span>Dashboard</span>
                        </router-link>
                    </li>
                    <li class="nav-item">
                        <router-link active-class="active" to="/users" class="nav-link d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box" viewBox="0 0 128 128">
                                <circle id="_x32_" cx="64.5" cy="28" r="21"/>
                                <path d="M30.4,118.2V85.7c0-1.6,1-2.6,2.6-2.6c1.6,0,2.6,1,2.6,2.6v32.5h57.7V85.7c0-1.6,1-2.6,2.6-2.6c1.6,0,2.6,1,2.6,2.6v32.5
                                h18.4V80.4c0-14.4-11.8-26.2-26.2-26.2H38.3c-14.4,0-26.2,11.8-26.2,26.2v37.8H30.4z"/>
                            </svg>
                            <span>Users</span>
                        </router-link>
                    </li>
                </ul>
            </div>

            <div class="col-10">
                @if (! $assetsAreCurrent)
                    <div class="alert alert-warning">
                        @lang('The published Folks assets are not up-to-date with the installed version. To update, run:')<br/><code>php artisan folks:publish</code>
                    </div>
                @endif

                <router-view></router-view>
            </div>
        </div>
    </div>

</div>
<!-- Global Folks Object -->
<script>
    window.Folks = @json($folksScriptVariables);
</script>

<script src="{{asset(mix('app.js', 'vendor/folks'))}}"></script>
</body>
</html>
