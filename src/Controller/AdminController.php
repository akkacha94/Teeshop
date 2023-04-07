<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use SebastianBergmann\CodeCoverage\Report\Html\Renderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/tableau-de-bord', name: 'show_dashboard', methods: ['GET'])]
    public function showDashboard(ProductRepository $productRepo): Response
    {
       
        $products = $productRepo->findBy(['deletedAt' => null]);

        return $this->render('admin/show_dashboard.html.twig',[
            'products' => $products,
        ]);
        
    }
}
