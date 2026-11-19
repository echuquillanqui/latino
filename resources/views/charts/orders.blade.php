@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Reporte: Hemodiálisis finalizadas por mes</h3>
      </div>
    </div>
  </div>
  <div class="card-body pb-0">
    <form action="{{ url('/charts/orders/line') }}" method="GET" class="form-row align-items-end">
      <div class="form-group col-md-3">
        <label for="mode">Consultar por</label>
        <select name="mode" id="mode" class="form-control">
          <option value="year" {{ $mode === 'year' ? 'selected' : '' }}>Año (resumen por mes)</option>
          <option value="month" {{ $mode === 'month' ? 'selected' : '' }}>Mes (resumen por día)</option>
          <option value="day" {{ $mode === 'day' ? 'selected' : '' }}>Día (resumen por turno)</option>
        </select>
      </div>
      <div class="form-group col-md-2" id="year-filter">
        <label for="year">Año</label>
        <input type="number" name="year" id="year" class="form-control" min="2000" max="2100" value="{{ $year }}">
      </div>
      <div class="form-group col-md-2" id="month-filter">
        <label for="month">Mes</label>
        <select name="month" id="month" class="form-control">
          @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $monthName)
            <option value="{{ $loop->iteration }}" {{ (int) $month === $loop->iteration ? 'selected' : '' }}>{{ $monthName }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group col-md-3" id="day-filter">
        <label for="date">Día</label>
        <input type="date" name="date" id="date" class="form-control" value="{{ $selectedDate }}">
      </div>
      <div class="form-group col-md-2">
        <button type="submit" class="btn btn-primary">Filtrar</button>
      </div>
    </form>
    <div class="text-muted mb-2">Periodo: <strong>{{ $periodLabel }}</strong>. Solo se cuentan hemodiálisis finalizadas.</div>
  </div>
  <div class="card-body">
    <div id="container"></div>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/export-data.js"></script>
<script>
  (function () {
    var mode = document.getElementById('mode');
    var yearFilter = document.getElementById('year-filter');
    var monthFilter = document.getElementById('month-filter');
    var dayFilter = document.getElementById('day-filter');

    function updateFilters() {
      var selectedMode = mode.value;
      yearFilter.style.display = selectedMode === 'day' ? 'none' : '';
      monthFilter.style.display = selectedMode === 'month' ? '' : 'none';
      dayFilter.style.display = selectedMode === 'day' ? '' : 'none';
    }

    mode.addEventListener('change', updateFilters);
    updateFilters();
  })();

  Highcharts.chart('container', {
    chart: {
        type: 'column'
    },
    title: {
        text: 'Hemodiálisis finalizadas: {{ $periodLabel }}'
    },
    xAxis: {
        categories: @json($labels)
    },
    yAxis: {
        title: {
            text: 'Cantidad de atenciones finalizadas'
        }
    },
    plotOptions: {
        column: {
            dataLabels: {
                enabled: true
            }
        }
    },
    series: [{
        name: 'Hemodiálisis finalizadas',
        data: @json($counts)
    }]
});

</script>
@endsection
