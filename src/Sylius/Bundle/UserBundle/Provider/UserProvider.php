<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\UserBundle\Provider;

use Sylius\Component\User\Model\UserInterface as SyliusUserInterface;
use Sylius\Component\User\Canonicalizer\CanonicalizerInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Łukasz Chruściel <lukasz.chrusciel@lakion.com>
 */
abstract class UserProvider implements UserProviderInterface
{
    /**
     * @var string
     */
    protected $supportedUserClass = 'Sylius\Component\User\Model\UserInterface';
    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;
    /**
     * @var CanonicalizerInterface
     */
    protected $canonicalizer;

    /**
     * @param UserRepositoryInterface $userRepository
     * @param CanonicalizerInterface  $canonicalizer
     */
    public function __construct(UserRepositoryInterface $userRepository, CanonicalizerInterface $canonicalizer)
    {
        $this->userRepository = $userRepository;
        $this->canonicalizer = $canonicalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByUsername($usernameOrEmail)
    {
        $usernameOrEmail = $this->canonicalizer->canonicalize($usernameOrEmail);
        $user = $this->findUser($usernameOrEmail);

        if (null === $user) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $usernameOrEmail)
            );
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof SyliusUserInterface) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }
        if (null === $reloadedUser = $this->userRepository->find($user->getId())) {
            throw new UsernameNotFoundException(
                sprintf('User with ID "%d" could not be refreshed.', $user->getId())
            );
        }

        return $reloadedUser;
    }

    /**
     * {@inheritDoc}
     */
    abstract protected function findUser($uniqueIdentifier);

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return $this->supportedUserClass === $class || is_subclass_of($class, $this->supportedUserClass);
    }
}
