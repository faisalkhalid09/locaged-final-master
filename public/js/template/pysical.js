 const roomInput = document.getElementById('roomInput');
        const shelfInput = document.getElementById('shelfInput');
        const rowInput = document.getElementById('rowInput');
        const addLocationBtn = document.getElementById('addLocationBtn');
        const locationTableBody = document.getElementById('locationTableBody');

        const locations = [
            {room: 1, shelf: 3, row: 5},
            {room: 2, shelf: 4, row: 5},
            {room: 3, shelf: 5, row: 5},
            {room: 4, shelf: 6, row: 5},
            {room: 5, shelf: 7, row: 5}
        ];

        function renderLocations() {
            locationTableBody.innerHTML = '';
            locations.forEach((loc, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>Room ${loc.room}</td>
                    <td class="padding-left">Shelf ${loc.shelf}</td>
                    <td>Row ${loc.row}</td>
                    <td class=" d-flex justify-content-center"><button class="btn-delete w-50" onclick="deleteLocation(${index})">Delete</button></td>
                `;
                locationTableBody.appendChild(row);
            });
        }

        function deleteLocation(index) {
            locations.splice(index, 1);
            renderLocations();
        }

        addLocationBtn.addEventListener('click', () => {
            const room = roomInput.value.trim();
            const shelf = shelfInput.value.trim();
            const row = rowInput.value.trim();

            if (room && shelf && row) {
                locations.push({room, shelf, row});
                roomInput.value = '';
                shelfInput.value = '';
                rowInput.value = '';
                renderLocations();
            }
        });

        renderLocations();