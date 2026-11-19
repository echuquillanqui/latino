@extends('layouts.panel')

@section('styles')
<style>
  .audit-page { color: #24364b; }
  .audit-filters { border-radius: 12px; box-shadow: 0 2px 8px rgba(30, 45, 62, .12); }
  .audit-table { min-width: 1650px; font-size: 12px; }
  .audit-table thead th { background: #252a30; color: #fff; border-color: #252a30; white-space: nowrap; }
  .audit-table tbody td { white-space: nowrap; vertical-align: middle; }
  .audit-module-1 td { background-color: #fce8e8; }
  .audit-module-2 td { background-color: #fff3bf; }
  .audit-module-3 td { background-color: #e2f2e6; }
  .audit-table tbody td.audit-hour-duplicate { background-color: #dc3545 !important; color: #fff !important; font-weight: 700; }
  .audit-count { border-radius: 20px; }
  .modal-dialog.audit-modal-fullscreen { width: calc(100vw - 16px) !important; max-width: none !important; height: calc(100vh - 16px) !important; margin: 8px !important; }
  .audit-modal-fullscreen .modal-content { display: flex; flex-direction: column; height: 100% !important; }
  .audit-modal-fullscreen .modal-body { display: flex; flex: 1 1 auto; min-height: 0; overflow: hidden !important; }
  .audit-edit-modal-body { position: relative; }
  .audit-edit-loading { position: absolute; inset: 0; z-index: 2; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, .96); }
  .audit-edit-frame { display: block; width: 100% !important; height: 100% !important; min-height: 0; flex: 1 1 auto; border: 0; background: #fff; }
</style>
@endsection

@section('content')
<div class="card shadow audit-page">
  <div class="card-header border-0 pb-0">
    <h2 class="mb-1"><i class="ni ni-ungroup mr-2"></i>Auditoría FISSAL</h2>
    <p class="text-muted mb-0">Vista consolidada de las sesiones de hemodiálisis.</p>
  </div>

  <div class="card-body pb-3">
    <form action="{{ route('nurses.audit') }}" method="GET" class="card card-body audit-filters mb-0">
      <div class="form-row align-items-end">
        <div class="form-group col-lg-3 col-md-6">
          <label for="search">Paciente, DNI u orden</label>
          <input type="search" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="Buscar...">
        </div>

        <div class="form-group col-lg-2 col-md-6">
          <label for="date_order">Fecha (día)</label>
          <input type="date" name="date_order" id="date_order" class="form-control" value="{{ $month ? '' : $dateOrder }}">
        </div>

        <div class="form-group col-lg-2 col-md-6">
          <label for="month">Mes</label>
          <input type="month" name="month" id="month" class="form-control" value="{{ $month }}">
        </div>

        <div class="form-group col-lg-2 col-md-6">
          <label for="sequence">Secuencia</label>
          <select name="sequence" id="sequence" class="form-control">
            <option value="">Todas</option>
            <option value="L - M - V" {{ request('sequence') === 'L - M - V' ? 'selected' : '' }}>Automática (L-M-V)</option>
            <option value="M - J - S" {{ request('sequence') === 'M - J - S' ? 'selected' : '' }}>Automática (M-J-S)</option>
          </select>
        </div>

        <div class="form-group col-lg-1 col-md-6">
          <label for="module">Módulo</label>
          <select name="module" id="module" class="form-control">
            <option value="">Todos</option>
            @foreach($rooms as $room)
              <option value="{{ $room->name }}" {{ request('module') === $room->name ? 'selected' : '' }}>{{ $room->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group col-lg-1 col-md-6">
          <label for="shift">Turno</label>
          <select name="shift" id="shift" class="form-control">
            <option value="">Todos</option>
            @foreach($shifts as $shift)
              <option value="{{ $shift->name }}" {{ request('shift') === $shift->name ? 'selected' : '' }}>{{ $shift->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group col-lg-2 col-md-6">
          <label for="status">Estado</label>
          <select name="status" id="status" class="form-control">
            <option value="">Todos</option>
            <option value="finalizada" {{ request('status', 'finalizada') === 'finalizada' ? 'selected' : '' }}>Finalizado</option>
            <option value="incompleta" {{ request('status') === 'incompleta' ? 'selected' : '' }}>Incompleto</option>
          </select>
        </div>
      </div>

      <div class="d-flex justify-content-end">
        <a href="{{ route('nurses.audit') }}" class="btn btn-outline-secondary btn-sm mr-2">Limpiar</a>
        <button type="submit" class="btn btn-primary btn-sm">Buscar</button>
      </div>
    </form>
  </div>

  <div class="px-3 pb-2 d-flex justify-content-between align-items-center">
    <button type="button" id="audit-print-selected" class="btn btn-sm btn-primary" disabled>
      <i class="fas fa-print mr-1"></i>Imprimir seleccionadas (0)
    </button>
    <div>
      <span class="badge badge-danger mr-2">Rojo: hora repetida en el turno</span>
      <span class="badge badge-light audit-count">{{ $nurses->total() }} registros encontrados</span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-bordered audit-table mb-0">
      <thead>
        <tr>
          <th class="text-center"><input type="checkbox" id="audit-select-all" aria-label="Seleccionar todas las historias de esta página"></th>
          <th>Apellidos y nombres del paciente</th>
          <th>Inicio</th>
          <th>Final</th>
          <th>Med. inicio</th>
          <th>Med. final</th>
          <th>ACCESO 1</th>
          <th>ACCESO 2</th>
          <th>Fecha</th>
          <th>Lic. inicia</th>
          <th>Lic. finaliza</th>
          <th>Nefrólogo</th>
          <th>Módulo</th>
          <th>Opciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse($nurses as $nurse)
          @php
            $roomName = strtoupper(trim((string) ($nurse->audit_room_name ?: $nurse->room)));
            $moduleInfo = in_array($roomName, ['MODULO 1', 'MÓDULO 1', 'AMARILLA'])
              ? ['label' => 'MÓDULO 1', 'class' => 'audit-module-1']
              : (in_array($roomName, ['MODULO 2', 'MÓDULO 2', 'AZUL'])
                ? ['label' => 'MÓDULO 2', 'class' => 'audit-module-2']
                : (in_array($roomName, ['MODULO 3', 'MÓDULO 3', 'VERDE']) ? ['label' => 'MÓDULO 3', 'class' => 'audit-module-3'] : ['label' => 'SIN MÓDULO', 'class' => '']));
            $startTime = $nurse->hr;
            $endTime = ($nurse->hr8 && $nurse->hr8 !== '-') ? $nurse->hr8 : $nurse->hr7;
            $nephrologist = $nurse->audit_medical_doctor_name ?: $nurse->audit_order_doctor_name;
            $auditDateKey = substr((string) ($nurse->audit_order_date ?: $nurse->date_order), 0, 10);
            $auditShiftKey = $nurse->audit_shift_name;
            $treatmentStartKey = $startTime ? substr((string) $startTime, 0, 5) : '';
            $treatmentEndKey = $endTime ? substr((string) $endTime, 0, 5) : '';
            $medicalStartKey = $nurse->audit_medical_start_hour ? substr((string) $nurse->audit_medical_start_hour, 0, 5) : '';
            $medicalEndKey = $nurse->audit_medical_end_hour ? substr((string) $nurse->audit_medical_end_hour, 0, 5) : '';
            $treatmentStartRepeated = $auditShiftKey && isset($duplicateHours['treatment_start'][$auditDateKey][$auditShiftKey][$treatmentStartKey]);
            $treatmentEndRepeated = $auditShiftKey && isset($duplicateHours['treatment_end'][$auditDateKey][$auditShiftKey][$treatmentEndKey]);
            $medicalStartRepeated = $auditShiftKey && isset($duplicateHours['medical_start'][$auditDateKey][$auditShiftKey][$medicalStartKey]);
            $medicalEndRepeated = $auditShiftKey && isset($duplicateHours['medical_end'][$auditDateKey][$auditShiftKey][$medicalEndKey]);
          @endphp
          <tr class="{{ $moduleInfo['class'] }}">
            <td class="text-center">
              <input type="checkbox" class="audit-print-checkbox" value="{{ $nurse->order_id }}" aria-label="Seleccionar historia de {{ $nurse->patient }}">
            </td>
            <td>
              <strong>{{ $nurse->patient }}</strong>
            </td>
            <td class="{{ $treatmentStartRepeated ? 'audit-hour-duplicate' : '' }}" @if($treatmentStartRepeated) title="Hora de inicio de tratamiento repetida en este turno" @endif>{{ $startTime ?: '—' }}</td>
            <td class="{{ $treatmentEndRepeated ? 'audit-hour-duplicate' : '' }}" @if($treatmentEndRepeated) title="Hora final de tratamiento repetida en este turno" @endif>{{ $endTime ?: '—' }}</td>
            <td class="audit-medical-start {{ $medicalStartRepeated ? 'audit-hour-duplicate' : '' }}" @if($medicalStartRepeated) title="Hora de inicio médica repetida en este turno" @endif>{{ $nurse->audit_medical_start_hour ?: '—' }}</td>
            <td class="audit-medical-end {{ $medicalEndRepeated ? 'audit-hour-duplicate' : '' }}" @if($medicalEndRepeated) title="Hora final médica repetida en este turno" @endif>{{ $nurse->audit_medical_end_hour ?: '—' }}</td>
            <td>{{ $nurse->audit_access_1 ?: '—' }}</td>
            <td>{{ $nurse->audit_access_2 ?: '—' }}</td>
            <td>{{ $nurse->audit_order_date ? \Carbon\Carbon::parse($nurse->audit_order_date)->format('d/m/Y') : ($nurse->date_order ? \Carbon\Carbon::parse($nurse->date_order)->format('d/m/Y') : '—') }}</td>
            <td>{{ $nurse->audit_start_licensed_name ?: '—' }}</td>
            <td>{{ $nurse->audit_end_licensed_name ?: '—' }}</td>
            <td>{{ $nephrologist ?: '—' }}</td>
            <td>{{ $moduleInfo['label'] }}</td>
            <td>
              <button type="button" class="btn btn-sm btn-warning mb-1 audit-calculate-hours" data-toggle="modal" data-target="#audit-hours-modal" data-url="{{ route('nurses.audit.calculate-medical-hours', $nurse->id) }}" data-patient="{{ $nurse->patient }}"><i class="fas fa-calculator mr-1"></i>Calcular horas</button>
              <button type="button" class="btn btn-sm btn-dark mb-1" data-toggle="modal" data-target="#audit-edit-modal" data-title="Imprimir historia — {{ $nurse->patient }}" data-loading-message="Preparando la historia para imprimir..." data-edit-url="{{ url('nurses/auditoria/imprimir?ids%5B%5D='.$nurse->order_id) }}"><i class="fas fa-print"></i> Imprimir</button>
              @if($nurse->audit_medical_id)
                <button type="button" class="btn btn-sm btn-info mb-1" data-toggle="modal" data-target="#audit-edit-modal" data-title="Medicina — {{ $nurse->patient }}" data-loading-message="Cargando plantilla de Medicina..." data-edit-url="{{ url('medicals/'.$nurse->audit_medical_id.'/edit?modal=1') }}">Medicina</button>
              @else
                <button type="button" class="btn btn-sm btn-info mb-1" disabled>Medicina</button>
              @endif
              <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#audit-edit-modal" data-title="Enfermería — {{ $nurse->patient }}" data-loading-message="Cargando datos básicos y tratamiento de Enfermería..." data-edit-url="{{ url('nurses/'.$nurse->id.'/edit?modal=1&template=nurse') }}">Enfermería</button>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="14" class="text-center text-muted py-4">No se encontraron historias con esos filtros.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="modal fade" id="audit-hours-modal" tabindex="-1" role="dialog" aria-labelledby="audit-hours-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
      <div class="modal-content">
        <div class="modal-header py-2">
          <h5 class="modal-title" id="audit-hours-title">Calcular horas médicas</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body text-center">
          <div id="audit-hours-patient" class="small text-muted mb-3"></div>
          <button type="button" id="audit-hours-calculate" class="btn btn-dark btn-sm"><i class="fas fa-calculator mr-1"></i>Calcular y guardar</button>
          <div id="audit-hours-result" class="mt-3 d-none" aria-live="polite">
            <div class="row">
              <div class="col-6"><small class="text-muted d-block">Inicio médico</small><strong id="audit-hours-start" class="h4"></strong></div>
              <div class="col-6"><small class="text-muted d-block">Final médico</small><strong id="audit-hours-end" class="h4"></strong></div>
            </div>
          </div>
          <div id="audit-hours-message" class="small mt-2" role="status"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="audit-edit-modal" tabindex="-1" role="dialog" aria-labelledby="audit-edit-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered audit-modal-fullscreen" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="audit-edit-title">Editar plantilla</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        </div>
        <div class="modal-body p-0 audit-edit-modal-body">
          <div id="audit-edit-loading" class="audit-edit-loading" role="status" aria-live="polite">
            <div class="text-center px-3">
              <div class="spinner-border text-primary mb-3" role="presentation"></div>
              <div id="audit-edit-loading-message">Cargando plantilla, por favor espere...</div>
            </div>
          </div>
          <iframe id="audit-edit-frame" class="audit-edit-frame" src="about:blank" title="Formulario de edición"></iframe>
        </div>
      </div>
    </div>
  </div>

  <div class="card-body">
    {{ $nurses->links() }}
  </div>
</div>
@endsection

@section('scripts')
<script>
  (function () {
    var hoursModal = document.getElementById('audit-hours-modal');
    var calculateHoursButton = document.getElementById('audit-hours-calculate');
    var hoursPatient = document.getElementById('audit-hours-patient');
    var hoursMessage = document.getElementById('audit-hours-message');
    var hoursResult = document.getElementById('audit-hours-result');
    var activeHoursUrl = null;
    var activeHoursRow = null;
    if (hoursModal && calculateHoursButton) {
      $('#audit-hours-modal').on('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        activeHoursUrl = trigger ? trigger.getAttribute('data-url') : null;
        activeHoursRow = trigger ? trigger.closest('tr') : null;
        hoursPatient.textContent = trigger ? trigger.getAttribute('data-patient') : '';
        hoursMessage.textContent = '';
        hoursMessage.className = 'small mt-2';
        hoursResult.classList.add('d-none');
        calculateHoursButton.disabled = false;
      });

      calculateHoursButton.addEventListener('click', function () {
        if (!activeHoursUrl) return;
        calculateHoursButton.disabled = true;
        hoursMessage.textContent = 'Calculando…';
        fetch(activeHoursUrl, {
          method: 'PATCH',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        }).then(function (response) {
          return response.json().then(function (data) {
            if (!response.ok) throw new Error(data.message || 'No se pudieron calcular las horas.');
            return data;
          });
        }).then(function (data) {
          document.getElementById('audit-hours-start').textContent = data.start;
          document.getElementById('audit-hours-end').textContent = data.end;
          hoursResult.classList.remove('d-none');
          hoursMessage.textContent = data.message + (data.adjustment ? ' Se aplicaron ' + data.adjustment + ' minutos por el acceso.' : ' Sin ajuste adicional por acceso.');
          hoursMessage.classList.add('text-success');
          if (activeHoursRow) {
            activeHoursRow.querySelector('.audit-medical-start').textContent = data.start;
            activeHoursRow.querySelector('.audit-medical-end').textContent = data.end;
          }
        }).catch(function (error) {
          hoursMessage.textContent = error.message;
          hoursMessage.classList.add('text-danger');
        }).finally(function () {
          calculateHoursButton.disabled = false;
        });
      });
    }
  })();
</script>
<script>
  (function () {
    var day = document.getElementById('date_order');
    var month = document.getElementById('month');
    if (!day || !month) return;
    month.addEventListener('change', function () { if (month.value) day.value = ''; });
    day.addEventListener('change', function () { if (day.value) month.value = ''; });
  })();
</script>
<script>
  (function () {
    var modal = document.getElementById('audit-edit-modal');
    var frame = document.getElementById('audit-edit-frame');
    var title = document.getElementById('audit-edit-title');
    var loading = document.getElementById('audit-edit-loading');
    var loadingMessage = document.getElementById('audit-edit-loading-message');
    var selectAll = document.getElementById('audit-select-all');
    var printSelectedButton = document.getElementById('audit-print-selected');
    var printCheckboxes = Array.prototype.slice.call(document.querySelectorAll('.audit-print-checkbox'));
    var printEndpoint = '{{ url('nurses/auditoria/imprimir') }}';
    var shouldRefresh = false;
    var loadingTimer = null;
    var waitingForTemplate = false;
    if (!modal || !frame) return;

    $('#audit-edit-modal').on('show.bs.modal', function (event) {
      var button = event.relatedTarget ? $(event.relatedTarget) : $(this);
      shouldRefresh = false;
      waitingForTemplate = true;
      if (loading) loading.classList.remove('d-none');
      if (loadingMessage) loadingMessage.textContent = button.data('loading-message') || 'Cargando plantilla, por favor espere...';
      title.textContent = button.data('title') || 'Editar plantilla';
      frame.src = button.data('edit-url');
      window.clearTimeout(loadingTimer);
      loadingTimer = window.setTimeout(function () {
        if (waitingForTemplate && loadingMessage) {
          loadingMessage.textContent = 'La plantilla está tardando más de lo habitual. Espere un momento, por favor.';
        }
      }, 5000);
    });

    function updatePrintSelection() {
      var selectedCount = printCheckboxes.filter(function (checkbox) { return checkbox.checked; }).length;
      if (printSelectedButton) {
        printSelectedButton.disabled = selectedCount === 0;
        printSelectedButton.innerHTML = '<i class="fas fa-print mr-1"></i>Imprimir seleccionadas (' + selectedCount + ')';
      }
      if (selectAll) {
        selectAll.checked = printCheckboxes.length > 0 && selectedCount === printCheckboxes.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < printCheckboxes.length;
      }
    }

    if (selectAll) {
      selectAll.addEventListener('change', function () {
        printCheckboxes.forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
        updatePrintSelection();
      });
    }
    printCheckboxes.forEach(function (checkbox) {
      checkbox.addEventListener('change', updatePrintSelection);
    });

    if (printSelectedButton) {
      printSelectedButton.addEventListener('click', function () {
        var selectedIds = printCheckboxes.filter(function (checkbox) { return checkbox.checked; }).map(function (checkbox) { return checkbox.value; });
        if (!selectedIds.length) return;

        var parameters = new URLSearchParams();
        selectedIds.forEach(function (id) { parameters.append('ids[]', id); });
        var $modal = $('#audit-edit-modal');
        $modal.data('title', 'Imprimir ' + selectedIds.length + ' historias seleccionadas');
        $modal.data('loading-message', 'Preparando las historias seleccionadas para imprimir...');
        $modal.data('edit-url', printEndpoint + '?' + parameters.toString());
        $modal.modal('show');
      });
    }

    frame.addEventListener('load', function () {
      if (!waitingForTemplate) return;
      try {
        if (frame.contentWindow.location.href === 'about:blank') return;
        var frameDocument = frame.contentDocument;
        var forms = frameDocument.querySelectorAll('form');
        Array.prototype.forEach.call(forms, function (form) {
          form.addEventListener('submit', function () {
            shouldRefresh = true;
            waitingForTemplate = true;
            if (loading) loading.classList.remove('d-none');
            if (loadingMessage) loadingMessage.textContent = 'Guardando cambios...';
          });
        });
      } catch (error) {
        // El formulario se sirve en el mismo origen; si no puede leerse, el modal sigue disponible.
      }
      waitingForTemplate = false;
      window.clearTimeout(loadingTimer);
      if (loading) loading.classList.add('d-none');
    });

    $('#audit-edit-modal').on('hidden.bs.modal', function () {
      waitingForTemplate = false;
      window.clearTimeout(loadingTimer);
      frame.src = 'about:blank';
      if (shouldRefresh) window.location.reload();
    });
  })();
</script>
@endsection
