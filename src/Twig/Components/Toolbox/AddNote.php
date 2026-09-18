<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components\Toolbox;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;
use WebDevelovers\ResourceBundle\ResourceInterface;
use WebDevelovers\ResourceBundle\Security\CurrentUserProviderInterface;
use WebDevelovers\ResourceBundle\Toolbox\ToolboxManagerInterface;
use WebDevelovers\ResourceBundle\Twig\Components\Toolbox\Form\AddNoteType;

use function array_filter;
use function array_map;
use function assert;

#[AsLiveComponent(name: 'Toolbox:AddNote', template: '@WebDeveloversResource/components/toolbox/AddNote.html.twig')]
final class AddNote extends AbstractController
{
    use DefaultActionTrait;
    use ComponentToolsTrait;
    use ComponentWithFormTrait;

    public function __construct(
        private readonly CurrentUserProviderInterface $currentUserProvider,
        private readonly EventDispatcherInterface $eventDispatcher,
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
    public string|null $section;

    #[LiveProp]
    public Model\AddNote|null $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(AddNoteType::class, $this->initialFormData);
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager): void
    {
        $this->submitForm();

        $note = $this->getForm()->getData();
        assert($note instanceof Model\AddNote);

        $metadata = $this->registry->get($this->resourceType);
        /** @var class-string $class */
        $class = $metadata->getClass('model');
        $resource = $entityManager->getRepository($class)->find($this->resourceID);
        if ($resource === null) {
            throw new LogicException('Unable to find the planActivity resource.');
        }

        assert($resource instanceof ResourceInterface);
        $noteString = $note->note;
        assert(! empty($noteString));

        $note = $this->toolboxManager->addNote($noteString, $resource);

        //ADD FOLLOWERS

        $this->emit('timelineUpdate');
        //$this->eventDispatcher->dispatch($this->noteToEvent($note));
        $this->resetForm();
        $this->dispatchBrowserEvent('trix:clear', ['offcanvasId' => 'addNoteOffcanvas']);
        $this->dispatchBrowserEvent('offcanvas:close', ['id' => 'addNoteOffcanvas']);
    }

    /*private function noteToEvent(TimelineEntry $entry): GenerateNotification
    {
        $recipients = $this->getRecipients($entry);

        $subject = 'wd.notification.added_note';
        $content = $entry->metadata['content'];
        $sender = $entry->blameId;
        $resourceReference = $entry->getResourceReference();

        return new GenerateNotification($recipients, $subject, $content, $sender, $resourceReference, $this->section);
    }

    /** @return UserInterface[]
    private function getRecipients(TimelineEntry $entry): array
    {
        $users = array_map(static function (Follower $entry) {
            return $entry->user;
        }, $this->toolboxManager->getFollowers($entry->subject));

        $currentUser = $this->security->getUser();
        assert($currentUser instanceof User);

        return array_filter($users, static function (User $user) use ($currentUser) {
            return $user->id !== $currentUser->id;
        });
    }*/
}
