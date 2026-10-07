<?php

namespace App\Livewire\Customer;

use App\Models\ItemCategory;
use App\Models\MasterItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CatalogIndex extends Component
{
    use WithPagination;

    /**
     * Real-time search string (synced with URL ?search=...).
     */
    #[Url(as: 'search', history: true, keep: false)]
    public string $search = '';

    /**
     * Selected category ID filter (synced with URL ?category_id=...).
     */
    #[Url(as: 'category_id', history: true, keep: false)]
    public ?int $categoryId = null;

    /**
     * Reset pagination to page 1 when real-time search term updates.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Reset pagination to page 1 when category selection changes.
     */
    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    /**
     * Select or toggle active category filter.
     */
    public function selectCategory(?int $id = null): void
    {
        $this->categoryId = ($this->categoryId === $id) ? null : $id;
        $this->resetPage();
    }

    /**
     * Reset search query and category filters back to default.
     */
    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryId']);
        $this->resetPage();
    }

    /**
     * Render catalog items with pagination.
     */
    public function render(): View
    {
        $categories = ItemCategory::withCount(['items' => function ($query) {
            $query->where('is_active', true);
        }])->get();

        $itemsQuery = MasterItem::with(['category', 'vendor', 'units'])
            ->where('is_active', true);

        $trimmedSearch = trim($this->search);
        if ($trimmedSearch !== '') {
            $itemsQuery->where(function ($query) use ($trimmedSearch) {
                $query->where('name', 'like', "%{$trimmedSearch}%")
                    ->orWhere('description', 'like', "%{$trimmedSearch}%");
            });
        }

        if ($this->categoryId) {
            $itemsQuery->where('category_id', $this->categoryId);
        }

        $items = $itemsQuery->latest()->paginate(12);

        return view('livewire.customer.catalog-index', [
            'categories' => $categories,
            'items' => $items,
        ]);
    }
}
