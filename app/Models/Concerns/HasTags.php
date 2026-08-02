<?php

namespace App\Models\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasTags
{
    /**
     * @return MorphToMany<Tag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function syncTagsByName(array $names, int $hospitalId): void
    {
        $ids = collect($names)
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique()
            ->map(fn ($n) => Tag::firstOrCreate(['hospital_id' => $hospitalId, 'name' => $n])->id)
            ->all();

        $this->tags()->sync($ids);
    }
}
