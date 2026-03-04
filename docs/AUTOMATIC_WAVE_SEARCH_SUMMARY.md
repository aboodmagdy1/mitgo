# Automatic Multi-Wave Driver Search - Implementation Summary

## What Changed

Transformed manual "search again" flow into **automatic progressive search** with real-time progress updates.

## Files Created

1. **app/Jobs/ProcessDriverSearch.php**
   - Smart job that executes waves automatically
   - Checks trip status before each wave
   - Schedules next wave after delay
   - Stops if driver accepts or max waves reached

2. **app/Events/SearchProgress.php**
   - Broadcasts to `trip.{tripId}` channel
   - Provides current_wave, total_waves, progress_percentage
   - Allows client to show progress bar

## Files Modified

1. **app/Services/DriverSearchService.php**
   - Added `searchWave()` method for single wave execution
   - Added `calculateRadius()` method for wave-based radius
   - Changed `handleNoDriverFound()` from private to public

2. **app/Listeners/InitiateDriverSearch.php**
   - Changed from calling `initiateSearch()` to dispatching `ProcessDriverSearch` job
   - Initializes Redis tracking
   - Dispatches first wave (wave 1)

3. **routes/API/V1/client.routes.php**
   - Added `POST /trips/{id}/restart-search` endpoint
   - Kept `search-next-wave` as deprecated

4. **app/Http/Controllers/API/V1/Client/TripController.php**
   - Added `restartSearch()` method
   - Resets trip to SEARCHING status
   - Dispatches ProcessDriverSearch job from wave 1

## Settings Required

Add to database `settings` table (general group):

```sql
INSERT INTO settings (group, name, payload, locked) VALUES
('general', 'search_wave_count', '{"value": 10}', 0),
('general', 'search_wave_time', '{"value": 30}', 0);
```

Or via Filament admin panel:
- `search_wave_count`: 10 (number of waves)
- `search_wave_time`: 30 (seconds between waves)

## Flow

### Before (Manual)
```
Trip Created → Wave 1
↓
Client waits... sees "No drivers"
↓
Client clicks "Search Again"
↓
Wave 2 executes
↓
Repeat manually...
```

### After (Automatic)
```
Trip Created
↓
ProcessDriverSearch Job (Wave 1) dispatches
↓
SearchProgress event (1/10) → Client shows progress bar
↓
Wait 30 seconds
↓
ProcessDriverSearch Job (Wave 2) dispatches
↓
SearchProgress event (2/10) → Progress bar updates
↓
... continues automatically ...
↓
Wave 10 → SearchProgress (10/10)
↓
If no driver: Trip status → NO_DRIVER_FOUND
↓
Client shows "Search Again" button
```

## Driver Acceptance During Waves

When a driver accepts during automatic search:

1. Trip status changes: `SEARCHING` → `IN_ROUTE_TO_PICKUP`
2. Future wave jobs check status and exit immediately
3. No more `SearchProgress` events broadcast
4. Client receives `TripDriverAccepted` event instead

```
Wave 1 (T=0s) → Wave 2 (T=30s) → Wave 3 (T=60s)
                                      ↓
                                Driver accepts at T=65s
                                      ↓
                                Wave 4 job (T=90s) → checks status → exits
                                Wave 5 job (T=120s) → checks status → exits
                                ... all future waves exit cleanly
```

## Mobile App Integration

### Client App - Listen for Progress

```javascript
// Subscribe to trip channel
Echo.channel(`trip.${tripId}`)
  .listen('.search_progress', (data) => {
    // Update progress bar
    const progress = data.progress_percentage; // 0-100
    updateProgressBar(progress);
    
    // Show status
    showStatus(`Searching... ${data.current_wave}/${data.total_waves}`);
    
    // Check if search complete
    if (data.current_wave === data.total_waves) {
      // All waves done, show search again button
      showSearchAgainButton();
    }
  })
  .listen('.driver_accepted', (data) => {
    // Driver found! Hide progress, show driver
    hideProgressBar();
    showDriverDetails(data.driver);
  });
```

### Search Again Button

```javascript
function searchAgain() {
  fetch(`/api/client/trips/${tripId}/restart-search`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  })
  .then(response => response.json())
  .then(() => {
    // Progress events will start coming again from wave 1
    resetProgressBar();
  });
}
```

## API Endpoints

### Restart Search
```http
POST /api/client/trips/{id}/restart-search
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Search restarted. Looking for drivers...",
  "data": []
}
```

**Conditions:**
- Trip must be in `NO_DRIVER_FOUND` status
- User must own the trip
- Resets to `SEARCHING` and starts from wave 1

### Deprecated Endpoint
```http
POST /api/client/trips/{id}/search-next-wave
```
Still works but deprecated. Use `restart-search` instead.

## Benefits

1. **Better UX**: Automatic progression, no manual clicking required
2. **Real-time feedback**: Progress bar shows search status
3. **Configurable**: Adjust wave count and timing via settings
4. **Scalable**: Job-based, handles long searches without blocking
5. **Clean restart**: One button to restart entire search from beginning
6. **Handles acceptance**: Future waves stop automatically when driver accepts

## Testing Checklist

- [ ] Create trip → verify automatic waves start
- [ ] Check SearchProgress events broadcast every 30 seconds
- [ ] Driver accepts during wave 3 → verify waves 4+ don't run
- [ ] All 10 waves complete with no driver → verify NO_DRIVER_FOUND status
- [ ] Click "Search Again" → verify search restarts from wave 1
- [ ] Change settings (wave count, wave time) → verify new values used
- [ ] Multiple trips searching simultaneously → verify no interference

## Configuration Tips

**Fast search (urban areas):**
- `search_wave_count`: 5
- `search_wave_time`: 15

**Extended search (rural areas):**
- `search_wave_count`: 15
- `search_wave_time`: 45

**Balanced (default):**
- `search_wave_count`: 10
- `search_wave_time`: 30

