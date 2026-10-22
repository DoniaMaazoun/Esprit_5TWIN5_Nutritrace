<?php

return [
    'models' => [
        \App\Models\ProductionLot::class => \App\Policies\ProductionLotPolicy::class,
        \App\Models\Recipe::class => \App\Policies\RecipePolicy::class,
        \App\Models\Farm::class => \App\Policies\FarmPolicy::class,
        \App\Models\Harvest::class => \App\Policies\HarvestPolicy::class,
        \App\Models\RawMaterialReception::class => \App\Policies\RawMaterialReceptionPolicy::class,
        \App\Models\ProductionLot::class => \App\Policies\ProductionLotPolicy::class,
    ],
];
