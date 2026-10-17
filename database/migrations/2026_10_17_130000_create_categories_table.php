<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Catégories de produits gérées par l'admin (3e entité du Module 1).
     * Les produits existants sont rattachés à la catégorie correspondant à leur ancien code.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('slug', 70)->unique();
            $table->string('description', 500)->nullable();
            $table->string('icon', 40)->default('fa-seedling');
            $table->char('color', 7)->default('#2D6A4F');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $defaults = [
            ['vegetables', 'Légumes', 'fa-carrot', '#E76F51', 'Tomates, piments, pommes de terre, courgettes…'],
            ['fruits', 'Fruits', 'fa-apple-whole', '#F4A261', 'Fruits de saison : fraises, abricots, grenades, melons…'],
            ['cereals', 'Céréales', 'fa-wheat-awn', '#C9A227', 'Blé dur, orge, avoine…'],
            ['olives', 'Olives', 'fa-seedling', '#556B2F', 'Olives à huile et olives de table.'],
            ['dates', 'Dattes', 'fa-tree', '#8B5E34', 'Dattes Deglet Nour et variétés des oasis.'],
            ['citrus', 'Agrumes', 'fa-lemon', '#F59E0B', 'Oranges Maltaises, citrons, mandarines…'],
            ['legumes', 'Légumineuses', 'fa-bowl-rice', '#7C6F57', 'Fèves, pois chiches, lentilles…'],
            ['herbs', 'Plantes aromatiques', 'fa-leaf', '#40916C', 'Menthe, persil, coriandre, romarin…'],
        ];
        Schema::table('harvests', function (Blueprint $table) {
            // restrictOnDelete : une catégorie utilisée ne peut pas être supprimée
            $table->foreignId('category_id')->nullable()->after('product_name')->constrained()->restrictOnDelete();
        });

        // Chaque ancien code de catégorie devient une ligne de la table, et les produits y sont rattachés
        foreach ($defaults as [$oldCode, $name, $icon, $color, $description]) {
            $id = DB::table('categories')->insertGetId([
                'name' => $name, 'slug' => Str::slug($name), 'icon' => $icon, 'color' => $color,
                'description' => $description, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('harvests')->where('category', $oldCode)->update(['category_id' => $id]);
        }

        Schema::table('harvests', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->string('category', 30)->default('vegetables')->after('product_name');
        });

        $codes = ['legumes' => 'vegetables', 'fruits' => 'fruits', 'cereales' => 'cereals', 'olives' => 'olives',
                  'dattes' => 'dates', 'agrumes' => 'citrus', 'legumineuses' => 'legumes', 'plantes-aromatiques' => 'herbs'];
        foreach (DB::table('categories')->get(['id', 'slug']) as $category) {
            DB::table('harvests')->where('category_id', $category->id)->update(['category' => $codes[$category->slug] ?? 'vegetables']);
        }

        Schema::table('harvests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
