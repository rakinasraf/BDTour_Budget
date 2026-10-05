# Bangladesh Tour Gambit (PHP, no database)

Chess-themed tour budget calculator for Bangladesh tourist spots.

## How it works
1. Pick start district -> 2. destination -> 3. transport (only routes that really exist) ->
4. tourist spots (multi-select) -> 5. average hotel/food/local costs shown -> 6. days, travellers, comfort ->
7. full itemised budget (travel, hotel, food, local transport, entry fees, 5% misc).
Dark/light toggle is saved in the browser.

## Files
- includes/data.php      64 districts, coordinates, cost tier, spots + fees (edit/add here)
- includes/config.php    cost tiers, fare rates, train/launch/airport route sets
- includes/functions.php route availability + cost calculation (server-side validated)
- api.php                JSON for dynamic transport/spot lists
- index.php, assets/     UI

## Notes
All prices are rough BDT estimates; adjust rates in config.php. Server re-validates every input
(transport must exist on the route, spots must belong to the destination), output is HTML-escaped.
