<?php

namespace App\Entity\Bible;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class VerseReference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Assuming this entity mimics the legacy 'verse_reference' table structure
    // Since exact structure is unknown without import, creating a placeholder structure
    // that links a verse to some reference.

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Verse $verse = null;

    #[ORM\Column(length: 255)]
    private ?string $referer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVerse(): ?Verse
    {
        return $this->verse;
    }

    public function setVerse(?Verse $verse): static
    {
        $this->verse = $verse;

        return $this;
    }

    public function getReferer(): ?string
    {
        return $this->referer;
    }

    public function setReferer(string $referer): static
    {
        $this->referer = $referer;

        return $this;
    }
}
