<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

use function array_key_exists;
use function in_array;
use function is_bool;
use function is_string;

#[AsLiveComponent('BootstrapModal', template: '@WebDeveloversResource/components/BootstrapModal.html.twig')]
class BootstrapModal
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public string|null $id = null;

    #[LiveProp]
    public string|null $title = null;

    #[LiveProp]
    public string $confirmMessage = 'Sei sicuro di voler procedere?';

    #[LiveProp]
    public string|null $actionId = null;

    #[LiveProp]
    public string|null $component = null;

    public string|null $internalComponent = null;

    /** @var array<string,mixed>|null */
    #[LiveProp]
    public array|null $componentData = null;

    /** @var array<string,mixed>|null */
    public array|null $context = null;

    #[LiveProp]
    public string $size = 'lg';

    #[LiveProp]
    public string $backdrop = 'true';

    #[LiveProp]
    public bool $keyboard = false;

    /**
     * @param array<string,mixed>|null $componentData
     * @param array<string,mixed>|null $context
     */
    public function mount(
        string|null $id = null,
        string|null $title = null,
        string|null $confirmMessage = null,
        string|null $actionId = null,
        string|null $component = null,
        array|null $componentData = null,
        string|null $internalComponent = null,
        array|null $context = null,
        string|null $size = null,
        string|null $backdrop = null,
        bool|null $keyboard = null,
    ): void {
        $this->id = $id;
        $this->title = $title;
        if ($confirmMessage !== null) {
            $this->confirmMessage = $confirmMessage;
        }

        $this->actionId = $actionId;
        $this->component = $component;
        $this->componentData = $componentData;
        $this->internalComponent = $internalComponent;
        $this->context = $context;

        if ($size !== null) {
            $this->size = $size;
        }

        if ($backdrop !== null) {
            $this->backdrop = $backdrop;
        }

        if ($keyboard !== null) {
            $this->keyboard = $keyboard;
        }

        $this->normalizeModalOptions();
    }

    /**
     * @param array<string,mixed>|null $componentData
     * @param array<string,mixed> $options
     */
    #[LiveListener('modal-component:open')]
    public function onModalOpen(
        #[LiveArg]
        string $component,
        #[LiveArg]
        array|null $componentData = null,
        #[LiveArg]
        array $options = [],
    ): void {
        $this->component = $component;
        $this->componentData = $componentData;
        $this->internalComponent = null;
        $this->context = null;

        if ($componentData !== null && array_key_exists('modal-title', $componentData) && is_string($componentData['modal-title'])) {
            $this->title = $componentData['modal-title'];
        }

        if (array_key_exists('size', $options) && is_string($options['size'])) {
            $this->size = $options['size'];
        }

        if (array_key_exists('backdrop', $options)) {
            if (is_bool($options['backdrop'])) {
                $this->backdrop = $options['backdrop'] ? 'true' : 'false';
            }

            if (is_string($options['backdrop'])) {
                $this->backdrop = $options['backdrop'];
            }
        }

        if (array_key_exists('keyboard', $options) && is_bool($options['keyboard'])) {
            $this->keyboard = $options['keyboard'];
        }

        $this->normalizeModalOptions();

        $this->dispatchBrowserEvent('modal:open');
    }

    /**
     * Forward an action from a modal to its parent LiveComponent.
     */
    #[LiveAction]
    public function forward(
        #[LiveArg]
        string $event,
        #[LiveArg]
        string $id,
    ): void {
        $this->emitUp($event, ['id' => $id]);
    }

    private function normalizeModalOptions(): void
    {
        if (in_array($this->backdrop, ['true', 'false', 'static'], true)) {
            return;
        }

        $this->backdrop = 'true';
    }
}
