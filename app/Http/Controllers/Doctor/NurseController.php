<?php

namespace App\Http\Controllers\Doctor;

use Carbon\Carbon;
//use http\Client\Curl\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Nurse;
use App\Order;
use App\Medical;
use App\Machine;
use App\Exports\NurseAttendanceWorkbookExport;
use App\Room;
use App\User;
use App\Shift;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use Monolog\Handler\IFTTTHandler;
use PDF;

class NurseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $rooms = Room::all();
        $shifts = Shift::all();

        $patient = $request->get('patient');
        $room = $request->get('room');
        $shift = $request->get('shift');
        $month = $request->input('month');
        if (!is_string($month) || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = null;
        }
        $date_order = $month ? null : ($request->input('date_order') ?: Carbon::today()->toDateString());
        $status = $request->get('status');

        $nursesQuery = Nurse::with(['order.room', 'order.patient', 'order.medical', 'user', 'user2'])
            ->orderBy('created_at', 'desc')
            ->room($room)
            ->shift($shift);

        if ($month) {
            $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
            $nursesQuery->whereBetween('nurses.date_order', [$monthStart, $monthEnd]);
        } else {
            $nursesQuery->date_order($date_order);
        }

        if (trim((string) $patient) !== '') {
            $patientSearch = trim((string) $patient);
            $nursesQuery->where(function ($matches) use ($patientSearch) {
                $matches->where('nurses.patient', 'LIKE', '%' . $patientSearch . '%')
                    ->orWhereHas('order.patient', function ($patientQuery) use ($patientSearch) {
                        $patientQuery->where('dni', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('name', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('firstname', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('othername', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('surname', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('lastname', 'LIKE', '%' . $patientSearch . '%');
                    });
            });
        }

        if ($status === 'finalizada') {
            $nursesQuery->complete();
        } elseif ($status === 'parcial') {
            $nursesQuery->partial();
        } elseif ($status === 'incompleta') {
            $nursesQuery->where(function ($noStartLicensed) {
                $noStartLicensed->whereNull('nurses.user_id')->orWhere('nurses.user_id', '');
            })->where(function ($noEndLicensed) {
                $noEndLicensed->whereNull('nurses.user_id2')->orWhere('nurses.user_id2', '');
            })->withoutClinicalData();
        }

        $nurses = $nursesQuery->paginate(15)->appends($request->query())->appends(['date_order' => $date_order, 'month' => $month]);
        $machinesByNumber = Machine::all(['equipment', 'brand', 'position'])->keyBy(function ($machine) {
            return preg_match('/\d+/', (string) $machine->equipment, $match) ? (int) $match[0] : null;
        });
        $pageRecords = $nurses->getCollection();
        $suggestionGroups = [];
        foreach ($pageRecords as $record) {
            $recordDate = Carbon::parse($record->date_order ?: $record->created_at);
            $groupKey = $record->patient . '|' . $recordDate->format('Y-m');
            $suggestionGroups[$groupKey] = [
                'patient' => $record->patient,
                'month' => $recordDate->format('Y-m'),
                'start' => $recordDate->copy()->startOfMonth()->toDateString(),
                'end' => $recordDate->copy()->endOfMonth()->toDateString(),
            ];
        }

        $previousFinalized = collect();
        if ($suggestionGroups) {
            $previousQuery = Nurse::query()->complete()->where(function ($groupsQuery) use ($suggestionGroups) {
                foreach ($suggestionGroups as $group) {
                    $groupsQuery->orWhere(function ($patientMonth) use ($group) {
                        $patientMonth->where('patient', $group['patient'])
                            ->whereBetween('date_order', [$group['start'], $group['end']]);
                    });
                }
            });

            $previousFinalized = $previousQuery->get(['id', 'patient', 'date_order', 'nhd']);
        }

        $nhdSuggestions = [];
        foreach ($pageRecords as $record) {
            $recordDate = Carbon::parse($record->date_order ?: $record->created_at);
            $completedBefore = 0;

            foreach ($previousFinalized as $previous) {
                if ($previous->patient !== $record->patient || !$previous->date_order) {
                    continue;
                }

                $previousDate = Carbon::parse($previous->date_order);
                if ($previousDate->format('Y-m') !== $recordDate->format('Y-m')) {
                    continue;
                }

                $isBefore = $previousDate->lt($recordDate)
                    || ($previousDate->isSameDay($recordDate) && $previous->id < $record->id);
                if (!$isBefore) {
                    continue;
                }

                $completedBefore++;
            }

            $nhdSuggestions[$record->id] = $completedBefore + 1;
        }

        $pagePatients = $pageRecords->pluck('patient')->filter()->unique()->values();
        $maxSessionDay = null;
        foreach ($pageRecords as $record) {
            $recordDay = Carbon::parse($record->date_order ?: $record->created_at)->toDateString();
            if ($maxSessionDay === null || $recordDay > $maxSessionDay) {
                $maxSessionDay = $recordDay;
            }
        }

        $previousHclSessions = collect();
        if ($pagePatients->isNotEmpty() && $maxSessionDay) {
            $previousHclSessions = Nurse::query()
                ->complete()
                ->whereIn('patient', $pagePatients)
                ->whereNotNull('date_order')
                ->where('date_order', '<=', $maxSessionDay)
                ->whereNotNull('hcl')
                ->where('hcl', '<>', '')
                ->get(['id', 'patient', 'date_order', 'hcl']);
        }

        $hclDefaults = [];
        foreach ($pageRecords as $record) {
            $currentHcl = trim((string) $record->hcl);
            $patientRecord = optional(optional($record->order)->patient);
            $patientHcl = trim((string) $patientRecord->hcl);
            $patientDni = trim((string) $patientRecord->dni);
            if ($patientHcl === '' && $currentHcl !== '' && $currentHcl === $patientDni) {
                $currentHcl = '';
            }
            if ($currentHcl !== '') {
                $hclDefaults[$record->id] = $currentHcl;
                continue;
            }

            $recordDate = Carbon::parse($record->date_order ?: $record->created_at);
            $latestHclSession = null;
            foreach ($previousHclSessions as $previous) {
                if ($previous->patient !== $record->patient || !$previous->date_order || trim((string) $previous->hcl) === '') {
                    continue;
                }

                $previousDate = Carbon::parse($previous->date_order);
                $isBefore = $previousDate->lt($recordDate)
                    || ($previousDate->isSameDay($recordDate) && $previous->id < $record->id);
                if (!$isBefore) {
                    continue;
                }

                if (!$latestHclSession) {
                    $latestHclSession = $previous;
                    continue;
                }

                $latestDate = Carbon::parse($latestHclSession->date_order);
                if ($previousDate->gt($latestDate)
                    || ($previousDate->isSameDay($latestDate) && $previous->id > $latestHclSession->id)) {
                    $latestHclSession = $previous;
                }
            }

            $hclDefaults[$record->id] = $latestHclSession ? trim((string) $latestHclSession->hcl) : '';
        }

        return view('nurses.index', compact('nurses', 'rooms', 'shifts', 'nhdSuggestions', 'hclDefaults', 'machinesByNumber', 'date_order', 'month'));
    }

    public function attendance(Request $request)
    {
        $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'patient' => 'nullable|string|max:100',
        ]);

        $month = $request->input('month') ?: Carbon::today()->format('Y-m');
        $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $patientSearch = trim((string) $request->input('patient', ''));

        $query = Nurse::with(['order.patient', 'order.medical'])
            ->whereBetween('nurses.date_order', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('nurses.patient')
            ->orderBy('nurses.date_order')
            ->orderBy('nurses.id');

        if ($patientSearch !== '') {
            $query->where(function ($matches) use ($patientSearch) {
                $matches->where('nurses.patient', 'LIKE', '%' . $patientSearch . '%')
                    ->orWhereHas('order.patient', function ($patientQuery) use ($patientSearch) {
                        $patientQuery->where('dni', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('name', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('firstname', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('othername', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('surname', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('lastname', 'LIKE', '%' . $patientSearch . '%');
                    });
            });
        }

        $records = $query->get();
        $patients = [];
        $partialStories = [];
        $finalizedCounts = [];

        foreach ($records as $record) {
            $patientRecord = optional(optional($record->order)->patient);
            $patientId = $patientRecord->id ?: 'name:' . mb_strtolower(trim((string) $record->patient));
            if (!isset($patients[$patientId])) {
                $patients[$patientId] = [
                    'name' => $patientRecord->name ?: $record->patient,
                    'dni' => $patientRecord->dni,
                    'sequence' => null,
                    'days' => [],
                ];
            }

            $sequence = strtoupper(preg_replace('/\s+/', '', (string) $record->others));
            if (in_array($sequence, ['L-M-V', 'M-J-S'], true)) {
                $patients[$patientId]['sequence'] = $sequence;
            }

            $date = $record->date_order ?: optional($record->order)->date_order;
            if (!$date) {
                continue;
            }
            $day = Carbon::parse($date)->day;

            $isFinalized = $record->isTreatmentComplete();
            $isPartial = !$isFinalized && $record->hasClinicalData();
            $status = $isFinalized ? 'finalized' : ($isPartial ? 'partial' : 'empty');
            $expectedNhd = (isset($finalizedCounts[$patientId]) ? $finalizedCounts[$patientId] : 0) + 1;

            if ($status === 'partial') {
                $actualNhd = is_numeric($record->nhd) ? (int) $record->nhd : null;
                $partialStories[] = [
                    'id' => $record->id,
                    'patient' => $patients[$patientId]['name'],
                    'dni' => $patients[$patientId]['dni'],
                    'date' => Carbon::parse($date)->format('Y-m-d'),
                    'shift' => $record->shift,
                    'nhd' => $actualNhd,
                    'expected_nhd' => $expectedNhd,
                    'nhd_consecutive' => $actualNhd !== null && $actualNhd === $expectedNhd,
                ];
            }

            if ($isFinalized) {
                $finalizedCounts[$patientId] = $expectedNhd;
            }
            $patients[$patientId]['days'][$day][] = [
                'status' => $status,
                'nhd' => $record->nhd,
                'order_id' => $record->order_id,
            ];
        }

        // Include patients with a recent L-M-V / M-J-S schedule even if they have no session record this month.
        $previousMonthStart = $monthStart->copy()->subDays(45)->toDateString();
        $previousMonthEnd = $monthStart->copy()->subDay()->toDateString();
        $previousQuery = Nurse::with(['order.patient'])
            ->whereBetween('nurses.date_order', [$previousMonthStart, $previousMonthEnd])
            ->orderBy('nurses.date_order', 'desc')
            ->orderBy('nurses.id', 'desc');
        if ($patientSearch !== '') {
            $previousQuery->where(function ($matches) use ($patientSearch) {
                $matches->where('nurses.patient', 'LIKE', '%' . $patientSearch . '%')
                    ->orWhereHas('order.patient', function ($patientQuery) use ($patientSearch) {
                        $patientQuery->where('dni', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('name', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('firstname', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('othername', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('surname', 'LIKE', '%' . $patientSearch . '%')
                            ->orWhere('lastname', 'LIKE', '%' . $patientSearch . '%');
                    });
            });
        }
        foreach ($previousQuery->get() as $record) {
            $sequence = strtoupper(preg_replace('/\s+/', '', (string) $record->others));
            if (!in_array($sequence, ['L-M-V', 'M-J-S'], true)) {
                continue;
            }

            $patientRecord = optional(optional($record->order)->patient);
            $patientId = $patientRecord->id ?: 'name:' . mb_strtolower(trim((string) $record->patient));
            if (!isset($patients[$patientId])) {
                $patients[$patientId] = [
                    'name' => $patientRecord->name ?: $record->patient,
                    'dni' => $patientRecord->dni,
                    'sequence' => $sequence,
                    'days' => [],
                ];
            } elseif (!$patients[$patientId]['sequence']) {
                $patients[$patientId]['sequence'] = $sequence;
            }
        }

        foreach ($patients as &$patient) {
            $patient['expected_days'] = $patient['sequence'] === 'L-M-V'
                ? [1, 3, 5]
                : ($patient['sequence'] === 'M-J-S' ? [2, 4, 6] : []);
        }
        unset($patient);

        return view('nurses.attendance', [
            'patients' => $patients,
            'partialStories' => $partialStories,
            'month' => $month,
            'daysInMonth' => $monthStart->daysInMonth,
            'monthName' => $monthStart->format('m/Y'),
            'today' => Carbon::today()->toDateString(),
            'patientSearch' => $patientSearch,
        ]);
    }

    public function attendanceExport(Request $request)
    {
        $attendanceView = $this->attendance($request);
        $data = $attendanceView->getData();

        return Excel::download(
            new NurseAttendanceWorkbookExport(
                $data['patients'],
                $data['partialStories'],
                $data['month'],
                $data['daysInMonth'],
                $data['today']
            ),
            'asistencia-hemodialisis-' . $data['month'] . '.xlsx'
        );
    }

    public function audit(Request $request)
    {
        $request->validate([
            'date_order' => 'nullable|date_format:Y-m-d',
            'month' => 'nullable|date_format:Y-m',
            'sequence' => 'nullable|in:L - M - V,M - J - S',
            'module' => 'nullable|exists:rooms,name',
            'shift' => 'nullable|exists:shifts,name',
            'status' => 'nullable|in:finalizada,incompleta',
        ]);

        $rooms = Room::all();
        $shifts = Shift::all();
        $month = $request->input('month');
        $dateOrder = $month ? null : ($request->input('date_order') ?: Carbon::today()->toDateString());
        $search = trim((string) $request->input('search', ''));
        $sequence = $request->input('sequence');
        $module = $request->input('module');
        $shift = $request->input('shift');
        $status = $request->input('status', 'finalizada');

        $query = Nurse::query()
            ->join('orders as audit_orders', 'audit_orders.id', '=', 'nurses.order_id')
            ->leftJoin('rooms as audit_rooms', 'audit_rooms.id', '=', 'audit_orders.room_id')
            ->leftJoin('shifts as audit_shifts', 'audit_shifts.id', '=', 'audit_orders.shift_id')
            ->leftJoin('patients as audit_patients', 'audit_patients.id', '=', 'audit_orders.patient_id')
            ->leftJoin('medicals as audit_medicals', 'audit_medicals.order_id', '=', 'audit_orders.id')
            ->leftJoin('users as audit_start_licensed', 'audit_start_licensed.id', '=', 'nurses.user_id')
            ->leftJoin('users as audit_end_licensed', 'audit_end_licensed.id', '=', 'nurses.user_id2')
            ->leftJoin('users as audit_medical_doctor', 'audit_medical_doctor.id', '=', 'audit_medicals.user_id')
            ->leftJoin('users as audit_order_doctor', 'audit_order_doctor.id', '=', 'audit_orders.user_id')
            ->select(
                'nurses.*',
                'audit_orders.date_order as audit_order_date',
                'audit_rooms.name as audit_room_name',
                'audit_shifts.name as audit_shift_name',
                'audit_medicals.id as audit_medical_id',
                'audit_medicals.start_hour as audit_medical_start_hour',
                'audit_medicals.end_hour as audit_medical_end_hour',
                'nurses.access_arterial as audit_access_1',
                'nurses.access_venoso as audit_access_2',
                'audit_start_licensed.name as audit_start_licensed_name',
                'audit_end_licensed.name as audit_end_licensed_name',
                'audit_medical_doctor.name as audit_medical_doctor_name',
                'audit_order_doctor.name as audit_order_doctor_name'
            )
            ->orderByRaw("CASE audit_rooms.name WHEN 'MODULO 1' THEN 1 WHEN 'AMARILLA' THEN 1 WHEN 'MODULO 2' THEN 2 WHEN 'AZUL' THEN 2 WHEN 'MODULO 3' THEN 3 WHEN 'VERDE' THEN 3 ELSE 4 END")
            ->orderBy('nurses.patient', 'asc');

        if ($month) {
            $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $monthEnd = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
            $query->whereBetween('audit_orders.date_order', [$monthStart, $monthEnd]);
        } elseif ($dateOrder) {
            $query->where('audit_orders.date_order', $dateOrder);
        }

        if ($search !== '') {
            $query->where(function ($matches) use ($search) {
                $matches->where('nurses.patient', 'LIKE', '%' . $search . '%')
                    ->orWhere('audit_patients.dni', 'LIKE', '%' . $search . '%')
                    ->orWhere('audit_patients.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('audit_patients.firstname', 'LIKE', '%' . $search . '%')
                    ->orWhere('audit_patients.othername', 'LIKE', '%' . $search . '%')
                    ->orWhere('audit_patients.surname', 'LIKE', '%' . $search . '%')
                    ->orWhere('audit_patients.lastname', 'LIKE', '%' . $search . '%')
                    ->orWhere('audit_orders.n_fua', 'LIKE', '%' . $search . '%');
            });
        }

        if ($sequence) {
            $query->where(function ($sequences) use ($sequence, $dateOrder, $month) {
                $sequences->where('others', $sequence);
                if ($dateOrder) {
                    $dayOfWeek = Carbon::parse($dateOrder)->dayOfWeek;
                    $automaticSequence = in_array($dayOfWeek, [1, 3, 5]) ? 'L - M - V' : 'M - J - S';
                    if ($sequence === $automaticSequence) {
                        $sequences->orWhereNull('others')->orWhere('others', '');
                    }
                } elseif ($month) {
                    $mysqlDays = $sequence === 'L - M - V' ? [2, 4, 6] : [3, 5, 7];
                    $sequences->orWhere(function ($automatic) use ($mysqlDays) {
                        $automatic->where(function ($blankSequence) {
                            $blankSequence->whereNull('others')->orWhere('others', '');
                        })->whereIn(DB::raw('DAYOFWEEK(audit_orders.date_order)'), $mysqlDays);
                    });
                }
            });
        }

        if ($module) {
            $query->where('audit_rooms.name', $module);
        }

        if ($shift) {
            $query->where('audit_shifts.name', $shift);
        }

        if ($status === 'finalizada') {
            $query->complete();
        } elseif ($status === 'incompleta') {
            $query->incomplete();
        }

        $duplicateBase = Nurse::query()
            ->join('orders as audit_orders', 'audit_orders.id', '=', 'nurses.order_id')
            ->join('shifts as audit_shifts', 'audit_shifts.id', '=', 'audit_orders.shift_id')
            ->leftJoin('medicals as audit_medicals', 'audit_medicals.order_id', '=', 'audit_orders.id');
        if ($month) {
            $duplicateBase->whereBetween('audit_orders.date_order', [$monthStart, $monthEnd]);
        } elseif ($dateOrder) {
            $duplicateBase->where('audit_orders.date_order', $dateOrder);
        }
        if ($shift) {
            $duplicateBase->where('audit_shifts.name', $shift);
        }

        $duplicateHours = [];
        $hourOccurrences = [];
        $seenSessionHours = [];
        $duplicateCandidates = (clone $duplicateBase)
            ->select(
                'nurses.id as nurse_id',
                'audit_orders.date_order as audit_date',
                'audit_shifts.name as audit_shift',
                'audit_medicals.start_hour as medical_start',
                'audit_medicals.end_hour as medical_end',
                'nurses.hr as treatment_start',
                'nurses.hr8 as treatment_end',
                'nurses.hr7 as treatment_end_fallback'
            )
            ->get();

        foreach ($duplicateCandidates as $candidate) {
            $treatmentEnd = $candidate->treatment_end;
            if ($treatmentEnd === null || trim((string) $treatmentEnd) === '' || trim((string) $treatmentEnd) === '-') {
                $treatmentEnd = $candidate->treatment_end_fallback;
            }

            $candidateHours = [
                'medical_start' => $candidate->medical_start,
                'medical_end' => $candidate->medical_end,
                'treatment_start' => $candidate->treatment_start,
                'treatment_end' => $treatmentEnd,
            ];

            foreach ($candidateHours as $field => $value) {
                $value = trim((string) $value);
                if ($value === '' || $value === '-' || $candidate->audit_date === null || $candidate->audit_shift === null) {
                    continue;
                }

                $time = substr($value, 0, 5);
                if ($time === '' || $time === '-') {
                    continue;
                }

                $key = $candidate->audit_date . '|' . $candidate->audit_shift . '|' . $time;
                if (isset($seenSessionHours[$field][$candidate->nurse_id][$key])) {
                    continue;
                }

                $seenSessionHours[$field][$candidate->nurse_id][$key] = true;
                $hourOccurrences[$field][$candidate->audit_date][$candidate->audit_shift][$time] =
                    isset($hourOccurrences[$field][$candidate->audit_date][$candidate->audit_shift][$time])
                        ? $hourOccurrences[$field][$candidate->audit_date][$candidate->audit_shift][$time] + 1
                        : 1;
            }
        }

        foreach ($hourOccurrences as $field => $dates) {
            foreach ($dates as $date => $shiftsByDate) {
                foreach ($shiftsByDate as $shiftName => $timesByShift) {
                    foreach ($timesByShift as $time => $occurrences) {
                        if ($occurrences > 1) {
                            $duplicateHours[$field][$date][$shiftName][$time] = true;
                        }
                    }
                }
            }
        }

        $nurses = $query->paginate(30)->appends($request->query());

        return view('nurses.audit', compact('nurses', 'rooms', 'shifts', 'dateOrder', 'month', 'duplicateHours'));
    }

    public function calculateAuditMedicalHours(Nurse $nurse)
    {
        $medical = $nurse->order->medical;
        if (!$medical) {
            return response()->json(['message' => 'Esta historia no tiene un Parte Médico asociado.'], 422);
        }

        $startValue = trim((string) $nurse->hr);
        $endValue = trim((string) $nurse->hr8);
        if ($endValue === '' || $endValue === '-') {
            $endValue = trim((string) $nurse->hr7);
        }

        if ($startValue === '' || $startValue === '-' || $endValue === '' || $endValue === '-') {
            return response()->json(['message' => 'Falta la hora inicial o final del tratamiento de Enfermería.'], 422);
        }

        try {
            $start = Carbon::parse($startValue);
            $end = Carbon::parse($endValue);
        } catch (\Exception $exception) {
            return response()->json(['message' => 'No se pudieron interpretar las horas del tratamiento.'], 422);
        }

        $accesses = [strtoupper(trim((string) $nurse->access_arterial)), strtoupper(trim((string) $nurse->access_venoso))];
        $adjustment = in_array('FAV', $accesses, true)
            ? 5
            : (count(array_intersect($accesses, ['CVCP', 'CVCL', 'CVCLP', 'CVCT'])) ? 12 : 0);
        $medicalStart = $start->copy()->subMinutes(10)->format('H:i');
        $medicalEnd = $end->copy()->addMinutes($adjustment)->format('H:i');

        $medical->start_hour = $medicalStart;
        $medical->end_hour = $medicalEnd;
        $medical->save();

        return response()->json([
            'message' => 'Horas médicas calculadas y guardadas.',
            'start' => $medicalStart,
            'end' => $medicalEnd,
            'adjustment' => $adjustment,
        ]);
    }

    public function auditPrint(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1|max:30',
            'ids.*' => 'required|integer|distinct|exists:orders,id',
        ]);

        $ids = array_map('intval', $request->input('ids'));
        $ordersById = Order::with([
            'patient',
            'room',
            'shift',
            'medical.user',
            'medical.user2',
            'nurse.user',
            'nurse.user2',
        ])->whereIn('id', $ids)->get()->keyBy('id');

        $orders = collect($ids)->map(function ($id) use ($ordersById) {
            return $ordersById->get($id);
        })->filter()->values();

        $pdf = PDF::loadView('orders.audit_impresion', compact('orders'));
        return $pdf->stream('historias-hemodialisis.pdf');
    }

    public function updateNhd(Request $request, $id)
    {
        $validated = $request->validate([
            'nhd' => 'required|integer|min:1',
        ]);

        $nurse = Nurse::findOrFail($id);
        $nurse->nhd = $validated['nhd'];
        $nurse->save();

        return response()->json([
            'message' => 'El N° HD se guardó correctamente.',
            'id' => $nurse->id,
            'nhd' => $nurse->nhd,
        ]);
    }

    public function updateHcl(Request $request, $id)
    {
        $validated = $request->validate([
            'hcl' => 'nullable|string|max:20',
        ]);

        $hcl = isset($validated['hcl']) ? trim($validated['hcl']) : null;
        if ($hcl === '') {
            $hcl = null;
        }

        $nurse = DB::transaction(function () use ($id, $hcl) {
            $nurse = Nurse::findOrFail($id);
            $nurse->hcl = $hcl;
            $nurse->save();
            $this->syncPatientHcl($nurse, $hcl);

            return $nurse;
        });

        return response()->json([
            'message' => 'El N° HCL se guardó correctamente.',
            'id' => $nurse->id,
            'hcl' => $nurse->hcl,
        ]);
    }

    protected function syncPatientHcl(Nurse $nurse, $hcl)
    {
        if (!Schema::hasColumn('patients', 'hcl')) {
            return;
        }

        $patient = optional($nurse->order)->patient;
        if ($patient) {
            $patient->hcl = $hcl;
            $patient->save();
        }
    }

    public function updateNhdBulk(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1|max:100',
            'items.*.id' => 'required|integer|distinct|exists:nurses,id',
            'items.*.nhd' => 'required|integer|min:1',
        ]);

        $updated = DB::transaction(function () use ($validated) {
            $items = [];
            foreach ($validated['items'] as $item) {
                $nurse = Nurse::findOrFail($item['id']);
                $nurse->nhd = $item['nhd'];
                $nurse->save();
                $items[] = ['id' => $nurse->id, 'nhd' => $nurse->nhd];
            }

            return $items;
        });

        return response()->json([
            'message' => 'Se guardaron ' . count($updated) . ' sugerencias de N° HD.',
            'updated' => $updated,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    /*public function create(Order $order)
    {
        return view('nurses.create', compact('order'));
    }*/

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    /*public function store(Request $request)
    {
        $existsNursesToday = Nurse::where('patient', $request->input('patient'))
            ->whereDate('created_at', date('Y-m-d'))->exists();

        // $from = '2019-08-01';
        // $to = $request->input();
        // ->whereBetween('created_at', [$from, $to])

        if ($existsNursesToday) {
            $notification = 'Este paciente ya tiene un parte de enfermeria registrada hoy. Intente nuevamente mañana.';
            return redirect('/medicals/')->with(compact('notification'));
        }

        Nurse::create($request->all());

        $notification = 'El Parte de enfermeria se ha registrado correctamente.';
        return redirect('/nurses')->with(compact('notification'));
    }*/

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $users = User::where('role', '=', 'enfermera')->get();

        $nurse = Nurse::findOrFail($id);

        $patientRecord = optional(optional($nurse->order)->patient);
        $storedHcl = trim((string) $nurse->hcl);
        if (trim((string) $patientRecord->hcl) === ''
            && $storedHcl !== ''
            && $storedHcl === trim((string) $patientRecord->dni)) {
            $nurse->hcl = null;
        }

        $previousSessions = Nurse::where('patient', $nurse->patient)
            ->where('id', '<>', $nurse->id)
            ->orderBy('created_at', 'desc')
            ->get(['position', 'machine', 'end_weight', 'brand_model', 'access_arterial', 'access_venoso']);

        foreach (['position', 'machine', 'end_weight', 'brand_model', 'access_arterial', 'access_venoso'] as $field) {
            if (!$nurse->$field) {
                foreach ($previousSessions as $previousSession) {
                    if ($previousSession->$field !== null && trim((string) $previousSession->$field) !== '') {
                        $nurse->$field = $previousSession->$field;
                        break;
                    }
                }
            }
        }

        if (trim((string) $nurse->hcl) === '') {
            $sessionDateForHcl = Carbon::parse($nurse->date_order ?: $nurse->created_at);
            $sessionDayForHcl = $sessionDateForHcl->toDateString();
            $previousFinalizedHcl = Nurse::where('patient', $nurse->patient)
                ->complete()
                ->whereNotNull('hcl')
                ->where('hcl', '<>', '')
                ->where(function ($previousSessions) use ($sessionDayForHcl, $nurse) {
                    $previousSessions->where('date_order', '<', $sessionDayForHcl)
                        ->orWhere(function ($sameDay) use ($sessionDayForHcl, $nurse) {
                            $sameDay->whereDate('date_order', $sessionDayForHcl)
                                ->where('id', '<', $nurse->id);
                        });
                })
                ->orderBy('date_order', 'desc')
                ->orderBy('id', 'desc')
                ->first(['hcl']);

            if ($previousFinalizedHcl) {
                $nurse->hcl = $previousFinalizedHcl->hcl;
            }
        }

        //$generados = $nurse->where('patient', $nurse->patient)->count();

        /*if ($nurse->hd == null)
        {
            if ($generados == 1)
            {
                $nurse->nhd = '';
            }
            else
                $nurse->nhd = $generados;
        } else
        {
            $nurse->nhd;
        }*/

        $dayWeek = Carbon::parse($nurse->date_order)->dayOfWeek;

        if ($nurse->others == null)
        {
            if ($dayWeek == 1 || $dayWeek == 3 || $dayWeek == 5)
            {
                $nurse->others = "L - M - V";
            } else
            {
                $nurse->others = "M - J - S";
            }
        } else
        {
            $nurse->others = $nurse->others;
        }

        if (!$nurse->hr)
        {
            $nurse->hr2 = $nurse->hr2;
            $nurse->hr3 = $nurse->hr3;
            $nurse->hr4 = $nurse->hr4;
            $nurse->hr5 = $nurse->hr5;
            $nurse->hr6 = $nurse->hr6;
            $nurse->hr7 = $nurse->hr7;
            $nurse->hr8 = $nurse->hr8;
        }
        else
        {
            if (!$nurse->hr2)
                $nurse->hr2 = Carbon::parse($nurse->hr)->addMinutes(30)->format('H:i');
            else
                $nurse->hr2 = $nurse->hr2;

            if (!$nurse->hr3)
                $nurse->hr3 = Carbon::parse($nurse->hr)->addHour(1)->format('H:i');
            else
                $nurse->hr3 = $nurse->hr3;

            if (!$nurse->hr4)
                $nurse->hr4 = Carbon::parse($nurse->hr)->addMinutes(90)->format('H:i');
            else
                $nurse->hr4 = $nurse->hr4;

            if (!$nurse->hr5)
                $nurse->hr5 = Carbon::parse($nurse->hr)->addHour(2)->format('H:i');
            else
                $nurse->hr5 = $nurse->hr5;

            if (!$nurse->hr6)
                $nurse->hr6 = Carbon::parse($nurse->hr)->addMinutes(150)->format('H:i');
            else
                $nurse->hr6 = $nurse->hr6;

            if (!$nurse->hr7)
                $nurse->hr7 = Carbon::parse($nurse->hr)->addHour(3)->format('H:i');
            else
                $nurse->hr7 = $nurse->hr7;

            if (!$nurse->hr8)
                if ($nurse->order->medical->hour_hd == '3.15')
                {
                    $nurse->hr8 = Carbon::parse($nurse->hr)->addMinutes(195)->format('H:i');
                }
                elseif ($nurse->order->medical->hour_hd == '3.75')
                {
                    $nurse->hr8 = Carbon::parse($nurse->hr)->addMinutes(225)->format('H:i');
                }
                elseif ($nurse->order->medical->hour_hd == '3' || $nurse->order->medical->hour_hd == '3 SOLO POR HOY')
                {
                    $nurse->hr8 = Carbon::parse($nurse->hr)->addHour(3)->format('H:i');
                }
                else
                {
                    $nurse->hr8 = Carbon::parse($nurse->hr)->addMinutes(210)->format('H:i');
                }
            else
                $nurse->hr8 = $nurse->hr8;
        }

        $sessionDate = Carbon::parse($nurse->date_order ?: $nurse->created_at);
        $sessionDay = $sessionDate->toDateString();
        $completedSessionsBefore = Nurse::where('patient', $nurse->patient)
            ->whereBetween('date_order', [$sessionDate->copy()->startOfMonth()->toDateString(), $sessionDate->copy()->endOfMonth()->toDateString()])
            ->where(function ($previousSessions) use ($sessionDay, $nurse) {
                $previousSessions->where('date_order', '<', $sessionDay)
                    ->orWhere(function ($sameDay) use ($sessionDay, $nurse) {
                        $sameDay->whereDate('date_order', $sessionDay)
                            ->where('id', '<', $nurse->id);
                    });
            })
            ->complete()
            ->count();
        if (!$nurse->nhd) {
            $nurse->nhd = $completedSessionsBefore + 1;
        }

        $nurseModal = request()->query('template') === 'nurse';

        return view('nurses.edit', compact('nurse', 'users', 'nurseModal'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $nurse = Nurse::findOrFail($id);

        $request->validate([
            'medical_start_hour' => 'nullable|date_format:H:i',
            'medical_end_hour' => 'nullable|date_format:H:i',
            'hcl' => 'nullable|string|max:20',
        ]);

        $data = $request->all();

        $nurse->fill($data);
        $nurse->save();

        if ($request->exists('hcl')) {
            $this->syncPatientHcl($nurse, $nurse->hcl);
        }

        if ($request->exists('medical_start_hour') || $request->exists('medical_end_hour')) {
            $medical = $nurse->order->medical;
            if ($request->exists('medical_start_hour')) {
                $medical->start_hour = $request->input('medical_start_hour');
            }
            if ($request->exists('medical_end_hour')) {
                $medical->end_hour = $request->input('medical_end_hour');
            }
            $medical->save();
        }

        $notification = 'El Parte de enfermeria se ha actualizado correctamente.';
        return back()->with(compact('notification'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /*public function destroy(Nurse $nurse)
    {
        $nurseHcl = $nurse->hcl;
        $nurse->delete();

        $notification = "La historia de enfermeria $nurseHcl se ha eliminado correctamente.";
        return redirect('/nurses')->with(compact('notification'));
    }*/
}
