<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOwner;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ItemFamily extends Model
{
    use HasFactory;
    use BelongsToOwner;

    protected $fillable = [
        'owner_id',
        'parent_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('name');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'family_id');
    }

    public function fullPathLabel(): string
    {
        $segments = [$this->name];
        $visited = [$this->id => true];
        $current = $this->relationLoaded('parent')
            ? $this->parent
            : $this->parent()->first(['id', 'name', 'parent_id']);
        $guard = 0;

        while ($current && $guard < 25) {
            if (isset($visited[$current->id])) {
                break;
            }

            array_unshift($segments, $current->name);
            $visited[$current->id] = true;
            $current = $current->relationLoaded('parent')
                ? $current->parent
                : $current->parent()->first(['id', 'name', 'parent_id']);
            $guard++;
        }

        return implode(' > ', $segments);
    }

    public function descendantIds(): array
    {
        return self::descendantIdsOf((int) $this->id);
    }

    public function ancestorIds(): array
    {
        return self::ancestorIdsOf((int) $this->id);
    }

    public static function descendantIdsOf(int $familyId): array
    {
        $families = self::query()->get(['id', 'parent_id']);

        return self::resolveDescendants($families, $familyId);
    }

    public static function ancestorIdsOf(int $familyId): array
    {
        $families = self::query()->get(['id', 'parent_id']);
        $byId = $families->keyBy('id');

        $ancestors = [];
        $visited = [];
        $currentId = $familyId;

        while (true) {
            $current = $byId->get($currentId);
            if (! $current || $current->parent_id === null) {
                break;
            }

            $parentId = (int) $current->parent_id;
            if (isset($visited[$parentId])) {
                break;
            }

            $visited[$parentId] = true;
            $ancestors[] = $parentId;
            $currentId = $parentId;
        }

        return $ancestors;
    }

    public static function descendantAndSelfIds(int $familyId): array
    {
        return array_merge([$familyId], self::descendantIdsOf($familyId));
    }

    public static function flattenedHierarchy(?EloquentCollection $families = null, array $excludedIds = []): Collection
    {
        $families = ($families ?? self::query()->get())
            ->sortBy(fn (self $family) => mb_strtolower($family->name))
            ->values();

        $childrenByParent = $families->groupBy(function (self $family): string {
            return $family->parent_id === null
                ? 'root'
                : (string) $family->parent_id;
        });

        $flattened = collect();
        $visited = [];
        $excluded = array_fill_keys($excludedIds, true);

        $walk = function (string $parentKey, int $depth, array $pathSegments) use (&$walk, $childrenByParent, $flattened, &$visited, $excluded): void {
            $children = $childrenByParent->get($parentKey, collect());

            foreach ($children as $family) {
                $familyId = (int) $family->id;
                if (isset($visited[$familyId])) {
                    continue;
                }

                $visited[$familyId] = true;
                $segments = array_merge($pathSegments, [$family->name]);

                $family->setAttribute('depth', $depth);
                $family->setAttribute('path_label', implode(' > ', $segments));

                if (! isset($excluded[$familyId])) {
                    $flattened->push($family);
                }

                $walk((string) $familyId, $depth + 1, $segments);
            }
        };

        $walk('root', 0, []);

        foreach ($families as $family) {
            $familyId = (int) $family->id;
            if (isset($visited[$familyId])) {
                continue;
            }

            $family->setAttribute('depth', 0);
            $family->setAttribute('path_label', $family->name);

            if (! isset($excluded[$familyId])) {
                $flattened->push($family);
            }
        }

        return $flattened->values();
    }

    private static function resolveDescendants(EloquentCollection $families, int $familyId): array
    {
        $childrenByParent = $families->groupBy(function (self $family): string {
            return $family->parent_id === null
                ? 'root'
                : (string) $family->parent_id;
        });

        $descendants = [];
        $queue = [$familyId];

        while (! empty($queue)) {
            $currentParentId = (int) array_shift($queue);
            $children = $childrenByParent->get((string) $currentParentId, collect());

            foreach ($children as $child) {
                $childId = (int) $child->id;
                if (in_array($childId, $descendants, true)) {
                    continue;
                }

                $descendants[] = $childId;
                $queue[] = $childId;
            }
        }

        return $descendants;
    }
}
