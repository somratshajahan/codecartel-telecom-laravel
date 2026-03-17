// Add search functionality to receiver dropdown
document.addEventListener('DOMContentLoaded', function () {
    const receiverSelect = document.getElementById('receiver_id');
    const receiverOptions = Array.from(receiverSelect.options).map(option => option.value);

    // Search input for receiver
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = 'Search user...';
    searchInput.style.marginBottom = '10px';
    searchInput.style.width = '100%';

    // Insert search input before select
    receiverSelect.parentNode.insertBefore(searchInput, receiverSelect);

    // Filter options based on search
    searchInput.addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        const filteredOptions = receiverOptions.filter(option => 
            option.toLowerCase().includes(searchTerm)
        );

        // Clear current options
        receiverSelect.innerHTML = '<option value="">Choose a user...</option>';

        // Add filtered options
        filteredOptions.forEach(optionId => {
            const user = users.find(u => u.id === parseInt(optionId));
            if (user && user.role !== 'admin') {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                receiverSelect.appendChild(option);
            }
        });
    });

    // Update available balance based on account type
    document.getElementById('account_type').addEventListener('change', function () {
        const accountType = this.value;
        const balance = getBalance(accountType); // Assume this function fetches balance
        document.getElementById('available_balance').value = '$' + balance.toFixed(2);
    });

    // Function to get balance based on account type
    function getBalance(accountType) {
        // This should be replaced with actual API call or data fetching logic
        // For example: return getUserBalance(user_id, accountType);
        return 0.00; // Placeholder
    }
});