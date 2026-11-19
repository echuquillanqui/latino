@extends(request()->query('modal') ? 'layouts.audit-modal' : 'layouts.panel')

@section('styles')

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/css/bootstrap-select.min.css">

@endsection

@section('content')
@php
  $displayFinalPa = $nurse->end_pa;
  if (!$displayFinalPa) {
      foreach (['pa8', 'pa7', 'pa6', 'pa5', 'pa4', 'pa3', 'pa2', 'pa'] as $treatmentPaField) {
          if ($nurse->$treatmentPaField) {
              $displayFinalPa = $nurse->$treatmentPaField;
              break;
          }
      }
  }
@endphp

<div class="card shadow">

  @if (session('notification'))
    <div class="alert alert-success" role="alert">
      <span class="alert-icon"><i class="ni ni-curved-next"></i></span>
      {{ session('notification') }}
    </div>
  @endif

  <div class="card-header border-0">
    <div class="row align-items-center">
      <div class="col">
        <h3 class="mb-0">Parte de enfermeria</h3>
        <h3>Paciente: {{ $nurse->patient }}</h3>
      </div>
      <div class="col text-right">
        <a href="{{ url('nurses') }}" class="btn btn-sm btn-default">
          Cancelar y volver
        </a>
      </div>
    </div>
  </div>
  <div class="card-body">

    @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif



    <form action="{{ url('nurses/'.$nurse->id) }}" method="POST">
      @csrf
      @method('PUT')

      <div class="nav-wrapper">
        <ul class="nav nav-pills nav-fill flex-column flex-md-row" id="tabs-icons-text" role="tablist">
          @if(!$nurseModal)
          <li class="nav-item">
            <a class="nav-link mb-sm-3 mb-md-0 active" id="tabs-icons-text-1-tab" data-toggle="tab" href="#medical" role="tab" aria-controls="tabs-icons-text-1" aria-selected="true"><i class="ni ni-folder-17 mr-2"></i>Parte médico</a>
          </li>
          @endif
          <li class="nav-item">
            <a class="nav-link mb-sm-3 mb-md-0 {{ $nurseModal ? 'active' : '' }}" id="tabs-icons-text-2-tab" data-toggle="tab" href="#nurse" role="tab" aria-controls="tabs-icons-text-2" aria-selected="{{ $nurseModal ? 'true' : 'false' }}"><i class="ni ni-book-bookmark mr-2"></i>{{ $nurseModal ? 'Datos básicos' : 'Enfermería' }}</a>
          </li>
          <li class="nav-item">
            <a class="nav-link mb-sm-3 mb-md-0" id="tabs-icons-text-3-tab" data-toggle="tab" href="#treatment" role="tab" aria-controls="tabs-icons-text-3" aria-selected="false"><i class="ni ni-archive-2 mr-2"></i>{{ $nurseModal ? 'Tratamiento' : 'Evolución / Tratamiento' }}</a>
          </li>
        </ul>
      </div>

      <div class="card shadow">
        <div class="card-body">
          <div class="tab-content" id="myTabContent">

            @if(!$nurseModal)
            <div class="tab-pane fade show active" id="medical" role="tabpanel" aria-labelledby="tabs-icons-text-1-tab">

                <div class="row">

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="start_hour">Hora Inicial</label>
                        <input type="time" name="start_hour" class="form-control" readonly value="{{ $nurse->order->medical->start_hour }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-3">
                        <label for="start_weight">Peso Inicial</label>
                        <input type="text" name="start_weight" class="form-control" readonly value="{{ $nurse->order->medical->start_weight }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-3">
                        <label for="start_pa">PA Inicial</label>
                        <input type="text" name="start_pa" class="form-control" readonly value="{{ $nurse->order->medical->start_pa }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-4">
                        <label for="fc">Frecuencia Cardiaca</label>
                        <input type="text" name="fc" class="form-control" readonly value="{{ $nurse->order->medical->fc }}">
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="epo2000">EPO 2000</label>
                        <input type="number" name="" class="form-control" value="{{ $nurse->order->medical->epo }}" disabled>
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="epo4000">EPO 4000</label>
                        <input type="number" name="" class="form-control" value="{{ $nurse->order->medical->epo4000 }}" disabled>
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="epo4000">Hierro</label>
                        <input type="number" name="" class="form-control" value="{{ $nurse->order->medical->iron }}" disabled>
                    </div>

                    <div class="form-group col-sm-12 col-lg-3">
                        <label for="hidroxi">Hidroxicobalamina</label>
                        <input type="number" name="" class="form-control" value="{{ $nurse->order->medical->vitb12 }}" disabled>
                    </div>

                    <div class="form-group col-sm-12 col-lg-3">
                        <label for="calcitriol">Calcitriol</label>
                        <input type="number" name="" class="form-control" value="{{ $nurse->order->medical->calci }}" disabled>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-sm-12 col-lg-4">
                        <label for="clinical_trouble">Problemas Clínicos:</label>
                        <textarea class="form-control" id="" name="clinical_trouble" rows="2" disabled>{{ $nurse->order->medical->clinical_trouble }}</textarea>
                    </div>

                    <div class="form-group col-sm-12 col-lg-4">
                        <label for="evaluation">Evaluación</label>
                        <textarea class="form-control" id="" name="evaluation" rows="2" disabled>{{ $nurse->order->medical->evaluation }}</textarea>
                    </div>

                    <div class="form-group col-sm-12 col-lg-4">
                        <label for="indications">Indicaciones</label>
                        <textarea class="form-control" id="" name="indications" rows="2" disabled>{{ $nurse->order->medical->indications }}</textarea>
                    </div>

                </div>

                <div class="row">
                    <div class="form-group col-sm-12 col-lg-1">
                        <label for="hour_hd">HORA HD</label>
                        <input type="text" name="hour_hd" class="form-control" readonly value="{{ $nurse->order->medical->hour_hd }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                        <label for="heparin">Heparina</label>
                        <input type="text" name="heparin" class="form-control" readonly value="{{ $nurse->order->medical->heparin }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="dry_weight">Peso Seco</label>
                        <input type="text" name="dry_weight" class="form-control" readonly value="{{ $nurse->end_weight ?: $nurse->order->medical->dry_weight }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="uf">UF</label>
                        <input type="text" name="uf" class="form-control" readonly value="{{ $nurse->order->medical->uf }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="qb">QB</label>
                        <input type="text" name="qb" class="form-control" readonly value="{{ $nurse->order->medical->qb }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="qd">QD</label>
                        <input type="text" name="qd" class="form-control" readonly value="{{ $nurse->order->medical->qd }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="bicarbonat">Bicarbonato</label>
                        <input type="text" name="bicarbonat" class="form-control" readonly value="{{ $nurse->order->medical->bicarbonat }}">
                    </div>
                </div>

                <div class="row">

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="start_na">NA INICIAL</label>
                        <input type="text" name="start_na" class="form-control" readonly value="{{ $nurse->order->medical->start_na }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                        <label for="cnd">CND</label>
                        <input type="text" name="cnd" class="form-control" readonly value="{{ $nurse->order->medical->cnd }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="end_na">NA FINAL</label>
                        <input type="text" name="end_na" class="form-control" readonly value="{{ $nurse->order->medical->end_na }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                        <label for="profile_na">Perfil Na</label>
                        <input type="text" name="profile_na" class="form-control" readonly value="{{ $nurse->order->medical->profile_na }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="area_filter">ÁREA/FILTRO</label>
                        <input type="text" name="area_filter" class="form-control" readonly value="{{ $nurse->order->medical->area_filter }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="membrane">MEMBRANA</label>
                        <input type="text" name="membrane" class="form-control" readonly value="{{ $nurse->order->medical->membrane }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="profile_uf">Perfil UF:</label>
                        <input type="text" name="profile_uf" class="form-control" readonly value="{{ $nurse->order->medical->profile_uf }}">
                    </div>
                </div>

                <div class="row">

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="bicarbonat">Bicarbonato</label>
                        <input type="text" name="bicarbonat" class="form-control" readonly value="{{ $nurse->order->medical->bicarbonat }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-4">
                        <label for="end_evaluation">Evaluación Final</label>
                        <textarea class="form-control" readonly id="" name="end_evaluation" rows="2">{{ $nurse->order->medical->evaluation }}</textarea>
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="end_hour">Hora final</label>
                        <input type="time" name="end_hour" class="form-control" readonly value="{{ $nurse->order->medical->end_hour }}">
                    </div>

                </div>

            </div>
            @endif

            <div class="tab-pane fade {{ $nurseModal ? 'show active' : '' }}" id="nurse" role="tabpanel" aria-labelledby="tabs-icons-text-2-tab">

              <div class="row text-center">
                <div class="form-group col-sm-12 col-lg-2">
                  <label for="hcl">H.CL</label>
                  <input type="text" name="hcl" class="form-control" value="{{ old('hcl', $nurse->hcl) }}" maxlength="20" placeholder="Ingrese N° HCL">
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="frequency">Frecuencia HD</label>
                  <input type="text" name="frequency" class="form-control" value="{{ old('frequency', '3 VECES POR SEMANA', $nurse->frequency) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="nhd">N° HD</label>
                  <input type="number" min="1" name="nhd" class="form-control" value="{{ old('nhd', $nurse->nhd) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="others">Otros</label>
                  <select class="form-control" name="others" data-toggle="select" title="Simple select" data-placeholder="Select a state">
                    <option value="{{$nurse->others}}">{{$nurse->others}}</option>
                    <option value="L - M - V">L - M - V</option>
                    <option value="M - J - S">M - J - S</option>
                  </select>
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="position">Puesto</label>
                  <input type="text" name="position" class="form-control" value="{{ old('position', $nurse->position) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="aspect_dializer">Aspecto del dializador</label>
                  <input type="text" name="aspect_dializer" class="form-control" value="{{ old('aspect_dializer', $nurse->aspect_dializer) }}">
                </div>
              </div>

              <div class="row text-center">
                <div class="form-group col-sm-12 col-lg-2">
                  <label for="start_pa">P.A. Inicial</label>
                  <input type="text" name="start_pa" class="form-control"
                  @if ($nurse->start_pa == null)
                    value="{{ old('start_pa', $nurse->order->medical->start_pa) }}"
                  @else
                    value="{{ old('start_pa', $nurse->start_pa) }}"
                  @endif
                  >
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="end_pa">P.A. Final</label>
                  <input type="text" name="end_pa" class="form-control" value="{{ old('end_pa', $displayFinalPa) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="start_weight">Peso Inicial</label>
                  <input type="text" name="start_weight" class="form-control" value="{{ old('start_weight', $nurse->order->medical->start_weight) }}" readonly>
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="end_weight">Peso Final</label>
                  <input type="text" id="end_weight" name="end_weight" class="form-control" value="{{ old('end_weight', $nurse->end_weight !== null ? $nurse->end_weight : '') }}">
                  <small class="form-text text-muted">Se sugiere según peso inicial y UF; puede modificarlo si corresponde.</small>
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="machine">N° de Maquina</label>
                  <input type="text" name="machine" class="form-control" value="{{ old('machine', $nurse->machine) }}">
                </div>

                @if(!$nurse->brand_model)

                    <div class="form-group col-sm-12 col-lg-2">
                      <label for="brand_model">Marca/Modelo</label>
                      <input type="text" name="brand_model" class="form-control" value="{{ old('brand_model', 'DORA/6000', $nurse->brand_model) }}">
                    </div>
                @else
                      <div class="form-group col-sm-12 col-lg-2">
                          <label for="brand_model">Marca/Modelo</label>
                          <input type="text" name="brand_model" class="form-control" value="{{ $nurse->brand_model }}">
                      </div>
                  @endif

              </div>

              <div class="row text-center">
                <div class="form-group col-sm-12 col-lg-2">
                  <label for="filter">Filtro</label>
                  <input type="text" name="filter" class="form-control" value="{{ old('filter', $nurse->filter) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="uf">UF</label>
                  <input type="text" name="uf" class="form-control" value="{{ old('uf', $nurse->order->medical->uf) }}" readonly>
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="access_arterial">ARTERIAL</label>
                  <select class="form-control" name="access_arterial" data-toggle="select" title="Simple select" data-placeholder="Select a state">
                    <option value="{{ $nurse->access_arterial }}">{{ old('access_arterial',$nurse->access_arterial) }}</option>
                    <option value="FAV">FAV</option>
                    <option value="INJ">INJ</option>
                    <option value="CVCL">CVCL</option>
                    <option value="CVCLP">CVCLP</option>
                      <option value="CVCT">CVCT</option>
                  </select>
                </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="access_venoso">VENOSO</label>
                  <select class="form-control" name="access_venoso" data-toggle="select" title="Simple select" data-placeholder="Select a state">
                    <option value="{{ $nurse->access_venoso }}">{{ old('access_venoso', $nurse->access_venoso) }}</option>
                      <option value="FAV">FAV</option>
                      <option value="VP">VP</option>
                      <option value="INJ">INJ</option>
                      <option value="CVCL">CVCL</option>
                      <option value="CVCLP">CVCLP</option>
                      <option value="CVCT">CVCT</option>
                  </select>
                </div>

                  <div class="form-group col-sm-12 col-lg-4">
                      <label for="iron">Hierro</label>
                        <input type="text" name="iron" class="form-control" value="{{ old('iron', $nurse->iron !== null && $nurse->iron !== '' ? $nurse->iron : optional($nurse->order->medical)->iron) }}">
                  </div>

              </div>

                <div class="row text-center">
                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="epo2000">EPO 2000</label>
                        <input type="number" name="epo2000" class="form-control" value="{{ old('epo2000', $nurse->epo2000 !== null && $nurse->epo2000 !== '' ? $nurse->epo2000 : optional($nurse->order->medical)->epo) }}" placeholder="COLOCAR SOLO CANTIDAD">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="epo4000">EPO 4000</label>
                        <input type="number" name="epo4000" class="form-control" value="{{ old('epo4000', $nurse->epo4000 !== null && $nurse->epo4000 !== '' ? $nurse->epo4000 : optional($nurse->order->medical)->epo4000) }}" placeholder="COLOCAR SOLO CANTIDAD">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="hidroxi">Hidroxicobalamina</label>
                        <input type="number" name="hidroxi" class="form-control" value="{{ old('hidroxi', $nurse->hidroxi !== null && $nurse->hidroxi !== '' ? $nurse->hidroxi : optional($nurse->order->medical)->vitb12) }}" placeholder="COLOCAR SOLO CANTIDAD">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <label for="calcitriol">Calcitriol</label>
                        <input type="number" name="calcitriol" class="form-control" value="{{ old('calcitriol', $nurse->calcitriol !== null && $nurse->calcitriol !== '' ? $nurse->calcitriol : optional($nurse->order->medical)->calci) }}" placeholder="COLOCAR SOLO CANTIDAD">
                    </div>

                    <div class="form-group col-sm-12 col-lg-4">
                        <label for="others_med">Otros Medicamentos</label>
                        <input type="text" name="others_med" class="form-control" value="{{ old('others_med', $nurse->others_med) }}">
                    </div>
                </div>

              <div class="row text-center">

                <div class="form-group col-sm-12 col-lg-3">
                  <label for="s">S.- </label>
                  <textarea class="form-control" id="" name="s" rows="2" value="">{{ old('s', $nurse->s) }}</textarea>
                </div>

                <div class="form-group col-sm-12 col-lg-3">
                  <label for="o">O.- </label>
                  <textarea class="form-control" id="" name="o" rows="2" value="">{{ old('o', $nurse->o) }}</textarea>
                </div>

                <div class="form-group col-sm-12 col-lg-3">
                  <label for="a">A.- </label>
                  <textarea class="form-control" id="" name="a" rows="2" value="">{{ old('a', $nurse->a) }}</textarea>
                </div>

                <div class="form-group col-sm-12 col-lg-3">
                  <label for="p">P.- </label>
                  <textarea class="form-control" id="" name="p" rows="2" value="">{{ old('p', $nurse->p) }}</textarea>
                </div>

              </div>

                <div class="row text-center">

                    <div class="form-group col-sm-12 col-lg-6">
                        <label for="end_observation">Observación Final</label>
                        <textarea class="form-control" id="" name="end_observation" rows="3" value="">{{ old('end_observation', $nurse->end_observation) }}</textarea>
                    </div>

                    <div class="form-group col-sm-12 col-lg-3">
                        <label for="user_id">USUARIO DE LA ATENCION</label>
                        <select class="form-control selectpicker" name="user_id" data-live-search="true" data-style="btn-info">
                            <option value="{{ !$nurse->user_id ? auth()->user()->id : $nurse->user_id }}">{{ !$nurse->user_id ? auth()->user()->name : $nurse->user->name }}</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach

                        </select>
                    </div>

					<div class="form-group col-sm-12 col-lg-3">
						<label for="user_id2">USUARIO QUE FINALIZA HD</label>
					<select class="form-control selectpicker" name="user_id2" data-live-search="true" data-style="btn-info">
						<option value="{{ !$nurse->user_id2 ? auth()->user()->id : $nurse->user_id2 }}">{{ !$nurse->user_id2 ? auth()->user()->name : $nurse->user2->name }}</option>
						@foreach($users as $user)
							<option value="{{ $user->id }}">{{ $user->name }}</option>
						@endforeach

						</select>
					</div>

                </div>
            </div>

            <div class="tab-pane fade" id="treatment" role="tabpanel" aria-labelledby="tabs-icons-text-3-tab">

              @if(!$nurseModal)
              <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modal-horarios-medicos">
                  Modificar horas del Parte Médico
                </button>
              </div>
              @endif

              <div class="alert alert-info py-2" role="status">
                <strong>Duración indicada por el médico:</strong>
          <span id="duracion-hd" data-horas="{{ $nurse->order->medical->hour_hd }}">{{ $nurse->order->medical->hour_hd }} h</span>
                <button type="button" class="btn btn-sm btn-primary ml-3" id="calcular-horarios">Calcular horarios cada 30 min</button>
                <span class="ml-2">El cálculo actualiza solo las horas; conserva los demás datos de cada control.</span>
              </div>

              <div class="row text-center">
                <div class="form-group col-sm-12 col-lg-1">
                  <label for="hr">HR</label>
                  <input type="time" name="hr" id="hr" class="form-control" value="{{ old('hr', $nurse->hr) }}">
                </div>

                      <div class="form-group col-sm-12 col-lg-1">
                          <label for="pa">PA</label>
                          <input type="text" name="pa" class="form-control" value="{{ old('pa', $nurse->pa ?: ($nurse->start_pa ?: optional($nurse->order->medical)->start_pa)) }}">
                      </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <label for="fc1">FC</label>
                  <input type="text" name="fc1" class="form-control" value="{{ old('fc1', $nurse->fc1) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <label for="qb">QB</label>
                  <input type="text" name="qb" class="form-control" value="{{ old('qb', $nurse->qb) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <label for="cnd">CND</label>
                  <input type="text" name="cnd" class="form-control" value="{{ old('cnd', $nurse->order->medical->start_na) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <label for="ra">RA</label>
                  <input type="text" name="ra" class="form-control" value="{{ old('ra', $nurse->ra) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <label for="rv">RV</label>
                  <input type="text" name="rv" class="form-control" value="{{ old('rv', $nurse->rv) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <label for="ptm">PTM</label>
                  <input type="text" name="ptm" class="form-control" value="{{ old('ptm', $nurse->ptm) }}">
                </div>

                  <div class="form-group col-sm-12 col-lg-2">
                      <label for="sol_hemodev">SOL/HEMODERIVADOS</label>
                      <textarea class="form-control" id="" name="sol_hemodev" rows="1">{{ old('sol_hemodev', $nurse->sol_hemodev) }}</textarea>
                  </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <label for="obs">Observación</label>
                  <textarea class="form-control" id="" name="obs" rows="1">{{ old('obs', $nurse->obs) }}</textarea>
                </div>
              </div>

              <div class="row text-center">

                    <div class="form-group col-sm-12 col-lg-1">
                      <input type="time" name="hr2" class="form-control" value="{{ $nurse->hr2 }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                      <input type="text" name="pa2" class="form-control" value="{{ old('pa2', $nurse->pa2) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                      <input type="text" name="fc2" class="form-control" value="{{ old('fc2', $nurse->fc2) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                      <input type="text" name="qb2" class="form-control" value="{{ old('qb2', $nurse->qb2) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                      <input type="text" name="cnd2" class="form-control" value="{{ old('cnd2', $nurse->cnd2) }}">
                    </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ra2" class="form-control" value="{{ old('ra2', $nurse->ra2) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="rv2" class="form-control" value="{{ old('rv2', $nurse->rv2) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ptm2" class="form-control" value="{{ old('ptm2', $nurse->ptm2) }}">
                </div>

                  <div class="form-group col-sm-12 col-lg-2">
                      <textarea class="form-control" id="" name="sol_hemodev2" rows="1">{{ old('sol_hemodev2', $nurse->sol_hemodev2) }}</textarea>
                  </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <textarea class="form-control" id="" name="obs2" rows="1">{{ old('obs2', $nurse->obs2) }}</textarea>
                </div>
              </div>

              <div class="row text-center">
                      <div class="form-group col-sm-12 col-lg-1">
                          <input type="time" name="hr3" class="form-control" value="{{ $nurse->hr3 }}">
                      </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="pa3" class="form-control" value="{{ old('pa3', $nurse->pa3) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="fc3" class="form-control" value="{{ old('fc3', $nurse->fc3) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="qb3" class="form-control" value="{{ old('qb3', $nurse->qb3) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="cnd3" class="form-control" value="{{ old('cnd3', $nurse->cnd3) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ra3" class="form-control" value="{{ old('ra3', $nurse->ra3) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="rv3" class="form-control" value="{{ old('rv3', $nurse->rv3) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ptm3" class="form-control" value="{{ old('ptm3', $nurse->ptm3) }}">
                </div>

                  <div class="form-group col-sm-12 col-lg-2">
                      <textarea class="form-control" id="" name="sol_hemodev3" rows="1">{{ old('sol_hemodev3', $nurse->sol_hemodev3) }}</textarea>
                  </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <textarea class="form-control" id="" name="obs3" rows="1">{{ old('obs3', $nurse->obs3) }}</textarea>
                </div>
              </div>

              <div class="row text-center">
                      <div class="form-group col-sm-12 col-lg-1">
                          <input type="time" name="hr4" class="form-control" value="{{ old('hr4', $nurse->hr4) }}">
                      </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="pa4" class="form-control" value="{{ old('pa4', $nurse->pa4) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="fc4" class="form-control" value="{{ old('fc4', $nurse->fc4) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="qb4" class="form-control" value="{{ old('qb4', $nurse->qb4) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="cnd4" class="form-control" value="{{ old('cnd4', $nurse->cnd4) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ra4" class="form-control" value="{{ old('ra4', $nurse->ra4) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="rv4" class="form-control" value="{{ old('rv4', $nurse->rv4) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ptm4" class="form-control" value="{{ old('ptm4', $nurse->ptm4) }}">
                </div>

                  <div class="form-group col-sm-12 col-lg-2">
                      <textarea class="form-control" id="" name="sol_hemodev4" rows="1">{{ old('sol_hemodev4', $nurse->sol_hemodev4) }}</textarea>
                  </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <textarea class="form-control" id="" name="obs4" rows="1">{{ old('obs4', $nurse->obs4) }}</textarea>
                </div>
              </div>

              <div class="row text-center">
                      <div class="form-group col-sm-12 col-lg-1">
                          <input type="time" name="hr5" class="form-control" value="{{ old('hr5', $nurse->hr5) }}">
                      </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="pa5" class="form-control" value="{{ old('pa5', $nurse->pa5) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="fc5" class="form-control" value="{{ old('fc5', $nurse->fc5) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="qb5" class="form-control" value="{{ old('qb5', $nurse->qb5) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="cnd5" class="form-control" value="{{ old('cnd5', $nurse->cnd5) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ra5" class="form-control" value="{{ old('ra5', $nurse->ra5) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="rv5" class="form-control" value="{{ old('rv5', $nurse->rv5) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ptm5" class="form-control" value="{{ old('ptm5', $nurse->ptm5) }}">
                </div>

                  <div class="form-group col-sm-12 col-lg-2">
                      <textarea class="form-control" id="" name="sol_hemodev5" rows="1">{{ old('sol_hemodev5', $nurse->sol_hemodev5) }}</textarea>
                  </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <textarea class="form-control" id="" name="obs5" rows="1">{{ old('obs5', $nurse->obs5) }}</textarea>
                </div>
              </div>

              <div class="row text-center">
                      <div class="form-group col-sm-12 col-lg-1">
                          <input type="time" name="hr6" class="form-control" value="{{ old('hr6', $nurse->hr6) }}">
                      </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="pa6" class="form-control" value="{{ old('pa6' , $nurse->pa6) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="fc6" class="form-control" value="{{ old('fc6' , $nurse->fc6) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="qb6" class="form-control" value="{{ old('qb6' , $nurse->qb6) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="cnd6" class="form-control" value="{{ old('cnd6' , $nurse->cnd6) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ra6" class="form-control" value="{{ old('ra6' , $nurse->ra6) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="rv6" class="form-control" value="{{ old('rv6' , $nurse->rv6) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ptm6" class="form-control" value="{{ old('ptm6' , $nurse->ptm6) }}">
                </div>

                  <div class="form-group col-sm-12 col-lg-2">
                      <textarea class="form-control" id="" name="sol_hemodev6" rows="1">{{ old('sol_hemodev6', $nurse->sol_hemodev6) }}</textarea>
                  </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <textarea class="form-control" id="" name="obs6" rows="1">{{ old('obs6' , $nurse->obs6) }}</textarea>
                </div>
              </div>

              <div class="row text-center">
                      <div class="form-group col-sm-12 col-lg-1">
                          <input type="time" name="hr7" class="form-control" value="{{ $nurse->hr7 }}">
                      </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="pa7" class="form-control" value="{{ old('pa7', $nurse->pa7) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="fc7" class="form-control" value="{{ old('fc7', $nurse->fc7) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="qb7" class="form-control" value="{{ old('qb7', $nurse->qb7) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="cnd7" class="form-control" value="{{ old('cnd7', $nurse->cnd7) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ra7" class="form-control" value="{{ old('ra7', $nurse->ra7) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="rv7" class="form-control" value="{{ old('rv7', $nurse->rv7) }}">
                </div>

                <div class="form-group col-sm-12 col-lg-1">
                  <input type="text" name="ptm7" class="form-control" value="{{ old('ptm7', $nurse->ptm7) }}">
                </div>

                  <div class="form-group col-sm-12 col-lg-2">
                      <textarea class="form-control" id="" name="sol_hemodev7" rows="1">{{ old('sol_hemodev7', $nurse->sol_hemodev7) }}</textarea>
                  </div>

                <div class="form-group col-sm-12 col-lg-2">
                  <textarea class="form-control" id="" name="obs7" rows="1">{{ old('obs7', $nurse->obs7) }}</textarea>
                </div>
              </div>

                <div class="row text-center">
                            <div class="form-group col-sm-12 col-lg-1">
                                <input type="time" name="hr8" class="form-control" value="{{ old('hr8', $nurse->hr8) }}">
                            </div>
                    <div class="form-group col-sm-12 col-lg-1">
                        <input type="text" name="pa8" class="form-control" value="{{ old('pa8', $nurse->pa8) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                        <input type="text" name="fc8" class="form-control" value="{{ old('fc8', $nurse->fc8) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                        <input type="text" name="qb8" class="form-control" value="{{ old('qb8', $nurse->qb8) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                        <input type="text" name="cnd8" class="form-control" value="{{ old('cnd8', $nurse->cnd8) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                        <input type="text" name="ra8" class="form-control" value="{{ old('ra8', $nurse->ra8) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                        <input type="text" name="rv8" class="form-control" value="{{ old('rv8', $nurse->rv8) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-1">
                        <input type="text" name="ptm8" class="form-control" value="{{ old('ptm8', $nurse->ptm8) }}">
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <textarea class="form-control" id="" name="sol_hemodev8" rows="1">{{ old('sol_hemodev8', $nurse->sol_hemodev8) }}</textarea>
                    </div>

                    <div class="form-group col-sm-12 col-lg-2">
                        <textarea class="form-control" id="" name="obs8" rows="1">{{ old('obs8', $nurse->obs8) }}</textarea>
                    </div>
                </div>

            </div>

            <button type="submit" class="btn btn-primary" >Guardar</button>
          </div>

        </div>
      </div>

      @if(!$nurseModal)
      <div class="modal fade" id="modal-horarios-medicos" tabindex="-1" role="dialog" aria-labelledby="titulo-modal-horarios" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="titulo-modal-horarios">Horas del Parte Médico</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
              <p class="text-muted">Puedes editar las horas directamente o calcularlas desde el horario de tratamiento.</p>
              <div class="form-group">
                <label for="medical_start_hour">Hora de inicio médica</label>
                <input type="time" id="medical_start_hour" name="medical_start_hour" class="form-control" value="{{ old('medical_start_hour', $nurse->order->medical->start_hour) }}">
              </div>
              <div class="form-group mb-0">
                <label for="medical_end_hour">Hora final médica</label>
                <input type="time" id="medical_end_hour" name="medical_end_hour" class="form-control" value="{{ old('medical_end_hour', $nurse->order->medical->end_hour) }}">
              </div>
              <small class="form-text text-muted mt-3" id="regla-acceso-horario"></small>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              <button type="button" class="btn btn-primary" id="calcular-horas-medicas">Calcular horas de inicio y final</button>
              <button type="submit" class="btn btn-success">Guardar horas médicas</button>
            </div>
          </div>
        </div>
      </div>
      @endif
    </form>
  </div>
</div>
@endsection

@section('scripts')

    <script src="{{ asset('vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.9/dist/js/bootstrap-select.min.js"></script>
    <script>
      (function () {
        var pesosIniciales = document.querySelectorAll('[name="start_weight"]');
        var valoresUf = document.querySelectorAll('[name="uf"]');
        var pesoInicial = pesosIniciales.length ? pesosIniciales[pesosIniciales.length - 1] : null;
        var uf = valoresUf.length ? valoresUf[valoresUf.length - 1] : null;
        var pesoFinal = document.getElementById('end_weight');
        if (!pesoInicial || !uf || !pesoFinal) return;
        // Cada apertura propone el cálculo; el licenciado puede reemplazarlo manualmente antes de guardar.
        var autocalculoActivo = true;
        var ultimoValorCalculado = '';

        function numero(valor) {
          var texto = String(valor || '').trim();
          if (/^cero\b/i.test(texto)) return 0;
          var coincidencia = texto.match(/^\s*(-?\d+(?:[.,]\d+)?)/);
          if (!coincidencia) return null;
          var resultado = Number(coincidencia[1].replace(',', '.'));
          return Number.isFinite(resultado) ? resultado : null;
        }

        function calcularPesoFinal() {
          var inicial = numero(pesoInicial.value);
          var ultrafiltrado = numero(uf.value);
          var final = numero(pesoFinal.value);

          if (autocalculoActivo && inicial !== null && ultrafiltrado !== null) {
            var pesoCalculado = inicial - (ultrafiltrado / 1000);
            ultimoValorCalculado = String(Number(pesoCalculado.toFixed(2)));
            pesoFinal.value = ultimoValorCalculado;
            final = numero(pesoFinal.value);
          }

          pesoFinal.style.backgroundColor = '';
          pesoFinal.style.borderColor = '';
          if (inicial === null || ultrafiltrado === null || final === null) return;

          // UF se registra en gramos; esto solo verifica y nunca cambia los valores ingresados.
          var esperado = inicial - (ultrafiltrado / 1000);
          if (Math.abs(final - esperado) <= 0.05) {
            pesoFinal.style.backgroundColor = '#d4edda';
            pesoFinal.style.borderColor = '#28a745';
          } else {
            pesoFinal.style.backgroundColor = '#f8d7da';
            pesoFinal.style.borderColor = '#dc3545';
          }
        }

        pesoFinal.addEventListener('input', function () {
          if (pesoFinal.value.trim() === '') {
            autocalculoActivo = true;
            calcularPesoFinal();
            return;
          }
          if (pesoFinal.value.trim() !== ultimoValorCalculado) autocalculoActivo = false;
          calcularPesoFinal();
        });
        [pesoInicial, uf].forEach(function (campo) {
          campo.addEventListener('input', calcularPesoFinal);
          campo.addEventListener('change', calcularPesoFinal);
        });
        calcularPesoFinal();
      })();
    </script>
    <script>
      (function () {
        var boton = document.getElementById('calcular-horarios');
        if (!boton) return;

        function minutosIndicados(valor) {
          valor = String(valor || '').trim().toUpperCase();
          if (!valor) return 210;
          if (valor.indexOf('SOLO POR HOY') !== -1) return 180;

          var partes = valor.match(/^(\d+)(?:[.,](\d+))?/);
          if (!partes) return 210;

          var horas = parseInt(partes[1], 10);
          var fraccion = partes[2] || '';
          var minutos = fraccion ? parseInt(fraccion, 10) : 0;
          // 3.5 representa 3 h 30 min; 3.15 representa 3 h 15 min.
          if (fraccion.length === 1) minutos = minutos * 6;
          else if (minutos >= 60) minutos = Math.round(minutos * 0.6);
          return horas * 60 + minutos;
        }

        function calcular() {
          var inicio = document.getElementById('hr');
          var duracion = document.getElementById('duracion-hd');
          if (!inicio || !inicio.value) {
            window.alert('Ingrese primero la hora inicial.');
            if (inicio) inicio.focus();
            return;
          }

          var partesHora = inicio.value.split(':');
          var inicioMinutos = parseInt(partesHora[0], 10) * 60 + parseInt(partesHora[1], 10);
          var totalMinutos = minutosIndicados(duracion ? duracion.getAttribute('data-horas') : '');
          var maximoControles = 8;

          for (var indice = 2; indice <= maximoControles; indice++) {
            var campo = document.querySelector('[name="hr' + indice + '"]');
            if (!campo) continue;

            // Las primeras seis casillas posteriores siguen el intervalo de 30 min.
            // La última registra el término indicado, incluso si supera 3 h 30 min.
            var offset = indice === maximoControles ? totalMinutos : (indice - 1) * 30;
            if (offset > totalMinutos) {
              campo.value = '';
              continue;
            }

            var resultado = (inicioMinutos + offset) % (24 * 60);
            var horas = String(Math.floor(resultado / 60)).padStart(2, '0');
            var minutos = String(resultado % 60).padStart(2, '0');
            campo.value = horas + ':' + minutos;
          }
        }

        var etiquetaDuracion = document.getElementById('duracion-hd');
        if (etiquetaDuracion) {
          var minutosDuracion = minutosIndicados(etiquetaDuracion.getAttribute('data-horas'));
          var horasDuracion = Math.floor(minutosDuracion / 60);
          var restoDuracion = minutosDuracion % 60;
          etiquetaDuracion.textContent = horasDuracion + ' h' + (restoDuracion ? ' ' + restoDuracion + ' min' : '');
        }

        boton.addEventListener('click', calcular);
      })();
    </script>
    <script>
      (function () {
        var boton = document.getElementById('calcular-horas-medicas');
        if (!boton) return;

        function minutosDesdeHora(valor) {
          if (!valor) return null;
          var partes = valor.split(':');
          return parseInt(partes[0], 10) * 60 + parseInt(partes[1], 10);
        }

        function horaDesdeMinutos(total) {
          total = (total + 24 * 60) % (24 * 60);
          return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
        }

        boton.addEventListener('click', function () {
          var primeraHora = minutosDesdeHora(document.querySelector('[name="hr"]').value);
          var ultimaHora = null;
          for (var indice = 8; indice >= 2; indice--) {
            var campo = document.querySelector('[name="hr' + indice + '"]');
            if (campo && campo.value) {
              ultimaHora = minutosDesdeHora(campo.value);
              break;
            }
          }
          if (ultimaHora === null) ultimaHora = primeraHora;

          if (primeraHora === null || ultimaHora === null) {
            window.alert('Complete la primera hora de tratamiento y calcule o ingrese la hora final del tratamiento.');
            return;
          }

          var arterial = (document.querySelector('[name="access_arterial"]') || {}).value || '';
          var venoso = (document.querySelector('[name="access_venoso"]') || {}).value || '';
          var accesos = [arterial.toUpperCase(), venoso.toUpperCase()];
          var ajusteFinal = 0;
          if (accesos.indexOf('FAV') !== -1) ajusteFinal = 5;
          else if (accesos.indexOf('CVCP') !== -1 || accesos.indexOf('CVCL') !== -1 || accesos.indexOf('CVCLP') !== -1 || accesos.indexOf('CVCT') !== -1) ajusteFinal = 12;

          document.getElementById('medical_start_hour').value = horaDesdeMinutos(primeraHora - 10);
          document.getElementById('medical_end_hour').value = horaDesdeMinutos(ultimaHora + ajusteFinal);

          var mensaje = ajusteFinal === 5
            ? 'Acceso FAV: se sumaron 5 minutos a la hora final del tratamiento.'
            : (ajusteFinal === 12
              ? 'Acceso por catéter (CVCL, CVCLP, CVCT o CVCP): se sumaron 12 minutos a la hora final del tratamiento.'
              : 'No se aplicó ajuste a la hora final: el acceso no es FAV ni un catéter CVCL, CVCLP o CVCT.');
          document.getElementById('regla-acceso-horario').textContent = mensaje;
        });
      })();
    </script>

@endsection
