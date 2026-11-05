<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // RF-60: los mensajes en español no dependen del idioma de la aplicación.
        config(['app.locale' => 'en']);
        app()->setLocale('en');
    }

    /**
     * @return array<string, string>
     */
    private function validRegistration(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ana Prueba',
            'email' => 'ana@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ], $overrides);
    }

    // RF-1
    public function test_a_visitor_registers_is_logged_in_and_goes_to_the_catalog(): void
    {
        $response = $this->post(route('auth.register'), $this->validRegistration());

        $response->assertRedirect(route('home'));

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame('Ana Prueba', $user->name);
        $this->assertTrue(Hash::check('secreto123', $user->password));
        $this->assertAuthenticatedAs($user);

        $this->get(route('home'))->assertOk();
    }

    // RF-2
    public function test_registering_with_is_admin_does_not_grant_admin_permissions(): void
    {
        $this->post(route('auth.register'), $this->validRegistration(['is_admin' => '1']))
            ->assertRedirect(route('home'));

        $this->assertFalse(User::where('email', 'ana@example.com')->firstOrFail()->is_admin);
        $this->get(route('admin.index'))->assertForbidden();
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function invalidRegistrations(): array
    {
        return [
            'nombre vacío' => [['name' => ''], 'El nombre es obligatorio.'],
            'email vacío' => [['email' => ''], 'El email es obligatorio.'],
            'contraseña vacía' => [['password' => '', 'password_confirmation' => ''], 'La contraseña es obligatoria.'],
            'email con formato incorrecto' => [['email' => 'no-es-un-email'], 'El email debe ser válido.'],
            'email ya registrado' => [['email' => 'ocupado@example.com'], 'Este email ya está registrado.'],
            'contraseña corta' => [['password' => 'corta12', 'password_confirmation' => 'corta12'], 'La contraseña debe tener al menos 8 caracteres.'],
            'confirmación distinta' => [['password_confirmation' => 'otra-cosa-123'], 'Las contraseñas no coinciden.'],
        ];
    }

    // RF-3
    #[DataProvider('invalidRegistrations')]
    public function test_an_invalid_registration_creates_nothing_and_shows_the_error_keeping_name_and_email(array $overrides, string $message): void
    {
        User::factory()->create(['email' => 'ocupado@example.com']);
        $usersBefore = User::count();

        $data = $this->validRegistration($overrides);

        $response = $this->from(route('register'))
            ->followingRedirects()
            ->post(route('auth.register'), $data);

        $response->assertOk();
        $response->assertSee($message);
        $response->assertDontSee('validation.');

        if ($data['name'] !== '') {
            $response->assertSee('value="'.$data['name'].'"', false);
        }
        if ($data['email'] !== '') {
            $response->assertSee('value="'.$data['email'].'"', false);
        }

        $this->assertSame($usersBefore, User::count());
        $this->assertGuest();
    }

    // RF-4
    public function test_correct_credentials_open_the_session_and_go_to_the_catalog(): void
    {
        $user = User::factory()->create(['password' => 'secreto123']);

        $this->post(route('auth.login'), ['email' => $user->email, 'password' => 'secreto123'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('home'))->assertOk();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function wrongCredentials(): array
    {
        return [
            'contraseña incorrecta' => ['ana@example.com', 'contraseña-mala'],
            'email que no existe' => ['nadie@example.com', 'secreto123'],
        ];
    }

    // RF-5
    #[DataProvider('wrongCredentials')]
    public function test_wrong_credentials_do_not_open_the_session_and_show_a_spanish_error(string $email, string $password): void
    {
        User::factory()->create(['email' => 'ana@example.com', 'password' => 'secreto123']);

        $response = $this->from(route('login'))
            ->followingRedirects()
            ->post(route('auth.login'), ['email' => $email, 'password' => $password]);

        $response->assertOk();
        $response->assertSee('Credenciales incorrectas');
        $response->assertSee('value="'.$email.'"', false);
        $this->assertGuest();
    }

    // RF-6
    public function test_logging_out_ends_the_session_and_goes_to_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    // RF-7: recorre las rutas reales, para que una ruta nueva no pueda quedar fuera.
    public function test_a_guest_is_redirected_to_login_from_every_protected_route(): void
    {
        $checked = 0;

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! in_array('auth', $route->gatherMiddleware(), true)) {
                continue;
            }

            $uri = preg_replace('/\{[^}]+\}/', '1', $route->uri());

            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $this->call($method, '/'.ltrim($uri, '/'))->assertRedirect(route('login'));
                $checked++;
            }
        }

        $this->assertGreaterThan(30, $checked);
        $this->assertGuest();
    }

    // RF-7: el caso contrario, solo login, registro y las rutas técnicas quedan sin `auth`.
    public function test_only_login_and_registration_are_open_to_guests(): void
    {
        $open = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! in_array('auth', $route->gatherMiddleware(), true)) {
                $open[] = $route->getName() ?? $route->uri();
            }
        }

        $technical = ['storage.local', 'storage.local.upload', 'up'];

        $this->assertEqualsCanonicalizing(
            ['login', 'register', 'auth.login', 'auth.register'],
            array_values(array_diff($open, $technical))
        );

        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    // RF-8
    public function test_a_logged_in_user_is_sent_to_the_catalog_from_login_and_registration(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get(route('login'))->assertRedirect(route('home'));
        $this->get(route('register'))->assertRedirect(route('home'));
        $this->post(route('auth.login'), ['email' => $user->email, 'password' => 'x'])->assertRedirect(route('home'));
        $this->post(route('auth.register'), $this->validRegistration())->assertRedirect(route('home'));

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    // RF-9
    public function test_password_fields_have_a_button_to_show_them_and_start_hidden(): void
    {
        $this->get(route('login'))
            ->assertSee('<input type="password" class="form-control" id="password"', false)
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('aria-label="Mostrar contraseña"', false);

        $this->get(route('register'))
            ->assertSee('<input type="password" class="form-control" id="password"', false)
            ->assertSee('<input type="password" class="form-control" id="password_confirmation"', false)
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false);
    }

    public function test_behind_the_https_proxy_forms_and_assets_use_https(): void
    {
        $url = 'http://urrushisha.test/login';

        $response = $this->withHeaders(['X-Forwarded-Proto' => 'https'])->get($url)->assertOk();

        $response->assertSee('action="https://urrushisha.test/auth/login"', false);
        $response->assertSee('href="https://urrushisha.test/images/favicon-urrushisha.png"', false);
        $response->assertDontSee('action="http://', false);
    }

    public function test_login_and_registration_show_the_logo_and_every_page_has_its_icon(): void
    {
        foreach ([route('login'), route('register')] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('images/logo-urrushisha-grande.png', false)
                ->assertSee('alt="UrruShisha"', false)
                ->assertSee('images/favicon-urrushisha.png', false);
        }
    }

    // RF-1 a 4: el logo nuevo conserva los nombres y las medidas, así que el HTML no cambia
    public function test_the_logo_images_keep_their_sizes(): void
    {
        $sizes = [
            'logo-urrushisha.png' => [176, 151],
            'logo-urrushisha-grande.png' => [400, 342],
            'favicon-urrushisha.png' => [64, 55],
        ];

        foreach ($sizes as $file => [$width, $height]) {
            $info = getimagesize(public_path('images/'.$file));

            $this->assertNotFalse($info, $file);
            $this->assertSame([$width, $height], [$info[0], $info[1]], $file);
            $this->assertSame('image/png', $info['mime'], $file);
        }
    }

    public function test_the_login_page_does_not_include_the_registration_script(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Iniciar sesión')
            ->assertDontSee('registerForm')
            ->assertDontSee('/auth/register');
    }

    /**
     * Un caso por cada regla de las filas de Login y Registro de RF-54.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>, 3: string, 4: string}>
     */
    public static function spanishMessages(): array
    {
        $tooLongEmail = str_repeat('a', 60).'@'.str_repeat(str_repeat('b', 60).'.', 4).'example.com';

        return [
            'login email obligatorio' => ['auth.login', 'login', ['email' => '', 'password' => 'x'], 'email', 'El email es obligatorio.'],
            'login email formato' => ['auth.login', 'login', ['email' => 'mal', 'password' => 'x'], 'email', 'El email debe ser válido.'],
            'login contraseña obligatoria' => ['auth.login', 'login', ['email' => 'a@example.com', 'password' => ''], 'password', 'La contraseña es obligatoria.'],
            'login contraseña texto' => ['auth.login', 'login', ['email' => 'a@example.com', 'password' => ['x']], 'password', 'La contraseña debe ser un texto.'],

            'registro nombre obligatorio' => ['auth.register', 'register', ['name' => ''], 'name', 'El nombre es obligatorio.'],
            'registro nombre texto' => ['auth.register', 'register', ['name' => ['x']], 'name', 'El nombre debe ser un texto.'],
            'registro nombre máximo' => ['auth.register', 'register', ['name' => str_repeat('a', 256)], 'name', 'El nombre no puede superar los 255 caracteres.'],
            'registro email obligatorio' => ['auth.register', 'register', ['email' => ''], 'email', 'El email es obligatorio.'],
            'registro email texto' => ['auth.register', 'register', ['email' => ['x']], 'email', 'El email debe ser un texto.'],
            'registro email formato' => ['auth.register', 'register', ['email' => 'mal'], 'email', 'El email debe ser válido.'],
            'registro email máximo' => ['auth.register', 'register', ['email' => $tooLongEmail], 'email', 'El email no puede superar los 255 caracteres.'],
            'registro email ya registrado' => ['auth.register', 'register', ['email' => 'ocupado@example.com'], 'email', 'Este email ya está registrado.'],
            'registro contraseña obligatoria' => ['auth.register', 'register', ['password' => ''], 'password', 'La contraseña es obligatoria.'],
            'registro contraseña texto' => ['auth.register', 'register', ['password' => ['x']], 'password', 'La contraseña debe ser un texto.'],
            'registro contraseña mínimo' => ['auth.register', 'register', ['password' => 'corta12', 'password_confirmation' => 'corta12'], 'password', 'La contraseña debe tener al menos 8 caracteres.'],
            'registro contraseña confirmación' => ['auth.register', 'register', ['password_confirmation' => 'otra-cosa-123'], 'password', 'Las contraseñas no coinciden.'],
        ];
    }

    // RF-54 y RF-60 (filas de Login y Registro)
    #[DataProvider('spanishMessages')]
    public function test_each_rule_has_its_own_spanish_message_with_the_application_in_english(
        string $action,
        string $form,
        array $payload,
        string $field,
        string $message
    ): void {
        User::factory()->create(['email' => 'ocupado@example.com']);

        $data = $action === 'auth.register' ? array_merge($this->validRegistration(), $payload) : $payload;

        $this->from(route($form))
            ->post(route($action), $data)
            ->assertSessionHasErrors([$field => $message]);

        $this->assertGuest();

        // El mensaje llega a la pantalla, sin claves de traducción ni texto en inglés.
        $page = $this->get(route($form));
        $page->assertOk();
        $page->assertSee($message);
        $page->assertDontSee('validation.');
        $page->assertDontSee('The '.$field);
    }

    /**
     * Un array en un campo de texto se conserva en la sesión con withInput y volvía
     * a pintarse en el formulario. La pantalla debe mostrar el error, no fallar.
     *
     * @return array<string, array{0: string, 1: string, 2: array<string, mixed>, 3: string}>
     */
    public static function arrayInputs(): array
    {
        return [
            'login email' => ['auth.login', 'login', ['email' => ['x'], 'password' => 'x'], 'El email debe ser válido.'],
            'registro nombre' => ['auth.register', 'register', ['name' => ['x'], 'email' => 'a@example.com', 'password' => 'secreto123', 'password_confirmation' => 'secreto123'], 'El nombre debe ser un texto.'],
            'registro email' => ['auth.register', 'register', ['name' => 'Ana', 'email' => ['x'], 'password' => 'secreto123', 'password_confirmation' => 'secreto123'], 'El email debe ser un texto.'],
        ];
    }

    // RF-54 sin error de servidor
    #[DataProvider('arrayInputs')]
    public function test_an_array_in_a_text_field_shows_the_error_instead_of_breaking_the_form(string $action, string $form, array $payload, string $message): void
    {
        $response = $this->from(route($form))
            ->followingRedirects()
            ->post(route($action), $payload);

        $response->assertOk();
        $response->assertSee($message);
        $this->assertGuest();
    }
}
