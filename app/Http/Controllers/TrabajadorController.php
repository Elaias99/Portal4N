<?php



namespace App\Http\Controllers;

use App\Http\Requests\StoreTrabajadorRequest;
use App\Http\Requests\UpdateTrabajadorRequest;
use App\Services\TrabajadorExportService; // Importa el servicio


use App\Models\Trabajador;
use App\Models\Empresa;
use App\Models\Cargo;
use App\Models\Situacion;
use App\Models\EstadoCivil;
use App\Models\Comuna;
use App\Models\AFP;
use App\Models\TasaAfp;
use App\Models\Salud;
use App\Models\TipoVestimenta;
use App\Models\Talla;
use App\Models\Region;
use App\Models\Hijo;
use App\Models\SistemaTrabajo;
use App\Models\Turno;
use App\Models\User;
use App\Models\Jefe;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;



class TrabajadorController extends Controller
{

    protected $exportService;

    // Inyectamos el servicio en el constructor
    public function __construct(TrabajadorExportService $exportService)
    {
        $this->exportService = $exportService;
    }


    public function index(Request $request)
    {
        $query = $request->input('search');

        $cargoId = $request->input('cargo_id');
        $empresaId = $request->input('empresa_id');
        $situacionId = $request->input('situacion_id');

        // En lugar de 1, le asignamos 'Sí' si está marcado el checkbox
        $casino = $request->has('casino') ? 'Sí' : null;
        $contratoFirmado = $request->has('contrato_firmado') ? 'Sí' : null;

    $empleados = Trabajador::when($query, function ($queryBuilder) use ($query) {
            $queryBuilder->where('Nombre', 'like', "%$query%")
                         ->orWhere('ApellidoPaterno', 'like', "%$query%")
                         ->orWhere('ApellidoMaterno', 'like', "%$query%")
                         ->orWhere('Rut', 'like', "%$query%");
        })
        ->when($cargoId, function ($queryBuilder) use ($cargoId) {
            $queryBuilder->where('cargo_id', $cargoId);
        })
        ->when($empresaId, function ($queryBuilder) use ($empresaId) {
            $queryBuilder->where('empresa_id', $empresaId);
        })
        ->when($situacionId, function ($queryBuilder) use ($situacionId) {
            $queryBuilder->where('situacion_id', $situacionId);
        })
        ->when($casino, function ($queryBuilder) use ($casino) {
            $queryBuilder->where('casino', $casino);
        })
        ->when($contratoFirmado, function ($queryBuilder) use ($contratoFirmado) {
            $queryBuilder->where('ContratoFirmado', $contratoFirmado);
        })
        ->get();

        // 2. Marcar empleados que están de cumpleaños
        $empleados = $this->markBirthdays($empleados);

        // 3. Ordenar empleados por cumpleaños y nombre
        $empleados = $this->sortEmpleados($empleados);

        // 4. Obtener datos para los filtros
        $cargos = Cargo::all();
        $empresas = Empresa::all();
        $situaciones = Situacion::all();

        // 5. Retornar la vista con los empleados ordenados y los filtros
        return view('empleados.index', [
            'empleados' => $empleados,
            'cargos' => $cargos,
            'empresas' => $empresas,
            'situaciones' => $situaciones
        ]);
    }




    public function create()
    {
        $empresas = Empresa::all();
        $cargos = Cargo::all();
        $situacions = Situacion::all();
        $estadoCivils = EstadoCivil::all();
        $comunas = Comuna::all();
        $afps = AFP::all();
        $saluds = Salud::all();
        $regiones = Region::with('comunas')->get(); // Obtener regiones con sus comunas
        $turnos = Turno::all();
        $tipoVestimentas = TipoVestimenta::all(); // Añadimos la lista de tipo de vestimentas
        $sistemasTrabajo = SistemaTrabajo::all();
        $jefes = Jefe::all(); // Obtener todos los jefes
    
        return view('empleados.create', compact('empresas', 'cargos', 'situacions', 'estadoCivils', 'comunas', 'afps', 'saluds', 'tipoVestimentas', 'regiones', 'turnos','sistemasTrabajo','jefes'));
    }


    public function store(StoreTrabajadorRequest $request)
    {
        // dd($request->all());
        // 1. Validar los datos del formulario
        $validated = $request->validated();

        // Manejar la opción "Otro" para Cargo
        $validated['cargo_id'] = $this->handleOtroOption($request, 'cargo_id', Cargo::class, 'nuevo_cargo') ?? $validated['cargo_id'];

        // Manejar la opción "Otro" para Salud
        $validated['salud_id'] = $this->handleOtroOption($request, 'salud_id', Salud::class, 'nuevo_salud') ?? $validated['salud_id'];

        // Manejar la opción "Otro" para Estado Civil
        $validated['estado_civil_id'] = $this->handleOtroOption($request, 'estado_civil_id', EstadoCivil::class, 'nuevo_estado_civil') ?? $validated['estado_civil_id'];
        
        // Manejar la opción "Otro" para Turno
        $validated['turno_id'] = $this->handleOtroOption($request, 'turno_id', Turno::class, 'nuevo_turno') ?? $validated['turno_id'];

        //Manejar la opción 'Otro' para Sistema de trabajo
        $validated['sistema_trabajo_id'] = $this->handleOtroOption($request, 'sistema_trabajo_id', SistemaTrabajo::class, 'nuevo_sistema_trabajo') ?? $validated['sistema_trabajo_id'];

        //Manjear la opción 'Otro' para Situación
        $validated['situacion_id'] = $this->handleOtroOption($request, 'situacion_id', Situacion::class, 'nuevo_situacion') ?? $validated['situacion_id'];

        //Manjear la opción 'Otro' para AFP
        // Manjear la opción 'Otro' para AFP (sin pasar las tasas aquí)
        $validated['afp_id'] = $this->handleOtroOption($request, 'afp_id', AFP::class, 'nuevo_afp') ?? $validated['afp_id'];


        // 3. Subir y guardar la foto si se proporciona
        $validated['Foto'] = $this->uploadFoto($request);


        // 4. Generar automáticamente el correo corporativo limpiando el nombre y apellido
        $corporateEmail = $this->generateCorporateEmail($request->Nombre, $request->ApellidoPaterno, $request->ApellidoMaterno);


        // 5. Crear un usuario en la tabla `users`
        $user = User::create([
            'name' => $request->Nombre,
            'email' => $corporateEmail,
            'password' => bcrypt('12345678'),
        ]);

        // 6. Crear el trabajador en la tabla `trabajadors` y vincularlo con el usuario
        $trabajador = Trabajador::create(array_merge(
            $validated,
            ['user_id' => $user->id]
        ));

        $this->saveTallas($request, $trabajador->id);
        $this->saveHijos($request, $trabajador->id);

        // 9. Redirigir con un mensaje de éxito
        return redirect()->route('empleados.index')->with('success', 'Trabajador y usuario creados exitosamente.');
    }


    public function edit($id)
    {
        $empleado = Trabajador::findOrFail($id);
        $empresas = Empresa::all();
        $cargos = Cargo::all();
        $situacions = Situacion::all();
        $estadoCivils = EstadoCivil::all();
        $comunas = Comuna::all();
        $regiones = Region::with('comunas')->get(); // Obtener regiones con sus comunas
        $afps = AFP::all();
        $turnos = Turno::all();
        $saluds = Salud::all();
        $tipoVestimentas = TipoVestimenta::all();
        $sistemasTrabajo = SistemaTrabajo::all(); // Obtener todos los sistemas de trabajo
        
        // Obtener los hijos del empleado
        $hijos = Hijo::where('trabajador_id', $id)->get();
        $tallas = Talla::where('trabajador_id', $id)->get()->keyBy('tipo_vestimenta_id');

        $jefes = Jefe::all(); // Obtener todos los jefes
        
        return view('empleados.edit', compact('empleado', 'empresas', 'cargos', 'situacions', 'estadoCivils', 'comunas', 'afps', 'saluds', 'tipoVestimentas', 'hijos', 'regiones', 'tallas', 'turnos','sistemasTrabajo','jefes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTrabajadorRequest $request, $id)
    {

        // dd($request->all());


        // 1. Validamos los datos del formulario
        $validated = $request->validated();
        $trabajador = Trabajador::findOrFail($id);

        // 2. Manejar la opción "Otro" para Cargo
        $validated['cargo_id'] = $this->handleOtroOption($request, 'cargo_id', Cargo::class, 'nuevo_cargo') ?? $validated['cargo_id'];

        // 3. Manejar la opción "Otro" para Salud
        $validated['salud_id'] = $this->handleOtroOption($request, 'salud_id', Salud::class, 'nuevo_salud') ?? $validated['salud_id'];

        // 4. Manejar la opción "Otro" para Estado Civil
        $validated['estado_civil_id'] = $this->handleOtroOption($request, 'estado_civil_id', EstadoCivil::class, 'nuevo_estado_civil') ?? $validated['estado_civil_id'];

        // Manjear la opción 'Otro' para sistema de trabajo
        $validated['sistema_trabajo_id'] = $this->handleOtroOption($request, 'sistema_trabajo_id', SistemaTrabajo::class, 'nuevo_sistema_trabajo') ?? $validated['sistema_trabajo_id'];

        //Manjear la opción 'Otro' para Situación
        $validated['situacion_id'] = $this->handleOtroOption($request, 'situacion_id', Situacion::class, 'nuevo_situacion') ?? $validated['situacion_id'];

        // Manjear la opción 'Otro' para AFP (sin pasar las tasas aquí)
        $validated['afp_id'] = $this->handleOtroOption($request, 'afp_id', AFP::class, 'nuevo_afp') ?? $validated['afp_id'];

 
        // Manejar la opción "Otro" para Turno
        // 3. Manejar específicamente la opción "Otro" para Turno
        $nuevoTurnoId = $this->handleOtroOption($request, 'turno_id', Turno::class, 'nuevo_turno');
        if ($nuevoTurnoId !== null) {
            // Reemplazamos el valor de 'turno_id' por el nuevo ID si se seleccionó "otro"
            $validated['turno_id'] = $nuevoTurnoId;
        }
        // 4. Subir y guardar la foto si se proporciona
        // $validated['Foto'] = $this->uploadFoto($request, $trabajador);

        // 4. Subir y guardar la foto si se proporciona
        if ($request->hasFile('Foto')) {
            // Subir la nueva foto y eliminar la anterior si existe
            $this->deleteOldPhoto($trabajador->Foto); // Función para eliminar foto antigua
            $validated['Foto'] = $this->uploadFoto($request, $trabajador);
        } else {
            unset($validated['Foto']); // Remover 'Foto' del array si no se sube una nueva
        }


        // 5. Actualizar el trabajador con los datos validados
        $trabajador->update($validated);

        // 6. Verificar si el trabajador tiene un usuario asociado
        if ($trabajador->user_id) {
            // $user = $trabajador->user;
            // if ($user) {
            //     // 7. Generar el correo corporativo aplicando la limpieza
            //     $corporateEmail = $this->generateCorporateEmail($request->Nombre, $request->ApellidoPaterno, $request->ApellidoMaterno);

            //     if ($user->email !== $corporateEmail) {
            //         $user->update(['email' => $corporateEmail]);
            //     }
            // }
        }

        // 8. Actualizar el correo personal del trabajador en la tabla trabajadors
        if ($request->CorreoPersonal) {
            $trabajador->update(['CorreoPersonal' => $request->CorreoPersonal]);
        }

        // 9. Guardar los turnos y otros campos adicionales si es necesario
        
        // $trabajador->sistema_trabajo_id = $request->input('sistema_trabajo_id');
        $trabajador->save();

        // 10. Guardar las tallas y los hijos
        $this->saveTallas($request, $trabajador->id);
        $this->saveHijos($request, $trabajador->id);

        // 11. Redirigir con un mensaje de éxito
        return redirect()->route('empleados.index')->with('success', 'Trabajador y usuario actualizados exitosamente');
    }



    public function destroy($id)
    {
        $empleado = Trabajador::findOrFail($id);
        $empleado->delete();
    
        return redirect()->route('empleados.index')->with('success', 'Empleado eliminado exitosamente');
    }


    public function mostrarLocalidad()
    {
        // Obtener todos los empleados junto con su comuna y región, ordenados por el nombre de los empleados
        $empleados = Trabajador::with('comuna.region')
                            ->orderBy('Nombre', 'asc') // Ordenar alfabéticamente por nombre
                            ->get();

        // Pasar los empleados a la vista
        return view('empleados.localidades', compact('empleados'));
    }





    //////////////////////////////////////////////////////////////////////////
    ////////  EXPORTACIÓN A PDF O EXCEL DEL LISTADO DE LOS EMPLEADOS  ////////
    /////////////////////////////////////////////////////////////////////////


    // Método para exportar PDF el listado de los empledos
    public function exportPdf()
    {
        return $this->exportService->exportPdf();
    }

    // Método para exportar Excel el listado de los empledos
    public function exportExcel()
    {
        return $this->exportService->exportExcel();
    }

    //Método para exportar en formato PDF, las liquidaciones pero para cada empleado
    public function exportCotizacion($id)
    {
        // Aumenta el tiempo máximo de ejecución a 300 segundos (5 minutos)
        set_time_limit(300);

        // Aumenta el límite de memoria a 256 MB
        ini_set('memory_limit', '256M');

        // Obtén el trabajador por su ID
        $trabajador = Trabajador::findOrFail($id);

        // Delegar la exportación al servicio
        return $this->exportService->exportCotizacionPdf($trabajador);
    }




    /////////////////////////////////////////////////////////
    /////    MÉTODOS REUTILIZABLES Y PRIVADOS       ////////
    ///////////////////////////////////////////////////////


    // Método privado para limpiar tildes y mayúsculas
    private function cleanString($string) {
        $search = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'];
        $replace = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'];
        $cleaned = str_replace($search, $replace, $string);
        $cleaned = strtolower($cleaned);
        $cleaned = str_replace(' ', '', $cleaned);
        return $cleaned;
    }

    //Método que genera automáticamente el correo corporativo limpiando el nombre y apellido
    private function generateCorporateEmail($nombre, $apellidoPaterno, $apellidoMaterno) {
        return $this->cleanString($nombre) . '.' . $this->cleanString($apellidoPaterno) .'.'.$this->cleanString($apellidoMaterno). '@4nlogistica.cl';
    }

    //Método para guardar hijos:
    private function saveHijos($request, $trabajadorId) {
        // Eliminar los hijos existentes
        Hijo::where('trabajador_id', $trabajadorId)->delete();
    
        // Guardar los nuevos hijos
        if ($request->has('hijos')) {
            foreach ($request->input('hijos') as $hijoData) {
                if (!empty($hijoData['nombre'])) {
                    Hijo::create([
                        'nombre' => $hijoData['nombre'],
                        'genero' => $hijoData['genero'],
                        'parentesco' => $hijoData['parentesco'],
                        'fecha_nacimiento' => $hijoData['fecha_nacimiento'],
                        'trabajador_id' => $trabajadorId,
                    ]);
                }
            }
        }
    }

    //Método para guardar tallas
    private function saveTallas($request, $trabajadorId) {
        if ($request->has('tallas')) {
            foreach ($request->input('tallas') as $tipoVestimentaId => $tallaData) {
                // Verifica si se seleccionó "Otro" y se proporcionó un valor personalizado
                $talla = isset($tallaData['talla']) && $tallaData['talla'] === 'otro' && !empty($tallaData['custom'])
                    ? $tallaData['custom']
                    : ($tallaData['talla'] ?? null); // Usar directamente "talla" si no es "Otro"
    
                // Validar que la talla no esté vacía
                if (!$talla) {
                    continue; // Saltar esta iteración si no hay una talla válida
                }
    
                // Crear o actualizar la talla en la base de datos
                Talla::updateOrCreate(
                    [
                        'trabajador_id' => $trabajadorId,
                        'tipo_vestimenta_id' => $tipoVestimentaId,
                    ],
                    [
                        'talla' => $talla,
                    ]
                );
            }
        }
    }
    
    //Método privado para manejar la lógica para subir y actualizar imagen
    private function uploadFoto($request, $trabajador = null)
    {
        // Verificar si se ha proporcionado una foto
        if ($request->hasFile('Foto')) {
            // Si ya existe una foto y estamos actualizando, eliminar la foto anterior
            if ($trabajador && $trabajador->Foto) {
                Storage::delete('public/' . $trabajador->Foto);
            }

            // Subir y guardar la nueva foto
            return $request->file('Foto')->store('uploads', 'public');
        }

        return null; // Si no se proporciona ninguna foto, retornamos null
    }

    private function deleteOldPhoto($oldPhoto)
    {
        if ($oldPhoto && Storage::exists($oldPhoto)) {
            Storage::delete($oldPhoto);
        }
    }


    /////////////////////////////////////////////////////////////////////////////////////////////////////
    ///// MÉTODOS PRIVADOS PARA REUTILIZARLO EN EL MÉTODO PUBLICO QUE MUESTRA A LOS EMPLEADO, 'INDEX'////
    /////////////////////////////////////////////////////////////////////////////////////////////////////

    private function searchEmpleados($query)
    {
        return Trabajador::when($query, function ($queryBuilder) use ($query) {
            $queryBuilder->where('Nombre', 'like', "%$query%")
                        ->orWhere('ApellidoPaterno', 'like', "%$query%")
                        ->orWhere('ApellidoMaterno', 'like', "%$query%")
                        ->orWhere('Rut', 'like', "%$query%");
        })->get();
    }

    private function markBirthdays($empleados)
    {
        $today = \Carbon\Carbon::now();

        foreach ($empleados as $empleado) {
            if ($empleado->FechaNacimiento->format('m-d') == $today->format('m-d')) {
                $empleado->is_birthday = true;
            } else {
                $empleado->is_birthday = false;
            }
        }

        return $empleados;
    }

    private function sortEmpleados($empleados)
    {
        return $empleados->sortBy('Nombre')->sortByDesc('is_birthday');
    }


    // Maneja la creación de un nuevo registro cuando la opción "Otro" es seleccionada en un campo de selección (dropdown).
    //Este método es reutilizable para cualquier modelo que requiera permitir la opción de agregar un nuevo valor desde el formulario.
    private function handleOtroOption($request, $fieldName, $modelName, $newFieldName)
    {
        if ($request->input($fieldName) == 'otro') {
            if ($request->filled($newFieldName)) {
                // Verificar si ya existe un registro con el mismo nombre o nombre
                $existingRecord = $modelName::where('Nombre', $request->input($newFieldName))
                                            ->orWhere('nombre', $request->input($newFieldName))
                                            ->first();

                if ($existingRecord) {
                    // Si ya existe, retornar el ID del registro existente
                    return $existingRecord->id;
                } else {
                    // Si es AFP, capturar las tasas y crear los registros en AFP y TasaAfp
                    if ($modelName === AFP::class) {
                        // Crear nueva AFP
                        $afp = AFP::create([
                            'Nombre' => $request->input($newFieldName),
                        ]);


                        // Crear la entrada en TasaAfp asociada con la nueva AFP
                        TasaAfp::create([
                            'id_afp' => $afp->id, // Relación con la AFP recién creada
                            'tasa_cotizacion' => $request->input('tasa_cotizacion'),
                            'tasa_sis' => $request->input('tasa_sis'),
                        ]);

                        // Debug para verificar que se haya insertado correctamente
                        // dd('TasaAfp created successfully');

                        // Retornar el ID del nuevo AFP
                        return $afp->id;
                    } else {
                        // Crear otros modelos sin tasas
                        $modelInstance = $modelName::create([
                            'Nombre' => $request->input($newFieldName),
                            'nombre' => $request->input($newFieldName)
                        ]);

                        // Retornar el ID del nuevo registro
                        return $modelInstance->id;
                    }
                }
            } else {
                // Si no se proporcionó un valor para el nuevo campo, retornar un error
                return back()->withErrors([$newFieldName => 'Debe ingresar un nombre para el nuevo valor.'])->withInput();
            }
        }

        return null; // Si no se selecciona "otro", no se hace nada
    }





}