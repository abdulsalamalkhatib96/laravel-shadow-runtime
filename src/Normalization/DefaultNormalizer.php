<?php

declare(strict_types=1);

namespace Evolvex\ShadowRuntime\Normalization;

use BackedEnum;
use DateTimeInterface;
use Evolvex\ShadowRuntime\Contracts\Normalizer;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use JsonSerializable;
use UnitEnum;

final class DefaultNormalizer implements Normalizer
{
    public function normalize(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::ATOM);
        }

        if ($value instanceof Model) {
            $relationships = [];
            foreach ($value->getRelations() as $name => $relation) {
                $relationships[$name] = $this->normalize($relation);
            }

            return [
                '__model' => $value::class,
                'attributes' => $this->normalize($value->getAttributes()),
                'relations' => $relationships,
            ];
        }

        if ($value instanceof \Illuminate\Support\Collection) {
            return $this->normalize($value->all());
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item);
            }
            return $normalized;
        }

        if ($value instanceof JsonSerializable) {
            return $this->normalize($value->jsonSerialize());
        }

        if ($value instanceof Arrayable) {
            return $this->normalize($value->toArray());
        }

        if (is_object($value)) {
            return [
                '__class' => $value::class,
                'properties' => $this->normalize(get_object_vars($value)),
            ];
        }

        return $value;
    }
}
