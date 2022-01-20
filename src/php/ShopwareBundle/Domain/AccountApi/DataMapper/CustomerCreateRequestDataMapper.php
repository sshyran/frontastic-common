<?php

namespace Frontastic\Common\ShopwareBundle\Domain\AccountApi\DataMapper;

use Frontastic\Common\AccountApiBundle\Domain\Account;
use Frontastic\Common\AccountApiBundle\Domain\Address;
use Frontastic\Common\ShopwareBundle\Domain\AccountApi\SalutationHelper;
use Frontastic\Common\ShopwareBundle\Domain\DataMapper\AbstractDataMapper;
use Frontastic\Common\ShopwareBundle\Domain\DataMapper\ProjectConfigApiAwareDataMapperInterface;
use Frontastic\Common\ShopwareBundle\Domain\DataMapper\ProjectConfigApiAwareDataMapperTrait;

class CustomerCreateRequestDataMapper extends AbstractDataMapper implements ProjectConfigApiAwareDataMapperInterface
{
    use ProjectConfigApiAwareDataMapperTrait;

    public const MAPPER_NAME = 'customer-create-request';

    /**
     * @var \Frontastic\Common\ShopwareBundle\Domain\AccountApi\DataMapper\AddressCreateRequestDataMapper
     */
    private $addressCreateRequestDataMapper;

    public function __construct(AddressCreateRequestDataMapper $addressCreateRequestDataMapper)
    {
        $this->addressCreateRequestDataMapper = $addressCreateRequestDataMapper;
    }

    public function getName(): string
    {
        return static::MAPPER_NAME;
    }

    /**
     * @param \Frontastic\Common\AccountApiBundle\Domain\Account $account
     *
     * @return string[]
     */
    public function map($account)
    {
        if ($this->isGuestAccount($account)) {
            $account = $this->generateGuestData($account);
        }

        $requestData = [
            'salutationId' => $this->resolveSalutationId($account->salutation),
            'firstName' => $account->firstName,
            'lastName' => $account->lastName,
            'guest' => $this->isGuestAccount($account),
            'email' => $account->email,
            'password' => $this->isGuestAccount($account) ? substr(md5(microtime()), 2, 8) : $account->getPassword(),
            'birthdayDay' => $account->birthday ? $account->birthday->format('d') : null,
            'birthdayMonth' => $account->birthday ? $account->birthday->format('m') : null,
            'birthdayYear' => $account->birthday ? $account->birthday->format('Y') : null,
            'acceptedDataProtection' => true,
        ];

        if (isset($account->addresses[0]) && !empty($account->addresses[0])) {
            $requestData['billingAddress'] = $this->getAddressCreateRequestDataMapper()->map($account->addresses[0]);
        }

        if (isset($account->addresses[1]) && !empty($account->addresses[1])) {
            $requestData['shippingAddress'] = $this->getAddressCreateRequestDataMapper()->map($account->addresses[1]);
        }

        return $requestData;
    }

    private function isGuestAccount(Account $account): bool
    {
        return empty($account->getPassword());
    }

    private function generateGuestData(Account $account): Account
    {
        $account->firstName = strstr($account->email,'@',true);
        $account->lastName = strstr($account->email,'@',true);
        $account->addresses = [
            new Address([
                'streetName' => strstr($account->email,'@',true),
                'postalCode' => '1234',
                'country' => 'DE',
                'city' => 'Berlin',
                ]),
        ];

        return $account;
    }

    private function getAddressCreateRequestDataMapper(): AddressCreateRequestDataMapper
    {
        return $this->addressCreateRequestDataMapper->setProjectConfigApi($this->getProjectConfigApi());
    }

    private function resolveSalutationId(?string $frontasticSalutation): ?string
    {
        if ($frontasticSalutation === null) {
            return null;
        }

        $shopwareSalutation = $this->getProjectConfigApi()->getSalutation(
            SalutationHelper::resolveShopwareSalutation($frontasticSalutation)
        );

        return $shopwareSalutation ? $shopwareSalutation->id : null;
    }
}
