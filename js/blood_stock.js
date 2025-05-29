// blood_stock.js - JavaScript for the Blood Stock page

document.addEventListener('DOMContentLoaded', function() {
    // Fetch blood stock data
    fetchBloodStock();
    
    // Set up refresh button if it exists
    const refreshButton = document.getElementById('refreshStockBtn');
    if (refreshButton) {
        refreshButton.addEventListener('click', fetchBloodStock);
    }
});

function fetchBloodStock() {
    const tableBody = document.getElementById('bloodStockTableBody');
    
    // Show loading state
    tableBody.innerHTML = '<tr><td colspan="3">Loading blood stock data...</td></tr>';
    
    // Fetch blood stock data from the server
    fetch('php/get_blood_stock.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Clear the table
            tableBody.innerHTML = '';
            
            // Add each blood group to the table
            data.forEach(stock => {
                const row = document.createElement('tr');
                
                // Determine status class based on units available
                let statusClass = 'normal';
                let statusText = 'Normal';
                
                if (stock.units_available < 5) {
                    statusClass = 'critical';
                    statusText = 'Critical';
                } else if (stock.units_available < 10) {
                    statusClass = 'low';
                    statusText = 'Low';
                }
                
                row.innerHTML = `
                    <td>${stock.group_name}</td>
                    <td>${stock.units_available}</td>
                    <td><span class="status-dot ${statusClass}"></span> ${statusText}</td>
                `;
                
                tableBody.appendChild(row);
            });
        })
        .catch(error => {
            tableBody.innerHTML = `<tr><td colspan="3">Error loading blood stock data: ${error.message}</td></tr>`;
            console.error('Error fetching blood stock:', error);
        });
}
