<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use WebDevelovers\ResourceBundle\Toolbox\Entity\Activity;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManagerInterface;
use WebDevelovers\ResourceBundle\Twig\Components\Toolbox\Form\PlanActivityType;
use function assert;

#[AsLiveComponent(name: 'Toolbox:PlannedActivities', template: '@WebDeveloversResource/components/toolbox/PlannedActivities.html.twig')]
final class PlannedActivities extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

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

    #[LiveProp]
    public string|null $editingActivityID = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FormFactoryInterface $formFactory,
        private readonly ToolboxManagerInterface $toolboxManager,
    ) {
    }

    /** @return Activity[] */
    public function getActivities(): array
    {
        return $this->toolboxManager->getActivities(Uuid::fromString($this->resourceID));
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->createNamed(
            'planned_activity_edit',
            PlanActivityType::class,
            $this->initialFormData ?? new Model\PlanActivity(),
        );
    }

    #[LiveAction]
    public function toggleDone(
        #[LiveArg]
        string $id,
    ): void {
        $entity = $this->findActivity($id);

        if ($entity->isDone()) {
            $entity->notDone();
        } else {
            $entity->done();
        }

        $this->entityManager->flush();
        $this->emit('activityUpdated');
    }

    #[LiveAction]
    public function deleteActivity(
        #[LiveArg]
        string $id,
    ): void {
        $entity = $this->findActivity($id);

        $this->entityManager->remove($entity);
        $this->entityManager->flush();
        $this->emit('activityUpdated');
    }

    #[LiveAction]
    public function startEdit(
        #[LiveArg]
        string $id,
    ): void {
        $activity = $this->findActivity($id);
        $this->editingActivityID = $activity->id->toRfc4122();
        $this->initialFormData = $this->mapActivityToFormData($activity);
        $this->resetForm();
        $this->dispatchBrowserEvent('trix:sync');
    }

    #[LiveAction]
    public function saveEdit(): void
    {
        if ($this->editingActivityID === null) {
            throw new RuntimeException('Unable to edit activity: no selected activity found.');
        }

        $this->submitForm();

        $planActivity = $this->getForm()->getData();
        assert($planActivity instanceof Model\PlanActivity);

        $summary = $planActivity->summary;
        $activityType = $planActivity->activityType;
        $dueDate = $planActivity->dueDate;
        $assignedTo = $planActivity->assignedTo;

        assert(
            $summary !== null &&
            $activityType !== null &&
            $dueDate !== null &&
            $assignedTo !== null,
        );

        $activity = $this->findActivity($this->editingActivityID);
        $activity->summary = $summary;
        $activity->activityType = $activityType;
        $activity->dueDate = $this->toMutableDateTime($dueDate);
        $activity->assignedTo = $assignedTo;

        $this->entityManager->flush();
        $this->emit('activityUpdated');

        $this->editingActivityID = null;
        $this->initialFormData = null;
        $this->resetForm();
        $this->dispatchBrowserEvent('offcanvas:close', ['id' => 'editActivityOffcanvas']);
    }

    #[LiveListener('activityAdded')]
    public function activityUpdate(): void
    {
    }

    private function findActivity(string $id): Activity
    {
        $entity = $this->entityManager->getRepository(Activity::class)->find($id);
        if ($entity === null) {
            throw new RuntimeException('Unable to find activity with id: ' . $id);
        }

        assert($entity instanceof Activity);

        return $entity;
    }

    private function mapActivityToFormData(Activity $activity): Model\PlanActivity
    {
        assert($activity->assignedTo instanceof UserInterface);

        $formData = new Model\PlanActivity();
        $formData->summary = $activity->summary;
        $formData->activityType = $activity->activityType;
        $formData->dueDate = $activity->dueDate;
        $formData->assignedTo = $activity->assignedTo;

        return $formData;
    }

    private function toMutableDateTime(DateTimeInterface $dateTime): DateTime
    {
        if ($dateTime instanceof DateTime) {
            return $dateTime;
        }

        return DateTime::createFromInterface($dateTime);
    }
}
