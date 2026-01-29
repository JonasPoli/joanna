<?php

namespace App\Entity\Joanna;

use App\Entity\Bible\Book;
use App\Entity\User;
use App\Enum\ReferenceType;
use App\Repository\Joanna\JoannaReferenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class JoannaReference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?JoannaWork $work = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $joannaChapter = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $bibleBook = null;

    #[ORM\Column(nullable: true)]
    private ?int $bibleChapter = null;

    #[ORM\Column(nullable: true)]
    private ?int $bibleVerseStart = null;

    #[ORM\Column(nullable: true)]
    private ?int $bibleVerseEnd = null;

    #[ORM\Column(length: 50, enumType: ReferenceType::class)]
    private ?ReferenceType $referenceType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $citation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'reference', targetEntity: ReferenceApproval::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $approvals;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->approvals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWork(): ?JoannaWork
    {
        return $this->work;
    }

    public function setWork(?JoannaWork $work): static
    {
        $this->work = $work;

        return $this;
    }

    public function getJoannaChapter(): ?string
    {
        return $this->joannaChapter;
    }

    public function setJoannaChapter(?string $joannaChapter): static
    {
        $this->joannaChapter = $joannaChapter;

        return $this;
    }

    public function getBibleBook(): ?Book
    {
        return $this->bibleBook;
    }

    public function setBibleBook(?Book $bibleBook): static
    {
        $this->bibleBook = $bibleBook;

        return $this;
    }

    public function getBibleChapter(): ?int
    {
        return $this->bibleChapter;
    }

    public function setBibleChapter(?int $bibleChapter): static
    {
        $this->bibleChapter = $bibleChapter;

        return $this;
    }

    public function getBibleVerseStart(): ?int
    {
        return $this->bibleVerseStart;
    }

    public function setBibleVerseStart(?int $bibleVerseStart): static
    {
        $this->bibleVerseStart = $bibleVerseStart;

        return $this;
    }

    public function getBibleVerseEnd(): ?int
    {
        return $this->bibleVerseEnd;
    }

    public function setBibleVerseEnd(?int $bibleVerseEnd): static
    {
        $this->bibleVerseEnd = $bibleVerseEnd;

        return $this;
    }

    public function getReferenceType(): ?ReferenceType
    {
        return $this->referenceType;
    }

    public function setReferenceType(ReferenceType $referenceType): static
    {
        $this->referenceType = $referenceType;

        return $this;
    }

    public function getCitation(): ?string
    {
        return $this->citation;
    }

    public function setCitation(?string $citation): static
    {
        $this->citation = $citation;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, ReferenceApproval>
     */
    public function getApprovals(): Collection
    {
        return $this->approvals;
    }

    public function addApproval(ReferenceApproval $approval): static
    {
        if (!$this->approvals->contains($approval)) {
            $this->approvals->add($approval);
            $approval->setReference($this);
        }

        return $this;
    }

    public function removeApproval(ReferenceApproval $approval): static
    {
        if ($this->approvals->removeElement($approval)) {
            if ($approval->getReference() === $this) {
                $approval->setReference(null);
            }
        }

        return $this;
    }

    /**
     * Retorna o número total de aprovações
     */
    public function getApprovalCount(): int
    {
        return $this->approvals->count();
    }

    /**
     * Verifica se um usuário já aprovou esta referência
     */
    public function hasUserApproved(User $user): bool
    {
        return $this->getUserApproval($user) !== null;
    }

    /**
     * Retorna a aprovação de um usuário específico, se existir
     */
    public function getUserApproval(User $user): ?ReferenceApproval
    {
        foreach ($this->approvals as $approval) {
            if ($approval->getApprovedBy() === $user) {
                return $approval;
            }
        }
        return null;
    }

    /**
     * Verifica se um usuário pode aprovar esta referência
     * (não é o autor E ainda não aprovou)
     */
    public function canUserApprove(User $user): bool
    {
        // Autor não pode aprovar própria referência
        if ($this->createdBy === $user) {
            return false;
        }

        // Usuário já aprovou
        if ($this->hasUserApproved($user)) {
            return false;
        }

        return true;
    }
}
