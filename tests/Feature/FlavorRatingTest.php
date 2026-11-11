<?php

namespace Tests\Feature;

use App\Models\Flavor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Spec 011, T1: guardar, cambiar y quitar la valoración de un sabor.
 */
class FlavorRatingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Flavor $flavor;

    protected function setUp(): void
    {
        parent::setUp();

        // Los mensajes no dependen del idioma de la aplicación.
        config(['app.locale' => 'en']);
        app()->setLocale('en');

        $this->user = User::factory()->create();
        $this->flavor = Flavor::factory()->create(['name' => 'Menta']);

        $this->actingAs($this->user);
    }

    /**
     * @return array<int, int> user_id => stars
     */
    private function savedRatings(?Flavor $flavor = null): array
    {
        return DB::table('flavor_ratings')
            ->where('flavor_id', ($flavor ?? $this->flavor)->id)
            ->pluck('stars', 'user_id')
            ->map(fn ($stars) => (int) $stars)
            ->all();
    }

    private function rateAs(User $user, int $stars): void
    {
        DB::table('flavor_ratings')->insert([
            'user_id' => $user->id,
            'flavor_id' => $this->flavor->id,
            'stars' => $stars,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // RF2: crear y después cambiar la puntuación propia, sin duplicarla.
    public function test_a_user_rates_and_then_changes_the_rating(): void
    {
        $this->from(route('home'))
            ->put(route('ratings.update', $this->flavor), ['stars' => 4])
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Valoración guardada.');

        $this->put(route('ratings.update', $this->flavor), ['stars' => 2]);

        $this->assertSame([$this->user->id => 2], $this->savedRatings());
    }

    // RF2: quitar la puntuación propia; si no había, no pasa nada. No toca las de otros.
    public function test_a_user_removes_the_rating(): void
    {
        $other = User::factory()->create();
        $this->rateAs($other, 5);
        $this->put(route('ratings.update', $this->flavor), ['stars' => 3]);

        $this->from(route('home'))
            ->delete(route('ratings.destroy', $this->flavor))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', 'Valoración quitada.');
        $this->delete(route('ratings.destroy', $this->flavor))->assertRedirect();

        $this->assertSame([$other->id => 5], $this->savedRatings());
    }

    // RF2 y RF3: con fetch responde la puntuación propia, la media con un decimal y el número.
    public function test_with_fetch_it_answers_the_average_and_count(): void
    {
        $this->rateAs(User::factory()->create(), 5);
        $this->rateAs(User::factory()->create(), 4);

        $this->putJson(route('ratings.update', $this->flavor), ['stars' => 4])
            ->assertOk()
            ->assertExactJson(['stars' => 4, 'average' => 4.3, 'count' => 3, 'message' => 'Valoración guardada.']);

        $this->deleteJson(route('ratings.destroy', $this->flavor))
            ->assertOk()
            ->assertExactJson(['stars' => null, 'average' => 4.5, 'count' => 2, 'message' => 'Valoración quitada.']);
    }

    // RF3: sin valoraciones no hay media.
    public function test_without_ratings_the_average_is_null(): void
    {
        $this->putJson(route('ratings.update', $this->flavor), ['stars' => 1]);

        $this->deleteJson(route('ratings.destroy', $this->flavor))
            ->assertOk()
            ->assertExactJson(['stars' => null, 'average' => null, 'count' => 0, 'message' => 'Valoración quitada.']);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function invalidStars(): array
    {
        return [
            'sin estrellas' => [null],
            'cero' => [0],
            'seis' => [6],
            'decimal' => ['2.5'],
            'texto' => ['tres'],
            'array' => [[3]],
            'desbordado' => ['99999999999999999999'],
        ];
    }

    // RF2: solo de 1 a 5, con su mensaje, y sin guardar nada.
    #[DataProvider('invalidStars')]
    public function test_invalid_stars_are_rejected_without_saving(mixed $stars): void
    {
        $this->from(route('home'))
            ->put(route('ratings.update', $this->flavor), ['stars' => $stars])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['stars' => 'La valoración debe ser de 1 a 5 estrellas.']);

        $this->putJson(route('ratings.update', $this->flavor), ['stars' => $stars])->assertUnprocessable();

        $this->assertSame([], $this->savedRatings());
    }

    // RF2: un sabor no público o inexistente da 404 y no guarda nada.
    public function test_a_hidden_or_missing_flavor_cannot_be_rated(): void
    {
        $hidden = Flavor::factory()->notPublic()->create();

        $this->put(route('ratings.update', $hidden), ['stars' => 4])->assertNotFound();
        $this->delete(route('ratings.destroy', $hidden))->assertNotFound();
        $this->put('/flavors/999999/rating', ['stars' => 4])->assertNotFound();

        $this->assertSame(0, DB::table('flavor_ratings')->count());
    }

    // RF2: un visitante va a login y no se guarda nada.
    public function test_a_guest_cannot_rate(): void
    {
        auth()->logout();

        $this->put(route('ratings.update', $this->flavor), ['stars' => 4])->assertRedirect(route('login'));
        $this->delete(route('ratings.destroy', $this->flavor))->assertRedirect(route('login'));

        $this->assertSame(0, DB::table('flavor_ratings')->count());
    }

    // RF1: una sola valoración por usuario y sabor en la base de datos.
    public function test_the_database_allows_one_rating_per_user_and_flavor(): void
    {
        $this->rateAs($this->user, 3);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        $this->rateAs($this->user, 4);
    }

    // RF1: al borrar el sabor o el usuario se borran sus valoraciones.
    public function test_deleting_the_flavor_or_the_user_deletes_their_ratings(): void
    {
        $other = User::factory()->create();
        $this->rateAs($this->user, 3);
        $this->rateAs($other, 4);

        $other->delete();
        $this->assertSame([$this->user->id => 3], $this->savedRatings());

        $this->flavor->delete();
        $this->assertSame(0, DB::table('flavor_ratings')->count());
    }

    /**
     * Spec 011, T3: la sección "Valoración" de la ventana de un sabor en una página.
     */
    private function ratingBox(string $html, Flavor $flavor): string
    {
        $this->assertSame(1, preg_match('/<div class="flavor-rating-box mb-4" data-rating-box="'.$flavor->id.'">(.*?)<!-- \/rating-box -->/s', $html, $match));

        return $match[1];
    }

    // RF5: solo la media (sin el número de valoraciones), o "Sin valoraciones todavía".
    public function test_the_window_shows_the_average_and_count(): void
    {
        $single = Flavor::factory()->create(['name' => 'Uno']);
        $this->rateAs(User::factory()->create(), 5);
        $this->rateAs(User::factory()->create(), 4);
        $this->rateAs(User::factory()->create(), 4);
        DB::table('flavor_ratings')->insert(['user_id' => User::factory()->create()->id, 'flavor_id' => $single->id, 'stars' => 2]);
        $unrated = Flavor::factory()->create(['name' => 'Nada']);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertStringContainsString('<p class="flavor-rating-summary" data-rating-summary>★ 4,3</p>', $this->ratingBox($html, $this->flavor));
        $this->assertStringContainsString('<p class="flavor-rating-summary" data-rating-summary>★ 2,0</p>', $this->ratingBox($html, $single));
        $this->assertStringContainsString('<p class="flavor-rating-summary" data-rating-summary>Sin valoraciones todavía</p>', $this->ratingBox($html, $unrated));
    }

    // RF5: con sesión, cinco estrellas con la propia marcada y "Quitar mi valoración" visible.
    public function test_the_window_marks_the_own_rating(): void
    {
        $this->rateAs($this->user, 3);

        $box = $this->ratingBox($this->get(route('home'))->getContent(), $this->flavor);

        $this->assertStringContainsString('action="'.route('ratings.update', $this->flavor).'"', $box);
        $this->assertStringContainsString('<button type="submit" name="stars" value="1" class="flavor-star is-on" aria-label="Puntuar con 1 estrella" aria-pressed="false">★</button>', $box);
        $this->assertStringContainsString('<button type="submit" name="stars" value="3" class="flavor-star is-on" aria-label="Puntuar con 3 estrellas" aria-pressed="true">★</button>', $box);
        $this->assertStringContainsString('<button type="submit" name="stars" value="4" class="flavor-star" aria-label="Puntuar con 4 estrellas" aria-pressed="false">★</button>', $box);
        $this->assertMatchesRegularExpression('/<form action="'.preg_quote(route('ratings.destroy', $this->flavor), '/').'" method="POST" data-rating-remove="'.$this->flavor->id.'">/', $box);
        $this->assertStringContainsString('Quitar mi valoración', $box);
    }

    // RF5: sin valoración propia, ninguna estrella marcada y "Quitar" oculto.
    public function test_without_own_rating_no_star_is_marked(): void
    {
        $this->rateAs(User::factory()->create(), 5);

        $box = $this->ratingBox($this->get(route('home'))->getContent(), $this->flavor);

        $this->assertStringNotContainsString('is-on', $box);
        $this->assertStringNotContainsString('aria-pressed="true"', $box);
        $this->assertStringContainsString('data-rating-remove="'.$this->flavor->id.'" hidden>', $box);
    }

    // RF5: sin sesión, las estrellas son enlaces a login y no hay formularios.
    public function test_for_a_guest_the_stars_link_to_login(): void
    {
        auth()->logout();

        $box = $this->ratingBox($this->get(route('home'))->assertOk()->getContent(), $this->flavor);

        $this->assertSame(5, substr_count($box, '<a href="'.route('login').'" class="flavor-star"'));
        $this->assertStringContainsString('aria-label="Inicia sesión para puntuar con 5 estrellas"', $box);
        $this->assertStringNotContainsString('<form', $box);
    }

    // RF5: Favoritos muestra la misma sección, con la media y la puntuación propia.
    public function test_favorites_show_the_rating_in_the_window(): void
    {
        \App\Models\Favorite::create(['user_id' => $this->user->id, 'flavor_id' => $this->flavor->id]);
        $this->rateAs($this->user, 4);
        $this->rateAs(User::factory()->create(), 5);

        $box = $this->ratingBox($this->get(route('favorites.index'))->assertOk()->getContent(), $this->flavor);

        $this->assertStringContainsString('★ 4,5', $box);
        $this->assertStringContainsString('value="4" class="flavor-star is-on" aria-label="Puntuar con 4 estrellas" aria-pressed="true"', $box);
    }
}
