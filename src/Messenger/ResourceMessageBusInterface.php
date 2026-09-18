<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Messenger;

use Symfony\Component\Messenger\Envelope;
use WebDevelovers\ResourceBundle\Messenger\Exception\ResourceBusException;
use WebDevelovers\ResourceBundle\RequestConfiguration\RequestConfiguration;
use WebDevelovers\ResourceBundle\ResourceInterface;

interface ResourceMessageBusInterface
{
    /** @throws ResourceBusException */
    public function dispatchCreate(
        RequestConfiguration $configuration,
        object $subject,
    ): ResourceInterface;

    /** @throws ResourceBusException */
    public function dispatchUpdate(
        RequestConfiguration $configuration,
        object $subject,
    ): ResourceInterface;

    /** @throws ResourceBusException */
    public function dispatchDelete(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): void;

    /** @throws ResourceBusException */
    public function dispatchTransition(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): ResourceInterface;

    /** @throws ResourceBusException */
    public function dispatchCreateAsync(
        RequestConfiguration $configuration,
        object $subject,
    ): Envelope;

    /** @throws ResourceBusException */
    public function dispatchUpdateAsync(
        RequestConfiguration $configuration,
        object $subject,
    ): Envelope;

    /** @throws ResourceBusException */
    public function dispatchDeleteAsync(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): Envelope;

    /** @throws ResourceBusException */
    public function dispatchTransitionAsync(
        RequestConfiguration $configuration,
        ResourceInterface $resource,
    ): Envelope;
}
