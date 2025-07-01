<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Trabajador;
use App\Models\User;


class TrabajadorTest extends TestCase
{
    use RefreshDatabase;

    
    /** @test */
    public function test_usuario_puede_crear_un_trabajador_desde_formulario()
    {
        /** @var \App\Models\User $usuario */
        $usuario = User::factory()->create();

        $this->actingAs($usuario);

        $trabajadorFake = \App\Models\Trabajador::factory()->make();

        $data = $trabajadorFake->toArray();
        unset($data['user_id']); // porque se genera dentro del controlador

        $tipoVestimenta = \App\Models\TipoVestimenta::factory()->create([
            'Nombre' => 'Polera' // o cualquier nombre de los usados en el modal
        ]);

        $data['tallas'] = [
            $tipoVestimenta->id => ['talla' => 'M']
        ];


        $response = $this->post('/empleados', $data);

        $response->assertRedirect('/empleados'); // o route('empleados.index')

        $this->assertDatabaseHas('trabajadors', [
            'Rut' => $trabajadorFake->Rut,
        ]);
    }



}
