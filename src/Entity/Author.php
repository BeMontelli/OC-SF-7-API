<?php

namespace App\Entity;

use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;

/**
 * @Hateoas\Relation(
 *     "self",
 *     href=@Hateoas\Route(
 *         "app_api_author_read",
 *         parameters = { "id" = "expr(object.getId())" }
 *     ),
 *     exclusion = @Hateoas\Exclusion(groups={"author:index","author:read"})
 * )
 * @Hateoas\Relation(
 *     "delete",
 *     href=@Hateoas\Route(
 *         "app_api_author_delete",
 *         parameters = { "id" = "expr(object.getId())" }
 *     ),
 *     exclusion = @Hateoas\Exclusion(groups={"author:index","author:read"}, excludeIf="expr(not is_granted('ROLE_ADMIN'))")
 * )
 * @Hateoas\Relation(
 *     "update",
 *     href=@Hateoas\Route(
 *         "app_api_author_update",
 *         parameters = { "id" = "expr(object.getId())" }
 *     ),
 *     exclusion = @Hateoas\Exclusion(groups={"author:index","author:read"}, excludeIf="expr(not is_granted('ROLE_ADMIN'))")
 * )
 */
#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['author:index','author:read','book:index','book:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['author:index','author:read','book:index','book:read'])]
    #[Assert\NotBlank(message: "Le prénom de l'auteur est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le prénom doit faire au moins {{ limit }} caractères", maxMessage: "Le prénom ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['author:index','author:read','book:index','book:read'])]
    #[Assert\NotBlank(message: "Le nom de l'auteur est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom doit faire au moins {{ limit }} caractères", maxMessage: "Le nom ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $lastname = null;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'author', orphanRemoval: true, cascade: ['persist'])]
    #[Groups(['author:index','author:read'])]
    private Collection $books;

    public function __construct()
    {
        $this->books = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): static
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $book->setAuthor($this);
        }

        return $this;
    }

    public function removeBook(Book $book): static
    {
        if ($this->books->removeElement($book)) {
            // set the owning side to null (unless already changed)
            if ($book->getAuthor() === $this) {
                $book->setAuthor(null);
            }
        }

        return $this;
    }
}
