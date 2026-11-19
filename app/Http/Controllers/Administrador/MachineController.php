<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Machine;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MachineController extends Controller
{
    public function index()
    {
        $machines = Machine::orderBy('id')->get();

        return view('machines.index', compact('machines'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedMachine($request);
        Machine::create($data);

        return redirect()->route('machines.index')->with('notification', 'La máquina se agregó correctamente.');
    }

    public function update(Request $request, Machine $machine)
    {
        $machine->update($this->validatedMachine($request, $machine));

        return redirect()->route('machines.index')->with('notification', 'La configuración de la máquina se guardó correctamente.');
    }

    public function destroy(Machine $machine)
    {
        $machine->delete();

        return redirect()->route('machines.index')->with('notification', 'La máquina se eliminó.');
    }

    private function validatedMachine(Request $request, Machine $machine = null)
    {
        return $request->validate([
            'equipment' => ['required', 'string', 'max:80', Rule::unique('machines', 'equipment')->ignore($machine ? $machine->id : null)],
            'brand' => 'nullable|string|max:80',
            'model' => 'nullable|string|max:80',
            'module' => 'nullable|string|max:80',
            'position' => 'nullable|string|max:80',
        ]);
    }
}
