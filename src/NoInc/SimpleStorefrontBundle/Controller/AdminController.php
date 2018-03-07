<?php

namespace NoInc\SimpleStorefrontBundle\Controller;

use NoInc\SimpleStorefrontBundle\Entity\Recipe;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use NoInc\SimpleStorefrontBundle\Entity\Ingredient;
use NoInc\SimpleStorefrontBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use NoInc\SimpleStorefrontBundle\Entity\RecipeIngredient;

/**
 * @Security("has_role('ROLE_ADMIN')")
 * @Route("/admin")
 */
class AdminController extends Controller
{
    /**
     * @Route("/", name="admin_home")
     * @Method("GET")
     */
    public function getAction()
    {
        $recipes = $this->getDoctrine()->getRepository('NoIncSimpleStorefrontBundle:Recipe')->getRecipesAndIngredients();
        
        $renderData = [];
        
        $renderData['title'] = 'A Simple Storefront';
        $renderData['recipes'] = $recipes;
            
        return $this->render('NoIncSimpleStorefrontBundle:Default:admin.html.twig', $renderData);
    }
    
    /**
     * @Route("/make/{recipe_id}", name="make_recipe")
     * @Method("POST")
     * @ParamConverter("recipe", class="NoIncSimpleStorefrontBundle:Recipe", options={"mapping": {"recipe_id": "id"}})
     */
    public function postMakeRecipeAction(Recipe $recipe)
    {
        $haveEnough = true;
        $requiredIngredients = $recipe->getRecipeIngredients();
        //Check stock levels
        foreach ($requiredIngredients as $ingredient) 
        {
            $currentIngredient = $this->getDoctrine()->getRepository('NoInc\SimpleStorefrontBundle\Entity\Ingredient')->find($ingredient->getIngredientId());
            $available = $currentIngredient->getStock();
            $required = $ingredient->getQuantity();
            if ($available < $required) 
            {
                return $this->redirectToRoute('admin_home'); //Can't make it, might as well move on
            }
        }
        if ($haveEnough) 
        {   
            //Update stock levels
            foreach ($requiredIngredients as $ingredient) 
            {
                $currentIngredient = $this->getDoctrine()->getRepository('NoInc\SimpleStorefrontBundle\Entity\Ingredient')->find($ingredient->getIngredientId());
                $available = $currentIngredient->getStock();
                $required = $ingredient->getQuantity();
                $currentIngredient->setStock($available - $required);
                $this->getDoctrine()->getEntityManager()->flush();
            }
            
            $product = new Product();
            $product->setCreatedAt(time());
            $product->setRecipe($recipe);
            $this->getDoctrine()->getEntityManager()->persist($product);
            $this->getDoctrine()->getEntityManager()->flush();
        }
        
        
        return $this->redirectToRoute('admin_home');
    }
    
    /**
     * @Route("/buy/{ingredient_id}", name="buy_ingredient")
     * @Method("POST")
     * @ParamConverter("ingredient", class="NoIncSimpleStorefrontBundle:Ingredient", options={"mapping": {"ingredient_id": "id"}})
     */
    public function postBuyIngredientAction(Ingredient $ingredient)
    {
            
        return $this->redirectToRoute('admin_home');
    }
    
}