<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'organisation_id' => $this->organisation_id,
            'zone_id' => $this->zone_id,

            'organisation_name' => $this->relationLoaded('organisation')
                 ? $this->organisation?->name
                 : null,

            'organisation_ids' => $this->whenLoaded('organisations', function () {
                return $this->organisations->pluck('id')->values();
            }),

            'organisations' => $this->whenLoaded('organisations', function () {
                return $this->organisations->map(function ($organisation) {
                    return [
                        'id' => $organisation->id,
                        'name' => $organisation->name,
                        'slug' => $organisation->slug,
                    ];
                })->values();
            }),
          
            /**
             * Added department details to the user response in a simplified
             * departments_id and departments_name array format.
             */
            'departments' => $this->whenLoaded('departments', function () {
                return $this->departments->map(function ($department) {
                    return [
                        'departments_id' => $department->id,
                        'departments_name' => $department->name,
                    ];
                })->values();
            }),
            
            'zone_name' => $this->relationLoaded('zone')
                ? $this->zone?->name
                : null,
            'avatar_url' => $this->avatar_url,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name,
                        'description' => $role->description,
                    ];
                });
            }),
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => $permission->display_name,
                        'description' => $permission->description,
                    ];
                });
            }),
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at ? $this->email_verified_at->format('Y-m-d H:i:s A') : null,
            'last_login_at' => $this->last_login_at ? $this->last_login_at->format('Y-m-d H:i:s A') : null,
            'full_name' => $this->full_name,
            'is_active' => $this->isActive(),
            'is_admin' => $this->isAdmin(),
            'is_parent' => $this->is_parent,
            'parents' => $this->whenLoaded('parents', function () {
                return $this->parents->map(function ($parent) {
                    return $this->buildParentHierarchy($parent);
                });
            }),
            // 'children' => $this->whenLoaded('children', function () {
            //     return $this->children->map(function ($child) {
            //         return [
            //             'id' => $child->id,
            //             'name' => $child->name,
            //             'email' => $child->email,
            //         ];
            //     });
            // }),
            'profile' => $this->whenLoaded('profile', function () {
                return new ProfileResource($this->profile);
            }),
        ]);
    }

    /**
     * Build parent hierarchy recursively
     */
    private function buildParentHierarchy($parent): array
    {
        $parents = $parent->parents()->select('users.id', 'users.name', 'users.email')->get();
        
        return [
            'id' => $parent->id,
            'name' => $parent->name,
            'email' => $parent->email,
            'parents' => $parents->isNotEmpty() ? $parents->map(function ($p) {
                return $this->buildParentHierarchy($p);
            })->toArray() : []
        ];
    }
}
