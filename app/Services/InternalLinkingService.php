<?php

namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InternalLinkingService
{
    public function applyInternalLinks(Article $article, string $body, int $maxLinks = 3): string
    {
        $relatedArticles = $this->findRelatedPosts($article, $maxLinks * 3);
        $linksInserted = 0;

        foreach ($relatedArticles as $related) {
            if ($linksInserted >= $maxLinks) {
                break;
            }

            $url = $this->buildArticleUrl($related);
            if ($this->alreadyLinked($body, $url)) {
                continue;
            }

            $phrase = $this->chooseLinkPhrase($related);
            if (!$phrase) {
                continue;
            }

            $updated = $this->insertMarkdownLink($body, $phrase, $url);
            if ($updated !== $body) {
                $body = $updated;
                $linksInserted++;
            }
        }

        return $body;
    }

    public function findRelatedPosts(Article $article, int $limit = 5): Collection
    {
        $searchTerms = $this->extractSearchTerms($article->title . ' ' . $article->excerpt . ' ' . $article->focus_keywords);
        $categoryIds = $article->categories()->pluck('categories.id')->toArray();

        $candidates = Article::where('status', 'published')
            ->where('id', '!=', $article->id)
            ->with('categories')
            ->orderBy('published_at', 'desc')
            ->take(120)
            ->get(['id', 'title', 'slug', 'excerpt', 'focus_keywords']);

        return $candidates
            ->map(function (Article $candidate) use ($searchTerms, $categoryIds, $article) {
                $score = $this->candidateScore($candidate, $searchTerms, $categoryIds);

                return ['article' => $candidate, 'score' => $score];
            })
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->values()
            ->take($limit)
            ->map(fn ($item) => $item['article']);
    }

    protected function candidateScore(Article $candidate, array $searchTerms, array $categoryIds): int
    {
        $title = strtolower($candidate->title ?? '');
        $excerpt = strtolower($candidate->excerpt ?? '');
        $keywords = strtolower($candidate->focus_keywords ?? '');

        $score = 0;
        foreach ($searchTerms as $term) {
            if ($term === '') {
                continue;
            }

            $weight = strlen($term) > 5 ? 5 : 3;
            if (Str::contains($title, $term)) {
                $score += 10 + $weight;
            }
            if (Str::contains($excerpt, $term)) {
                $score += 5 + $weight;
            }
            if (Str::contains($keywords, $term)) {
                $score += 4;
            }
        }

        if (!empty($categoryIds) && $candidate->relationLoaded('categories')) {
            $shared = $candidate->categories->pluck('id')->intersect($categoryIds);
            if ($shared->isNotEmpty()) {
                $score += 15;
            }
        }

        return min(100, max(0, $score));
    }

    protected function extractSearchTerms(string $text): array
    {
        $clean = preg_replace('/[^\pL\pN\s]+/u', ' ', mb_strtolower($text));
        $terms = array_filter(array_unique(array_map('trim', preg_split('/\s+/', $clean, -1, PREG_SPLIT_NO_EMPTY))));

        return array_slice($terms, 0, 18);
    }

    protected function chooseLinkPhrase(Article $article): ?string
    {
        $title = trim($article->title);
        if ($title !== '') {
            return $title;
        }

        if (!empty($article->focus_keywords)) {
            return explode(',', $article->focus_keywords)[0] ?? null;
        }

        return null;
    }

    protected function buildArticleUrl(Article $article): string
    {
        return url('/articles/' . ($article->slug ?? ''));
    }

    protected function alreadyLinked(string $body, string $url): bool
    {
        return Str::contains($body, '(' . $url . ')');
    }

    protected function insertMarkdownLink(string $body, string $phrase, string $url): string
    {
        $escapedPhrase = preg_quote($phrase, '/');
        $pattern = '/(?<!\[)\b(' . $escapedPhrase . ')\b(?!\]\()/iu';

        if (!preg_match($pattern, $body, $matches, PREG_OFFSET_CAPTURE)) {
            return $this->insertFallbackLink($body, $phrase, $url);
        }

        $matchText = $matches[1][0];
        $position = $matches[1][1];

        if ($this->phraseAlreadyLinked($body, $matchText, $url)) {
            return $body;
        }

        return substr_replace($body, "[{$matchText}]({$url})", $position, strlen($matchText));
    }

    protected function phraseAlreadyLinked(string $body, string $phrase, string $url): bool
    {
        $link = '[' . $phrase . '](' . $url . ')';
        return Str::contains($body, $link);
    }

    protected function insertFallbackLink(string $body, string $phrase, string $url): string
    {
        $paragraphs = preg_split('/(\r?\n\r?\n)/', $body, 2, PREG_SPLIT_DELIM_CAPTURE);
        if (count($paragraphs) < 1) {
            return $body;
        }

        $firstParagraph = $paragraphs[0];
        if (Str::contains($firstParagraph, '[' . $phrase . '](' . $url . ')')) {
            return $body;
        }

        $linkedPhrase = "[{$phrase}]({$url})";
        if (Str::contains($firstParagraph, $linkedPhrase)) {
            return $body;
        }

        $paragraphs[0] = trim($firstParagraph) . ' ' . $linkedPhrase;

        return implode('', $paragraphs);
    }
}
