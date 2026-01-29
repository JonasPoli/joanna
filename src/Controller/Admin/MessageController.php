<?php

namespace App\Controller\Admin;

use App\Entity\Message;
use App\Entity\Joanna\JoannaReference;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/message')]
#[IsGranted('ROLE_EDITOR')]
class MessageController extends AbstractController
{
    #[Route('/', name: 'app_admin_message_index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository, Request $request): Response
    {
        $status = $request->query->get('status', 'unresolved');
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        
        $qb = $messageRepository->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC');

        // Admins veem todas as mensagens, outros apenas suas próprias
        if (!$isAdmin) {
            $qb->where('m.recipient = :user OR m.sender = :user')
               ->setParameter('user', $this->getUser());
        }

        if ($status === 'resolved') {
            $qb->andWhere('m.status = :status')->setParameter('status', 'resolved');
        } else if ($status === 'unresolved') {
            $qb->andWhere('m.status != :status')->setParameter('status', 'resolved');
        }

        $messages = $qb->getQuery()->getResult();

        return $this->render('admin/message/index.html.twig', [
            'messages' => $messages,
            'current_status' => $status,
            'is_admin' => $isAdmin
        ]);
    }

    #[Route('/reference/{id}/chat', name: 'app_admin_message_chat', methods: ['GET', 'POST'])]
    public function chat(
        JoannaReference $reference, 
        Request $request, 
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();
        
        // Mark as read
        $unreadMessages = $messageRepository->findBy([
            'reference' => $reference,
            'recipient' => $user,
            'status' => 'unread'
        ]);
        
        foreach ($unreadMessages as $msg) {
            $msg->setStatus('read');
        }
        $entityManager->flush();

        if ($request->isMethod('POST')) {
            $content = $request->request->get('content');
            if ($content) {
                $message = new Message();
                $message->setSender($user);
                $message->setContent($content);
                $message->setReference($reference);
                
                // Lógica de destinatário:
                // - Revisor (não autor): envia para o autor
                // - Autor: responde para quem mandou última mensagem, ou envia para qualquer admin
                $author = $reference->getCreatedBy();
                $recipient = null;
                
                if ($user !== $author) {
                    // Revisor/Editor: sempre envia para o autor
                    $recipient = $author;
                } else {
                    // Autor: tenta responder para quem mandou a última mensagem
                    $lastInbound = $messageRepository->findOneBy([
                        'reference' => $reference, 
                        'recipient' => $user
                    ], ['createdAt' => 'DESC']);
                    
                    if ($lastInbound) {
                        $recipient = $lastInbound->getSender();
                    } else {
                        // Fallback: Busca qualquer usuário com ROLE_ADMIN ou ROLE_EDITOR (exceto o próprio autor)
                        $admins = $entityManager->getRepository(\App\Entity\User::class)
                            ->createQueryBuilder('u')
                            ->where('u.roles LIKE :admin OR u.roles LIKE :editor')
                            ->andWhere('u.id != :userId')
                            ->setParameter('admin', '%ROLE_ADMIN%')
                            ->setParameter('editor', '%ROLE_EDITOR%')
                            ->setParameter('userId', $user->getId())
                            ->setMaxResults(1)
                            ->getQuery()
                            ->getOneOrNullResult();
                        $recipient = $admins;
                    }
                }

                if ($recipient) {
                    $message->setRecipient($recipient);
                    $message->setStatus('unread'); // Nova mensagem sempre não lida
                    $entityManager->persist($message);
                    
                    // Reabrir todas as mensagens resolvidas desta conversa (volta para pendente)
                    $resolvedMessages = $messageRepository->findBy([
                        'reference' => $reference,
                        'status' => 'resolved'
                    ]);
                    foreach ($resolvedMessages as $msg) {
                        $msg->setStatus('read'); // Volta para pendente (não resolvida)
                    }
                    
                    $entityManager->flush();
                }
            }
            return $this->redirectToRoute('app_admin_message_chat', ['id' => $reference->getId()]);
        }

        $history = $messageRepository->findBy(['reference' => $reference], ['createdAt' => 'ASC']);
        
        // Verificar se a conversa está resolvida
        $isResolved = $messageRepository->count([
            'reference' => $reference,
            'status' => 'resolved'
        ]) > 0;

        return $this->render('admin/message/chat.html.twig', [
            'reference' => $reference,
            'history' => $history,
            'is_resolved' => $isResolved
        ]);
    }

    #[Route('/reference/{id}/resolve', name: 'app_admin_message_resolve', methods: ['POST'])]
    public function resolve(
        JoannaReference $reference,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Marcar todas as mensagens desta conversa como resolvidas
        $messages = $messageRepository->findBy(['reference' => $reference]);
        
        foreach ($messages as $message) {
            $message->setStatus('resolved');
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Conversa marcada como resolvida.');
        
        return $this->redirectToRoute('app_admin_message_index');
    }
}
