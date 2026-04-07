<?php namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
final class HomeController extends AbstractController
{
    #[Route("/", name: "app_home")]
    public function index(): Response
    {
        $products = [
            ["name" => "T-Shirt", "price" => 499],
            ["name" => "Shoes", "price" => 1499],
            ["name" => "Cap", "price" => 299],
        ];
        return $this->render("home/index.html.twig", [
            "title" => "Symfony Demo Project",
            "products" => $products,
        ]);
    }
}
