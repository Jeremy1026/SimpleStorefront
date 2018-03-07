<?php

namespace NoInc\SimpleStorefrontBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use NoInc\SimpleStorefrontBundle\Entity\User;
use NoInc\SimpleStorefrontBundle\Entity\Ingredient;
use NoInc\SimpleStorefrontBundle\Entity\Recipe;
use NoInc\SimpleStorefrontBundle\Entity\RecipeIngredient;

class LoadSweetTeaData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $ingredients = [];
        foreach ( $this->ingredientArray() as $ingredientData )
        {
            $ingredient = new Ingredient();
            
            $ingredient->setName($ingredientData["name"]);
            $ingredient->setPrice($ingredientData["price"]);
            $ingredient->setMeasure($ingredientData["measure"]);
            $ingredient->setStock(100);
    
            $manager->persist($ingredient);
        
            $ingredients[$ingredient->getName()] = $ingredient;
        }
        $manager->flush();
        
        $recipeData = $this->recipeArray();
        
        $recipe = new Recipe();
        $recipe->setName($recipeData["name"]);
        $recipe->setPrice($recipeData["price"]);
        $recipe->setImage($recipeData["image"]);
        $manager->persist($recipe);
        $manager->flush();
        
        foreach( $recipeData["ingredients"] as $recipeIngredientData )
        {
            $recipeIngredient = new RecipeIngredient();
            
            $recipeIngredient->setIngredient($ingredients[$recipeIngredientData["name"]]);
            $recipeIngredient->setRecipe($recipe);
            $recipeIngredient->setQuantity($recipeIngredientData["quantity"]);
            $manager->persist($recipeIngredient);
        }
        $manager->flush();
    }
    
    public function ingredientArray()
    {
        return [
            [
                "name" => "Tea Bags",
                "price" => 0.35,
                "measure" => "Count"
            ],
            [
                "name" => "Sugar",
                "price" => 0.75,
                "measure" => "Cup"
            ],
            [
                "name" => "Water",
                "price" => 0.00,
                "measure" => "Cup"
            ]
        ];
    }
    
    public function recipeArray()
    {
        return [
            "name" => "Sweet Tea",
            "price" => 2,
            "image" => "sweet-tea.jpg",
            "ingredients" => [
                [
                    "name" => "Tea Bags",
                    "quantity" => 3
                ],
                [
                    "name" => "Sugar",
                    "quantity" => 2
                ],
                [
                    "name" => "Water",
                    "quantity" => 4
                ],
            ]
        ];
    }
    
    public function getOrder()
    {
        return 2;
    }
}