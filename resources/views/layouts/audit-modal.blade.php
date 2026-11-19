<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>{{ config('app.name') }} — Edición</title>
  <link href="{{ asset('vendor/nucleo/css/nucleo.css') }}" rel="stylesheet">
  <link href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  @yield('styles')
  <link href="{{ asset('css/argon.css?v=1.0.0') }}" rel="stylesheet">
  <style>
    html, body { min-height: 100%; }
    body.audit-edit-modal { background: #f7f8fc; }
    .audit-edit-container { width: 100%; max-width: none; padding: 12px; }
    .audit-edit-container > .card { width: 100%; margin: 0; }
    .audit-edit-container > .card > .card-header .text-right { display: none; }
  </style>
</head>
<body class="audit-edit-modal">
  <main class="container-fluid audit-edit-container">
    @yield('content')
  </main>

  <script src="{{ asset('vendor/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('vendor/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('vendor/sweetalert2/sweetalert2.min.js') }}"></script>
  @yield('scripts')
  <script src="{{ asset('js/argon.js?v=1.0.0') }}"></script>
</body>
</html>
