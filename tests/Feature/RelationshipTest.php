<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class RelationshipTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;

    public function test_crear_empresa()
    {

        $this->loginUsuario();

        $empresaFake = \App\Models\Empresa::factory()->make();

        $data = $empresaFake->toArray();
        unset($data['user_id']); // porque se genera dentro del controlador

        $response = $this->post('/empresas', $data);

        $response->assertRedirect('/empresas');

        $this->assertDatabaseHas('empresas', [
            'Nombre' => $empresaFake->Nombre,
        ]);
    }

    public function test_crear_cargo()
    {

        $this->loginUsuario();

        $cargoFake = \App\Models\Cargo::factory()->make();

        $data = $cargoFake->toArray();
        unset($data['user_id']); // porque se genera dentro del controlador

        $response = $this->post('/cargos', $data);

        $response->assertRedirect('/cargos');

        $this->assertDatabaseHas('cargos', [
            'Nombre' => $cargoFake->Nombre,
        ]);
    }

    public function test_crear_situacion()
    {

        $this->loginUsuario();

        $situacionFake = \App\Models\Situacion::factory()->make();

        $data = $situacionFake->toArray();
        unset($data['user_id']); // porque se genera dentro del controlador

        $response = $this->post('/situacions', $data);

        $response->assertRedirect('/situacions');

        $this->assertDatabaseHas('situacions', [
            'Nombre' => $situacionFake->Nombre,
        ]);
    }

    public function test_crear_estadocivil()
    {

        $this->loginUsuario();

        $estadocivilFake = \App\Models\EstadoCivil::factory()->make();

        $data = $estadocivilFake->toArray();
        unset($data['user_id']); // porque se genera dentro del controlador

        $response = $this->post('/estado_civil', $data);

        $response->assertRedirect('/estado_civil');

        $this->assertDatabaseHas('estado_civils', [
            'Nombre' => $estadocivilFake->Nombre,
        ]);
    }

    public function test_crear_comuna()
    {
        $this->loginUsuario();

        $comunaFake = \App\Models\Comuna::factory()->make();

        $data = $comunaFake->toArray();
        unset($data['user_id']); // porque se genera dentro del controlador

        $response = $this->post('/comunas', $data);

        $response->assertRedirect('/comunas');

        $this->assertDatabaseHas('comunas', [
            'Nombre' => $comunaFake->Nombre,
            'region_id' => $comunaFake->region_id,
        ]);
    }


    public function test_crear_afp()
    {
        $this->loginUsuario();

        $afpFake = \App\Models\Afp::factory()->make();

        $data = array_merge($afpFake->toArray(), [
            'tasa_cotizacion' => 11.35,
            'tasa_sis' => 1.52,
        ]);

        $response = $this->post('/afps', $data);

        $response->assertRedirect('/afps');

        $this->assertDatabaseHas('a_f_p_s', [
            'Nombre' => $afpFake->Nombre,
        ]);

        $this->assertDatabaseHas('tasa_afps', [
            'tasa_cotizacion' => 11.35,
            'tasa_sis' => 1.52,
        ]);
    }

    public function test_crear_salud()
    {

        $this->loginUsuario();

        $saludFake = \App\Models\Salud::factory()->make();

        $data = $saludFake->toArray();
        unset($data['user_id']); // porque se genera dentro del controlador

        $response = $this->post('/saluds', $data);

        $response->assertRedirect('/saluds');

        $this->assertDatabaseHas('saluds', [
            'Nombre' => $saludFake->Nombre,
        ]);

    }


    public function test_crear_region_con_numero_romano()
    {
        $this->loginUsuario(); // método helper que ya usás

        $data = [
            'Nombre' => 'Región de Prueba',
            'Numero' => 99,
            'Abreviatura' => 'RP',
            'NumeroRomano' => 'XCIX', // ← nuevo campo
        ];

        $response = $this->post('/regions', $data);

        $response->assertRedirect('/regions');

        $this->assertDatabaseHas('regions', [
            'Nombre' => 'Región de Prueba',
            'NumeroRomano' => 'XCIX',
        ]);
    }


    public function test_crear_registro_empresa()
    {
        $this->loginUsuario(); // método helper que ya usás

        $banco = \App\Models\Banco::factory()->create();
        $comuna = \App\Models\Comuna::factory()->create();

        $data = [
            'Nombre' => 'Empresa Prueba',
            'logo' => null, // no se envía imagen aquí, se puede testear aparte
            'giro' => 'Servicios Generales',
            'direccion' => 'Av. Las Palmas 123',
            'cta_corriente' => '1234567890',
            'mail_formalizado' => 'empresa@prueba.com',
            'banco_id' => $banco->id,
            'comuna_id' => $comuna->id,
            'rut' => '76.123.456-7',
        ];

        $response = $this->post('/empresas', $data);

        $response->assertRedirect('/empresas');

        $this->assertDatabaseHas('empresas', [

            'Nombre' => 'Empresa Prueba',
            'giro' => 'Servicios Generales',
            'direccion' => 'Av. Las Palmas 123',
            'cta_corriente' => '1234567890',
            'mail_formalizado' => 'empresa@prueba.com',
            'banco_id' => $banco->id,
            'comuna_id' => $comuna->id,
            'rut' => '76.123.456-7',

        ]);
    }


















    private function loginUsuario(): void
    {
        /** @var \App\Models\User $usuario */
        $usuario = \App\Models\User::factory()->create();

        $this->actingAs($usuario);
    }


    

}
