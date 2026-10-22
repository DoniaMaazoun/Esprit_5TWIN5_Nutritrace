<?php

namespace Tests\Feature\ModuleProduit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsModuleProduitData;
use Tests\TestCase;

/**
 * Catégories (3e entité) : CRUD admin, contrôle de saisie et règles de suppression.
 */
class CategoryManagementTest extends TestCase
{
    use BuildsModuleProduitData, RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge(['name' => 'Fruits secs', 'icon' => 'fa-jar', 'color' => '#A0522D', 'is_active' => 1], $overrides);
    }

    public function test_default_categories_are_created_by_the_migration(): void
    {
        $this->assertSame(8, Category::count());
        $this->assertNotNull(Category::firstWhere('slug', 'plantes-aromatiques'));
    }

    public function test_only_admins_manage_categories(): void
    {
        $this->actingAs($this->makeProducer())->get(route('backend.categories.index'))->assertForbidden();
        $this->actingAs($this->makeAdmin())->get(route('backend.categories.index'))->assertOk();
    }

    public function test_admin_creates_a_category_with_its_slug(): void
    {
        $this->actingAs($this->makeAdmin())->post(route('backend.categories.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', ['name' => 'Fruits secs', 'slug' => 'fruits-secs', 'color' => '#A0522D']);
    }

    public function test_category_input_is_validated(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post(route('backend.categories.store'), $this->payload(['name' => 'X', 'color' => 'vert', 'icon' => 'fa-hacker']))
            ->assertSessionHasErrors(['name', 'color', 'icon']);
    }

    public function test_equivalent_names_are_refused(): void
    {
        // « LEGUMES » produit le même slug que « Légumes »
        $this->actingAs($this->makeAdmin())->post(route('backend.categories.store'), $this->payload(['name' => 'LEGUMES']))
            ->assertSessionHasErrors('name');
    }

    public function test_used_category_cannot_be_deleted_but_can_be_disabled(): void
    {
        $admin = $this->makeAdmin();
        $category = $this->category('legumes');
        $this->makeHarvest($this->makeFarm($this->makeProducer()), ['category_id' => $category->id]);

        $this->actingAs($admin)->delete(route('backend.categories.destroy', $category))->assertSessionHas('error');
        $this->assertModelExists($category);

        $this->actingAs($admin)->patch(route('backend.categories.toggle', $category));
        $this->assertFalse($category->fresh()->is_active);
    }

    public function test_unused_category_is_deleted(): void
    {
        $category = $this->category('agrumes');

        $this->actingAs($this->makeAdmin())->delete(route('backend.categories.destroy', $category))->assertSessionHas('success');
        $this->assertModelMissing($category);
    }
}
