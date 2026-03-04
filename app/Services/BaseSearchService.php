<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

abstract class BaseSearchService
{
    protected Model $model;
    protected string $searchField;
    protected array $defaultRelations;
    protected array $defaultFilters;

    public function __construct(Model $model, string $searchField = 'name', array $defaultRelations = [], array $defaultFilters = [])
    {
        $this->model = $model;
        $this->searchField = $searchField;
        $this->defaultRelations = $defaultRelations;
        $this->defaultFilters = $defaultFilters;
    }

    /**
     * Enhanced search with multiple strategies
     * Combines exact match, partial match, and fuzzy matching
     */
    public function searchSuggestions($query, $limit = 10): Collection
    {
        if (empty($query) || strlen($query) < 3) {
            return collect();
        }

        $query = trim($query);
        
        // Strategy 1: Exact match (highest priority)
        $exactMatches = $this->exactMatch($query);
        
        // Strategy 2: Starts with match
        $startsWithMatches = $this->startsWithMatch($query);
        
        // Strategy 3: Contains match (partial)
        $containsMatches = $this->containsMatch($query);
        
        // Strategy 4: Fuzzy/Typo tolerance match
        $fuzzyMatches = $this->fuzzyMatch($query);
        
        // Merge results with priority (remove duplicates)
        $results = collect();
        $seenIds = [];
        
        foreach ([$exactMatches, $startsWithMatches, $containsMatches, $fuzzyMatches] as $matches) {
            foreach ($matches as $match) {
                if (!in_array($match->id, $seenIds)) {
                    $results->push($match);
                    $seenIds[] = $match->id;
                    
                    if ($results->count() >= $limit) {
                        break 2;
                    }
                }
            }
        }
        
        return $results;
    }
    public function searchSuggestionsWithFilters($query, $limit = 10, $filters = []): Collection
    {
        $results = $this->searchSuggestions($query, $limit);
        
        if (!empty($filters)) {
            $results = $results->filter(function ($item) use ($filters) {
                foreach ($filters as $key => $value) {
                    if (!isset($item->$key) || $item->$key != $value) {
                        return false;
                    }
                }
                return true;
            });
        }
        
        return $results;
    }

    /**
     * Exact name match
     */
    protected function exactMatch($query): Collection
    {
        return $this->model
            ->where($this->searchField, 'LIKE', $query)
            ->limit(5)
            ->get();
    }

    /**
     * Starts with match
     */
    protected function startsWithMatch($query): Collection
    {
        return $this->model
            ->where($this->searchField, 'LIKE', $query . '%')
            ->limit(5)
            ->get();
    }

    /**
     * Contains match (partial word matching)
     */
    protected function containsMatch($query): Collection
    {
        return $this->model
            ->where($this->searchField, 'LIKE', '%' . $query . '%')
            ->limit(10)
            ->get();
    }

    /**
     * Fuzzy matching for typo tolerance
     * Uses multiple techniques for better results
     */
    protected function fuzzyMatch($query): Collection
    {
        $results = collect();
        
        // Method 1: SOUNDEX for phonetic matching
        $soundexResults = $this->soundexMatch($query);
        $results = $results->merge($soundexResults);
        
        // Method 2: Levenshtein distance simulation using SQL
        $levenshteinResults = $this->levenshteinLikeMatch($query);
        $results = $results->merge($levenshteinResults);
        
        // Method 3: Character substitution patterns
        $substitutionResults = $this->characterSubstitutionMatch($query);
        $results = $results->merge($substitutionResults);
        
        return $results->unique('id')->take(10);
    }

    /**
     * SOUNDEX phonetic matching
     */
    protected function soundexMatch($query): Collection
    {
        return $this->model
            ->whereRaw('SOUNDEX(' . $this->searchField . ') = SOUNDEX(?)', [$query])
            ->limit(10)
            ->get();
    }

    /**
     * Simulate Levenshtein distance using SQL patterns
     * Good for single character typos
     */
    protected function levenshteinLikeMatch($query): Collection
    {
        $patterns = [];
        $bindings = [];
        
        // Generate patterns for single character insertions, deletions, substitutions
        for ($i = 0; $i <= strlen($query); $i++) {
            // Deletion: remove one character
            if ($i < strlen($query)) {
                $pattern = substr($query, 0, $i) . substr($query, $i + 1);
                if (strlen($pattern) >= 2) {
                    $patterns[] = $this->searchField . " LIKE ?";
                    $bindings[] = '%' . $pattern . '%';
                }
            }
            
            // Insertion: add wildcard for one extra character
            $pattern = substr($query, 0, $i) . '_' . substr($query, $i);
            $patterns[] = $this->searchField . " LIKE ?";
            $bindings[] = '%' . $pattern . '%';
            
            // Substitution: replace one character with wildcard
            if ($i < strlen($query)) {
                $pattern = substr($query, 0, $i) . '_' . substr($query, $i + 1);
                $patterns[] = $this->searchField . " LIKE ?";
                $bindings[] = '%' . $pattern . '%';
            }
        }
        
        if (empty($patterns)) {
            return collect();
        }
        
        $whereClause = '(' . implode(' OR ', $patterns) . ')';
        
        return $this->model
            ->whereRaw($whereClause, $bindings)
            ->limit(10)
            ->get();
    }

    /**
     * Common character substitution patterns
     * Handles common typing mistakes
     */
    protected function characterSubstitutionMatch($query): Collection
    {
        $substitutions = [
            'a' => ['e', 'o'],
            'e' => ['a', 'i'],
            'i' => ['e', 'o'],
            'o' => ['a', 'u'],
            'u' => ['o', 'i'],
            'c' => ['k', 's'],
            'k' => ['c'],
            's' => ['c', 'z'],
            'z' => ['s'],
            'f' => ['ph', 'v'],
            'v' => ['f', 'w'],
            'w' => ['v'],
            'y' => ['i'],
            'ph' => ['f'],
        ];
        
        $patterns = [];
        $bindings = [];
        
        foreach (str_split(strtolower($query)) as $i => $char) {
            if (isset($substitutions[$char])) {
                foreach ($substitutions[$char] as $substitute) {
                    $pattern = substr_replace($query, $substitute, $i, 1);
                    $patterns[] = "LOWER(" . $this->searchField . ") LIKE ?";
                    $bindings[] = '%' . strtolower($pattern) . '%';
                }
            }
        }
        
        if (empty($patterns)) {
            return collect();
        }
        
        $whereClause = '(' . implode(' OR ', $patterns) . ')';
        
        return $this->model
            ->whereRaw($whereClause, $bindings)
            ->limit(5)
            ->get();
    }

    /**
     * Add fuzzy matching patterns to query
     */
    protected function addFuzzyMatchingToQuery(Builder $query, $searchTerm): void
    {
        // Add Levenshtein-like patterns for single character typos
        for ($i = 0; $i < strlen($searchTerm); $i++) {
            // Substitution: replace one character with wildcard
            $pattern = substr_replace($searchTerm, '_', $i, 1);
            $query->orWhere($this->searchField, 'LIKE', '%' . $pattern . '%');
        }

        // Add character substitution patterns for common typos
        $substitutions = [
            'a' => ['e', 'o'], 'e' => ['a', 'i'], 'i' => ['e', 'o'],
            'o' => ['a', 'u'], 'u' => ['o', 'i'], 'c' => ['k', 's'],
            'k' => ['c'], 's' => ['c', 'z'], 'z' => ['s'],
            'f' => ['ph', 'v'], 'v' => ['f', 'w'], 'w' => ['v'],
            'y' => ['i'], 'ph' => ['f']
        ];

        foreach (str_split(strtolower($searchTerm)) as $i => $char) {
            if (isset($substitutions[$char])) {
                foreach ($substitutions[$char] as $substitute) {
                    $pattern = substr_replace(strtolower($searchTerm), $substitute, $i, 1);
                    $query->orWhereRaw('LOWER(' . $this->searchField . ') LIKE ?', ['%' . $pattern . '%']);
                }
            }
        }
    }

    /**
     * Get search query builder with scoring
     */
    public function getSearchQueryBuilder($query): Builder
    {
        if (empty($query) || strlen($query) < 3) {
            return $this->model->newQuery()->whereRaw('1 = 0'); // Return empty query
        }

        $query = trim($query);
        
        // Single optimized query with scoring and all search strategies
        $queryBuilder = $this->model->newQuery()
            ->selectRaw(
                $this->model->getTable() . '.*,
                CASE 
                    WHEN LOWER(' . $this->searchField . ') = LOWER(?) THEN 100
                    WHEN LOWER(' . $this->searchField . ') LIKE LOWER(?) THEN 90
                    WHEN LOWER(' . $this->searchField . ') LIKE LOWER(?) THEN 80
                    WHEN LOWER(' . $this->searchField . ') LIKE LOWER(?) THEN 70
                    WHEN SOUNDEX(' . $this->searchField . ') = SOUNDEX(?) THEN 60
                    ELSE 50
                END as relevance_score
            ', [$query, $query . '%', '%' . $query . '%', '%' . $query, $query])
            ->where(function($q) use ($query) {
                $q->where($this->searchField, 'LIKE', '%' . $query . '%')
                  ->orWhere($this->searchField, 'LIKE', $query . '%')
                  ->orWhere($this->searchField, 'LIKE', '%' . $query)
                  ->orWhere(function($subQ) use ($query) {
                      try {
                          $subQ->orWhereRaw('SOUNDEX(' . $this->searchField . ') = SOUNDEX(?)', [$query]);
                      } catch (\Exception $e) {
                          // Skip SOUNDEX if not available
                      }
                  })
                  ->orWhere(function($subQ) use ($query) {
                      // Add fuzzy matching patterns
                      $this->addFuzzyMatchingToQuery($subQ, $query);
                  });
            })
            ->with($this->defaultRelations)
            ->orderByDesc('relevance_score')
            ->orderBy($this->searchField);
            

        return $queryBuilder;
    }

    /**
     * Search with filters
     */
    public function searchWithFilters($request)
    {
        try {
            // Start with search query builder
            if($request->has('q') && !empty($request->query('q'))){
                $queryBuilder = $this->getSearchQueryBuilder($request->query('q'));
            }else{
                $queryBuilder = $this->model->newQuery();   
            }

            // Apply model-specific filters
            $queryBuilder = $this->applyFilters($queryBuilder, $request);

            // Apply pagination
            $perPage = $request->query('per_page', 9);
            
            return $queryBuilder->paginate($perPage);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error($this->model->getTable() . ' search error: ' . $e->getMessage(), [
                'query' => $request->query('q'),
                'filters' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return empty pagination result instead of throwing
            return $this->model->newQuery()->whereRaw('1 = 0')->paginate($request->query('per_page', 9));
        }
    }

    /**
     * Apply filters to the query builder
     * This method should be overridden by child classes for model-specific filters
     */
    protected function applyFilters(Builder $queryBuilder, $request): Builder
    {
        // Apply default filters
        foreach ($this->defaultFilters as $filter) {
            if ($request->has($filter) && !empty($request->query($filter))) {
                $this->applyDefaultFilter($queryBuilder, $filter, $request->query($filter));
            }
        }

        return $queryBuilder;
    }

    /**
     * Apply a default filter
     */
    protected function applyDefaultFilter(Builder $queryBuilder, string $filter, $value): void
    {
        // This can be overridden by child classes for custom filter logic
        if (is_array($value)) {
            $queryBuilder->whereIn($filter, $value);
        } else {
            $queryBuilder->where($filter, $value);
        }
    }
} 