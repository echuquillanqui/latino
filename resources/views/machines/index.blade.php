@extends('layouts.panel')

@section('content')
<div class="card shadow">
  <div class="card-header border-0 d-flex justify-content-between align-items-center flex-wrap">
    <div>
      <h3 class="mb-1">Configuración de máquinas</h3>
      <p class="text-muted mb-0">Administra el equipo, marca, modelo, módulo y puesto.</p>
    </div>
  </div>

  <div class="card-body">
    @if (session('notification'))
      <div class="alert alert-success">{{ session('notification') }}</div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger">
        <strong>Revisa los datos ingresados:</strong>
        <ul class="mb-0 mt-2">
          @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
      </div>
    @endif

    <form action="{{ route('machines.store') }}" method="POST" class="mb-4">
      @csrf
      <div class="table-responsive">
        <table class="table table-sm table-bordered mb-2">
          <thead class="thead-light">
            <tr><th>Equipo</th><th>Marca</th><th>Modelo</th><th>Ubicación (módulo)</th><th>Ubicación (puesto)</th><th></th></tr>
          </thead>
          <tbody>
            <tr>
              <td><input class="form-control form-control-sm" name="equipment" placeholder="MAQUINA Nº 16" required maxlength="80"></td>
              <td><input class="form-control form-control-sm" name="brand" maxlength="80"></td>
              <td><input class="form-control form-control-sm" name="model" maxlength="80"></td>
              <td><input class="form-control form-control-sm" name="module" placeholder="MODULO I" maxlength="80"></td>
              <td><input class="form-control form-control-sm" name="position" placeholder="PUESTO 16" maxlength="80"></td>
              <td><button class="btn btn-sm btn-primary mb-0" type="submit"><i class="fas fa-plus"></i> Agregar</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover table-bordered align-items-center">
        <thead class="thead-light">
          <tr><th>Equipo</th><th>Marca</th><th>Modelo</th><th>Ubicación (módulo)</th><th>Ubicación (puesto)</th><th style="min-width: 150px;">Opciones</th></tr>
        </thead>
        <tbody>
          @forelse ($machines as $machine)
            @php($formId = 'machine-update-' . $machine->id)
            <tr>
              <td>
                <form id="{{ $formId }}" action="{{ route('machines.update', $machine) }}" method="POST">
                  @csrf
                  @method('PUT')
                </form>
                <input form="{{ $formId }}" class="form-control form-control-sm" name="equipment" value="{{ $machine->equipment }}" required maxlength="80">
              </td>
              <td><input form="{{ $formId }}" class="form-control form-control-sm" name="brand" value="{{ $machine->brand }}" maxlength="80"></td>
              <td><input form="{{ $formId }}" class="form-control form-control-sm" name="model" value="{{ $machine->model }}" maxlength="80"></td>
              <td><input form="{{ $formId }}" class="form-control form-control-sm" name="module" value="{{ $machine->module }}" maxlength="80"></td>
              <td><input form="{{ $formId }}" class="form-control form-control-sm" name="position" value="{{ $machine->position }}" maxlength="80"></td>
              <td class="text-nowrap">
                <button form="{{ $formId }}" class="btn btn-sm btn-success mb-0" type="submit" title="Guardar cambios"><i class="fas fa-save"></i> Guardar</button>
                <form action="{{ route('machines.destroy', $machine) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar {{ $machine->equipment }}?');">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-danger mb-0" type="submit" title="Eliminar máquina"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted">No hay máquinas configuradas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
