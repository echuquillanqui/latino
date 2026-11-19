<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    @page { margin: 0; }
    .audit-history-page { page-break-after: always; }
    .audit-history-page:last-child { page-break-after: auto; }
  </style>
</head>
<body>
  @foreach($orders as $order)
    <div class="audit-history-page">
      @include('orders.impresion2020', ['order' => $order])
    </div>
  @endforeach
</body>
</html>
