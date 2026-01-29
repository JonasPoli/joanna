<?php

namespace App\Twig;

use App\Repository\MessageRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MessageExtension extends AbstractExtension
{
    public function __construct(
        private MessageRepository $messageRepository,
        private Security $security
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('unread_message_count', [$this, 'getUnreadCount']),
            new TwigFunction('pending_message_count', [$this, 'getPendingCount']),
            new TwigFunction('pending_messages', [$this, 'getPendingMessages']),
        ];
    }

    public function getUnreadCount(): int
    {
        $user = $this->security->getUser();
        if (!$user) {
            return 0;
        }

        return $this->messageRepository->findUnreadCount($user);
    }

    /**
     * Conta conversas pendentes (não resolvidas) do usuário
     */
    public function getPendingCount(): int
    {
        $user = $this->security->getUser();
        if (!$user) {
            return 0;
        }

        // Conta mensagens não resolvidas onde o usuário é sender ou recipient
        return $this->messageRepository->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.reference)')
            ->where('(m.sender = :user OR m.recipient = :user)')
            ->andWhere('m.status != :resolved')
            ->setParameter('user', $user)
            ->setParameter('resolved', 'resolved')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Retorna mensagens pendentes do usuário
     */
    public function getPendingMessages(int $limit = 5): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        return $this->messageRepository->createQueryBuilder('m')
            ->where('(m.sender = :user OR m.recipient = :user)')
            ->andWhere('m.status != :resolved')
            ->setParameter('user', $user)
            ->setParameter('resolved', 'resolved')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
