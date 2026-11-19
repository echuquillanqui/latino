@extends('layouts.panel')

@section('content')

<div class="card shadow">
  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h1 class="mb-0">Enfermeria</h1>
      </div>
      <div class="col-auto">
        <a href="{{ route('nurses.attendance') }}" class="btn btn-sm btn-primary">Asistencia mensual</a>
      </div>
    </div>
  </div>

  <div class="card-body">
    @if (session('notification'))
      <div class="alert alert-success" role="alert">
        <span class="alert-icon"><i class="ni ni-curved-next"></i></span>
        {{ session('notification') }}
      </div>
    @endif
  </div>

  <div class="card-body">
    <form action="{{ url('nurses') }}" method="GET">
      <div class="row">

        <div class="col-lg-4">
          <div class="form-group">
            <label for="patient">Nombre, apellido o DNI</label>
            <input type="text" name="patient" class="form-control" value="{{ request('patient') }}" autofocus>
          </div>
        </div>

        <div class="form-group col-sm-12 col-lg-2">
          <label for="room">Salas</label>
          <select data-live-search="true" name="room" id="room" class="form-control selectpicker" data-style="btn-info">
            <option value=""></option>
            @foreach ($rooms as $room)
            <option value="{{ $room->name }}" {{ request('room') == $room->name ? 'selected' : '' }}>{{ $room->name }}</option>

            @endforeach
          </select>
        </div>

        <div class="form-group col-sm-12 col-lg-3">
          <label for="shift">Turnos</label>
          <select name="shift" id="shift" class="form-control selectpicker" data-live-search="true" data-style="btn-info">
            <option value=""></option>
            @foreach ($shifts as $shift)
            <option value="{{ $shift->name }}" {{ request('shift') == $shift->name ? 'selected' : '' }}>{{ $shift->name }}</option>

            @endforeach
          </select>
        </div>

        <div class="form-group col-lg-3">
            <label for="date_order">Fecha (día)</label>
          <div class="input-group">
            <div class="input-group-prepend">
            <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
            </div>
            <input class="form-control datepicker" placeholder="Seleccionar fecha"
                id="date_order" name="date_order" type="text"
                value="{{ $month ? '' : $date_order }}"
                data-date-format="yyyy-mm-dd"
                >
          </div>
        </div>

        <div class="form-group col-lg-2">
          <label for="month">Mes</label>
          <input type="month" id="month" name="month" class="form-control" value="{{ $month }}">
        </div>

        <div class="form-group col-sm-12 col-lg-3">
          <label for="status">Estado de la historia</label>
          <select name="status" id="status" class="form-control">
            <option value="">Todas</option>
            <option value="finalizada" {{ request('status') === 'finalizada' ? 'selected' : '' }}>Finalizadas</option>
            <option value="parcial" {{ request('status') === 'parcial' ? 'selected' : '' }}>Parciales</option>
            <option value="incompleta" {{ request('status') === 'incompleta' ? 'selected' : '' }}>Incompletas</option>
          </select>
        </div>


      </div>
      <button class="btn btn-info btn-sm" type="submit">Buscar</button>
    </form>
  </div>

  <div class="card-body py-2">
    <button type="button" id="nhd-select-all" class="btn btn-sm btn-outline-primary">
      Seleccionar todas las sugerencias
    </button>
    <button type="button" id="nhd-bulk-save" class="btn btn-sm btn-primary" data-url="{{ route('nurses.nhd.bulk') }}" disabled>
      Guardar sugerencias seleccionadas (<span id="nhd-selected-count">0</span>)
    </button>
    <button type="button" id="nurse-print-selected" class="btn btn-sm btn-dark ml-2" disabled>
      <i class="fas fa-print mr-1"></i> Mostrar PDF de historias seleccionadas (0)
    </button>
  </div>

  <div class="table-responsive">
    <!-- Projects table -->
    <table class="table align-items-center table-flush">
      <thead class="thead-light text-center">
        <tr>
          <th scope="col"><input type="checkbox" id="nurse-print-select-all" aria-label="Seleccionar todas las historias visibles"></th>
          <th scope="col">Nombres y Apellidos</th>
	        <th scope="col">N° HCL</th>
	        <th scope="col">N° HD</th>
	        <th scope="col">Sugerencia</th>
	        <th scope="col">Máquina / puesto</th>
	        <th scope="col">Opciones</th>
	        <th scope="col">Peso final</th>
          <th scope="col">Turno</th>
          <th>Fecha</th>
            <th>Licenciado</th>
            <th>Módulo</th>
            <th>Estado</th>

        </tr>
      </thead>
      <tbody class="text-center">
        @foreach ($nurses as $nurse)
        @php
          $salaDeOrden = $nurse->order && $nurse->order->room ? $nurse->order->room->name : $nurse->room;
          $salaModulo = strtoupper(trim((string) $salaDeOrden));
          $modulo = in_array($salaModulo, ['MODULO 1', 'MÓDULO 1', 'AMARILLA'])
            ? ['nombre' => 'MÓDULO 1', 'color' => '#fce8e8']
            : (in_array($salaModulo, ['MODULO 2', 'MÓDULO 2', 'AZUL'])
              ? ['nombre' => 'MÓDULO 2', 'color' => '#fff3bf']
              : (in_array($salaModulo, ['MODULO 3', 'MÓDULO 3', 'VERDE']) ? ['nombre' => 'MÓDULO 3', 'color' => '#e2f2e6'] : null));
          $nhdSuggestion = $nhdSuggestions[$nurse->id];
          $needsNhdSuggestion = $nurse->nhd === null || $nurse->nhd === '' || (int) $nurse->nhd !== (int) $nhdSuggestion;
          $extraerCantidad = function ($valor) {
            $texto = trim((string) $valor);
            if (preg_match('/^cero\b/iu', $texto)) {
              return 0.0;
            }
            return preg_match('/^\s*(-?\d+(?:[.,]\d+)?)/u', $texto, $coincidencia)
              ? (float) str_replace(',', '.', $coincidencia[1])
              : null;
          };
          $pesoInicialRevision = $extraerCantidad(optional(optional($nurse->order)->medical)->start_weight);
          $ufRevision = $extraerCantidad(optional(optional($nurse->order)->medical)->uf);
          $pesoFinalRevision = $extraerCantidad($nurse->end_weight);
          $pesoFinalCoincide = null;
          if ($pesoInicialRevision !== null && $ufRevision !== null && $pesoFinalRevision !== null) {
            // Se toma la cantidad inicial aunque UF incluya una unidad/texto, por ejemplo "4000 AT".
            $pesoFinalCoincide = abs($pesoFinalRevision - ($pesoInicialRevision - ($ufRevision / 1000))) <= 0.05;
          }
          $numeroMaquinaRegistrada = preg_match('/\d+/', (string) $nurse->machine, $maquinaMatch) ? (int) $maquinaMatch[0] : null;
          $numeroPuestoRegistrado = preg_match('/\d+/', (string) $nurse->position, $puestoMatch) ? (int) $puestoMatch[0] : null;
          $maquinaConfigurada = $numeroMaquinaRegistrada !== null ? $machinesByNumber->get($numeroMaquinaRegistrada) : null;
          $numeroPuestoConfigurado = $maquinaConfigurada && preg_match('/\d+/', (string) $maquinaConfigurada->position, $puestoConfiguradoMatch)
            ? (int) $puestoConfiguradoMatch[0]
            : null;
          $maquinaPuestoCoincide = $maquinaConfigurada && $numeroPuestoRegistrado !== null
            && $numeroPuestoConfigurado !== null && $numeroPuestoRegistrado === $numeroPuestoConfigurado;
        @endphp
        <tr @if($modulo) style="background-color: {{ $modulo['color'] }};" @endif>

            <td>
              <input type="checkbox" class="nurse-print-checkbox" value="{{ $nurse->order_id }}" aria-label="Seleccionar historia de {{ $nurse->patient }}">
            </td>

	        <th scope="row">
                {{ $nurse->patient }}
            </th>

            <td>
              <form class="hcl-inline-form d-flex justify-content-center" action="{{ route('nurses.hcl.update', $nurse->id) }}" method="POST" style="gap: 4px;">
                @csrf
                @method('PATCH')
                <input type="text" id="hcl-value-{{ $nurse->id }}" name="hcl" maxlength="20" class="form-control form-control-sm" style="width: 110px;" value="{{ $hclDefaults[$nurse->id] }}" data-saved-value="{{ $hclDefaults[$nurse->id] }}" aria-label="N° HCL de {{ $nurse->patient }}">
                <button type="submit" class="btn btn-sm btn-success px-2" title="Guardar N° HCL" aria-label="Guardar N° HCL">
                  <i class="fas fa-save"></i>
                </button>
              </form>
            </td>

            <td>
              <form class="nhd-inline-form d-flex justify-content-center" action="{{ route('nurses.nhd.update', $nurse->id) }}" method="POST" style="gap: 4px;">
                @csrf
                @method('PATCH')
                <input type="number" id="nhd-value-{{ $nurse->id }}" name="nhd" min="1" required class="form-control form-control-sm" style="width: 78px;" value="{{ $nurse->nhd }}" data-saved-value="{{ $nurse->nhd }}" aria-label="N° HD de {{ $nurse->patient }}">
                <button type="submit" class="btn btn-sm btn-success px-2" title="Guardar N° HD" aria-label="Guardar N° HD">
                  <i class="fas fa-save"></i>
                </button>
              </form>
            </td>

            <td>
              @if($needsNhdSuggestion)
                <div id="nhd-suggestion-cell-{{ $nurse->id }}" class="d-flex align-items-center justify-content-center" style="gap: 5px;">
                  <input type="checkbox" class="nhd-suggestion-checkbox" data-nurse-id="{{ $nurse->id }}" data-suggestion="{{ $nhdSuggestion }}" aria-label="Seleccionar sugerencia de {{ $nurse->patient }}">
                  <span class="badge badge-info" style="font-size: 1rem; padding: .45rem .7rem; min-width: 2.25rem;">{{ $nhdSuggestion }}</span>
                  <button type="button" class="btn btn-sm btn-outline-primary nhd-accept-suggestion" data-nurse-id="{{ $nurse->id }}" data-suggestion="{{ $nhdSuggestion }}">Aceptar</button>
                </div>
              @endif
            </td>

            <td>
              <span class="d-inline-block px-2 py-1 rounded" style="min-width: 125px; background-color: {{ $maquinaPuestoCoincide ? '#d4edda' : '#f8d7da' }}; border: 1px solid {{ $maquinaPuestoCoincide ? '#28a745' : '#dc3545' }};">
                {{ $maquinaConfigurada && $maquinaConfigurada->brand ? $maquinaConfigurada->brand : 'Marca —' }}
                / {{ $numeroPuestoRegistrado !== null ? $numeroPuestoRegistrado : 'Puesto —' }}
              </span>
              @if(!$maquinaPuestoCoincide && $maquinaConfigurada)
                <small class="d-block text-danger">Configurado: {{ $maquinaConfigurada->brand }} / {{ $maquinaConfigurada->position }}</small>
              @elseif(!$maquinaConfigurada && $numeroMaquinaRegistrada !== null)
                <small class="d-block text-danger">Máquina no configurada</small>
              @endif
            </td>

        <td>

            <form action="{{ url('/nurses/'.$nurse->id) }}" method="POST">
              @csrf
              @method('DELETE')

              <a href="{{ url('/nurses/'.$nurse->id.'/edit') }}" class="btn btn-sm btn-success" target="_blank">
                  <i class="fas fa-edit"></i>
              </a>

                @if ($nurse->order->created_at->format('Y-m-d') <= date('2020-03-10'))
                    <a href="{{ url('/orders/'.$nurse->order->id.'/impresion') }}" class="btn btn-sm btn-info" target="_blank">Impresión</a>
                @else
                    <a href="{{ url('/orders/'.$nurse->order->id.'/impresion2020') }}" class="btn btn-sm btn-danger" target="_blank">Historia</a>
                @endif
            </form>
          </td>

          <td>
            <span class="d-inline-block px-2 py-1 rounded" style="min-width: 72px; {{ $pesoFinalCoincide === true ? 'background-color: #d4edda; border: 1px solid #28a745;' : ($pesoFinalCoincide === false ? 'background-color: #f8d7da; border: 1px solid #dc3545;' : '') }}">
              {{ $nurse->end_weight !== null && $nurse->end_weight !== '' ? $nurse->end_weight : '—' }}
            </span>
          </td>

          <td>
              @if($nurse->shift == 'TURNO 1')
                  1

              @elseif($nurse->shift == 'TURNO 2')
                  2

              @else($nurse->shift == 'TURNO 3')
                  3
              @endif
          </td>
          <td>
	    {{ $nurse->date_order}}
          </td>
            @if($nurse->user_id)
                <td>
                    {{ $nurse->user->name}}
                </td>
            @else
                <td>actualizar orden</td>
            @endif

            <td>
              @if($modulo)
                <span class="badge badge-lg" style="background-color: {{ $modulo['color'] }}; color: #343a40;">{{ $modulo['nombre'] }}</span>
              @else
                <span class="text-muted">Sin módulo asignado</span>
              @endif
            </td>

            <td>
              @if($nurse->isTreatmentComplete())
                <span class="badge badge-success">FINALIZADA</span>
              @elseif($nurse->hasClinicalData())
                <span class="badge badge-warning">PARCIAL</span>
              @else
                <span class="badge badge-danger">INCOMPLETA</span>
              @endif
            </td>


        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-body">
    {{ $nurses->appends($_GET)->links() }}
  </div>
</div>

<div class="modal fade" id="nurse-print-modal" tabindex="-1" role="dialog" aria-labelledby="nurse-print-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document" style="width: calc(100vw - 24px); max-width: none; height: calc(100vh - 24px); margin: 12px auto;">
    <div class="modal-content" style="height: 100%; display: flex; flex-direction: column;">
      <div class="modal-header">
        <h5 class="modal-title" id="nurse-print-title">Historias seleccionadas</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body p-0" style="position: relative; flex: 1 1 auto; min-height: 0;">
        <div id="nurse-print-loading" class="d-flex align-items-center justify-content-center" style="position: absolute; inset: 0; z-index: 2; background: rgba(255,255,255,.92);">
          <div class="text-center"><div class="spinner-border text-primary mb-2" role="status"><span class="sr-only">Cargando</span></div><div>Preparando el PDF de las historias seleccionadas...</div></div>
        </div>
        <iframe id="nurse-print-frame" title="PDF de historias seleccionadas" src="about:blank" style="border: 0; width: 100%; height: 100%;"></iframe>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script>
  (function () {
    var day = document.getElementById('date_order');
    var month = document.getElementById('month');
    if (!day || !month) return;
    month.addEventListener('change', function () { if (month.value) day.value = ''; });
    day.addEventListener('change', function () { if (day.value) month.value = ''; });
  })();
</script>
<style>
  #nhd-toast {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 2000;
    padding: 12px 18px;
    border-radius: 4px;
    color: #fff;
    background: #2dce89;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .2);
    opacity: 0;
    transform: translateY(10px);
    transition: opacity .2s ease, transform .2s ease;
    pointer-events: none;
  }
  #nhd-toast.show { opacity: 1; transform: translateY(0); }
  #nhd-toast.error { background: #f5365c; }
</style>
<div id="nhd-toast" role="status" aria-live="polite"></div>
<script>
  (function () {
    var toast = document.getElementById('nhd-toast');
    var toastTimer;

    function showToast(message, failed) {
      toast.textContent = message;
      toast.classList.toggle('error', !!failed);
      toast.classList.add('show');
      window.clearTimeout(toastTimer);
      toastTimer = window.setTimeout(function () { toast.classList.remove('show'); }, 2800);
    }

    document.querySelectorAll('.nhd-inline-form').forEach(function (form) {
      var input = form.querySelector('[name="nhd"]');

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var button = form.querySelector('button[type="submit"]');
        if (button.disabled) return;
        button.disabled = true;

        fetch(form.action, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          body: new URLSearchParams(new FormData(form)).toString()
        }).then(function (response) {
          return response.json().then(function (data) {
            if (!response.ok) {
              var validationMessage = data.errors && data.errors.nhd ? data.errors.nhd[0] : data.message;
              throw new Error(validationMessage || 'No se pudo guardar el N° HD.');
            }
            return data;
          });
        }).then(function (data) {
          input.value = data.nhd;
          input.setAttribute('data-saved-value', data.nhd);
          var suggestionButton = document.querySelector('.nhd-accept-suggestion[data-nurse-id="' + data.id + '"]');
          if (suggestionButton && input.value === suggestionButton.getAttribute('data-suggestion')) {
            var suggestionCell = document.getElementById('nhd-suggestion-cell-' + data.id);
            if (suggestionCell) {
              suggestionCell.remove();
              updateBulkButton();
            }
          }
          showToast(data.message, false);
        }).catch(function (error) {
          showToast(error.message || 'Error al guardar el N° HD.', true);
        }).finally(function () {
          button.disabled = false;
        });
      });

      input.addEventListener('change', function () {
        if (input.value !== input.getAttribute('data-saved-value')) {
          form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
      });
    });

    document.querySelectorAll('.hcl-inline-form').forEach(function (form) {
      var input = form.querySelector('[name="hcl"]');

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var button = form.querySelector('button[type="submit"]');
        if (button.disabled) return;
        button.disabled = true;

        fetch(form.action, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
          },
          body: new URLSearchParams(new FormData(form)).toString()
        }).then(function (response) {
          return response.json().then(function (data) {
            if (!response.ok) {
              var validationMessage = data.errors && data.errors.hcl ? data.errors.hcl[0] : data.message;
              throw new Error(validationMessage || 'No se pudo guardar el N° HCL.');
            }
            return data;
          });
        }).then(function (data) {
          input.value = data.hcl || '';
          input.setAttribute('data-saved-value', data.hcl || '');
          showToast(data.message, false);
        }).catch(function (error) {
          showToast(error.message || 'Error al guardar el N° HCL.', true);
        }).finally(function () {
          button.disabled = false;
        });
      });

      input.addEventListener('change', function () {
        if (input.value !== input.getAttribute('data-saved-value')) {
          form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        }
      });
    });

    document.querySelectorAll('.nhd-accept-suggestion').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = document.getElementById('nhd-value-' + button.getAttribute('data-nurse-id'));
        var suggestion = button.getAttribute('data-suggestion');
        if (input.value === suggestion) {
          showToast('El N° HD ya coincide con la sugerencia.', false);
          return;
        }
        input.value = suggestion;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });

    var bulkSaveButton = document.getElementById('nhd-bulk-save');
    var selectedCount = document.getElementById('nhd-selected-count');

    function selectedSuggestions() {
      return Array.prototype.slice.call(document.querySelectorAll('.nhd-suggestion-checkbox:checked'));
    }

    function updateBulkButton() {
      var count = selectedSuggestions().length;
      var total = document.querySelectorAll('.nhd-suggestion-checkbox').length;
      selectedCount.textContent = count;
      bulkSaveButton.disabled = count === 0;
      if (selectAllButton) {
        selectAllButton.textContent = total > 0 && count === total
          ? 'Quitar selección de sugerencias'
          : 'Seleccionar todas las sugerencias';
      }
    }

    document.querySelectorAll('.nhd-suggestion-checkbox').forEach(function (checkbox) {
      checkbox.addEventListener('change', updateBulkButton);
    });

    var selectAllButton = document.getElementById('nhd-select-all');
    selectAllButton.addEventListener('click', function () {
      var checkboxes = document.querySelectorAll('.nhd-suggestion-checkbox');
      var shouldSelect = selectedSuggestions().length !== checkboxes.length;
      checkboxes.forEach(function (checkbox) { checkbox.checked = shouldSelect; });
      selectAllButton.textContent = shouldSelect
        ? 'Quitar selección de sugerencias'
        : 'Seleccionar todas las sugerencias';
      updateBulkButton();
    });

    bulkSaveButton.addEventListener('click', function () {
      var selected = selectedSuggestions();
      if (!selected.length || bulkSaveButton.disabled) return;

      var tokenField = document.querySelector('.nhd-inline-form [name="_token"]');
      var items = selected.map(function (checkbox) {
        return {
          id: parseInt(checkbox.getAttribute('data-nurse-id'), 10),
          nhd: parseInt(checkbox.getAttribute('data-suggestion'), 10)
        };
      });

      bulkSaveButton.disabled = true;
      fetch(bulkSaveButton.getAttribute('data-url'), {
        method: 'PATCH',
        headers: {
          'X-CSRF-TOKEN': tokenField ? tokenField.value : '',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ items: items })
      }).then(function (response) {
        return response.json().then(function (data) {
          if (!response.ok) {
            var validationMessage = data.errors && data.errors.items ? data.errors.items[0] : data.message;
            throw new Error(validationMessage || 'No se pudieron guardar las sugerencias.');
          }
          return data;
        });
      }).then(function (data) {
        data.updated.forEach(function (item) {
          var input = document.getElementById('nhd-value-' + item.id);
          if (input) {
            input.value = item.nhd;
            input.setAttribute('data-saved-value', item.nhd);
          }
          var checkbox = document.querySelector('.nhd-suggestion-checkbox[data-nurse-id="' + item.id + '"]');
          if (checkbox) checkbox.checked = false;
          var suggestionCell = document.getElementById('nhd-suggestion-cell-' + item.id);
          if (suggestionCell) suggestionCell.remove();
        });
        updateBulkButton();
        showToast(data.message, false);
      }).catch(function (error) {
        updateBulkButton();
        showToast(error.message || 'Error al guardar las sugerencias.', true);
      });
    });

    var printCheckboxes = Array.prototype.slice.call(document.querySelectorAll('.nurse-print-checkbox'));
    var printSelectAll = document.getElementById('nurse-print-select-all');
    var printSelectedButton = document.getElementById('nurse-print-selected');
    var printModal = $('#nurse-print-modal');
    var printFrame = document.getElementById('nurse-print-frame');
    var printLoading = document.getElementById('nurse-print-loading');
    var printEndpoint = '{{ url('nurses/auditoria/imprimir') }}';
    var printLoadingTimer = null;

    function showPrintLoading() {
      printLoading.classList.remove('d-none');
      printLoading.classList.add('d-flex');
    }

    function hidePrintLoading() {
      printLoading.classList.remove('d-flex');
      printLoading.classList.add('d-none');
      window.clearTimeout(printLoadingTimer);
    }

    function updatePrintSelection() {
      var count = printCheckboxes.filter(function (checkbox) { return checkbox.checked; }).length;
      printSelectedButton.disabled = count === 0;
      printSelectedButton.innerHTML = '<i class="fas fa-print mr-1"></i>Mostrar PDF de historias seleccionadas (' + count + ')';
      printSelectAll.checked = printCheckboxes.length > 0 && count === printCheckboxes.length;
      printSelectAll.indeterminate = count > 0 && count < printCheckboxes.length;
    }

    printSelectAll.addEventListener('change', function () {
      printCheckboxes.forEach(function (checkbox) { checkbox.checked = printSelectAll.checked; });
      updatePrintSelection();
    });

    printCheckboxes.forEach(function (checkbox) {
      checkbox.addEventListener('change', updatePrintSelection);
    });

    printFrame.addEventListener('load', function () {
      if (printFrame.getAttribute('src') !== 'about:blank') hidePrintLoading();
    });

    printSelectedButton.addEventListener('click', function () {
      var selectedIds = printCheckboxes.filter(function (checkbox) { return checkbox.checked; }).map(function (checkbox) { return checkbox.value; });
      if (!selectedIds.length) return;
      if (selectedIds.length > 30) {
        showToast('Puedes mostrar hasta 30 historias por PDF.', true);
        return;
      }

      var parameters = new URLSearchParams();
      selectedIds.forEach(function (id) { parameters.append('ids[]', id); });
      document.getElementById('nurse-print-title').textContent = 'PDF de ' + selectedIds.length + ' historias seleccionadas';
      showPrintLoading();
      printFrame.src = printEndpoint + '?' + parameters.toString();
      printModal.modal('show');
      window.clearTimeout(printLoadingTimer);
      printLoadingTimer = window.setTimeout(hidePrintLoading, 10000);
    });

    printModal.on('hidden.bs.modal', function () {
      printFrame.src = 'about:blank';
      window.clearTimeout(printLoadingTimer);
      showPrintLoading();
    });
  })();
</script>
@endsection
