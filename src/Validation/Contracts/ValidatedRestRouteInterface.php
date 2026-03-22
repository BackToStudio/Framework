<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Contracts;

use BackTo\Framework\RestApi\Contracts\RestRouteInterface;

/**
 * A REST route that declares validation rules for its request payload.
 *
 * Routes implementing this interface will have their request
 * parameters validated automatically before the handler is called.
 */
interface ValidatedRestRouteInterface extends RestRouteInterface
{
    /**
     * Return the validation rules for request parameters.
     *
     * @return array<string, ConstraintInterface|ConstraintInterface[]>
     */
    public function rules(): array;
}
