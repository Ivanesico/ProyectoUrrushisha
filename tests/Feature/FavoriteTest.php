<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Flavor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.locale' => 'en']);
        app()->setLocale('en');
    }

    private function favoriteExists(User $user, Flavor $flavor): bool
    {
        return Favorite::where('user_id', $user->id)->where('flavor_id', $flavor->id)->exists();
    }

    // RF-16
    public function test_it_adds_a_flavor_that_is_not_saved_and_shows_a_message(): void
    {
        $user = User::factory()->create();
        $flavor = Flavor::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('home'))
            ->followingRedirects()
            ->post(route('favorites.store', $flavor));

        $response->assertOk();
        $response->assertSee('Sabor añadido a favoritos.');
        $this->assertTrue($this->favoriteExists($user, $flavor));
    }

    public function test_it_removes_a_flavor_that_is_already_saved_and_shows_a_message(): void
    {
        $user = User::factory()->create();
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => $user->id, 'flavor_id' => $flavor->id]);

        $response = $this->actingAs($user)
            ->from(route('favorites.index'))
            ->followingRedirects()
            ->post(route('favorites.store', $flavor));

        $response->assertOk();
        $response->assertSee('Sabor eliminado de favoritos.');
        $this->assertFalse($this->favoriteExists($user, $flavor));
        $this->assertModelExists($flavor);
    }

    public function test_it_requires_a_session(): void
    {
        $flavor = Flavor::factory()->create();

        $this->post(route('favorites.store', $flavor))->assertRedirect(route('login'));
        $this->assertSame(0, Favorite::count());
    }

    // RF-17
    public function test_it_only_lists_the_favorites_of_the_user(): void
    {
        $user = User::factory()->create();
        $mine = Flavor::factory()->create(['name' => 'Mi favorito']);
        $theirs = Flavor::factory()->create(['name' => 'Favorito de otro']);
        Favorite::create(['user_id' => $user->id, 'flavor_id' => $mine->id]);
        Favorite::create(['user_id' => User::factory()->create()->id, 'flavor_id' => $theirs->id]);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertSee('Mi favorito');
        $response->assertDontSee('Favorito de otro');
    }

    public function test_removing_a_favorite_does_not_affect_another_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => $user->id, 'flavor_id' => $flavor->id]);
        Favorite::create(['user_id' => $other->id, 'flavor_id' => $flavor->id]);

        $this->actingAs($user)->from(route('home'))->post(route('favorites.store', $flavor));

        $this->assertFalse($this->favoriteExists($user, $flavor));
        $this->assertTrue($this->favoriteExists($other, $flavor));
    }

    public function test_a_favorite_of_another_user_is_not_marked_in_the_catalog(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $flavor = Flavor::factory()->create();
        Favorite::create(['user_id' => $other->id, 'flavor_id' => $flavor->id]);

        $this->actingAs($user)->get(route('home'))->assertDontSee('favorite-btn active');
        $this->actingAs($other)->get(route('home'))->assertSee('favorite-btn active');
    }

    // RF-18
    public function test_a_non_public_flavor_can_be_added_listed_and_removed(): void
    {
        $user = User::factory()->create();
        $flavor = Flavor::factory()->notPublic()->create(['name' => 'Sabor oculto']);

        $this->actingAs($user)->from(route('favorites.index'))
            ->post(route('favorites.store', $flavor))
            ->assertRedirect(route('favorites.index'));
        $this->assertTrue($this->favoriteExists($user, $flavor));

        $this->get(route('favorites.index'))->assertOk()->assertSee('Sabor oculto');
        $this->get(route('home'))->assertOk()->assertDontSee('Sabor oculto');

        $this->from(route('favorites.index'))->post(route('favorites.store', $flavor));
        $this->assertFalse($this->favoriteExists($user, $flavor));
    }

    // RF-19
    public function test_a_nonexistent_flavor_responds_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/favorites/999999')
            ->assertNotFound();

        $this->assertSame(0, Favorite::count());
    }
}
