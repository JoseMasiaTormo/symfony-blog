<?php

namespace App\Controller;

use Symfony\Component\Filesystem\Filesystem;
use App\Entity\Comment;
use App\Entity\Post;
use App\Form\CommentFormType;
use App\Form\PostFormType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class BlogController extends AbstractController
{
    #[Route("/blog/buscar/{page}", name: 'blog_buscar')]
    public function buscar(ManagerRegistry $doctrine,  Request $request, int $page = 1): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $searchTerm = $request->query->get('searchTerm') ?? "";
        $recents = $repository->findBy([], ['PublishedAt' => 'DESC'], 2);
        $posts = null;
        if (!empty($searchTerm)) {
            $posts = $repository->findByText($searchTerm);
        }

        return $this->render('blog/blog.html.twig', [
            'posts' => $posts, 'recents' => $recents, 'searchTerm' => $searchTerm,
        ]);
    } 
   
    #[Route("/blog/new", name: 'new_post')]
    public function newPost(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $form = $this->createForm(PostFormType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('Image')->getData();
            if ($image) {
                $originalImgName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeImgName = $slugger->slug($originalImgName);
                $newImgName = $safeImgName . '-' . uniqid() . '.' . $image->guessExtension();

                try {
                    $image->move(
                        $this->getParameter('images_directory'), $newImgName
                    );
                    $filesystem = new Filesystem();
                    $filesystem->copy(
                        $this->getParameter('images_directory') . '/' . $newImgName, true
                    );
                } catch (FileException $e) {
                    
                }
                $post->setImage($newImgName);
            }
            $post = $form->getData();
            $post->setUser($this->getUser());
            $post->setSlug($slugger->slug($post->getTitle()));
            $post->setNumLikes(0);
            $post->setNumComments(0);
            $post->setNumViews(0);
            $entityManager = $doctrine->getManager();
            $entityManager->persist($post);
            $entityManager->flush();
            return $this->redirectToRoute('blog');
        }
     return $this->render('blog/new_post.html.twig', array(
        'form' => $form->createView()
     ));
    }
    
    #[Route("/single_post/{slug}/like", name: 'post_like')]
    public function like(ManagerRegistry $doctrine, Request $request, $slug): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $post = $repository->findOneBy(["Slug" => $slug]);
        
        if (!$post) {
            throw $this->createNotFoundException('Post no encontrado');
        }

        $post->setNumLikes($post->getNumLikes() + 1);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($post);
        $entityManager->flush();

        return $this->redirectToRoute('single_post', ['slug' => $post->getSlug()]);

    }

    #[Route("/blog", name: 'blog')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $posts = $repository->findAll();

        $recentsRepository = $doctrine->getRepository(Post::class);
        $recents = $recentsRepository->findBy([], ['PublishedAt' => 'DESC'], 2);
        
        return $this->render('blog/blog.html.twig', [
            'posts' => $posts, 'recents' => $recents,
        ]);
    }

    #[Route("/single_post/{slug}", name: 'single_post')]
    public function post(ManagerRegistry $doctrine, Request $request, $slug = 'Segundo-Post'): Response
    {
        $repository = $doctrine->getRepository(Post::class);
        $post = $repository->findOneBy(["Slug" => $slug]);

        $recentsRepository = $doctrine->getRepository(Post::class);
        $recents = $recentsRepository->findBy([], ['PublishedAt' => 'DESC'], 2);

        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();
            $comment->setPost($post);
            $entityManager = $doctrine->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
            return $this->redirectToRoute('single_post', ['slug' => $post->getSlug()]);
        }
        return $this->render('blog/single_post.html.twig', [
            'post' => $post, 'recents' => $recents, 'commentForm' => $form->createView()
        ]);
    }
}
