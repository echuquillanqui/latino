<?php

namespace App\Http\Controllers\Administrador;

use App\Exports\MedicationAttendancesWorkbookExport;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MedicationReportController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'period' => 'nullable|date_format:Y-m',
        ]);
        $period = $request->input('period', Carbon::now()->format('Y-m'));

        return view('reports.medications', compact('period'));
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:todas,finalizada,incompleta',
            'period' => 'required|date_format:Y-m',
        ]);

        return Excel::download(
            new MedicationAttendancesWorkbookExport($validated['status'], $validated['period']),
            'reporte-medicamentos-atenciones-' . $validated['period'] . '.xlsx'
        );
    }
}
