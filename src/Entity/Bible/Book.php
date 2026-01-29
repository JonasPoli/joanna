<?php

namespace App\Entity\Bible;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Testament $testament = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $abbrev = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTestament(): ?Testament
    {
        return $this->testament;
    }

    public function setTestament(?Testament $testament): static
    {
        $this->testament = $testament;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getAbbrev(): ?string
    {
        return $this->abbrev;
    }

    public function setAbbrev(string $abbrev): static
    {
        $this->abbrev = $abbrev;

        return $this;
    }
}
