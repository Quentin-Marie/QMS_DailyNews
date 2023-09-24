<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Form\UpdatePictureType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/article')]
class ArticleController extends AbstractController
{
    #[Route('/gestion', name: 'article_gestion')]
    public function gestion(ArticleRepository $repository): Response
    {
        $articles = $repository->ordre();

        return $this->render(
            'article/gestion.html.twig',
            [
                'articles' => $articles
            ]
        );
    }
    #[Route('/focus/{id}', name: 'article_focus')]
    public function focus(ArticleRepository $repository, $id = null): Response
    {
        $article = $repository->find($id);

        return $this->render(
            'article/focus.html.twig',
            [
                'article' => $article
            ]
        );
    }

    #[Route('/create', name: 'create_article')]
    public function index(Request $request, EntityManagerInterface $manager, ArticleRepository $repository, $id = null): Response
    {


        if ($id) {
            $article = $repository->find($id);
        } else {
            $article = new Article();
        }

        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $article->setPublishDate(new \DateTime());

            $picture = $form->get('picture')->getData();

            $picture_bdd = date("YmdHis") . '-' . $picture->getClientOriginalName();

            $article->setPicture($picture_bdd);

            $picture->move($this->getParameter('upload_dir'), $picture_bdd);

            $manager->persist($article);
            $manager->flush();

            return $this->redirectToRoute('article_gestion');
        }

        return $this->render('article/index.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/delete/{id}', name: 'delete_article')]
    public function delete(Article $article, EntityManagerInterface $manager)
    {

        unlink('upload/' . $article->getPicture());
        $manager->remove($article);
        $manager->flush();

        return $this->redirectToRoute('article_gestion');
    }

    #[Route('/update/{id}', name: 'update_article')]
    public function update(Request $request, EntityManagerInterface $manager, ArticleRepository $repository, $id = null): Response
    {


        $article = $repository->find($id);

        $form = $this->createForm(UpdatePictureType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $article->setPublishDate(new \DateTime());

            $picture = $form->get('updatePicture')->getData();

            if ($picture) {

                unlink('upload/' . $article->getPicture());

                $picture_bdd = date("YmdHis") . '-' . $picture->getClientOriginalName();

                $article->setPicture($picture_bdd);

                $picture->move($this->getParameter('upload_dir'), $picture_bdd);
            }

            $manager->persist($article);
            $manager->flush();

            return $this->redirectToRoute('home'); // En général on ne publie qu'un seul article à la fois, donc je renvoie à l'accueil où il y a le fil d'article
        }

        return $this->render('article/index.html.twig', [
            'form' => $form
        ]);
    }
}
