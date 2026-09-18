<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox\Model;

use Symfony\Component\Validator\Constraints as Assert;

class AddNote
{
    #[Assert\NotBlank]
    public string|null $note;
}
