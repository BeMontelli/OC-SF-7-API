<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *     "self",
 *     href=@Hateoas\Route(
 *         "app_api_book_read",
 *         parameters = { "id" = "expr(object.getId())" }
 *     ),
 *     exclusion = @Hateoas\Exclusion(groups={"book:index","book:read"})
 * )
 * @Hateoas\Relation(
 *     "delete",
 *     href=@Hateoas\Route(
 *         "app_api_book_delete",
 *         parameters = { "id" = "expr(object.getId())" }
 *     ),
 *     exclusion = @Hateoas\Exclusion(groups={"book:index","book:read"}, excludeIf="expr(not is_granted('ROLE_ADMIN'))")
 * )
 * @Hateoas\Relation(
 *     "update",
 *     href=@Hateoas\Route(
 *         "app_api_book_update",
 *         parameters = { "id" = "expr(object.getId())" }
 *     ),
 *     exclusion = @Hateoas\Exclusion(groups={"book:index","book:read"}, excludeIf="expr(not is_granted('ROLE_ADMIN'))")
 * )
 */
#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['author:index','author:read','book:index','book:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['author:index','author:read','book:index','book:read'])]
    #[Assert\NotBlank(message: "Le titre du livre est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le titre doit faire au moins {{ limit }} caractères", maxMessage: "Le titre ne peut pas faire plus de {{ limit }} caractères")]
    
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['author:read','book:read'])]
    private ?string $coverText = null;

    #[ORM\ManyToOne(inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['book:index','book:read'])]
    private ?Author $author = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCoverText(): ?string
    {
        return $this->coverText;
    }

    public function setCoverText(?string $coverText): static
    {
        $this->coverText = $coverText;

        return $this;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): static
    {
        $this->author = $author;

        return $this;
    }
}
