@extends('layouts.panel')

@section('content')
<div class="card shadow">
  <div class="card-header border-0">
    <h3 class="mb-0">Reporte de medicamentos por atención</h3>
  </div>
  <div class="card-body">
    <p class="text-muted">Genera un Excel mensual con las cantidades registradas para EPO 2000, EPO 4000, Hierro, Vitamina B12 y Calcitriol.</p>

    <form method="GET" action="{{ route('reports.medications.excel') }}" class="form-inline">
      <label for="period" class="mr-2">Mes</label>
      <input type="month" id="period" name="period" class="form-control mr-3" value="{{ $period }}" required>
      <label for="status" class="mr-2">Estado de las atenciones</label>
      <select id="status" name="status" class="form-control mr-3">
        <option value="todas">Todas</option>
        <option value="finalizada">Finalizadas</option>
        <option value="incompleta">Incompletas</option>
      </select>
      <button type="submit" class="btn btn-primary">
        <i class="ni ni-cloud-download-95 mr-1"></i> Descargar Excel
      </button>
    </form>
  </div>
</div>
@endsection
