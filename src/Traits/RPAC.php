<?php


namespace Codewiser\Rpac\Traits;

use Codewiser\Rpac\Exceptions\RpacException;
use Codewiser\Rpac\Helpers\RpacHelper;
use Codewiser\Rpac\Policies\RpacPolicy;
use Codewiser\Rpac\Role;
use \Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
 * @property array $relationships
 * @property-read array $authorizedActions
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

            foreach ($policy->getNonModelActions() as $action) {
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

            foreach ($policy->getModelActions() as $action) {
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
     * Get listing of defined relationships (qualified names)
     * @return array
     */
    public static function getRelationshipListing()
    {
        $relationships = [];

        foreach ((array)(new static())->relationships as $relationship) {
            $relationships[] = self::getRelationshipQualifiedName($relationship);
        }

        return $relationships;
    }

    /**
     * Get qualified name of relationship
     * @param string $relationship
     * @return string|null
     */
    public static function getRelationshipQualifiedName($relationship)
    {
        if (in_array($relationship, (array)(new static())->relationships)) {
            return self::getPolicy()->getNamespace() . '\\' . Str::studly($relationship);
        } else {
            return null;
        }
    }

    /**
     * Check if User relates to the Model
     * @param User|Roles $user
     * @param string $as
     * @return boolean
     */
    public function relatedTo(User $user, $as)
    {
        list (
            $relationship,  // big_boss
            $actor,         // bigBoss
            $actors,        // bigBosses
            $relatedTo,     // relatedToBigBoss
            $scopeName      // scopeRelatedToBigBoss
            ) = $this->getRelationMethods($as);

        if (method_exists($this, $scopeName)) {
            // scopeRelatedToAuthor contains current Model?
            return static::$relatedTo($user)->whereKey($this->getKey())->exists();

        } elseif (method_exists($this, $actor)) {
            // Single
            // $model->author() relation found
            return $user->is($this->$actor);

        } elseif (method_exists($this, $actors)) {
            // Plural
            // $model->managers() relation found
            /** @var Relation $relation */
            $relation = $this->$actors();
            return $relation->whereKey($user->getKey())->exists();
        }

        return false;
    }

    /**
     * Get methods and properties for given relationship
     * @param string $relationship Namespace\BigBoss or big_boss
     * @return array [big_boss, bigBoss, bigBosses, relatedToBigBoss, scopeRelatedToBigBoss]
     */
    private function getRelationMethods($relationship)
    {
        $relationship = Str::afterLast($relationship, '\\'); // clear namespace
        $singleRelation = Str::camel($relationship); // author() or chiefOfficer()
        $pluralRelation = Str::pluralStudly($singleRelation); // authors() or chiefOfficers()
        $relatedTo = 'relatedTo' . Str::studly($singleRelation); // relatedToAuthor()
        $scopeName = 'scope' . Str::studly($relatedTo); // scopeRelatedToAuthor()

        return [
            $relationship,
            $singleRelation,
            $pluralRelation,
            $scopeName,
            $relatedTo
        ];
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

        list (
            $relationship,      // big_boss
            $singleRelation,    // bigBoss
            $pluralRelation,    // bigBosses
            $queryName,         // relatedToBigBoss
            $scopeName          // scopeRelatedToBigBoss
            ) = $this->getRelationMethods($relationship);

        $relation = null;
        $scope = null;

        if (method_exists($this, $scopeName)) {
            // scopeRelatedTo...
            $scope = $scopeName;
        } elseif (method_exists($this, $singleRelation)) {
            // Single
            $relation = $this->$singleRelation();
        } elseif (method_exists($this, $pluralRelation)) {
            // Plural
            $relation = $this->$pluralRelation();
        }

        if ($scope) {
            return $this->$scopeName($query, $user);
        } elseif ($relation) {
            return $this->applyScopeForRelation($query, $relation, $user);
        } else {
            throw new RpacException("Neither `{$singleRelation}` or `{$pluralRelation}` relations nor `{$scopeName}` scope defined in model.");
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
            // Will never thrown
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

        $userNonModelRoles = $user ? $user->getRoles() : ['guest'];
        $authNonModelRoles = $this->getAuthorizedNonModelRoles($action);

        $fullAccess = array_intersect($userNonModelRoles, $authNonModelRoles);

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
                    $query->whereKey(0);
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
            return Role::allSlugs();
        } else {
            // Some non-model roles allowed
            return array_intersect($roles, Role::allSlugs());
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
            return self::getRelationshipListing();
        } else {
            // Some model roles allowed
            return array_intersect($relationships, self::getRelationshipListing());
        }
    }

}