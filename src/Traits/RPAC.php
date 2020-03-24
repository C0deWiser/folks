<?php


namespace Codewiser\Rpac\Traits;

use Codewiser\Rpac\Exceptions\RpacException;
use Codewiser\Rpac\Helpers\RpacHelper;
use Codewiser\Rpac\Policies\RpacPolicy;
use Codewiser\Rpac\Role;
use \Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Get list of authorized actions (uses Laravel Policies)
 * @package Codewiser\Rpac\Traits
 *
 * Every defined relationship must have relation method.
 * For example:
 * For 'author' relationship we assume author() relation.
 * For 'manager' relationship we assume managers() relation.
 * Etc.
 *
 * @property-read array $relationships Set of relationships (aka Model Roles) between User and Model
 * @property-read array $authorizedActions
 *
 */
trait RPAC
{
    /**
     * Get Model R.P.A.C. Policy
     * @return RpacPolicy|null
     */
    public static function getPolicy()
    {
        if (($policy = Gate::getPolicyFor(static::class)) && $policy instanceof RpacPolicy) {
            return $policy;
        } else {
            return null;
        }
    }

    /**
     * Get list of non-model actions allowed to given User
     * @param User|null $user
     * @return array|string[]
     */
    public static function authorizedActions(?User $user)
    {
        if ($policy = self::getPolicy()) {
            $abilities = [];

            try {
                $actions = (new RpacHelper(static::class))->getNonModelActions();
            } catch (\ReflectionException $e) {
                $actions = [];
            }

            foreach ($actions as $action) {
                if ($policy->$action($user)) {
                    $abilities[] = $action;
                }
            }

            return $abilities;
        } else {
            return [];
        }
    }

    /**
     * Get list of model actions allowed to given User
     * @param User|null $user
     * @return array|string[]
     */
    public function getAuthorizedActions(?User $user)
    {
        if ($policy = self::getPolicy()) {
            $abilities = [];

            try {
                $actions = (new RpacHelper(get_class($this)))->getModelActions();
            } catch (\ReflectionException $e) {
                $actions = [];
            }

            foreach ($actions as $action) {
                if ($policy->$action($user, $this)) {
                    $abilities[] = $action;
                }
            }

            return $abilities;
        } else {
            return [];
        }
    }

    public function getAuthorizedActionsAttribute()
    {
        return $this->getAuthorizedActions(Auth::user());
    }

    /**
     * Get listing of defined relationships, isolated with Policy pseudo-name
     * @return array
     */
    public function getRelationshipListing()
    {
        $relationships = [];
        $policy = self::getPolicy();
        foreach ($this->relationships as $relationship) {
            $relationships[] = $policy->getNamespace() . '\\' . Str::studly($relationship);
        }
        return $relationships;
    }

    /**
     * Scope will limit Model to only records related to given User
     * @param Builder $query
     * @param $relationship
     * @param User $user
     * @return Builder
     * @throws RpacException
     */
    public function scopeRelated(Builder $query, $relationship, ?User $user)
    {
        if ($user == null) {
            // no records
            return $query->whereKey(0);
        }

        // clear relationship from namespace
        $relationship = Str::afterLast($relationship, '\\');

        // if $relationship is author or chief_officer
        $single = Str::camel($relationship); // author() or chiefOfficer()
        $plural = Str::pluralStudly($single); // authors() or chiefOfficers()

        if (method_exists($this, $single)) {
            // Single
            $relation = $this->$single();
        } elseif (method_exists($this, $plural)) {
            // Plural
            $relation = $this->$plural();
        } else {
            $relation = null;
        }

        if ($relation) {
            return $this->applyScopeForRelation($query, $relation, $user);
        } else {
            throw new RpacException("Unknown relation `{$relationship}`");
        }
    }

    /**
     * @param Builder $query
     * @param Relation $relation
     * @param User|Model $user
     * @return Builder
     * @throws RpacException
     */
    private function applyScopeForRelation(Builder $query, Relation $relation, User $user)
    {
        if ($relation instanceof HasOneOrMany) {
            return $this->applyScopeForHasOneOrManyRelation($query, $relation, $user);
        } elseif ($relation instanceof BelongsTo) {
            return $this->applyScopeForBelongsToRelation($query, $relation, $user);
        } elseif ($relation instanceof BelongsToMany) {
            return $this->applyScopeForBelongsToManyRelation($query, $relation, $user);
        } else {
            throw new RpacException("Not implemented");
        }
    }

    /**
     * @param Builder $query
     * @param HasOneOrMany $relation
     * @param User|Model $user
     * @return Builder
     */
    private function applyScopeForHasOneOrManyRelation(Builder $query, HasOneOrMany $relation, User $user)
    {
        // Relation means that one User has one This
        // hasOne(User::class, foreignKey, localKey);
        // foreignKey is an attribute of User
        // localKey is an attribute of This
        // this.localKey = related.foreignKey
        // this.id = user.id
        return $query->where(
            $relation->getLocalKeyName(),
            $user->getAttribute(
                $relation->getForeignKeyName()
            )
        );
    }

    /**
     * @param Builder $query
     * @param BelongsTo $relation
     * @param User|Model $user
     * @return Builder
     */
    private function applyScopeForBelongsToRelation(Builder $query, BelongsTo $relation, User $user)
    {
        // Relation means that one User has many of This
        // BelongsTo(User::class, foreignKey, ownerKey);
        // foreignKey is an attribute of This
        // ownerKey is an attribute of User
        // this.foreignKey = related.ownerKey
        // this.author_id = user.id
        // whereHas(author, user.id=5)
        return $query->where(
            $relation->getForeignKeyName(),
            $user->getAttribute(
                $relation->getOwnerKeyName()
            )
        );
    }

    /**
     * @param Builder $query
     * @param BelongsToMany $relation
     * @param User|Model $user
     * @return Builder
     */
    private function applyScopeForBelongsToManyRelation(Builder $query, BelongsToMany $relation, User $user)
    {
        // Relation means that many Users have many of This
        // BelongsToMany(User::class, table, foreignPivotKey, relatedPivotKey, parentKey, relatedKey);
        // table is a Pivot
        // foreignPivotKey is an attribute related to This
        // relatedPivotKey is an attribute related to User
        // parentKey is an attribute of This
        // relatedKey is an attribute of User
        // this.parentKey = pivot.foreignPivotKey AND pivot.relatedPivotKey = related.relatedKey
        // this.id = pivot.post_id AND pivot.user_id = user.id
        // whereHas(editors, user.id=5)
        return $query->whereHas($relation->getRelationName(), function (Builder $query) use ($relation, $user) {
            $query->where(
                $relation->getRelatedKeyName(),
                $user->getAttribute(
                    $relation->getRelatedKeyName()
                )
            );
        }
        );
    }

    /**
     * Apply global scope to the Model, so user can get only records he allowed to $action
     * @param Builder $query
     * @param string $action
     * @param User|Roles|null $user
     * @return Builder
     */
    public function scopeAllowedTo(Builder $query, $action, ?User $user)
    {
        // if one of user's non-model Roles is permitted to $action, user has access to all records
        // if not:
        // we get all positive permissions with Model+Action signature
        // we get Relationships from those permissions
        // we apply Relationship Scopes, so Collection got only models from those scopes

        // Without model policy checks only non-model roles
        // If it returns true — user may observe all recordSet

        $userNonModelRoles = $user ? array_merge(
            ['any'], $user->roles->pluck('slug')->toArray()
        ) : ['guest'];
        $authNonModelRoles = $this->getAuthorizedNonModelRoles($action);

        $fullAccess =
            in_array('*', $authNonModelRoles)
            ||
            array_intersect($userNonModelRoles, $authNonModelRoles);

        if (!$fullAccess) {

            $authModelRoles = $this->getAuthorizedModelRoles($action);

            // User not authorized by his non-model roles
            // But may be has access through relationships (model roles)

            // Get all user relationship scopes and combine them into one query
            // Then scope model with that query
            // If model has no relationship scopes, will return empty scope

            $query->where(function (Builder $query) use ($authModelRoles, $user) {
                if ($user && $authModelRoles) {
                    // It will combine query
                    // where ('records where user is record-author' OR 'records where user is one of record managers')
                    // etc
                    foreach ($authModelRoles as $relationship) {
                        $query->orWhere(function (Builder $query) use ($relationship, $user) {
                            $query->related($relationship, $user);
                        });
                    }
                } else {
                    // User is anon or there are no authorized model roles
                    // Apply empty scope to prevent user access to unauthorized models
                    $query->where($this->getKeyName(), 0);
                }
            });
        }
        // else: User has total access to whole recordSet
    }

    /**
     * Get non-model roles, allowed to perform given action
     * @param $action
     * @return array of roles
     */
    protected function getAuthorizedNonModelRoles($action)
    {
        // Keep only non-model roles
        $roles = static::getPolicy()->getPermissions($action);

        if (in_array('*', $roles)) {
            // All non-model roles allowed
            return RpacHelper::getNonModelRoles();
        } else {
            // Some non-model roles allowed
            return array_intersect($roles, RpacHelper::getNonModelRoles());
        }
    }

    /**
     * Get model roles, allowed to perform given action
     * @param string $action
     * @return array of roles
     */
    protected function getAuthorizedModelRoles($action)
    {
        // Keep only model roles
        $relationships = static::getPolicy()->getPermissions($action);

        if (in_array('*', $relationships)) {
            // All model roles allowed
            return $this->getRelationshipListing();
        } else {
            // Some model roles allowed
            return array_intersect($relationships, $this->getRelationshipListing());
        }
    }

}