<?php

namespace Frontastic\Common\ShopifyBundle\Domain\CartApi;

use Frontastic\Common\AccountApiBundle\Domain\Account;
use Frontastic\Common\AccountApiBundle\Domain\Address;
use Frontastic\Common\CartApiBundle\Domain\Cart;
use Frontastic\Common\CartApiBundle\Domain\CartApi;
use Frontastic\Common\CartApiBundle\Domain\LineItem;
use Frontastic\Common\CartApiBundle\Domain\Order;
use Frontastic\Common\CartApiBundle\Domain\Payment;
use Frontastic\Common\CartApiBundle\Domain\ShippingMethod;
use Frontastic\Common\ShopifyBundle\Domain\Mapper\ShopifyAccountMapper;
use Frontastic\Common\ShopifyBundle\Domain\Mapper\ShopifyProductMapper;
use Frontastic\Common\ShopifyBundle\Domain\ShopifyClient;

class ShopifyCartApi implements CartApi
{
    private const DEFAULT_ELEMENTS_TO_FETCH = 10;

    /**
     * @var ShopifyClient
     */
    private $client;

    /**
     * @var string
     */
    private $currentTransaction;

    /**
     * @var ShopifyProductMapper
     */
    private $productMapper;

    /**
     * @var ShopifyAccountMapper
     */
    private $accountMapper;

    public function __construct(
        ShopifyClient $client,
        ShopifyProductMapper $productMapper,
        ShopifyAccountMapper $accountMapper
    ) {
        $this->client = $client;
        $this->productMapper = $productMapper;
        $this->accountMapper = $accountMapper;
    }

    public function getForUser(Account $account, string $locale): Cart
    {
        if (is_null($account->authToken)) {
            throw new \RuntimeException(sprintf('Account %s is not logged in', $account->email));
        }

        $anonymousCart = $this->getAnonymous(uniqid(), $locale);

        $mutation = "
            mutation {
                checkoutCustomerAssociateV2(
                    checkoutId: \"{$anonymousCart->cartId}\",
                    customerAccessToken: \"{$account->authToken}\"
                ) {
                    checkout {
                        {$this->getCheckoutQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    id
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                        shippingLine {
                            {$this->getShippingLineQueryFields()}
                        }
                    }
                    checkoutUserErrors {
                        {$this->getErrorsQueryFields()}
                    }
                }
            }";

        return $this->client
            ->request($mutation, $locale)
            ->then(function ($result) : Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToCart($result['body']['data']['checkoutCustomerAssociateV2']['checkout']);
            })
            ->wait();
    }

    public function getAnonymous(string $anonymousId, string $locale): Cart
    {
        $mutation = "
            mutation {
                checkoutCreate(input: {}) {
                    checkout {
                        {$this->getCheckoutQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    id
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                        shippingLine {
                            {$this->getShippingLineQueryFields()}
                        }
                    }
                    checkoutUserErrors {
                        {$this->getErrorsQueryFields()}
                    }
                }
            }";

        return $this->client
            ->request($mutation, $locale)
            ->then(function ($result) : Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToCart($result['body']['data']['checkoutCreate']['checkout']);
            })
            ->wait();
    }

    public function getById(string $cartId, string $locale = null): Cart
    {
        $query = "
            query {
                node(id: \"{$cartId}\") {
                    ... on Checkout {
                        {$this->getCheckoutQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    id
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                        shippingLine {
                            {$this->getShippingLineQueryFields()}
                        }
                    }
                }
            }
        ";

        return $this->client
            ->request($query)
            ->then(function (array $result): Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToCart($result['body']['data']['node']);
            })
            ->wait();
    }

    public function setCustomLineItemType(array $lineItemType): void
    {
        // TODO: Implement setCustomLineItemType() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function getCustomLineItemType(): array
    {
        // TODO: Implement getCustomLineItemType() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function setTaxCategory(array $taxCategory): void
    {
        // TODO: Implement setTaxCategory() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function getTaxCategory(): array
    {
        // TODO: Implement getTaxCategory() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function addToCart(Cart $cart, LineItem $lineItem, string $locale = null): Cart
    {
        $mutation = "
            mutation {
                checkoutLineItemsAdd(
                    checkoutId: \"{$cart->cartId}\",
                    lineItems: {
                        quantity: {$lineItem->count}
                        variantId: \"{$lineItem->variant->sku}\"
                    }
                ) {
                    checkout {
                        {$this->getCheckoutQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    id
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                        shippingLine {
                            {$this->getShippingLineQueryFields()}
                        }
                    }
                    checkoutUserErrors {
                        {$this->getErrorsQueryFields()}
                    }
                }
            }";

        return $this->client
            ->request($mutation, $locale)
            ->then(function ($result) : Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToCart($result['body']['data']['checkoutLineItemsAdd']['checkout']);
            })
            ->wait();
    }

    public function updateLineItem(
        Cart $cart,
        LineItem $lineItem,
        int $count,
        ?array $custom = null,
        string $locale = null
    ): Cart {
        $mutation = "
            mutation {
                checkoutLineItemsUpdate(
                    checkoutId: \"{$cart->cartId}\",
                    lineItems: {
                        id: \"{$lineItem->lineItemId}\"
                        quantity: {$count}
                    }
                ) {
                    checkout {
                        {$this->getCheckoutQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    id
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                        shippingLine {
                            {$this->getShippingLineQueryFields()}
                        }
                    }
                    checkoutUserErrors {
                        {$this->getErrorsQueryFields()}
                    }
                }
            }";

        return $this->client
            ->request($mutation, $locale)
            ->then(function ($result) : Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToCart($result['body']['data']['checkoutLineItemsUpdate']['checkout']);
            })
            ->wait();
    }

    public function removeLineItem(Cart $cart, LineItem $lineItem, string $locale = null): Cart
    {
        $mutation = "
            mutation {
                checkoutLineItemsRemove(
                    checkoutId: \"{$cart->cartId}\",
                    lineItemIds: \"{$lineItem->lineItemId}\"
                ) {
                    checkout {
                        {$this->getCheckoutQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    id
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                        shippingLine {
                            {$this->getShippingLineQueryFields()}
                        }
                    }
                    checkoutUserErrors {
                        {$this->getErrorsQueryFields()}
                    }
                }
            }";

        return $this->client
            ->request($mutation, $locale)
            ->then(function ($result) : Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToCart($result['body']['data']['checkoutLineItemsRemove']['checkout']);
            })
            ->wait();
    }

    public function setEmail(Cart $cart, string $email, string $locale = null): Cart
    {
        $mutation = "
            mutation {
                checkoutEmailUpdateV2(
                    checkoutId: \"{$cart->cartId}\",
                    email: \"{$email}\",
                ) {
                    checkout {
                        {$this->getCheckoutQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    id
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                        shippingLine {
                            {$this->getShippingLineQueryFields()}
                        }
                    }
                    checkoutUserErrors {
                        {$this->getErrorsQueryFields()}
                    }
                }
            }";

        return $this->client
            ->request($mutation, $locale)
            ->then(function ($result) : Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToCart($result['body']['data']['checkoutEmailUpdateV2']['checkout']);
            })
            ->wait();
    }

    public function setShippingMethod(Cart $cart, string $shippingMethod, string $locale = null): Cart
    {
        $mutation = "
            mutation {
                checkoutShippingLineUpdate(
                    checkoutId: \"{$cart->cartId}\",
                    shippingRateHandle: \"{$shippingMethod}\",
                ) {
                    checkout {
                        {$this->getCheckoutQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    id
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                        shippingLine {
                            {$this->getShippingLineQueryFields()}
                        }
                    }
                    checkoutUserErrors {
                        {$this->getErrorsQueryFields()}
                    }
                }
            }";

        return $this->client
            ->request($mutation, $locale)
            ->then(function ($result) : Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToCart($result['body']['data']['checkoutShippingLineUpdate']['checkout']);
            })
            ->wait();
    }

    public function setCustomField(Cart $cart, array $fields, string $locale = null): Cart
    {
        // TODO: Implement setCustomField() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function setRawApiInput(Cart $cart, string $locale = null): Cart
    {
        // TODO: Implement setRawApiInput() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function setShippingAddress(Cart $cart, Address $address, string $locale = null): Cart
    {
        $mutation = "
            mutation {
                 checkoutShippingAddressUpdateV2(
                    checkoutId: \"{$cart->cartId}\",
                    shippingAddress: {
                        {$this->accountMapper->mapAddressToData($address)}
                    },
                ) {
                    checkout {
                        {$this->getCheckoutQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    id
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                        shippingLine {
                            {$this->getShippingLineQueryFields()}
                        }
                    }
                    checkoutUserErrors {
                        {$this->getErrorsQueryFields()}
                    }
                }
            }";

        return $this->client
            ->request($mutation, $locale)
            ->then(function ($result) : Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToCart($result['body']['data']['checkoutShippingAddressUpdateV2']['checkout']);
            })
            ->wait();
    }

    public function setBillingAddress(Cart $cart, Address $address, string $locale = null): Cart
    {
        // Not supported by Shopify.
        // The billing address should be set up on checkout-complete flow.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function addPayment(Cart $cart, Payment $payment, ?array $custom = null, string $locale = null): Cart
    {
        // TODO: Implement addPayment() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function updatePayment(Cart $cart, Payment $payment, string $localeString): Payment
    {
        // TODO: Implement updatePayment() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function redeemDiscountCode(Cart $cart, string $code, string $locale = null): Cart
    {
        // TODO: Implement redeemDiscountCode() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function removeDiscountCode(Cart $cart, LineItem $discountLineItem, string $locale = null): Cart
    {
        // TODO: Implement removeDiscountCode() method.
        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function order(Cart $cart, string $locale = null): Order
    {
        // Shopify handle the checkout complete action in their side.
        // The url where the customer should be redirected to complete this process
        // can be found in $cart->dangerousInnerCart['webUrl'].

        throw new \RuntimeException(__METHOD__ . ' not implemented');
    }

    public function getOrder(Account $account, string $orderId, string $locale = null): Order
    {
        $query = "
            query {
                node(id: \"{$orderId}\") {
                    ... on Order {
                        {$this->getOrderQueryFields()}
                        lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                            edges {
                                node {
                                    {$this->getLineItemQueryFields()}
                                    variant {
                                        {$this->getVariantQueryFields()}
                                    }
                                }
                            }
                        }
                        shippingAddress {
                            {$this->getAddressQueryFields()}
                        }
                    }
                }
            }
        ";

        return $this->client
            ->request($query)
            ->then(function (array $result): Cart {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToOrder($result['body']['data']['node']);
            })
            ->wait();
    }

    public function getOrders(Account $account, string $locale = null): array
    {
        if (is_null($account->authToken)) {
            throw new \RuntimeException(sprintf('Account %s is not logged in', $account->email));
        }

        $query = "
            query {
                customer(customerAccessToken: \"{$account->authToken}\") {
                    orders(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                        edges {
                            node {
                                {$this->getOrderQueryFields()}
                                lineItems(first: " . self::DEFAULT_ELEMENTS_TO_FETCH . ") {
                                    edges {
                                        node {
                                            {$this->getLineItemQueryFields()}
                                            variant {
                                                {$this->getVariantQueryFields()}
                                            }
                                        }
                                    }
                                }
                                shippingAddress {
                                    {$this->getAddressQueryFields()}
                                }
                            }
                        }
                    }
                }
            }";

        return $this->client
            ->request($query)
            ->then(function (array $result): array {
                if ($result['errors']) {
                    throw new \RuntimeException($result['errors'][0]['message']);
                }

                return $this->mapDataToOrders($result['body']['data']['customer']);
            })
            ->wait();
    }

    public function startTransaction(Cart $cart): void
    {
        $this->currentTransaction = $cart->cartId;
    }

    public function commit(string $locale = null): Cart
    {
        if ($this->currentTransaction === null) {
            throw new \RuntimeException('No transaction currently in progress');
        }

        $cartId = $this->currentTransaction;

        $this->currentTransaction = null;

        return $this->getById($cartId, $locale);
    }

    public function getDangerousInnerClient()
    {
        return $this->client;
    }

    private function mapDataToCart(array $cartData): Cart
    {
        return new Cart([
            'cartId' => $cartData['id'] ?? null,
            'cartVersion' => $cartData['createdAt'] ?? null,
            'email' => $cartData['email'] ?? null,
            'sum' => $this->productMapper->mapDataToPriceValue(
                $cartData['totalPriceV2'] ?? []
            ),
            'currency' => $cartData['totalPriceV2']['currencyCode'] ?? null,
            'lineItems' => $this->mapDataToLineItems($cartData['lineItems']['edges'] ?? []),
            'shippingAddress' => $this->accountMapper->mapDataToAddress(
                $cartData['shippingAddress'] ?? []
            ),
            'shippingMethod' => new ShippingMethod([
                'name' => $cartData['shippingLine']['name'],
                'price' => $cartData['shippingLine']['priceV2']['amount'],
            ]),
            'dangerousInnerCart' => $cartData,
        ]);
    }

    private function mapDataToOrders(array $orderData): array
    {
        $orders = [];
        foreach ($orderData['edges'] as $orderData) {
            $orders[] = $this->mapDataToOrder($orderData['node']);
        }

        return $orders;
    }

    private function mapDataToOrder(array $orderData): Order
    {
        return new Order([
            'orderId' => $orderData['orderNumber'],
            'cartId' => $orderData['id'] ?? null,
            'orderState' => $orderData['financialStatus'],
            'createdAt' => new \DateTimeImmutable($orderData['processedAt']),
            'email' => $orderData['email'] ?? null,
            'lineItems' => $this->mapDataToLineItems($orderData['lineItems']['edges'] ?? []),
            'shippingAddress' => $this->accountMapper->mapDataToAddress(
                $orderData['shippingAddress'] ?? []
            ),
            'shippingMethod' => new ShippingMethod([
                'name' => $orderData['shippingLine']['name'],
                'price' => $orderData['shippingLine']['priceV2']['amount'],
            ]),
            'sum' => $this->productMapper->mapDataToPriceValue(
                $orderData['totalPriceV2'] ?? []
            ),
            'currency' => $orderData['totalPriceV2']['currencyCode'] ?? null,
            'dangerousInnerCart' => $orderData,
            'dangerousInnerOrder' => $orderData,
        ]);
    }

    private function mapDataToLineItems(array $lineItemsData): array
    {
        $lineItems = [];

        foreach ($lineItemsData as $lineItemData) {
            $lineItems[] = new LineItem\Variant([
                'lineItemId' => $lineItemData['node']['id'] ?? null,
                'name' => $lineItemData['node']['title'] ?? null,
                'count' => $lineItemData['node']['quantity'] ?? null,
                'price' => $lineItemData['node']['unitPrice']['amount'] ?? null,
                'variant' => $this->productMapper->mapDataToVariant($lineItemData['node']['variant']),
                'dangerousInnerItem' => $lineItemData['node'],
            ]);
        }

        return $lineItems;
    }

    protected function getCheckoutQueryFields(): string
    {
        return '
            id
            createdAt
            email
            webUrl
            totalPriceV2 {
                amount
                currencyCode
            }
        ';
    }

    protected function getOrderQueryFields(): string
    {
        return '
            id
            email
            orderNumber
            processedAt
            financialStatus
            totalPriceV2 {
                amount
                currencyCode
            }
        ';
    }

    protected function getLineItemQueryFields(): string
    {
        return '
            quantity
            title
            unitPrice {
                amount
                currencyCode
            }
        ';
    }

    protected function getVariantQueryFields(): string
    {
        return '
            id
            sku
            title
            currentlyNotInStock
            priceV2 {
                amount
                currencyCode
            }
            product {
                id
            }
            selectedOptions {
                name
                value
            }
            image {
                originalSrc
            }
        ';
    }

    protected function getAddressQueryFields(): string
    {
        return '
            id
            address1
            address2
            city
            country
            firstName
            lastName
            phone
            province
            zip
        ';
    }

    protected function getShippingLineQueryFields(): string
    {
        return '
            handle
            title
            priceV2 {
                amount
            }
        ';
    }

    protected function getErrorsQueryFields(): string
    {
        return '
            code
            field
            message
        ';
    }
}