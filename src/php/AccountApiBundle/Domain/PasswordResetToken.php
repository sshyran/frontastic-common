<?php

namespace Frontastic\Common\AccountApiBundle\Domain;

use Kore\DataObject\DataObject;

class PasswordResetToken extends DataObject
{
    /**
     * @var string
     */
    public $email;

    /**
     * @var string|null
     */
    public $confirmationToken;

    /**
     * @var \DateTime|null
     */
    public $tokenValidUntil;
}
