<?php

namespace App\Http\Controllers;

use App\Models\ContextualFaq;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PublicFaqController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $faqs = ContextualFaq::query()
            ->published()
            ->forContext('public')
            ->with('category')
            ->when($search !== '', function (Builder $query) use ($search): Builder {
                $term = '%'.$search.'%';

                return $query->where(function (Builder $builder) use ($term): void {
                    $builder->where('question', 'like', $term)
                        ->orWhere('answer', 'like', $term);
                });
            })
            ->when($category !== '', function (Builder $query) use ($category): Builder {
                return $query->whereHas('category', fn (Builder $builder) => $builder->where('code', $category));
            })
            ->orderBy('sort_order')
            ->orderBy('question')
            ->get();

        return view('public.faq', [
            'faqs' => $faqs,
            'search' => $search,
            'category' => $category,
        ]);
    }
}
