@extends('layouts.panel')

@section('styles')
<style>
  .attendance-table-scroll { max-height: calc(100vh - 360px); min-height: 260px; overflow: auto !important; position: relative; }
  .attendance-table { min-width: 1500px; font-size: 12px; margin-bottom: 0; }
  .attendance-table thead th { position: sticky; top: 0; z-index: 2; background: #252a30; color: #fff; text-align: center; }
  .attendance-table th:first-child, .attendance-table td:first-child { position: sticky; left: 0; z-index: 1; min-width: 210px; background: #fff; }
  .attendance-table thead th:first-child { z-index: 3; background: #252a30; }
  .attendance-cell { min-width: 86px; text-align: center; vertical-align: middle !important; }
  .attendance-finalized { background: #d4edda !important; border-color: #28a745 !important; }
  .attendance-partial { background: #ffe0b2 !important; border-color: #f0ad4e !important; }
  .attendance-empty, .attendance-absent { background: #f8d7da !important; border-color: #dc3545 !important; }
  .attendance-off { background: #f3f4f5 !important; color: #adb5bd; }
  .attendance-entry { display: block; padding: 4px 2px; margin: 2px 0; border: 1px solid transparent; border-radius: 4px; font-weight: 600; white-space: nowrap; }
  .attendance-entry small { display: block; font-weight: 400; }
  .attendance-legend .badge { padding: .55rem .7rem; }
</style>
@endsection

@section('content')
<div class="card shadow">
  <div class="card-header border-0 d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <h2 class="mb-1">Asistencia mensual de hemodiálisis</h2>
      <p class="text-muted mb-0">Atenciones finalizadas, historias parciales y sesiones programadas sin registro.</p>
    </div>
    <a href="{{ url('/nurses') }}" class="btn btn-sm btn-outline-primary">Volver a enfermería</a>
  </div>

  <div class="card-body pb-2">
    <form action="{{ route('nurses.attendance') }}" method="GET" class="form-row align-items-end">
      <div class="form-group col-md-4">
        <label for="patient">Paciente, apellido o DNI</label>
        <input type="search" id="patient" name="patient" class="form-control" value="{{ $patientSearch }}" placeholder="Buscar paciente">
      </div>
      <div class="form-group col-md-3">
        <label for="month">Mes</label>
        <input type="month" id="month" name="month" class="form-control" value="{{ $month }}" required>
      </div>
      <div class="form-group col-md-2">
        <button type="submit" class="btn btn-primary">Consultar</button>
      </div>
      <div class="form-group col-md-3">
        <button type="submit" formaction="{{ route('nurses.attendance.export') }}" class="btn btn-success">
          <i class="fas fa-file-excel mr-1"></i> Exportar a Excel
        </button>
      </div>
    </form>

    <div class="attendance-legend d-flex flex-wrap mb-3" style="gap: 8px;">
      <span class="badge badge-success">Finalizada</span>
      <span class="badge" style="background: #f0ad4e; color: #212529;">Parcial</span>
      <span class="badge badge-danger">Sin registro / no asistió</span>
      <span class="badge badge-info">Programado</span>
      <span class="badge badge-light">Día no programado</span>
    </div>
    <p class="text-muted mb-0">Periodo: <strong>{{ $monthName }}</strong> · {{ count($patients) }} pacientes</p>
  </div>

  <div class="table-responsive attendance-table-scroll">
    <table class="table table-sm table-bordered attendance-table mb-0">
      <thead>
        <tr>
          <th>Paciente / secuencia</th>
          @for($day = 1; $day <= $daysInMonth; $day++)
            @php
              $dayDate = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT));
              $weekDays = [1 => 'Lu', 2 => 'Ma', 3 => 'Mi', 4 => 'Ju', 5 => 'Vi', 6 => 'Sá', 7 => 'Do'];
            @endphp
            <th title="{{ $dayDate->format('d/m/Y') }}">{{ $day }}<small class="d-block">{{ $weekDays[$dayDate->dayOfWeekIso] }}</small></th>
          @endfor
        </tr>
      </thead>
      <tbody>
        @forelse($patients as $patient)
          <tr>
            <td>
              <strong>{{ $patient['name'] }}</strong>
              @if($patient['dni'])<small class="d-block text-muted">DNI: {{ $patient['dni'] }}</small>@endif
              @if($patient['sequence'])<small class="d-block text-muted">{{ str_replace('-', ' - ', $patient['sequence']) }}</small>@endif
            </td>
            @for($day = 1; $day <= $daysInMonth; $day++)
              @php
                $entries = $patient['days'][$day] ?? [];
                $dayDate = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-' . str_pad($day, 2, '0', STR_PAD_LEFT));
                $scheduled = in_array($dayDate->dayOfWeekIso, $patient['expected_days'], true);
                $isCurrentOrFuture = $dayDate->toDateString() >= $today;
              @endphp
              <td class="attendance-cell {{ !$entries && !$scheduled ? 'attendance-off' : '' }}">
                @if($entries)
                  @foreach($entries as $entry)
                    @php
                      $entryClass = $entry['status'] === 'finalized' ? 'attendance-finalized' : ($entry['status'] === 'partial' ? 'attendance-partial' : 'attendance-empty');
                      $entryLabel = $entry['status'] === 'finalized' ? 'FINALIZADA' : ($entry['status'] === 'partial' ? 'PARCIAL' : 'SIN DATOS');
                    @endphp
                    <span class="attendance-entry {{ $entryClass }}">
                      {{ $entryLabel }}
                      @if($entry['nhd'])<small>N° HD {{ $entry['nhd'] }}</small>@endif
                    </span>
                  @endforeach
                @elseif($scheduled)
                  @if($isCurrentOrFuture)
                    <span class="attendance-entry bg-info text-white">PROGRAMADO</span>
                  @else
                    <span class="attendance-entry attendance-absent">NO ASISTIÓ</span>
                  @endif
                @else
                  —
                @endif
              </td>
            @endfor
          </tr>
        @empty
          <tr><td colspan="{{ $daysInMonth + 1 }}" class="text-center text-muted py-4">No hay atenciones de hemodiálisis para este mes y filtro.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
