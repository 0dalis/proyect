<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Filament\Resources\Ratings\Pages\ListRatings;
use App\Filament\Widgets\RatingsModeration;
use App\Models\Rating;
use App\Models\SuperAdmin;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Opiniones de dueños y administradores (1 a 5 estrellas con medias) y su
 * publicación en la landing, que decide el Super Admin.
 */
class RatingTest extends TestCase
{
    public function test_owner_rates_with_half_stars(): void
    {
        $company = $this->createCompany('plus', ['name' => 'Ferretería Sol']);
        $owner = $this->ownerOf($company);

        $this->as($owner)->getJson('/api/rating')->assertOk()->assertJsonPath('rating', null);
        $this->as($owner)->getJson('/api/me')->assertJsonPath('can_rate', true);

        $this->as($owner)->putJson('/api/rating', ['score' => 4.5, 'comment' => 'Muy fácil de usar', 'allow_publish' => true])
            ->assertOk()
            ->assertJsonPath('rating.score', 4.5)
            ->assertJsonPath('rating.status', 'pending');

        $this->assertDatabaseHas('ratings', ['user_id' => $owner->id, 'company_id' => $company->id, 'score' => 4.5], 'central');
    }

    public function test_scores_must_be_between_one_and_five_in_half_steps(): void
    {
        $owner = $this->ownerOf($this->createCompany());

        foreach ([0.5, 4.3, 5.5] as $score) {
            $this->as($owner)->putJson('/api/rating', ['score' => $score])->assertUnprocessable()->assertJsonValidationErrors('score');
        }

        $this->as($owner)->putJson('/api/rating', ['score' => 1])->assertOk();
    }

    public function test_only_owner_and_admins_can_rate(): void
    {
        $company = $this->createCompany();
        $admin = $this->createUserFor($company, $this->createEmployee($company), [Role::Admin]);
        $employee = $this->createUserFor($company, $this->createEmployee($company));

        $this->as($admin)->putJson('/api/rating', ['score' => 3])->assertOk();
        $this->as($employee)->putJson('/api/rating', ['score' => 5])->assertForbidden();
        $this->as($admin)->getJson('/api/me')->assertJsonPath('can_rate', true);
    }

    public function test_super_admin_chooses_which_ratings_appear_on_the_landing(): void
    {
        $company = $this->createCompany('plus', ['name' => 'Ferretería Sol']);
        $owner = $this->ownerOf($company);
        $owner->update(['name' => 'Laura Méndez Ruiz']);
        $this->as($owner)->putJson('/api/rating', ['score' => 5, 'comment' => 'Nos ahorra horas', 'allow_publish' => true]);
        $private = $this->createCompany();
        $this->as($this->ownerOf($private))->putJson('/api/rating', ['score' => 3.5, 'comment' => 'Bien', 'allow_publish' => false]);

        $this->getJson('/api/testimonials')->assertOk()->assertJsonCount(0, 'items')->assertJsonPath('count', 2);

        $this->actingAs(SuperAdmin::query()->firstOrFail(), 'super_admin');
        $rating = Rating::query()->where('user_id', $owner->id)->firstOrFail();
        $hidden = Rating::query()->where('company_id', $private->id)->firstOrFail();

        Livewire::test(RatingsModeration::class)
            ->assertSee('Nos ahorra horas')
            ->assertActionHidden(TestAction::make('publish')->table($hidden))
            ->callAction(TestAction::make('publish')->table($rating));

        $this->assertSame(Rating::PUBLISHED, $rating->fresh()->status);
        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'rating.published'], 'central');

        $this->getJson('/api/testimonials')
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.name', 'Laura M.')
            ->assertJsonPath('items.0.company', 'Ferretería Sol')
            ->assertJsonPath('items.0.role', 'Dueño')
            ->assertJsonPath('average', 4.3);

        // Si cambia su opinión vuelve a revisión y deja de mostrarse
        $this->as($owner)->putJson('/api/rating', ['score' => 4, 'comment' => 'Nos ahorra horas', 'allow_publish' => true]);
        $this->assertSame(Rating::PENDING, $rating->fresh()->status);
        $this->getJson('/api/testimonials')->assertJsonCount(0, 'items');

        Livewire::test(ListRatings::class)->callAction(TestAction::make('hide')->table($rating));
        $this->assertSame(Rating::HIDDEN, $rating->fresh()->status);
    }
}
