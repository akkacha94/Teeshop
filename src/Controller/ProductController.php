<?php

namespace App\Controller;

use DateTime;
use App\Entity\Product;
use App\Form\ProductFormType;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;


#[Route('/admin')]
class ProductController extends AbstractController
{
    #[Route('/ajouter-un-produit', name: "create_product", methods: ['GET', 'POST'])]
    public function createProduct(Request $request, ProductRepository $repository,  SluggerInterface $slugger): Response
    {
        $product = new Product();

        $form = $this->createForm(ProductFormType::class, $product,)
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
                $product->setCreatedAt(new DateTime());
                $product->setUpdatedAt(new DateTime());
                    
                /**@var UploadedFile $photo */
                $photo = $form->get('photo')->getData();

                if($photo) {
                $this->handleFile($product, $photo, $slugger);
                }
            

            $repository->save($product, true);

            $this->addFlash('success', "Le produit est en ligne avec succés!");
            return $this->redirectToRoute('show_dashboard');
        }

          return $this->render('admin/product/form.html.twig', [
            'form' => $form->createView(),
            'product' => $product
        ]);
    }

    #[Route('/modifier-un-produit/{id}', name:'update_product', methods:['GET', 'POST'])]
    public function updateProduct(Product $product, Request $request, ProductRepository $repository, SluggerInterface $slugger):Response
        {
            $currentPhoto = $product->getPhoto();
            $form = $this->createForm( ProductFormType::class, $product)
            ->handleRequest($request); 

            if ($form->isSubmitted() && $form->isValid()){
                $product->setUpdatedAt(new DateTime());

                $newPhoto = $form->get('photo')->getData();

                if($newPhoto) {
                $this->handleFile($product, $newPhoto,$slugger);
                }else {
                    $product->setPhoto($currentPhoto);
                }

                $repository->save($product, true);
            }
            return $this->render( 'admin/product/form.html.twig', [
                'form' => $form->createView(),
                'product' => $product
            ]);
        }

        #[Route('/archiver-un-produit/{id}',name: 'soft_delete_product', methods: ['GET'])]
        public function softDeleteProduct(Product $product, ProductRepository $repository):Response
        {
           $product->setDeletedAt(new DateTime());

           $repository->save($product,  true);

           $this->addFlash( 'success', "Le produit a bien été archivé");
           return $this->redirectToRoute('show_dashboard');
        }




        /////////////////////////////////////// PRIVATE FUNCTIONS /////////////////////////////////////////////////////////////////

         private function handleFile(Product $product, UploadedFile $photo, SluggerInterface $slugger)
        {
             
          
            # 1 - Déconstruire le nom du fichier
              # a : Variabiliser l'extension du fichier
                   $extension = '.' . $photo->guessExtension();
              
             # 2 - Assainir le nom du fichier (c-a-d retirer les accents et les espaces blancs)
                   $safeFilename= $slugger->slug(pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME));
              # 3 - Rendre le nom du fichier unique
                     
                  $newFilename = $safeFilename . '-'.uniqid("", true). $extension;
              # 4 - Reconstruire le nom du fichier
  
              # 5 - Déplacer le fichier (upload dans notre application Symfony)
                try{

                  # On a défini un paramètre dans config/service.yaml qui est le chemin (absolu) du dossier 'uploads'
                  # On récupère la valeur (le paramètre) avec getParameter() et le nom du param défini dans le fichier service.yaml.  
                  $photo->move($this->getParameter('uploads_dir'), $newFilename);
                  # Si tout s'est bien passé (aucune Exception lancée) alors on doit set le nom de la photo en BDD
                  $product->setPhoto($newFilename);
                }     catch(FileException $exception){
                    $this->addFlash('warning', "le fichier photo ne s'est pas importé correctement. Vouillez rééseyez" . $exception->getMessage());
   
                }
        }

} 
