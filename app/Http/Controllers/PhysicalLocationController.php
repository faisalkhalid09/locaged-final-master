<?php

namespace App\Http\Controllers;

use App\Models\PhysicalLocation;
use App\Models\Room;
use App\Models\Row;
use App\Models\Shelf;
use App\Models\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exports\PhysicalLocationsExport;
use App\Exports\PhysicalLocationFilesExport;
use Maatwebsite\Excel\Facades\Excel;

class PhysicalLocationController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', PhysicalLocation::class);

        // Legacy admin role should not access Physical Locations settings page
        if (auth()->user()?->hasRole('admin')) {
            abort(403);
        }

        $user = auth()->user();
        
        $roomsQuery = Room::with(['rows.shelves.boxes' => function ($query) use ($user) {
            // Apply service-based filtering to boxes
            $query->forUser($user);
            
            // Load documents for filtered boxes
            $query->with(['documents' => function ($docQuery) {
                $docQuery->select('id', 'box_id', 'title');
            }]);
        }]);

        // Filter rooms selection based on user permissions
        $accessibleServiceIds = Box::getAccessibleServiceIds($user);

        if ($accessibleServiceIds !== 'all') {
            $roomsQuery->where(function($q) use ($accessibleServiceIds) {
                // Show rooms that have boxes belonging to user's services
                $q->whereHas('rows.shelves.boxes', function($boxQ) use ($accessibleServiceIds) {
                    $boxQ->whereIn('service_id', $accessibleServiceIds);
                })
                // OR show empty rooms (rooms with no boxes at all)
                // Note: We check 'rows.shelves.boxes' to catch deep nesting
                ->orWhereDoesntHave('rows.shelves.boxes');
            });
        }

        $rooms = $roomsQuery->get();
        
        return view('physical_locations.index', compact('rooms'));
    }


    public function store(Request $request)
    {
        Gate::authorize('create', PhysicalLocation::class);

        $validated = $request->validate([
            'room_name' => 'required|string|max:255',
            'row_name' => 'required|string|max:255',
            'shelf_name' => 'required|string|max:255',
            'box_name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Step 1: Find or create room
            $room = Room::firstOrCreate(
                ['name' => $validated['room_name']],
                ['description' => null]
            );

            // Step 2: Find or create row inside the room
            $row = Row::firstOrCreate(
                [
                    'room_id' => $room->id,
                    'name' => $validated['row_name']
                ],
                ['description' => null]
            );

            // Step 3: Find or create shelf inside the row
            $shelf = Shelf::firstOrCreate(
                [
                    'row_id' => $row->id,
                    'name' => $validated['shelf_name']
                ],
                ['description' => null]
            );

            // Step 4: Create box inside the shelf (this is what gets created - boxes don't reuse)
            $box = Box::create([
                'shelf_id' => $shelf->id,
                'name' => $validated['box_name'],
                'description' => $validated['description'] ?? null,
            ]);

            DB::commit();

            return back()->with('success', 'Location path created successfully: <strong>' . $box->__toString() . '</strong>');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create location: ' . $e->getMessage()]);
        }
    }



    /**
     * Add a new Room
     */
    public function addRoom(Request $request)
    {
        Gate::authorize('create', PhysicalLocation::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rooms,name',
            'description' => 'nullable|string',
        ]);

        $room = Room::create($validated);

        return back()->with('success', 'Room "<strong>' . $room->name . '</strong>" created successfully.');
    }

    /**
     * Add a Row to an existing Room
     */
    public function addRow(Request $request)
    {
        Gate::authorize('create', PhysicalLocation::class);

        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Check if row with same name already exists in this room
        $existingRow = Row::where('room_id', $validated['room_id'])
            ->where('name', $validated['name'])
            ->first();

        if ($existingRow) {
            return back()->withErrors(['error' => 'A row with this name already exists in the selected room.']);
        }

        $row = Row::create([
            'room_id' => $validated['room_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $room = Room::find($validated['room_id']);
        return back()->with('success', 'Row "<strong>' . $row->name . '</strong>" added to room "<strong>' . $room->name . '</strong>" successfully.');
    }

    /**
     * Add a Shelf to an existing Row
     */
    public function addShelf(Request $request)
    {
        Gate::authorize('create', PhysicalLocation::class);

        $validated = $request->validate([
            'row_id' => 'required|exists:rows,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Check if shelf with same name already exists in this row
        $existingShelf = Shelf::where('row_id', $validated['row_id'])
            ->where('name', $validated['name'])
            ->first();

        if ($existingShelf) {
            return back()->withErrors(['error' => 'A shelf with this name already exists in the selected row.']);
        }

        $shelf = Shelf::create([
            'row_id' => $validated['row_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $row = Row::with('room')->find($validated['row_id']);
        return back()->with('success', 'Shelf "<strong>' . $shelf->name . '</strong>" added to row "<strong>' . $row->name . '</strong>" in room "<strong>' . $row->room->name . '</strong>" successfully.');
    }

    /**
     * Add a Box to an existing Shelf
     */
    public function addBox(Request $request)
    {
        Gate::authorize('create', PhysicalLocation::class);

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'shelf_id' => 'required|exists:shelves,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Check if box with same name already exists in this shelf
        $existingBox = Box::where('shelf_id', $validated['shelf_id'])
            ->where('name', $validated['name'])
            ->first();

        if ($existingBox) {
            return back()->withErrors(['error' => 'A box with this name already exists in the selected shelf.']);
        }

        $box = Box::create([
            'shelf_id' => $validated['shelf_id'],
            'service_id' => $validated['service_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        $box->load('shelf.row.room');
        return back()->with('success', 'Box "<strong>' . $box->name . '</strong>" added successfully. Path: <strong>' . $box->__toString() . '</strong>');
    }

    /**
     * Update a box in the hierarchical structure
     */
    public function updateBox(Request $request, Box $box)
    {
        // Authorize using PhysicalLocation policy (same scope as create)
        Gate::authorize('create', PhysicalLocation::class);

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'room_name' => 'required|string|max:255',
            'row_name' => 'required|string|max:255',
            'shelf_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Find or create the new path structure
            $room = Room::firstOrCreate(['name' => $validated['room_name']]);
            
            $row = Row::firstOrCreate([
                'room_id' => $room->id,
                'name' => $validated['row_name']
            ]);
            
            $shelf = Shelf::firstOrCreate([
                'row_id' => $row->id,
                'name' => $validated['shelf_name']
            ]);

            // Check if a box with this name already exists in the target shelf (excluding current box)
            $existingBox = Box::where('shelf_id', $shelf->id)
                ->where('name', $validated['name'])
                ->where('id', '!=', $box->id)
                ->first();

            if ($existingBox) {
                DB::rollBack();
                return back()->withErrors(['error' => ui_t('errors.physical_location.box_name_duplicate')]);
            }

            // Move the box to the new shelf and update details
            $box->update([
                'shelf_id' => $shelf->id,
                'service_id' => $validated['service_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            DB::commit();
            return back()->with('success', 'Box updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Check if it's a duplicate entry error (in case we missed something)
            if (str_contains($e->getMessage(), '1062') || str_contains($e->getMessage(), 'Duplicate entry')) {
                return back()->withErrors(['error' => ui_t('errors.physical_location.box_name_duplicate')]);
            }
            
            return back()->withErrors(['error' => ui_t('errors.physical_location.box_update_failed')]);
        }
    }

    /**
     * Delete a box (will cascade delete if no documents)
     */
    public function destroyBox(Box $box)
    {
        // Authorize via permission name (no PhysicalLocation instance available here)
        Gate::authorize('delete physical location');

        // Check if box has documents
        if ($box->documents()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete box with documents. Move documents first.']);
        }

        $box->delete();

        return back()->with('success', 'Box deleted successfully.');
    }


    public function export()
    {
        Gate::authorize('viewAny', PhysicalLocation::class);
        return Excel::download(new PhysicalLocationsExport(), 'physical-locations-report-' . now()->format('Ymd_His') . '.xlsx');
    }

    public function exportFiles(PhysicalLocation $physicalLocation)
    {
        Gate::authorize('viewAny', PhysicalLocation::class);
        return Excel::download(new PhysicalLocationFilesExport($physicalLocation), 'location-' . $physicalLocation->id . '-files-' . now()->format('Ymd_His') . '.xlsx');
    }
}
