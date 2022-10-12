<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Place;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\PlaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class PlaceController extends AbstractController
{
    private $twig;
    private $entityManager;
    private $bus;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    #[Route('/')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('homepage', ['_locale' => 'en']);
    }

    #[Route('/{_locale<%app.supported_locales%>}/', name: 'homepage')]
    public function index(PlaceRepository $placeRepository): Response
    {
        $response = new Response($this->twig->render('place/index.html.twig', [
            'places' => $placeRepository->findAll(),
        ]));
        $response->setSharedMaxAge(1);

        return $response;
    }

    #[Route('/{_locale<%app.supported_locales%>}/place_header', name: 'place_header')]
    public function placeHeader(PlaceRepository $placeRepository): Response
    {
        $response = new Response($this->twig->render('place/header.html.twig', [
            'places' => $placeRepository->findAll(),
        ]));
        $response->setSharedMaxAge(1);

        return $response;
    }

    #[Route('/{_locale<%app.supported_locales%>}/place/{slug}', name: 'place')]
    public function show(Request $request, Place $place, CommentRepository $commentRepository, NotifierInterface $notifier, string $photoDir): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setPlace($place);
            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $e) {
                    // unable to upload the photo, give up
                }
                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];

            $reviewUrl = $this->generateUrl('review_comment', ['id' => $comment->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            $this->bus->dispatch(new CommentMessage($comment->getId(), $reviewUrl, $context));

            $notifier->send(new Notification('Thank you for the feedback; your comment will be posted after moderation.', ['browser']));

            return $this->redirectToRoute('place', ['slug' => $place->getSlug()]);
        }

        if ($form->isSubmitted()) {
            $notifier->send(new Notification('Can you check your submission? There are some problems with it.', ['browser']));
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($place, $offset);

        return new Response($this->twig->render('place/show.html.twig', [
            'place' => $place,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView(),
        ]));
    }
}