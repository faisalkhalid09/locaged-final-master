<?php

namespace App\Http\Controllers;

use App\Models\UiTranslation;
use App\Support\Branding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;

class UiTranslationController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', UiTranslation::class);

        $translations = UiTranslation::all();
        return view('ui_translations.index', compact('translations'));
    }

    public function create()
    {
        Gate::authorize('create', UiTranslation::class);

        // Load all keys from PHP keyed files as defaults, then merge DB overrides
        $q = request('q');
        $missingOnly = request()->boolean('missing');

        $defaultsEn = $this->flattenLocale('en');
        $defaultsAr = $this->flattenLocale('ar');
        $defaultsFr = $this->flattenLocale('fr');

        $db = UiTranslation::all()->keyBy('key');

        $items = [];
        foreach ($defaultsEn as $key => $en) {
            $row = [
                'key'         => $key,
                'default_en'  => $en,
                'default_ar'  => $defaultsAr[$key] ?? '',
                'default_fr'  => $defaultsFr[$key] ?? '',
                'id'          => optional($db->get($key))->id,
                'en_text'     => optional($db->get($key))->en_text,
                'ar_text'     => optional($db->get($key))->ar_text,
                'fr_text'     => optional($db->get($key))->fr_text,
            ];

            // Apply filters
            if ($q) {
                $hay = strtolower($row['key'] . ' ' . $row['default_en'] . ' ' . $row['default_ar'] . ' ' . $row['default_fr'] . ' ' . ($row['en_text'] ?? '') . ' ' . ($row['ar_text'] ?? '') . ' ' . ($row['fr_text'] ?? ''));
                if (strpos($hay, strtolower($q)) === false) {
                    continue;
                }
            }

            if ($missingOnly) {
                $isMissing = empty($row['en_text']) || empty($row['ar_text']) || empty($row['fr_text']);
                if (! $isMissing) { continue; }
            }

            $items[] = $row;
        }

        // Sort by key for determinism
        usort($items, fn($a, $b) => strcmp($a['key'], $b['key']));

        // Pagination
        $perPage = (int) request('perPage', 25);
        if ($perPage <= 0) { $perPage = 25; }
        $page = (int) request('page', 1);
        if ($page <= 0) { $page = 1; }
        $total = count($items);
        $slice = array_slice($items, ($page - 1) * $perPage, $perPage);
        $paginator = new LengthAwarePaginator($slice, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        return view('ui_translations.create', [
            'items' => $paginator,
            'q' => $q,
            'missingOnly' => $missingOnly,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('create', UiTranslation::class);

        $validated = $request->validate([
            'key' => 'required|string|max:100|unique:ui_translations,key',
            'en_text' => 'nullable|string|max:255',
            'ar_text' => 'nullable|string|max:255',
            'fr_text' => 'nullable|string|max:255',
        ]);

        $en = trim((string)($validated['en_text'] ?? ''));
        $ar = trim((string)($validated['ar_text'] ?? ''));
        $fr = trim((string)($validated['fr_text'] ?? ''));

        // Do not create an empty override row; keep using defaults
        if ($en === '' && $ar === '' && $fr === '') {
            return redirect()->back()->with('success', 'No override saved (using defaults).');
        }

        UiTranslation::create($validated);

        return redirect()->back()->with('success', 'Translation override saved.');
    }


    public function update(Request $request, $id)
    {
        $translation = UiTranslation::findOrFail($id);
        Gate::authorize('update', $translation);

        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:100',
                Rule::unique('ui_translations', 'key')->ignore($translation->id),
            ],
            'en_text' => 'nullable|string|max:255',
            'ar_text' => 'nullable|string|max:255',
            'fr_text' => 'nullable|string|max:255',
        ]);

        $en = trim((string)($validated['en_text'] ?? ''));
        $ar = trim((string)($validated['ar_text'] ?? ''));
        $fr = trim((string)($validated['fr_text'] ?? ''));

        // If all empty, delete the row so it truly reverts to defaults
        if ($en === '' && $ar === '' && $fr === '') {
            $translation->delete();
            return redirect()->back()->with('success', 'Override removed. Using defaults.');
        }

        $translation->update($validated);

        return redirect()->back()->with('success', 'Translation override updated.');
    }

    public function destroy($id)
    {
        $translation = UiTranslation::findOrFail($id);
        Gate::authorize('delete', $translation);

        $translation->delete();

        return redirect()->back()->with('success', 'Translation deleted.');
    }

    public function changeLocale(Request $request)
    {
        $validated = $request->validate([
            'locale' => 'required|in:ar,fr,en'
        ]);

        auth()->user()->locale = $validated['locale'];
        auth()->user()->save();

        return redirect()->back();
    }

    public function brandingUpdate(Request $request)
    {
        Gate::authorize('update', UiTranslation::class);

        $request->validate([
            'header_logo' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:5120'],
            'login_left_image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:8192'],
            'max_users' => ['nullable','integer','min:0'],
            'timezone' => ['nullable','string','timezone'],
        ]);

        if ($request->hasFile('header_logo')) {
            $path = $request->file('header_logo')->store('branding', 'public');
            Branding::set('header_logo', $path);
        }

        if ($request->hasFile('login_left_image')) {
            $path = $request->file('login_left_image')->store('branding', 'public');
            Branding::set('login_left_image', $path);
        }

        if ($request->has('max_users')) {
            Branding::setMaxUsers((int) $request->input('max_users'));
        }

        if ($request->filled('timezone')) {
            Branding::setTimezone($request->input('timezone'));
        }

        return redirect()->back()->with('success','Branding updated successfully.');
    }

    private function flattenLocale(string $locale): array
    {
        $base = base_path('lang/' . $locale);
        $result = [];
        if (!is_dir($base)) {
            return $result;
        }

        $files = glob($base . '/*.php') ?: [];
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            try {
                $arr = include $file;
                if (is_array($arr)) {
                    $this->flattenArray($arr, $result, $name);
                }
            } catch (\Throwable $e) {
                // ignore invalid file
            }
        }

        return $result;
    }

    private function flattenArray(array $arr, array &$out, string $prefix): void
    {
        foreach ($arr as $k => $v) {
            $key = $prefix . '.' . $k;
            if (is_array($v)) {
                $this->flattenArray($v, $out, $key);
            } else {
                $out[$key] = (string) $v;
            }
        }
    }
}
