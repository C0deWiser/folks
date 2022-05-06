<?php

namespace Codewiser\Folks\Controls\Traits;

use Codewiser\Folks\Controls\Option;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait HasOptionList
{
    protected array $options = [];

    /**
     * @param array|Builder|Collection $values
     * @param string|null $caption_attribute Attribute name vor option caption.
     * @param string|null $value_attribute Attribute name vor option value.
     * @return self
     */
    public function options($values, string $caption_attribute = null, string $value_attribute = null): self
    {
        if ($values instanceof Collection) {
            $values = $this->optionsFromCollection($values, $caption_attribute, $value_attribute);
        }

        if ($values instanceof Builder) {
            $values = $this->optionsFromBuilder($values, $caption_attribute, $value_attribute);
        }

        if (is_array($values) || $values instanceof \Illuminate\Support\Collection) {
            $this->options = collect($values)
                ->filter(function ($value) {
                    return $value instanceof Option;
                })
                ->toArray();
        }

        return $this;
    }

    protected function optionsFromBuilder(Builder $builder, string $caption_attr = null, string $value_attr = null): array
    {
        $values = [];
        $builder->each(function (Model $model) use ($caption_attr, $value_attr, &$values) {
            $option = Option::make($value_attr ? $model->{$value_attr} : $model->getKey());
            $option->label($caption_attr ? $model->{$caption_attr} : (string)$model);
            $values[] = $option;
        });
        return $values;
    }

    protected function optionsFromCollection(Collection $collection, string $caption_attr = null, string $value_attr = null)
    {
        return $collection->map(function (Model $model) use ($caption_attr, $value_attr) {
            $option = Option::make($value_attr ? $model->{$value_attr} : $model->getKey());
            $option->label($caption_attr ? $model->{$caption_attr} : (string)$model);
            return $option;
        });
    }
}
