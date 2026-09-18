<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox\Model;

use DateTimeInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use WebDevelovers\ResourceBundle\Toolbox\Entity\ActivityType;

class PlanActivity
{
    #[Assert\NotBlank]
    public string|null $summary = null;

    #[Assert\NotNull]
    public ActivityType|null $activityType = null;

    #[Assert\NotNull]
    public DateTimeInterface|null $dueDate = null;

    #[Assert\NotNull]
    public UserInterface|null $assignedTo = null;

    public string|null $description = null;
}
