<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManagerInterface;
use WebDevelovers\ResourceBundle\Twig\Components\Toolbox\Form\PlanActivityType;
use function assert;

#[AsLiveComponent(name: 'Toolbox:PlanActivity', template: '@WebDeveloversResource/components/toolbox/PlanActivity.html.twig')]
final class PlanActivity extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    public function __construct(
        private readonly MetadataRegistryInterface $registry,
        private readonly ToolboxManagerInterface $toolboxManager,
    ) {
    }

    #[LiveProp]
    public string $resourceID;

    #[LiveProp]
    public string $resourceName;

    #[LiveProp]
    public string $resourceType;

    #[LiveProp]
    public string|null $section = null;

    #[LiveProp]
    public Model\PlanActivity|null $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(PlanActivityType::class, $this->initialFormData ?? new Model\PlanActivity());
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): void
    {
        $this->submitForm();

        $planActivity = $this->getForm()->getData();
        assert($planActivity instanceof Model\PlanActivity);

        $metadata = $this->registry->get($this->resourceType);
        /** @var class-string $class */
        $class = $metadata->getClass('model');

        $resource = $entityManager->getRepository($class)->find($this->resourceID);
        if ($resource === null) {
            throw new LogicException('Unable to find the planActivity resource.');
        }

        assert($resource instanceof ResourceInterface);
        $summary = $planActivity->summary;
        $activityType = $planActivity->activityType;
        $dueDate = $planActivity->dueDate;
        $assignedTo = $planActivity->assignedTo;
        $description = $planActivity->description;

        assert(
            $summary !== null &&
            $activityType !== null &&
            $dueDate !== null &&
            $assignedTo !== null,
        );

        $this->toolboxManager->addActivity(
            summary: $summary,
            activityType: $activityType,
            dueDate: $dueDate,
            assignedTo: $assignedTo,
            resource: $resource,
            description: $description,
        );

        $this->emit('activityAdded');
        $this->emit('activityUpdated');
        $this->resetForm();
        $this->dispatchBrowserEvent('trix:clear', ['offcanvasId' => 'planActivityOffcanvas']);
        $this->dispatchBrowserEvent('offcanvas:close', ['id' => 'planActivityOffcanvas']);
    }

    #[LiveAction]
    public function resetDraft(): void
    {
        $this->resetForm();
        $this->dispatchBrowserEvent('trix:clear', ['offcanvasId' => 'planActivityOffcanvas']);
    }
}
