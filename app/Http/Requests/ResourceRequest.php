<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for every admin CRUD screen. The rules come from the field definitions in
 * App\Admin\ResourceRegistry, so there is one place to change them. Authorization is done by
 * the `admin.can` middleware (ACL gate) before this request is resolved.
 */
class ResourceRequest extends FormRequest
{
    /** @return array<string, mixed> The resource definition set by the admin.can middleware. */
    public function definition(): array
    {
        return $this->attributes->get('resource', []);
    }

    public function isCreating(): bool
    {
        return $this->route('id') === null;
    }

    public function authorize(): bool
    {
        return $this->definition() !== [];
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        $rules = [];

        foreach ($this->definition()['fields'] ?? [] as $field) {
            if (! empty($field['readonly'])) {
                continue;
            }

            $rule = $field['rules'] ?? 'nullable';
            // `unique:table,column,{id}` ignores the record being edited.
            $rule = $this->isCreating() ? str_replace(',{id}', '', $rule) : str_replace('{id}', (string) $this->route('id'), $rule);

            if ($this->isCreating() && ($field['required_on'] ?? null) === 'create') {
                $rule = 'required|'.str_replace('nullable|', '', $rule);
            }

            $rules[$field['name']] = $rule;
        }

        return $rules;
    }
}
