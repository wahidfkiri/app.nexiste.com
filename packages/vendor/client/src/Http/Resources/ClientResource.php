<?php

namespace Vendor\Client\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'website' => $this->website,
            'address' => $this->address,
            'address2' => $this->address2,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'state' => $this->state,
            'country' => $this->country,
            'vat_number' => $this->vat_number,
            'siret' => $this->siret,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'source' => $this->source,
            'source_label' => $this->source_label,
            'tags' => $this->tags,
            'revenue' => $this->revenue,
            'potential_value' => $this->potential_value,
            'payment_term' => $this->payment_term,
            'industry' => $this->industry,
            'employee_count' => $this->employee_count,
            'notes' => $this->notes,
            'full_address' => $this->full_address,
            'initials' => $this->initials,
            'last_contact_at' => $this->last_contact_at,
            'next_follow_up_at' => $this->next_follow_up_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relations
            'assigned_to' => $this->whenLoaded('assignedTo', function() {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                ];
            }),
            
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
        ];
    }
}